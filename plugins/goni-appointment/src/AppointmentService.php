<?php
declare(strict_types=1);
namespace GoniAppointment;

use GoniCore\Core\Database\QueryBuilder;

final class AppointmentService
{
    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Settings ──────────────────────────────────────────────────────────────
    public function setting(string $key, string $default = ''): string
    {
        $row = $this->qb->table('gapp_settings')->where('key','=',$key)->first();
        return $row ? (string)$row['value'] : $default;
    }
    public function setSetting(string $key, string $value): void
    {
        $exists = $this->qb->table('gapp_settings')->where('key','=',$key)->first();
        if ($exists) $this->qb->table('gapp_settings')->where('key','=',$key)->update(['value'=>$value]);
        else $this->qb->table('gapp_settings')->insert(['key'=>$key,'value'=>$value]);
    }

    // ── Services ──────────────────────────────────────────────────────────────
    public function allServices(bool $activeOnly = false): array
    {
        $qb = $this->qb->table('gapp_services');
        if ($activeOnly) $qb = $qb->where('status','=','active');
        return $qb->orderBy('sort_order','ASC')->get() ?: [];
    }
    public function getService(int $id): ?array { return $this->qb->table('gapp_services')->where('id','=',$id)->first(); }
    public function createService(array $d): int { return (int)$this->qb->table('gapp_services')->insert($d); }
    public function updateService(int $id, array $d): void { $this->qb->table('gapp_services')->where('id','=',$id)->update($d); }
    public function deleteService(int $id): void { $this->qb->table('gapp_services')->where('id','=',$id)->delete(); }

    // ── Staff ─────────────────────────────────────────────────────────────────
    public function allStaff(bool $activeOnly = false): array
    {
        $qb = $this->qb->table('gapp_staff');
        if ($activeOnly) $qb = $qb->where('status','=','active');
        return $qb->orderBy('sort_order','ASC')->get() ?: [];
    }
    public function getStaff(int $id): ?array
    {
        $s = $this->qb->table('gapp_staff')->where('id','=',$id)->first();
        if (!$s) return null;
        $s['services']      = $this->servicesForStaff($id);
        $s['working_hours'] = $this->workingHoursForStaff($id);
        return $s;
    }
    public function createStaff(array $d): int
    {
        $id = (int)$this->qb->table('gapp_staff')->insert($d);
        $this->initDefaultWorkingHours($id);
        return $id;
    }
    public function updateStaff(int $id, array $d): void { $this->qb->table('gapp_staff')->where('id','=',$id)->update($d); }
    public function deleteStaff(int $id): void { $this->qb->table('gapp_staff')->where('id','=',$id)->delete(); }

    public function assignServices(int $staffId, array $serviceIds): void
    {
        $this->qb->table('gapp_staff_services')->where('staff_id','=',$staffId)->delete();
        foreach (array_unique($serviceIds) as $sid) {
            try { $this->qb->table('gapp_staff_services')->insert(['staff_id'=>$staffId,'service_id'=>(int)$sid]); }
            catch(\Throwable){}
        }
    }

    public function servicesForStaff(int $staffId): array
    {
        $links = $this->qb->table('gapp_staff_services')->where('staff_id','=',$staffId)->get() ?: [];
        $ids   = array_column($links,'service_id');
        if (empty($ids)) return [];
        $services = [];
        foreach ($ids as $sid) {
            $svc = $this->getService((int)$sid);
            if ($svc) $services[] = $svc;
        }
        return $services;
    }

    public function staffForService(int $serviceId): array
    {
        $links = $this->qb->table('gapp_staff_services')->where('service_id','=',$serviceId)->get() ?: [];
        $result = [];
        foreach ($links as $l) {
            $s = $this->qb->table('gapp_staff')->where('id','=',(int)$l['staff_id'])->where('status','=','active')->first();
            if ($s) $result[] = $s;
        }
        return $result;
    }

    // ── Working Hours ─────────────────────────────────────────────────────────
    public function workingHoursForStaff(int $staffId): array
    {
        $rows = $this->qb->table('gapp_working_hours')->where('staff_id','=',$staffId)->orderBy('day_of_week','ASC')->get() ?: [];
        $map  = [];
        foreach ($rows as $r) $map[(int)$r['day_of_week']] = $r;
        return $map;
    }

    public function saveWorkingHours(int $staffId, array $hours): void
    {
        foreach ($hours as $day => $h) {
            $day = (int)$day;
            $existing = $this->qb->table('gapp_working_hours')
                ->where('staff_id','=',$staffId)->where('day_of_week','=',$day)->first();
            $data = [
                'start_time'  => $h['start'] ?? '09:00',
                'end_time'    => $h['end']   ?? '18:00',
                'is_day_off'  => !empty($h['day_off']) ? 1 : 0,
            ];
            if ($existing) $this->qb->table('gapp_working_hours')->where('id','=',(int)$existing['id'])->update($data);
            else $this->qb->table('gapp_working_hours')->insert(array_merge($data,['staff_id'=>$staffId,'day_of_week'=>$day]));
        }
    }

    // ── Available Slots ───────────────────────────────────────────────────────
    /**
     * Returns available time slots for a staff+service+date combo.
     * @return string[] e.g. ['09:00','09:30','10:00',…]
     */
    public function availableSlots(int $staffId, int $serviceId, string $date): array
    {
        $service = $this->getService($serviceId);
        if (!$service) return [];

        $duration = (int)$service['duration_minutes'];
        $interval = max(15, (int)$this->setting('slot_interval','30'));
        $dow      = (int)date('w', strtotime($date)); // 0=Sun

        // Working hours for that day
        $wh = $this->workingHoursForStaff($staffId)[$dow] ?? null;
        if (!$wh || $wh['is_day_off']) return [];

        $startTs = strtotime($date . ' ' . $wh['start_time']);
        $endTs   = strtotime($date . ' ' . $wh['end_time']);

        // Existing appointments for that staff+date
        $booked = $this->qb->table('gapp_appointments')
            ->where('staff_id',   '=', $staffId)
            ->where('appointment_date', '=', $date)
            ->where('status', '!=', 'cancelled')
            ->get() ?: [];

        $minAdvanceHours = (int)$this->setting('min_advance_hours','1');
        $minTs = time() + $minAdvanceHours * 3600;

        $slots = [];
        $cursor = $startTs;
        while ($cursor + $duration * 60 <= $endTs) {
            $slotEnd = $cursor + $duration * 60;

            // Skip past slots
            if ($cursor < $minTs) { $cursor += $interval * 60; continue; }

            // Check conflicts
            $conflict = false;
            foreach ($booked as $appt) {
                $as = strtotime($date . ' ' . $appt['start_time']);
                $ae = strtotime($date . ' ' . $appt['end_time']);
                if ($cursor < $ae && $slotEnd > $as) { $conflict = true; break; }
            }

            if (!$conflict) $slots[] = date('H:i', $cursor);
            $cursor += $interval * 60;
        }

        return $slots;
    }

    // ── Appointments ──────────────────────────────────────────────────────────
    public function allAppointments(int $page = 1, int $perPage = 25, string $date = '', string $status = '', int $staffId = 0): array
    {
        $qb = $this->qb->table('gapp_appointments');
        if ($date)    $qb = $qb->where('appointment_date','=',$date);
        if ($status)  $qb = $qb->where('status','=',$status);
        if ($staffId) $qb = $qb->where('staff_id','=',$staffId);
        $total = (int)($qb->count() ?? 0);
        $items = $qb->orderBy('appointment_date','ASC')->orderBy('start_time','ASC')
            ->limit($perPage)->offset(($page-1)*$perPage)->get() ?: [];
        // Enrich
        foreach ($items as &$a) {
            $a['service'] = $this->getService((int)$a['service_id']);
            $a['staff']   = $this->qb->table('gapp_staff')->where('id','=',(int)$a['staff_id'])->first();
        }
        unset($a);
        return ['items'=>$items,'total'=>$total,'pages'=>max(1,(int)ceil($total/$perPage))];
    }

    public function getAppointment(int $id): ?array
    {
        $a = $this->qb->table('gapp_appointments')->where('id','=',$id)->first();
        if (!$a) return null;
        $a['service'] = $this->getService((int)$a['service_id']);
        $a['staff']   = $this->qb->table('gapp_staff')->where('id','=',(int)$a['staff_id'])->first();
        return $a;
    }

    public function getAppointmentByNumber(string $num): ?array
    {
        $a = $this->qb->table('gapp_appointments')->where('appointment_number','=',$num)->first();
        if (!$a) return null;
        $a['service'] = $this->getService((int)$a['service_id']);
        $a['staff']   = $this->qb->table('gapp_staff')->where('id','=',(int)$a['staff_id'])->first();
        return $a;
    }

    public function createAppointment(array $data): int
    {
        $data['appointment_number'] = $this->generateNumber();
        return (int)$this->qb->table('gapp_appointments')->insert($data);
    }

    public function updateAppointment(int $id, array $data): void
    {
        $this->qb->table('gapp_appointments')->where('id','=',$id)->update($data);
    }

    // ── Stats ─────────────────────────────────────────────────────────────────
    public function globalStats(): array
    {
        $total     = (int)($this->qb->table('gapp_appointments')->count() ?? 0);
        $today     = (int)($this->qb->table('gapp_appointments')->where('appointment_date','=',date('Y-m-d'))->count() ?? 0);
        $pending   = (int)($this->qb->table('gapp_appointments')->where('status','=','pending')->count() ?? 0);
        $rows      = $this->qb->table('gapp_appointments')->where('payment_status','=','paid')->get() ?: [];
        $revenue   = (float)array_sum(array_column($rows,'price'));
        return compact('total','today','pending','revenue');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    public function generateNumber(): string
    {
        $last = $this->qb->table('gapp_appointments')->orderBy('id','DESC')->first();
        $next = $last ? (int)$last['id'] + 1 : 1;
        return 'AP-'.date('Ym').'-'.str_pad((string)$next,4,'0',STR_PAD_LEFT);
    }
    public function formatPrice(float $p): string { return number_format($p,2,'.',',' ).' '.$this->setting('currency_symbol','₾'); }
    public function statusLabel(string $s): string
    {
        return match($s){ 'pending'=>'⏳ Pending','confirmed'=>'✅ Confirmed','cancelled'=>'❌ Cancelled','completed'=>'🏁 Completed','no_show'=>'👻 No Show',default=>ucfirst($s) };
    }
    public function statusColor(string $s): string
    {
        return match($s){ 'pending'=>'#f59e0b','confirmed'=>'#10b981','cancelled'=>'#ef4444','completed'=>'#4f46e5','no_show'=>'#94a3b8',default=>'#94a3b8' };
    }
    public function allStatuses(): array { return ['pending','confirmed','cancelled','completed','no_show']; }
    public function dayName(int $dow): string { return ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$dow]; }

    private function initDefaultWorkingHours(int $staffId): void
    {
        for ($d = 0; $d < 7; $d++) {
            $this->qb->table('gapp_working_hours')->insert([
                'staff_id'    => $staffId,
                'day_of_week' => $d,
                'start_time'  => '09:00:00',
                'end_time'    => '18:00:00',
                'is_day_off'  => in_array($d,[0,6]) ? 1 : 0, // Sun, Sat off by default
            ]);
        }
    }
}
