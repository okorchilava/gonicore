<?php
declare(strict_types=1);
namespace GoniTaxi;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Mail\MailService;
use GoniCore\Modules\Login\LoginService;

final class AdminController
{
    public function __construct(
        private readonly TaxiService  $taxi,
        private readonly QueryBuilder $qb,
        private readonly LoginService $auth,
        private readonly HookManager  $hooks,
        private readonly MailService  $mail,
        private readonly string       $siteName = 'GoniCore',
    ) {}

    public function dashboard(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $stats   = $this->taxi->globalStats();
        $recent  = $this->taxi->allRides(1, 8)['items'];
        $drivers = $this->taxi->allDrivers(true);
        return $this->page('dashboard', compact('stats','recent','drivers') + ['base'=>$r->basePath(),'taxi'=>$this->taxi]);
    }

    // Rides
    public function rides(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $page   = max(1,(int)$r->query('page','1'));
        $status = (string)$r->query('status','');
        $data   = $this->taxi->allRides($page,25,$status);
        return $this->page('rides', $data + ['base'=>$r->basePath(),'page'=>$page,'filterStatus'=>$status,'taxi'=>$this->taxi]);
    }

    public function rideView(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id   = (int)$r->getAttribute('id');
        $ride = $this->taxi->getRide($id);
        if (!$ride) return Response::redirect($r->basePath().'/manage/taxi/rides');
        return $this->page('ride', [
            'base'    => $r->basePath(),
            'ride'    => $ride,
            'drivers' => $this->taxi->allDrivers(true),
            'taxi'    => $this->taxi,
            'flash'   => $r->query('msg',''),
        ]);
    }

    public function rideUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int)$r->getAttribute('id');
        $upd = [
            'status'         => (string)$r->post('status','pending'),
            'driver_id'      => $r->post('driver_id') ? (int)$r->post('driver_id') : null,
            'actual_price'   => $r->post('actual_price') !== '' ? (float)$r->post('actual_price') : null,
            'payment_status' => (string)$r->post('payment_status','unpaid'),
            'driver_note'    => trim((string)$r->post('driver_note','')),
        ];
        $oldRide = $this->taxi->getRide($id);
        if ($upd['status'] === 'driver_assigned' && $upd['driver_id']) {
            $this->taxi->updateDriver($upd['driver_id'], ['status' => 'busy']);
        }
        if (in_array($upd['status'], ['completed', 'cancelled'], true)) {
            $driverId = $upd['driver_id'] ?? ($oldRide['driver_id'] ?? null);
            if ($driverId) {
                $this->taxi->updateDriver((int)$driverId, ['status' => 'active']);
            }
        }
        $this->taxi->updateRide($id, $upd);
        if ($upd['status'] === 'completed') {
            $this->taxi->settleRide($id);
        }
        return Response::redirect($r->basePath().'/manage/taxi/rides/'.$id.'?msg=Updated.');
    }

    // Drivers
    public function drivers(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->page('drivers', ['base'=>$r->basePath(),'drivers'=>$this->taxi->allDrivers(),'taxi'=>$this->taxi,'saved'=>$r->query('saved')==='1']);
    }

    public function driverCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = $this->taxi->createDriver([
            'name'       => trim((string)$r->post('name','')),
            'phone'      => trim((string)$r->post('phone','')),
            'email'      => trim((string)$r->post('email','')),
            'car_model'  => trim((string)$r->post('car_model','')),
            'car_number' => trim((string)$r->post('car_number','')),
            'car_color'  => trim((string)$r->post('car_color','')),
            'car_type'   => (string)$r->post('car_type','sedan'),
            'status'     => $r->post('status','active')==='active'?'active':'inactive',
            'notes'      => trim((string)$r->post('notes','')),
        ]);
        $pass    = trim((string)$r->post('password',''));
        $confirm = trim((string)$r->post('password_confirm',''));
        if ($pass !== '' && $pass === $confirm) $this->taxi->setDriverPassword($id, $pass);
        return Response::redirect($r->basePath().'/manage/taxi/drivers?saved=1');
    }

    public function driverUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int)$r->getAttribute('id');
        $this->taxi->updateDriver($id, [
            'name'       => trim((string)$r->post('name','')),
            'phone'      => trim((string)$r->post('phone','')),
            'email'      => trim((string)$r->post('email','')),
            'car_model'  => trim((string)$r->post('car_model','')),
            'car_number' => trim((string)$r->post('car_number','')),
            'car_color'  => trim((string)$r->post('car_color','')),
            'car_type'     => (string)$r->post('car_type','sedan'),
            'status'       => (string)$r->post('status','active'),
            'notes'        => trim((string)$r->post('notes','')),
            'bank_account' => trim((string)$r->post('bank_account','')),
            'bank_name'    => trim((string)$r->post('bank_name','')),
            'bank_code'    => trim((string)$r->post('bank_code','')),
            'personal_id'  => trim((string)$r->post('personal_id','')),
        ]);
        $pass    = trim((string)$r->post('password',''));
        $confirm = trim((string)$r->post('password_confirm',''));
        if ($pass !== '' && $pass === $confirm) $this->taxi->setDriverPassword($id, $pass);
        return Response::redirect($r->basePath().'/manage/taxi/drivers?saved=1');
    }

    public function driverDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->taxi->deleteDriver((int)$r->getAttribute('id'));
        return Response::redirect($r->basePath().'/manage/taxi/drivers');
    }

    // Routes
    public function routes(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->page('routes', ['base'=>$r->basePath(),'routes'=>$this->taxi->allRoutes(),'taxi'=>$this->taxi,'saved'=>$r->query('saved')==='1']);
    }

    public function routeCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->taxi->createRoute([
            'name'=>trim((string)$r->post('name','')), 'from_location'=>trim((string)$r->post('from_location','')),
            'to_location'=>trim((string)$r->post('to_location','')), 'distance_km'=>max(0,(float)$r->post('distance_km','0')),
            'price'=>max(0,(float)$r->post('price','0')), 'car_type'=>trim((string)$r->post('car_type','')),
            'active'=>$r->post('active')==='1'?1:0, 'sort_order'=>(int)$r->post('sort_order','0'),
        ]);
        return Response::redirect($r->basePath().'/manage/taxi/routes?saved=1');
    }

    public function routeUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int)$r->getAttribute('id');
        $this->taxi->updateRoute($id, [
            'name'=>trim((string)$r->post('name','')), 'from_location'=>trim((string)$r->post('from_location','')),
            'to_location'=>trim((string)$r->post('to_location','')), 'distance_km'=>max(0,(float)$r->post('distance_km','0')),
            'price'=>max(0,(float)$r->post('price','0')), 'car_type'=>trim((string)$r->post('car_type','')),
            'active'=>$r->post('active')==='1'?1:0, 'sort_order'=>(int)$r->post('sort_order','0'),
        ]);
        return Response::redirect($r->basePath().'/manage/taxi/routes?saved=1');
    }

    public function routeDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->taxi->deleteRoute((int)$r->getAttribute('id'));
        return Response::redirect($r->basePath().'/manage/taxi/routes');
    }

    // Manual re-dispatch from admin
    public function rideDispatch(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int)$r->getAttribute('id');
        $this->taxi->dispatchDriver($id);
        return Response::redirect($r->basePath().'/manage/taxi/rides/'.$id.'?msg=Dispatching+to+new+driver.');
    }

    // Regenerate driver portal token
    public function driverRegenToken(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->taxi->regenerateDriverToken((int)$r->getAttribute('id'));
        return Response::redirect($r->basePath().'/manage/taxi/drivers?saved=1');
    }

    // ── Tariffs ───────────────────────────────────────────────────────────────
    public function tariffs(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->page('tariffs', [
            'base'    => $r->basePath(),
            'tariffs' => $this->taxi->allTariffs(),
            'taxi'    => $this->taxi,
            'saved'   => $r->query('saved') === '1',
            'deleted' => $r->query('deleted') === '1',
        ]);
    }

    public function tariffCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $days = implode('', array_keys(array_intersect_key(
            array_flip(['1','2','3','4','5','6','7']),
            array_flip((array)($r->post('days') ?? []))
        )));
        $this->taxi->createTariff([
            'name'             => trim((string)$r->post('name','')),
            'car_type'         => (string)$r->post('car_type',''),
            'base_fare'        => max(0,(float)$r->post('base_fare','5')),
            'price_per_km'     => max(0,(float)$r->post('price_per_km','1.5')),
            'min_fare'         => max(0,(float)$r->post('min_fare','5')),
            'surge_multiplier' => max(0.1,(float)$r->post('surge_multiplier','1')),
            'time_from'        => $r->post('time_from') ?: null,
            'time_to'          => $r->post('time_to') ?: null,
            'days'             => $days ?: '1234567',
            'active'           => $r->post('active') === '1' ? 1 : 0,
            'priority'         => (int)$r->post('priority','0'),
        ]);
        return Response::redirect($r->basePath().'/manage/taxi/tariffs?saved=1');
    }

    public function tariffUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int)$r->getAttribute('id');
        $days = implode('', array_keys(array_intersect_key(
            array_flip(['1','2','3','4','5','6','7']),
            array_flip((array)($r->post('days') ?? []))
        )));
        $this->taxi->updateTariff($id, [
            'name'             => trim((string)$r->post('name','')),
            'car_type'         => (string)$r->post('car_type',''),
            'base_fare'        => max(0,(float)$r->post('base_fare','5')),
            'price_per_km'     => max(0,(float)$r->post('price_per_km','1.5')),
            'min_fare'         => max(0,(float)$r->post('min_fare','5')),
            'surge_multiplier' => max(0.1,(float)$r->post('surge_multiplier','1')),
            'time_from'        => $r->post('time_from') ?: null,
            'time_to'          => $r->post('time_to') ?: null,
            'days'             => $days ?: '1234567',
            'active'           => $r->post('active') === '1' ? 1 : 0,
            'priority'         => (int)$r->post('priority','0'),
        ]);
        return Response::redirect($r->basePath().'/manage/taxi/tariffs?saved=1');
    }

    public function tariffDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->taxi->deleteTariff((int)$r->getAttribute('id'));
        return Response::redirect($r->basePath().'/manage/taxi/tariffs?deleted=1');
    }

    // ── Settlements ───────────────────────────────────────────────────────────

    public function settlements(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $dateFrom = (string)$r->query('from', date('Y-m-01'));
        $dateTo   = (string)$r->query('to',   date('Y-m-t'));
        $tab      = (string)$r->query('tab', 'calculate');

        // ── Pseudo-cron: auto-reminder on 1st and 15th at 12:00+ ──────────────
        $reminderSent = $this->maybeSendSettlementReminder($r->basePath());

        $calcData    = $tab === 'calculate' ? $this->taxi->calcSettlementData($dateFrom, $dateTo) : [];
        $history     = $tab === 'history'   ? $this->taxi->allSettlements(1, 50) : ['items'=>[],'total'=>0];
        $sym         = $this->taxi->setting('currency_symbol','₾');

        return $this->page('settlements', compact('dateFrom','dateTo','tab','calcData','history','sym','reminderSent') + [
            'base' => $r->basePath(), 'taxi' => $this->taxi,
        ]);
    }

    public function settlementReminderManual(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->sendSettlementReminder($r->basePath(), force: true);
        return Response::redirect($r->basePath().'/manage/taxi/settlements?reminder=1');
    }

    private function maybeSendSettlementReminder(string $base): bool
    {
        $day  = (int)date('j');   // 1-31
        $hour = (int)date('G');   // 0-23

        if (($day !== 1 && $day !== 15) || $hour < 12) return false;

        $lastKey  = 'settlement_reminder_last_sent';
        $todayStr = date('Y-m-d');
        if ($this->taxi->setting($lastKey) === $todayStr) return false;

        $this->sendSettlementReminder($base);
        $this->taxi->setSetting($lastKey, $todayStr);
        return true;
    }

    private function sendSettlementReminder(string $base, bool $force = false): void
    {
        $adminEmail = (string)$this->taxi->setting('admin_email', '');
        $sym        = $this->taxi->setting('currency_symbol', '₾');
        $brand      = $this->taxi->setting('brand_name', 'GoniTaxi');
        $period     = date('Y-m-01').' – '.date('Y-m-t');
        $day        = date('j');
        $settleUrl  = $base.'/manage/taxi/settlements?tab=calculate&from='.date('Y-m-01').'&to='.date('Y-m-t');

        // Count pending (unsettled) drivers
        $calcData  = $this->taxi->calcSettlementData(date('Y-m-01'), date('Y-m-t'));
        $driverCount = count($calcData);
        $totalDebt   = round(array_sum(array_filter(array_column($calcData, 'net_amount'), fn($v) => $v < 0)), 2);
        $totalPayout = round(array_sum(array_filter(array_column($calcData, 'net_amount'), fn($v) => $v > 0)), 2);

        $subject = "💳 ანგარიშსწორების შეხსენება — {$brand} · {$period}";

        $body = <<<HTML
<p>გამარჯობა!</p>
<p>დღეს თვის <strong>{$day}</strong> რიცხვია — ანგარიშსწორების დროა.</p>
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:15px;margin:20px 0">
  <tr>
    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b">პერიოდი</td>
    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:700">{$period}</td>
  </tr>
  <tr>
    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b">მძღოლები</td>
    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:700">{$driverCount}</td>
  </tr>
  <tr>
    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b">შემოსავალი (მძღოლთა ვალი)</td>
    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:700;color:#10b981">".number_format(abs($totalDebt),2).$sym."</td>
  </tr>
  <tr style="background:#f8fafc">
    <td style="padding:12px 8px;font-weight:800">გასარიცხი მძღოლებზე</td>
    <td style="padding:12px 8px;text-align:right;font-weight:900;font-size:18px;color:#ef4444">".number_format($totalPayout,2).$sym."</td>
  </tr>
</table>
HTML;

        $html = $this->mail->template($subject, $body, $settleUrl, '📊 გახსნა Settlements');

        // Send email if configured
        if ($adminEmail !== '' && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->mail->send($adminEmail, $subject, $html);
        }

        // Also try global admin email from core settings
        try {
            /** @var \GoniCore\Modules\Settings\SettingsService $settings */
            $settings = \gc_container()->get(\GoniCore\Modules\Settings\SettingsService::class);
            $coreEmail = (string)$settings->get('admin_email', '');
            if ($coreEmail !== $adminEmail && $coreEmail !== '' && filter_var($coreEmail, FILTER_VALIDATE_EMAIL)) {
                $this->mail->send($coreEmail, $subject, $html);
            }
        } catch (\Throwable) {}
    }

    public function settlementCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $dateFrom = (string)$r->post('date_from', date('Y-m-01'));
        $dateTo   = (string)$r->post('date_to',   date('Y-m-t'));
        $driverIds = array_map('intval', (array)($r->post('driver_ids') ?? []));
        if (empty($driverIds)) {
            return Response::redirect($r->basePath().'/manage/taxi/settlements?tab=calculate&from='.$dateFrom.'&to='.$dateTo.'&err=nosel');
        }

        $calcData = $this->taxi->calcSettlementData($dateFrom, $dateTo);
        $byId = [];
        foreach ($calcData as $row) { $byId[(int)$row['driver']['id']] = $row; }

        $sym = $this->taxi->setting('currency_symbol', '₾');
        foreach ($driverIds as $did) {
            if (!isset($byId[$did])) continue;
            $row    = $byId[$did];
            $driver = $row['driver'];
            $this->taxi->createSettlement([
                'driver_id'    => $did,
                'rides_count'  => $row['rides_count'],
                'gross_amount' => $row['gross_amount'],
                'commission'   => $row['commission'],
                'net_amount'   => $row['net_amount'],
                'period_from'  => $dateFrom,
                'period_to'    => $dateTo,
                'bank_account' => $driver['bank_account'] ?? '',
                'bank_name'    => $driver['bank_name']    ?? '',
                'status'       => 'pending',
            ]);
            $this->notifyDriverSettlement($driver, $row, $sym, $dateFrom, $dateTo);
        }
        return Response::redirect($r->basePath().'/manage/taxi/settlements?tab=history&created=1');
    }

    public function settlementMarkPaid(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id     = (int)$r->getAttribute('id');
        $action = (string)$r->post('action', 'paid');
        $ref    = trim((string)$r->post('bank_ref',''));

        $upd = ['status' => $action === 'failed' ? 'failed' : 'paid'];
        if ($action !== 'failed') $upd['paid_at'] = date('Y-m-d H:i:s');
        if ($ref) $upd['bank_ref'] = $ref;
        $this->taxi->updateSettlement($id, $upd);

        return Response::redirect($r->basePath().'/manage/taxi/settlements?tab=history&updated=1');
    }

    public function settlementExportXml(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $dateFrom = (string)$r->query('from', date('Y-m-01'));
        $dateTo   = (string)$r->query('to',   date('Y-m-t'));
        $data     = $this->taxi->calcSettlementData($dateFrom, $dateTo);

        // Company settings
        $companyName    = $this->taxi->setting('company_name',    'GoniTaxi');
        $companyInn     = $this->taxi->setting('company_inn',     '');
        $sourceAccount  = $this->taxi->setting('company_bank_account', '');
        $companyBankCode= $this->taxi->setting('company_bank_code',    '');
        $taxRegCode     = $this->taxi->setting('company_tax_reg_code', '');
        $dispatchType   = $this->taxi->setting('dispatch_type',   '0');
        $currency       = $this->taxi->setting('currency',        'GEL');

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElementNS(
            'http://schemas.datacontract.org/2004/07/CIBApi.Models',
            'ArrayOfDomesticPayment'
        );
        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:i',
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $dom->appendChild($root);

        $docIndex = 1;
        foreach ($data as $row) {
            if ($row['net_amount'] <= 0) continue;
            $d = $row['driver'];

            $payment = $dom->createElement('DomesticPayment');
            $root->appendChild($payment);

            $fields = [
                'Amount'                   => number_format($row['net_amount'], 2, '.', ''),
                'BeneficiaryAccountNumber' => $d['bank_account'] ?? '',
                'Currency'                 => $currency,
                'DocumentNo'               => str_pad((string)$docIndex++, 6, '0', STR_PAD_LEFT),
                'SourceAccountNumber'      => $sourceAccount,
                'AdditionalInformation'    => $dateFrom.' – '.$dateTo,
                'BeneficiaryBankCode'      => $d['bank_code'] ?? '',
                'BeneficiaryInn'           => $d['personal_id'] ?? '',
                'BeneficiaryName'          => $d['name'] ?? '',
                'DispatchType'             => $dispatchType,
                'IsSalary'                 => 'true',
                'Nomination'               => 'ტაქსის მძღოლის ანაზღაურება '.$dateFrom.' – '.$dateTo,
                'PayerInn'                 => $companyInn,
                'PayerName'                => $companyName,
                'IsPayerResident'          => 'true',
                'PayerTaxRegistrationCode' => $taxRegCode,
                'PayerPenaltyNumber'       => '',
            ];

            foreach ($fields as $tag => $value) {
                $el = $dom->createElement($tag);
                $el->appendChild($dom->createTextNode((string)$value));
                $payment->appendChild($el);
            }
        }

        $xml = $dom->saveXML();
        $filename = 'bog-payments-'.$dateFrom.'-'.$dateTo.'.xml';

        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.strlen($xml));
        echo $xml;
        exit;
    }

    public function settlementExportCsv(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $dateFrom = (string)$r->query('from', date('Y-m-01'));
        $dateTo   = (string)$r->query('to',   date('Y-m-t'));
        $data     = $this->taxi->calcSettlementData($dateFrom, $dateTo);
        $sym      = $this->taxi->setting('currency_symbol','₾');

        $lines = ['"მძღოლი","ტელეფონი","IBAN","ბანკი","მგზავრობები","სრული","კომისია","გასარიცხი"'];
        foreach ($data as $row) {
            if ($row['net_amount'] <= 0) continue;
            $d = $row['driver'];
            $lines[] = implode(',', [
                '"'.($d['name']??'').'"',
                '"'.($d['phone']??'').'"',
                '"'.($d['bank_account']??'').'"',
                '"'.($d['bank_name']??'').'"',
                $row['rides_count'],
                $row['gross_amount'],
                $row['commission'],
                $row['net_amount'],
            ]);
        }

        $csv = implode("\n", $lines);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="settlement-'.$dateFrom.'-'.$dateTo.'.csv"');
        echo "\xEF\xBB\xBF".$csv; // UTF-8 BOM for Excel
        exit;
    }

    public function driverUpdateBank(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int)$r->getAttribute('id');
        $this->taxi->updateDriver($id, [
            'bank_account' => trim((string)$r->post('bank_account','')),
            'bank_name'    => trim((string)$r->post('bank_name','')),
        ]);
        return Response::redirect($r->basePath().'/manage/taxi/drivers?saved=1');
    }

    // Settings
    public function settingsForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->page('settings', ['base'=>$r->basePath(),'taxi'=>$this->taxi,'saved'=>$r->query('saved')==='1']);
    }

    public function settingsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        foreach ([
            'currency','currency_symbol','page_slug','brand_name','phone','admin_email',
            'base_fare','price_per_km','min_fare','commission_pct',
            'waiting_free_minutes','waiting_rate_per_min',
            'company_name','company_inn','company_bank_account',
            'company_bank_code','company_tax_reg_code','dispatch_type',
        ] as $k) {
            $this->taxi->setSetting($k, trim((string)$r->post($k,'')));
        }
        return Response::redirect($r->basePath().'/manage/taxi/settings?saved=1');
    }

    public function liveMap(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $drivers = $this->taxi->allDrivers();
        $rides   = $this->taxi->allRides(1, 200, '')['items'];
        $stats   = $this->taxi->globalStats();
        $sym     = $this->taxi->setting('currency_symbol','₾');
        return $this->page('livemap', compact('drivers','rides','stats','sym') + [
            'base'=>$r->basePath(),'taxi'=>$this->taxi,
        ]);
    }

    private function notifyDriverSettlement(array $driver, array $row, string $sym, string $dateFrom, string $dateTo): void
    {
        $driverId = (int)$driver['id'];
        $isDebt   = $row['net_amount'] < 0;
        $abs      = abs($row['net_amount']);

        // Portal notification body (plain text for in-app display)
        $dirLine = $isDebt
            ? "საიტის ვალი: {$sym}".number_format($abs, 2)
            : "საიტიდან გასარიცხი: {$sym}".number_format($abs, 2);

        $body = "პერიოდი: {$dateFrom} – {$dateTo} | "
              . "ხელზე ქეში: {$sym}".number_format((float)$row['gross_amount'], 2)." | "
              . "კომისია: {$sym}".number_format((float)$row['commission'], 2)." | "
              . $dirLine;

        $this->taxi->createDriverNotification($driverId, 'settlement', 'ფინანსური ანგარიშგება', $body);

        // Email
        $email = trim((string)($driver['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) return;

        $dirColor = $isDebt ? '#ef4444' : '#10b981';
        $dirLabel = $isDebt ? 'საიტის ვალი' : 'საიტიდან გასარიცხი';
        $name     = htmlspecialchars((string)($driver['name'] ?? ''), ENT_QUOTES);
        $gross    = number_format((float)$row['gross_amount'], 2).$sym;
        $comm     = number_format((float)$row['commission'], 2).$sym;
        $net      = number_format($abs, 2).$sym;

        $html = $this->mail->template('ფინანსური ანგარიშგება', <<<HTML
<p>გამარჯობა, <strong>{$name}</strong>!</p>
<p style="color:#64748b;font-size:14px;margin-bottom:20px">პერიოდი: {$dateFrom} – {$dateTo}</p>
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:15px">
  <tr>
    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b">ხელზე ქეში</td>
    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:700">{$gross}</td>
  </tr>
  <tr>
    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b">კომისია ({$this->taxi->commissionPct()}%)</td>
    <td style="padding:10px 0;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:700;color:#ef4444">−{$comm}</td>
  </tr>
  <tr style="background:#f8fafc">
    <td style="padding:12px 8px;font-weight:800;font-size:16px">{$dirLabel}</td>
    <td style="padding:12px 8px;text-align:right;font-weight:900;font-size:20px;color:{$dirColor}">{$net}</td>
  </tr>
</table>
HTML);
        $this->mail->send($email, 'ფინანსური ანგარიშგება · '.$dateFrom.' – '.$dateTo, $html);
    }

    private function guard(Request $r): ?Response
    {
        return $this->auth->isLoggedIn() ? null : Response::redirect($r->basePath().'/login');
    }

    private function page(string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__,3).'/themes/default/views';
        require_once $themeDir.'/helpers.php';
        $base=$data['base']??''; $siteName=$this->siteName; $hooks=$this->hooks;
        $userId=$this->auth->currentUserId();
        $user=$userId?$this->qb->table('users')->where('id','=',$userId)->first():null;
        $notifList=[]; $notifUnread=0; $panelLangs=[]; $currentLangCode='en';
        extract($data, EXTR_SKIP);
        ob_start();
        try { include __DIR__.'/../views/admin/'.$view.'.php'; $content=(string)ob_get_clean(); }
        catch(\Throwable $e){ ob_end_clean(); throw $e; }
        ob_start();
        try { include $themeDir.'/manage/layout.php'; return Response::html((string)ob_get_clean()); }
        catch(\Throwable $e){ ob_end_clean(); throw $e; }
    }
}
