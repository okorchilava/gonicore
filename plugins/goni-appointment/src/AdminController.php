<?php
declare(strict_types=1);

namespace GoniAppointment;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class AdminController
{
    public function __construct(
        private readonly AppointmentService $svc,
        private readonly QueryBuilder        $qb,
        private readonly LoginService        $auth,
        private readonly HookManager         $hooks,
        private readonly string              $siteName = 'GoniCore',
    ) {}

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $stats  = $this->svc->globalStats();
        $recent = $this->svc->allAppointments(1, 10)['items'];
        return $this->renderPage('dashboard', compact('stats', 'recent') + ['base' => $r->basePath()]);
    }

    // ── Services ──────────────────────────────────────────────────────────────

    public function services(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $services = $this->svc->allServices();
        $deleted  = $r->query('deleted') === '1';
        return $this->renderPage('services', compact('services', 'deleted') + ['base' => $r->basePath()]);
    }

    public function serviceNew(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('service_form', ['base' => $r->basePath(), 'service' => null, 'saved' => false]);
    }

    public function serviceCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = $this->svc->createService($this->extractServiceData($r));
        return Response::redirect($r->basePath() . '/manage/appointment/services/' . $id . '/edit?saved=1');
    }

    public function serviceEdit(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id      = (int) $r->getAttribute('id');
        $service = $this->svc->getService($id);
        if (!$service) return Response::redirect($r->basePath() . '/manage/appointment/services');
        $saved = $r->query('saved') === '1';
        return $this->renderPage('service_form', compact('service', 'saved') + ['base' => $r->basePath()]);
    }

    public function serviceUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->getAttribute('id');
        $this->svc->updateService($id, $this->extractServiceData($r));
        return Response::redirect($r->basePath() . '/manage/appointment/services/' . $id . '/edit?saved=1');
    }

    public function serviceDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->svc->deleteService((int) $r->getAttribute('id'));
        return Response::redirect($r->basePath() . '/manage/appointment/services?deleted=1');
    }

    // ── Staff ─────────────────────────────────────────────────────────────────

    public function staff(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $staffList = $this->svc->allStaff();
        $deleted   = $r->query('deleted') === '1';
        return $this->renderPage('staff', ['staff' => $staffList, 'deleted' => $deleted, 'base' => $r->basePath()]);
    }

    public function staffNew(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $services = $this->svc->allServices(true);
        return $this->renderPage('staff_form', [
            'base'        => $r->basePath(),
            'member'      => null,
            'services'    => $services,
            'assignedIds' => [],
            'saved'       => false,
        ]);
    }

    public function staffCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id         = $this->svc->createStaff($this->extractStaffData($r));
        $serviceIds = array_map('intval', (array) ($r->post('service_ids') ?? []));
        if ($serviceIds) $this->svc->assignServices($id, $serviceIds);
        return Response::redirect($r->basePath() . '/manage/appointment/staff/' . $id . '/edit?saved=1');
    }

    public function staffEdit(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id     = (int) $r->getAttribute('id');
        $member = $this->svc->getStaff($id);
        if (!$member) return Response::redirect($r->basePath() . '/manage/appointment/staff');
        $services    = $this->svc->allServices(true);
        $assignedIds = array_map('intval', array_column($member['services'], 'id'));
        $saved       = $r->query('saved') === '1';
        return $this->renderPage('staff_form', compact('member', 'services', 'assignedIds', 'saved') + ['base' => $r->basePath()]);
    }

    public function staffUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id         = (int) $r->getAttribute('id');
        $this->svc->updateStaff($id, $this->extractStaffData($r));
        $serviceIds = array_map('intval', (array) ($r->post('service_ids') ?? []));
        $this->svc->assignServices($id, $serviceIds);
        return Response::redirect($r->basePath() . '/manage/appointment/staff/' . $id . '/edit?saved=1');
    }

    public function staffDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->svc->deleteStaff((int) $r->getAttribute('id'));
        return Response::redirect($r->basePath() . '/manage/appointment/staff?deleted=1');
    }

    public function staffSchedule(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id     = (int) $r->getAttribute('id');
        $member = $this->svc->getStaff($id);
        if (!$member) return Response::redirect($r->basePath() . '/manage/appointment/staff');
        $hours = $this->svc->workingHoursForStaff($id);
        $saved = $r->query('saved') === '1';
        return $this->renderPage('staff_schedule', compact('member', 'hours', 'saved') + [
            'base' => $r->basePath(),
            'svc'  => $this->svc,
        ]);
    }

    public function staffScheduleSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id       = (int) $r->getAttribute('id');
        $hoursIn  = (array) ($r->post('hours') ?? []);
        $hoursMap = [];
        for ($d = 0; $d < 7; $d++) {
            $hoursMap[$d] = [
                'start'   => $hoursIn[$d]['start'] ?? '09:00',
                'end'     => $hoursIn[$d]['end']   ?? '18:00',
                'day_off' => !empty($hoursIn[$d]['day_off']) ? 1 : 0,
            ];
        }
        $this->svc->saveWorkingHours($id, $hoursMap);
        return Response::redirect($r->basePath() . '/manage/appointment/staff/' . $id . '/schedule?saved=1');
    }

    // ── Appointments ──────────────────────────────────────────────────────────

    public function appointments(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $page      = max(1, (int) ($r->query('page', '1')));
        $date      = trim((string) $r->query('date', ''));
        $status    = trim((string) $r->query('status', ''));
        $staffId   = (int) $r->query('staff', '0');
        $data      = $this->svc->allAppointments($page, 25, $date, $status, $staffId);
        $staffList = $this->svc->allStaff();
        return $this->renderPage('appointments', $data + [
            'base'      => $r->basePath(),
            'page'      => $page,
            'date'      => $date,
            'status'    => $status,
            'staffId'   => $staffId,
            'staffList' => $staffList,
            'svc'       => $this->svc,
        ]);
    }

    public function appointmentView(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id   = (int) $r->getAttribute('id');
        $appt = $this->svc->getAppointment($id);
        if (!$appt) return Response::redirect($r->basePath() . '/manage/appointment/appointments');
        return $this->renderPage('appointment_view', [
            'base'  => $r->basePath(),
            'appt'  => $appt,
            'svc'   => $this->svc,
            'flash' => $r->query('msg', ''),
            'error' => $r->query('err', ''),
        ]);
    }

    public function appointmentStatus(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id     = (int) $r->getAttribute('id');
        $status = (string) $r->post('status', '');
        if (in_array($status, $this->svc->allStatuses(), true)) {
            $upd = ['status' => $status];
            if ($status === 'confirmed') $upd['payment_status'] = 'paid';
            $this->svc->updateAppointment($id, $upd);
        }
        return Response::redirect($r->basePath() . '/manage/appointment/appointments/' . $id . '?msg=Status+updated.');
    }

    public function appointmentNote(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id   = (int) $r->getAttribute('id');
        $note = trim((string) $r->post('admin_note', ''));
        $this->svc->updateAppointment($id, ['admin_note' => $note]);
        return Response::redirect($r->basePath() . '/manage/appointment/appointments/' . $id . '?msg=Note+saved.');
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public function settings(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('settings', [
            'base'  => $r->basePath(),
            'svc'   => $this->svc,
            'saved' => $r->query('saved') === '1',
        ]);
    }

    public function settingsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        foreach (['currency', 'currency_symbol', 'page_slug', 'brand_name', 'slot_interval', 'advance_days', 'min_advance_hours'] as $k) {
            $this->svc->setSetting($k, trim((string) $r->post($k, '')));
        }
        return Response::redirect($r->basePath() . '/manage/appointment/settings?saved=1');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function guard(Request $r): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($r->basePath() . '/login');
        }
        return null;
    }

    private function extractServiceData(Request $r): array
    {
        return [
            'name'             => trim((string) $r->post('name', '')),
            'description'      => trim((string) $r->post('description', '')),
            'duration_minutes' => max(15, (int) $r->post('duration_minutes', '60')),
            'price'            => max(0.0, (float) $r->post('price', '0')),
            'color'            => trim((string) $r->post('color', '#4f46e5')),
            'image'            => trim((string) $r->post('image', '')),
            'status'           => $r->post('status') === 'inactive' ? 'inactive' : 'active',
            'sort_order'       => (int) $r->post('sort_order', '0'),
        ];
    }

    private function extractStaffData(Request $r): array
    {
        return [
            'name'       => trim((string) $r->post('name', '')),
            'email'      => trim((string) $r->post('email', '')),
            'phone'      => trim((string) $r->post('phone', '')),
            'bio'        => trim((string) $r->post('bio', '')),
            'image'      => trim((string) $r->post('image', '')),
            'title'      => trim((string) $r->post('title', '')),
            'status'     => $r->post('status') === 'inactive' ? 'inactive' : 'active',
            'sort_order' => (int) $r->post('sort_order', '0'),
        ];
    }

    /** @param array<string, mixed> $data */
    private function renderPage(string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeDir . '/helpers.php';

        $base     = $data['base'] ?? '';
        $siteName = $this->siteName;
        $hooks    = $this->hooks;

        $userId = $this->auth->currentUserId();
        $user   = $userId ? $this->qb->table('users')->where('id', '=', $userId)->first() : null;

        $notifList       = [];
        $notifUnread     = 0;
        $panelLangs      = [];
        $currentLangCode = 'en';

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include __DIR__ . '/../views/admin/' . $view . '.php';
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        ob_start();
        try {
            include $themeDir . '/manage/layout.php';
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return Response::html($html);
    }
}
