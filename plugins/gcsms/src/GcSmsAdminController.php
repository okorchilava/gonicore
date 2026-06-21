<?php
declare(strict_types=1);

namespace GcSms;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class GcSmsAdminController
{
    public function __construct(
        private readonly GcSmsService $sms,
        private readonly QueryBuilder $qb,
        private readonly LoginService $auth,
        private readonly HookManager  $hooks,
        private readonly string       $siteName = 'GoniCore',
    ) {}

    // ── Settings + Sender ─────────────────────────────────────────────────────

    public function senderCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $name   = trim((string) $r->post('name', ''));
        $result = $this->sms->createSender($name);
        // Pass result back to settings page
        $saved   = false;
        $balance = null;
        $balErr  = null;
        if ($this->sms->setting('api_key') !== '') {
            $b = $this->sms->balance();
            if ($b['success'] ?? false) $balance = (int) ($b['balance'] ?? 0);
        }
        return $this->renderPage('settings', compact('saved', 'balance', 'balErr') + [
            'base'         => $r->basePath(),
            'sms'          => $this->sms,
            'senderResult' => $result,
        ]);
    }

    public function settings(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $saved   = $r->query('saved') === '1';
        $balance = null;
        $balErr  = null;

        if ($this->sms->setting('api_key') !== '') {
            $b = $this->sms->balance();
            if ($b['success'] ?? false) {
                $balance = (int) ($b['balance'] ?? 0);
            } else {
                $balErr = $this->errorMessage((int) ($b['errorCode'] ?? 0));
            }
        }

        return $this->renderPage('settings', compact('saved', 'balance', 'balErr') + [
            'base' => $r->basePath(),
            'sms'  => $this->sms,
        ]);
    }

    public function settingsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->sms->setSetting('api_key',     trim((string) $r->post('api_key', '')));
        $this->sms->setSetting('sender_name', trim((string) $r->post('sender_name', '')));
        return Response::redirect($r->basePath() . '/manage/gcsms/settings?saved=1');
    }

    // ── Send SMS ──────────────────────────────────────────────────────────────

    public function sendForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('send', [
            'base'   => $r->basePath(),
            'sms'    => $this->sms,
            'result' => null,
            'error'  => null,
            'tab'    => $r->query('tab', 'single'),
        ]);
    }

    public function sendPost(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $tab  = (string) $r->post('tab', 'single');
        $text = trim((string) $r->post('text', ''));
        $result = null;
        $error  = null;

        if ($text === '') {
            $error = 'შეტყობინების ტექსტი სავალდებულოა.';
        } elseif ($tab === 'bulk') {
            $raw    = trim((string) $r->post('phones', ''));
            $phones = array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $raw) ?: [])));
            if (empty($phones)) {
                $error = 'მიუთითეთ მინიმუმ ერთი ნომერი.';
            } elseif (count($phones) > 1000) {
                $error = 'მაქსიმუმ 1000 ნომერია დაშვებული ერთ გაგზავნაზე.';
            } else {
                $noSms  = trim((string) $r->post('no_sms_number', ''));
                $result = $this->sms->sendBulk($phones, $text, false, $noSms);
            }
        } else {
            $phone = trim((string) $r->post('phone', ''));
            if ($phone === '') {
                $error = 'ტელეფონის ნომერი სავალდებულოა.';
            } else {
                $result = $this->sms->send($phone, $text);
            }
        }

        return $this->renderPage('send', [
            'base'   => $r->basePath(),
            'sms'    => $this->sms,
            'result' => $result,
            'error'  => $error,
            'tab'    => $tab,
        ]);
    }

    // ── OTP test ──────────────────────────────────────────────────────────────

    public function otpForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('otp', [
            'base'   => $r->basePath(),
            'sms'    => $this->sms,
            'step'   => 1,
            'phone'  => '',
            'hash'   => '',
            'result' => null,
            'error'  => null,
        ]);
    }

    public function otpSend(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $phone  = trim((string) $r->post('phone', ''));
        $result = $this->sms->sendOtp($phone);
        $error  = ($result['success'] ?? false) ? null : $this->errorMessage((int) ($result['errorCode'] ?? 0));
        return $this->renderPage('otp', [
            'base'   => $r->basePath(),
            'sms'    => $this->sms,
            'step'   => ($result['success'] ?? false) ? 2 : 1,
            'phone'  => $phone,
            'hash'   => (string) ($result['hash'] ?? ''),
            'result' => $result,
            'error'  => $error,
        ]);
    }

    public function otpVerify(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $phone  = trim((string) $r->post('phone', ''));
        $hash   = trim((string) $r->post('hash', ''));
        $code   = trim((string) $r->post('code', ''));
        $result = $this->sms->verifyOtp($phone, $hash, $code);
        $error  = ($result['success'] ?? false) ? null : $this->errorMessage((int) ($result['errorCode'] ?? 0));
        return $this->renderPage('otp', [
            'base'   => $r->basePath(),
            'sms'    => $this->sms,
            'step'   => 3,
            'phone'  => $phone,
            'hash'   => $hash,
            'result' => $result,
            'error'  => $error,
        ]);
    }

    // ── Logs ──────────────────────────────────────────────────────────────────

    public function logs(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $page = max(1, (int) $r->query('page', '1'));
        $data = $this->sms->logs($page);
        return $this->renderPage('logs', $data + [
            'base' => $r->basePath(),
            'sms'  => $this->sms,
            'page' => $page,
        ]);
    }

    public function logsClear(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->sms->clearLogs();
        return Response::redirect($r->basePath() . '/manage/gcsms/logs?cleared=1');
    }

    // ── Webhooks / Inbound replies ──────────────────────────────────────────────

    public function webhookRegenerate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->sms->regenerateWebhookToken();
        return Response::redirect($r->basePath() . '/manage/gcsms/settings?saved=1');
    }

    public function inbound(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $page = max(1, (int) $r->query('page', '1'));
        $data = $this->sms->inbound($page);
        // Viewing the inbox clears the unread badge.
        $this->sms->markInboundRead();
        return $this->renderPage('inbound', $data + [
            'base' => $r->basePath(),
            'sms'  => $this->sms,
            'page' => $page,
        ]);
    }

    public function inboundClear(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->sms->clearInbound();
        return Response::redirect($r->basePath() . '/manage/gcsms/inbound?cleared=1');
    }

    // ── Error code → human message ────────────────────────────────────────────

    public static function errorMessage(int $code): string
    {
        return match ($code) {
            100  => 'API გასაღები არასწორია ან მითითებული არ არის.',
            101  => 'გამომგზავნის სახელი (sender) არ არის დარეგისტრირებული ან არ არის გააქტიურებული.',
            102  => 'SMS ბალანსი არ არის საკმარისი.',
            103  => 'პარამეტრები არასწორია ან ტექსტი ლიმიტს სცილდება.',
            104  => 'მესიჯის ID ვერ მოიძებნა.',
            105  => 'ტელეფონის ნომრის ფორმატი არასწორია.',
            106  => 'OTP გაგზავნა ვერ მოხერხდა.',
            107  => 'ამ სახელით sender უკვე არსებობს.',
            108  => 'Sender შექმნა ვერ მოხერხდა ან OTP კონფიგურირებული არ არის.',
            109  => 'OTP მოთხოვნების ლიმიტი ამოიწურა.',
            110  => 'ანგარიში დაბლოკილია — ძალიან ბევრი წარუმატებელი მცდელობა.',
            111  => 'OTP კოდის ვადა ამოიწურა.',
            112  => 'OTP კოდი უკვე გამოყენებულია.',
            113  => 'noSmsNumber პარამეტრი არასწორია.',
            default => "უცნობი შეცდომა (კოდი: {$code}).",
        };
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function guard(Request $r): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($r->basePath() . '/login');
        }
        return null;
    }

    /** @param array<string,mixed> $data */
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
