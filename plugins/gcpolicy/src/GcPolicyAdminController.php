<?php
declare(strict_types=1);

namespace GcPolicy;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class GcPolicyAdminController
{
    private const DEFAULTS = [
        'enabled'      => '1',
        'text'         => 'ვებსაიტი იყენებს "ქუქი ჩანაწერებს". დამატებითი ინფორმაციის მისაღებად იხილეთ:',
        'link_text'    => 'Cookie პოლიტიკა',
        'link_url'     => '#',
        'btn_text'     => 'კეთილი',
        'position'     => 'bottom',
        'show_decline' => '1',
        'decline_text' => 'უარყოფა',
        'expire_days'  => '365',
    ];

    public function __construct(
        private readonly QueryBuilder $qb,
        private readonly LoginService $auth,
        private readonly HookManager  $hooks,
        private readonly string       $siteName = 'GoniCore',
    ) {}

    // ── Pages ─────────────────────────────────────────────────────────────────

    public function settings(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('settings', [
            'base'     => $r->basePath(),
            'settings' => $this->allSettings(),
            'saved'    => $r->query('saved') === '1',
        ]);
    }

    public function settingsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        foreach (array_keys(self::DEFAULTS) as $key) {
            if ($key === 'enabled' || $key === 'show_decline') {
                // Toggle: hidden input carries '0'; checkbox sends '1' when checked
                $value = (string) $r->post($key, '0') === '1' ? '1' : '0';
            } else {
                $value = trim((string) $r->post($key, ''));
                if ($value === '') $value = self::DEFAULTS[$key];
            }
            $this->save($key, $value);
        }

        return Response::redirect($r->basePath() . '/manage/gcpolicy/settings?saved=1');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function allSettings(): array
    {
        $out = [];
        foreach (self::DEFAULTS as $key => $default) {
            try {
                $row = $this->qb->table('gcpolicy_settings')->where('key', '=', $key)->first();
                $out[$key] = ($row && $row['value'] !== '') ? (string) $row['value'] : $default;
            } catch (\Throwable) {
                $out[$key] = $default;
            }
        }
        return $out;
    }

    private function save(string $key, string $value): void
    {
        try {
            $exists = $this->qb->table('gcpolicy_settings')->where('key', '=', $key)->first();
            if ($exists) {
                $this->qb->table('gcpolicy_settings')->where('key', '=', $key)->update(['value' => $value]);
            } else {
                $this->qb->table('gcpolicy_settings')->insert(['key' => $key, 'value' => $value]);
            }
        } catch (\Throwable) {}
    }

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
