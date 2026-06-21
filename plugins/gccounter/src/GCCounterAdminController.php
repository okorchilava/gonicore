<?php
declare(strict_types=1);

namespace GCCounter;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class GCCounterAdminController
{
    public function __construct(
        private readonly GCCounterService $svc,
        private readonly QueryBuilder     $qb,
        private readonly LoginService     $auth,
        private readonly HookManager      $hooks,
        private readonly string           $siteName = 'GoniCore',
    ) {}

    // ── Dashboard ──────────────────────────────────────────────────────────────

    public function dashboard(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('dashboard', [
            'base'   => $r->basePath(),
            'stats'  => $this->svc->stats(),
            'groups' => $this->svc->allGroups(),
        ]);
    }

    // ── Counter group list ──────────────────────────────────────────────────────

    public function counters(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $groups = $this->svc->allGroups();
        // Enrich with item count
        foreach ($groups as &$g) {
            $g['item_count'] = count($this->svc->itemsByGroup((int)$g['id']));
        }
        unset($g);
        return $this->renderPage('counters', [
            'base'    => $r->basePath(),
            'groups'  => $groups,
            'saved'   => $r->query('saved')   === '1',
            'deleted' => $r->query('deleted') === '1',
        ]);
    }

    // ── Counter form ────────────────────────────────────────────────────────────

    public function counterForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id    = $r->query('id') ? (int) $r->query('id') : null;
        $group = $id ? $this->svc->group($id) : null;
        $items = ($id && $group) ? $this->svc->itemsByGroup($id) : [];
        return $this->renderPage('counter_form', [
            'base'   => $r->basePath(),
            'group'  => $group,
            'items'  => $items,
            'isEdit' => $id !== null && $group !== null,
        ]);
    }

    // ── Save ────────────────────────────────────────────────────────────────────

    public function counterSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;

        $id   = $r->post('id') ? (int) $r->post('id') : null;
        $name = trim((string) $r->post('name', ''));
        if ($name === '') $name = 'Counter Group';

        $slug = trim((string) $r->post('slug', ''));
        if ($slug === '') $slug = GCCounterService::slugify($name);
        $slug = GCCounterService::slugify($slug);
        // Make slug unique if creating new
        if (!$id) {
            $base  = $slug;
            $count = 1;
            while ($this->svc->groupBySlug($slug)) {
                $slug = $base . '-' . $count++;
            }
        }

        $cols = max(2, min(6, (int) $r->post('columns', '4')));
        $dur  = max(200, min(8000, (int) $r->post('duration_ms', '2000')));
        $sep  = in_array((string) $r->post('separator', ','), [',', '.', ''], true)
                ? (string) $r->post('separator', ',')
                : ',';
        $align = in_array((string) $r->post('align', 'center'), ['left','center','right'], true)
                 ? (string) $r->post('align', 'center')
                 : 'center';

        $groupData = [
            'name'        => $name,
            'slug'        => $slug,
            'columns'     => $cols,
            'duration_ms' => $dur,
            'separator'   => $sep,
            'align'       => $align,
        ];

        $groupId = $this->svc->saveGroup($groupData, $id);

        // Items — submitted as items[0][field], items[1][field], ...
        $rawItems = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];
        // Filter out completely empty rows
        $cleanItems = array_values(array_filter($rawItems, static function (mixed $item): bool {
            $num = trim((string)($item['number'] ?? ''));
            $lbl = trim((string)($item['label']  ?? ''));
            return $num !== '' || $lbl !== '';
        }));

        $this->svc->replaceItems($groupId, $cleanItems);

        return Response::redirect($r->basePath() . '/manage/gccounter?saved=1');
    }

    // ── Delete ──────────────────────────────────────────────────────────────────

    public function counterDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->post('id', '0');
        if ($id > 0) $this->svc->deleteGroup($id);
        return Response::redirect($r->basePath() . '/manage/gccounter?deleted=1');
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
