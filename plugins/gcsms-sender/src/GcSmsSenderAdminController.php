<?php
declare(strict_types=1);

namespace GcSmsSender;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class GcSmsSenderAdminController
{
    public function __construct(
        private readonly GcSmsSenderService $sms,
        private readonly QueryBuilder       $qb,
        private readonly LoginService       $auth,
        private readonly HookManager        $hooks,
        private readonly string             $siteName = 'GoniCore',
    ) {}

    // ── Settings ──────────────────────────────────────────────────────────────

    public function settings(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $saved   = $r->query('saved') === '1';
        $balance = null;
        $balErr  = null;

        if ($this->sms->isConfigured()) {
            $b = $this->sms->balance();
            if ($b['httpCode'] === 200) {
                $balance = $b['result'];
            } else {
                $balErr = self::httpMessage($b['httpCode'])
                    . (isset($b['result']['error']) ? ' — ' . $b['result']['error'] : '');
            }
        }

        return $this->renderPage('settings', [
            'base'    => $r->basePath(),
            'sms'     => $this->sms,
            'saved'   => $saved,
            'balance' => $balance,
            'balErr'  => $balErr,
        ]);
    }

    public function settingsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->sms->setSetting('api_key', trim((string) $r->post('api_key', '')));
        $this->sms->setSetting('smsno',   in_array((string) $r->post('smsno', '2'), ['1','2']) ? (string) $r->post('smsno') : '2');
        return Response::redirect($r->basePath() . '/manage/gcsmssender/settings?saved=1');
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
        ]);
    }

    public function sendPost(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $rawPhone = trim((string) $r->post('phone', ''));
        $text     = trim((string) $r->post('text', ''));
        $smsNo    = in_array((string) $r->post('smsno', ''), ['1','2'])
                    ? (int) $r->post('smsno')
                    : (int) $this->sms->setting('smsno', '2');
        $priority = (string) $r->post('priority', '0') === '1' ? 1 : 0;

        $result = null;
        $error  = null;

        if ($rawPhone === '') {
            $error = 'ტელეფონის ნომერი სავალდებულოა.';
        } elseif ($text === '') {
            $error = 'შეტყობინების ტექსტი სავალდებულოა.';
        } else {
            $phone = $this->sms->normalizePhone($rawPhone);
            if (strlen($phone) !== 9) {
                $error = 'ტელეფონის ნომერი სწორი ფორმატისაა: 9 ციფრი (მაგ: 595123456).';
            } else {
                $sent   = $this->sms->send($phone, $text, $smsNo, $priority);
                $result = $sent;
                if ($sent['httpCode'] !== 200) {
                    $error = self::httpMessage($sent['httpCode'])
                        . (isset($sent['result']['error']) ? ' — ' . $sent['result']['error'] : '');
                }
            }
        }

        return $this->renderPage('send', [
            'base'   => $r->basePath(),
            'sms'    => $this->sms,
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
        return Response::redirect($r->basePath() . '/manage/gcsmssender/logs?cleared=1');
    }

    // ── Delivery status check ─────────────────────────────────────────────────

    /** GET /manage/gcsmssender/logs/check?id=MESSAGEID&page=N */
    public function statusCheck(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $messageId = trim((string) $r->query('id', ''));
        $page      = max(1, (int) $r->query('page', '1'));

        if ($messageId !== '') {
            $this->sms->checkStatus($messageId);
        }

        return Response::redirect(
            $r->basePath() . '/manage/gcsmssender/logs?page=' . $page . '&checked=1'
        );
    }

    // ── HTTP status → Georgian message ────────────────────────────────────────

    public static function httpMessage(int $code): string
    {
        return match ($code) {
            200  => 'წარმატებით გაიგზავნა.',
            401  => 'API გასაღები არასწორია (401 Unauthorized).',
            402  => 'SMS ბალანსი არ არის საკმარისი (402 Payment Required).',
            403  => 'წვდომა აკრძალულია (403 Forbidden).',
            503  => 'სერვისი დროებით მიუწვდომელია (503 Service Unavailable).',
            0    => 'ქსელური შეცდომა — cURL ვერ დაუკავშირდა.',
            default => "HTTP შეცდომა ({$code}).",
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
