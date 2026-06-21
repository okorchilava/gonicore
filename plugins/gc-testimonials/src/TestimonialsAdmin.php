<?php

declare(strict_types=1);

namespace GCTestimonials;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Language\LanguageRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Notifications\NotificationService;

/**
 * Admin panel for GC Testimonials, rendered inside the GoniCore manage layout.
 * Tabs: reviews (list + add/edit) and campaigns. All POSTs are CSRF-guarded.
 */
final class TestimonialsAdmin
{
    public function __construct(
        private readonly LoginService        $auth,
        private readonly SessionManager      $session,
        private readonly TestimonialsService $service,
        private readonly LanguageService     $langService,
        private readonly LanguageRepository  $langRepo,
        private readonly NotificationService $notifications,
        private readonly QueryBuilder        $qb,
        private readonly HookManager         $hooks,
        private readonly string              $siteName = 'GoniCore',
    ) {}

    private function flash(string $msg, string $icon = 'success'): void
    {
        $this->session->flash('gc_msg',  $msg);
        $this->session->flash('gc_icon', $icon);
    }

    /** Auth + CSRF gate. Returns a redirect Response when the request must stop. */
    private function guard(Request $request): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login?redirect=' . urlencode($request->path()));
        }
        if ($request->method() === 'POST'
            && !$this->session->verifyCsrf((string) $request->post('_csrf', ''))) {
            $this->flash('Your session has expired. Please sign in again.', 'warning');
            $this->session->flash('gc_action', 'logout');
            return Response::redirect($request->basePath() . '/manage/testimonials');
        }
        return null;
    }

    private function base(Request $request): string
    {
        return $request->basePath();
    }

    // ── GET /manage/testimonials ──────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;

        $tab    = (string) $request->query('tab', 'reviews');
        $action = (string) $request->query('action', 'list');

        if ($tab === 'campaigns') {
            return $this->renderPage($request, 'admin/campaigns', [
                'campaigns' => $this->service->campaigns(),
            ]);
        }

        if ($action === 'add' || $action === 'edit') {
            $id   = (int) $request->query('id', '0');
            $edit = $id > 0 ? $this->service->find($id) : null;
            if ($action === 'edit' && $edit === null) {
                return Response::redirect($this->base($request) . '/manage/testimonials');
            }
            return $this->renderPage($request, 'admin/form', [
                'edit'      => $edit,
                'campaigns' => $this->service->campaigns(),
            ]);
        }

        return $this->renderPage($request, 'admin/list', [
            'items'        => $this->service->all(),
            'pendingCount' => $this->service->pendingCount(),
        ]);
    }

    // ── POST handlers ──────────────────────────────────────────────────────────────

    public function save(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;

        $id   = (int) $request->post('id', '0');
        $name = trim(strip_tags((string) $request->post('client_name', '')));
        $text = trim(strip_tags((string) $request->post('testimonial_text', '')));

        if ($name === '' || $text === '') {
            $this->flash('Name and review text are required.', 'error');
            return Response::redirect($this->base($request) . '/manage/testimonials?action=' . ($id > 0 ? 'edit&id=' . $id : 'add'));
        }

        $data = [
            'campaign_id'      => max(0, (int) $request->post('campaign_id', '0')),
            'client_name'      => mb_substr($name, 0, 240),
            'client_role'      => mb_substr(trim(strip_tags((string) $request->post('client_role', ''))), 0, 240),
            'testimonial_text' => mb_substr($text, 0, 2000),
            'rating'           => max(1, min(5, (int) $request->post('rating', '5'))),
            'is_public'        => $request->post('is_public') !== null ? 1 : 0,
        ];

        if ($id > 0) {
            $this->service->update($id, $data);
            $this->flash('Review updated.');
        } else {
            $this->service->create($data);
            $this->flash('Review added.');
        }

        return Response::redirect($this->base($request) . '/manage/testimonials');
    }

    public function delete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id = (int) $request->post('id', '0');
        if ($id > 0) {
            $this->service->delete($id);
            $this->flash('Review deleted.');
        }
        return Response::redirect($this->base($request) . '/manage/testimonials');
    }

    public function toggle(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id = (int) $request->post('id', '0');
        if ($id > 0) {
            $row = $this->service->find($id);
            if ($row !== null) {
                $this->service->setPublic($id, (int) $row['is_public'] !== 1);
                $this->flash((int) $row['is_public'] === 1 ? 'Review hidden.' : 'Review published.');
            }
        }
        return Response::redirect($this->base($request) . '/manage/testimonials');
    }

    public function saveCampaign(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $name = trim(strip_tags((string) $request->post('name', '')));
        if ($name === '') {
            $this->flash('Campaign name is required.', 'error');
        } else {
            $this->service->createCampaign(mb_substr($name, 0, 240));
            $this->flash('Campaign added.');
        }
        return Response::redirect($this->base($request) . '/manage/testimonials?tab=campaigns');
    }

    public function deleteCampaign(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id = (int) $request->post('id', '0');
        if ($id > 0) {
            $this->service->deleteCampaign($id);
            $this->flash('Campaign deleted.');
        }
        return Response::redirect($this->base($request) . '/manage/testimonials?tab=campaigns');
    }

    // ── Render harness (manage layout chrome) ───────────────────────────────────────

    /** @param array<string,mixed> $data */
    private function renderPage(Request $request, string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeDir . '/helpers.php';

        // Plugin strings come from this plugin's OWN pack (never the engine's).
        $t = gc_plugin_translator(dirname(__DIR__));

        $base     = $request->basePath();
        $siteName = $this->siteName;
        $hooks    = $this->hooks;

        $userId = $this->auth->currentUserId();
        $user   = $userId
            ? $this->qb->table('users')->where('id', '=', $userId)->first()
            : null;

        $notifList       = $user ? $this->notifications->forUser((int) $user['id']) : [];
        $notifUnread     = $user ? $this->notifications->unreadCount((int) $user['id']) : 0;
        $panelLangs      = $this->langRepo->allActive();
        $currentLangCode = $this->langService->currentCode();

        $flashMsg    = $this->session->getFlash('gc_msg');
        $flashIcon   = $this->session->getFlash('gc_icon') ?? 'success';
        $flashAction = $this->session->getFlash('gc_action');
        $csrfToken   = $this->session->csrfToken();

        $activeNav = 'testimonials';

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include __DIR__ . '/../views/' . $view . '.php';
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
