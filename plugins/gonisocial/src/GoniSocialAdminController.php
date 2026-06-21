<?php
declare(strict_types=1);

namespace GoniSocial;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class GoniSocialAdminController
{
    private const DEFAULTS = [
        'enabled'           => '1',
        'og_enabled'        => '1',
        'og_type'           => 'website',
        'og_site_name'      => '',
        'og_default_image'  => '',
        'twitter_card'      => 'summary_large_image',
        'twitter_handle'    => '',
        'facebook_app_id'   => '',
        'share_enabled'     => '1',
        'share_position'    => 'floating-left',
        'share_networks'    => 'facebook,twitter,whatsapp,telegram,linkedin',
        'share_hide_mobile' => '0',
    ];

    public function __construct(
        private readonly GoniSocialService $svc,
        private readonly QueryBuilder      $qb,
        private readonly LoginService      $auth,
        private readonly HookManager       $hooks,
        private readonly string            $siteName = 'GoniCore',
    ) {}

    // ── Dashboard ──────────────────────────────────────────────────────────────

    public function dashboard(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('dashboard', [
            'base'     => $r->basePath(),
            'stats'    => $this->svc->stats(),
            'settings' => array_merge(self::DEFAULTS, $this->svc->getSettings()),
            'profiles' => $this->svc->activeProfiles(),
        ]);
    }

    // ── Settings (OG / Twitter) ────────────────────────────────────────────────

    public function settings(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('settings', [
            'base'     => $r->basePath(),
            'settings' => array_merge(self::DEFAULTS, $this->svc->getSettings()),
            'saved'    => $r->query('saved') === '1',
        ]);
    }

    public function settingsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $settingsKeys = ['enabled', 'og_enabled', 'og_type', 'og_site_name',
                         'og_default_image', 'twitter_card', 'twitter_handle', 'facebook_app_id'];
        foreach ($settingsKeys as $key) {
            if (in_array($key, ['enabled', 'og_enabled'], true)) {
                $value = (string) $r->post($key, '0') === '1' ? '1' : '0';
            } else {
                $value = trim((string) $r->post($key, ''));
            }
            $this->svc->saveSetting($key, $value);
        }
        return Response::redirect($r->basePath() . '/manage/gonisocial/settings?saved=1');
    }

    // ── Share buttons ──────────────────────────────────────────────────────────

    public function share(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('share', [
            'base'     => $r->basePath(),
            'settings' => array_merge(self::DEFAULTS, $this->svc->getSettings()),
            'saved'    => $r->query('saved') === '1',
        ]);
    }

    public function shareSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        // share_enabled toggle
        $this->svc->saveSetting('share_enabled',
            (string) $r->post('share_enabled', '0') === '1' ? '1' : '0'
        );
        // share_hide_mobile toggle
        $this->svc->saveSetting('share_hide_mobile',
            (string) $r->post('share_hide_mobile', '0') === '1' ? '1' : '0'
        );
        // position
        $pos = trim((string) $r->post('share_position', 'floating-left'));
        if (!in_array($pos, ['floating-left','floating-right','bottom-bar'], true)) {
            $pos = 'floating-left';
        }
        $this->svc->saveSetting('share_position', $pos);

        // networks: array of checkboxes
        $allowed  = GoniSocialService::SHARE_NETWORKS;
        $selected = [];
        $raw      = $r->post('share_networks'); // may be array
        if (is_array($raw)) {
            foreach ($raw as $n) {
                $n = trim((string) $n);
                if (in_array($n, $allowed, true)) $selected[] = $n;
            }
        }
        // Preserve order from $allowed
        $ordered = array_filter($allowed, static fn($n) => in_array($n, $selected, true));
        $this->svc->saveSetting('share_networks', implode(',', $ordered));

        return Response::redirect($r->basePath() . '/manage/gonisocial/share?saved=1');
    }

    // ── Profiles ───────────────────────────────────────────────────────────────

    public function profiles(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('profiles', [
            'base'     => $r->basePath(),
            'profiles' => $this->svc->allProfiles(),
            'saved'    => $r->query('saved') === '1',
            'deleted'  => $r->query('deleted') === '1',
        ]);
    }

    public function profileForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id  = $r->query('id') ? (int) $r->query('id') : null;
        $row = $id ? $this->svc->profile($id) : null;
        return $this->renderPage('profile_form', [
            'base'     => $r->basePath(),
            'row'      => $row,
            'isEdit'   => $id !== null && $row !== null,
            'networks' => GoniSocialService::PROFILE_NETWORKS,
        ]);
    }

    public function profileSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = $r->post('id') ? (int) $r->post('id') : null;

        $network = trim((string) $r->post('network', ''));
        if (!array_key_exists($network, GoniSocialService::PROFILE_NETWORKS)) {
            $network = array_key_first(GoniSocialService::PROFILE_NETWORKS);
        }

        $data = [
            'network'      => $network,
            'display_name' => trim((string) $r->post('display_name', '')),
            'url'          => trim((string) $r->post('url',          '')),
            'handle'       => ltrim(trim((string) $r->post('handle', '')), '@'),
            'active'       => (string) $r->post('active', '1') === '1' ? 1 : 0,
            'sort_order'   => max(0, (int) $r->post('sort_order', '0')),
        ];

        $this->svc->saveProfile($data, $id);
        return Response::redirect($r->basePath() . '/manage/gonisocial/profiles?saved=1');
    }

    public function profileDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->post('id', '0');
        if ($id > 0) $this->svc->deleteProfile($id);
        return Response::redirect($r->basePath() . '/manage/gonisocial/profiles?deleted=1');
    }

    public function profileToggle(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->post('id', '0');
        if ($id > 0) $this->svc->toggleProfile($id);
        return Response::redirect($r->basePath() . '/manage/gonisocial/profiles');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

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
        $user   = $userId
            ? $this->qb->table('users')->where('id', '=', $userId)->first()
            : null;

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
