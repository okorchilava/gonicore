<?php
declare(strict_types=1);

namespace GoniTickets;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class AdminController
{
    public function __construct(
        private readonly TicketService $tickets,
        private readonly QueryBuilder  $qb,
        private readonly LoginService  $auth,
        private readonly HookManager   $hooks,
        private readonly string        $siteName = 'GoniCore',
    ) {}

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $stats  = $this->tickets->globalStats();
        $recent = $this->tickets->allEvents(1, 5)['events'];
        return $this->renderPage('dashboard', compact('stats', 'recent') + ['base' => $r->basePath(), 'tickets' => $this->tickets]);
    }

    // ── Events ────────────────────────────────────────────────────────────────

    public function eventsList(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $page   = max(1, (int) ($r->query('page', '1')));
        $search = trim((string) ($r->query('q', '')));
        $data    = $this->tickets->allEvents($page, 20, false, $search);
        $deleted = $r->query('deleted') === '1';
        return $this->renderPage('events', $data + [
            'base'    => $r->basePath(),
            'page'    => $page,
            'search'  => $search,
            'deleted' => $deleted,
            'tickets' => $this->tickets,
        ]);
    }

    public function eventNew(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('event_form', ['base' => $r->basePath(), 'event' => null, 'ticketTypes' => [], 'tickets' => $this->tickets]);
    }

    public function eventCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $data  = $this->extractEventData($r);
        $slug  = $data['slug'] ?: $this->tickets->slugify($data['title']);
        $data['slug'] = $this->uniqueSlug($slug);
        $id = $this->tickets->createEvent($data);
        return Response::redirect($r->basePath() . '/manage/tickets/events/' . $id . '/edit?saved=1');
    }

    public function eventEdit(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id    = (int) $r->getAttribute('id');
        $event = $this->tickets->getEvent($id);
        if (!$event) return Response::redirect($r->basePath() . '/manage/tickets/events');
        $ticketTypes = $this->tickets->ticketTypesForEvent($id);
        $saved       = $r->query('saved') === '1';
        $eventStats  = $this->tickets->statsForEvent($id);
        return $this->renderPage('event_form', compact('event', 'ticketTypes', 'saved', 'eventStats') + [
            'base'    => $r->basePath(),
            'tickets' => $this->tickets,
        ]);
    }

    public function eventUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id   = (int) $r->getAttribute('id');
        $data = $this->extractEventData($r);
        if (empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($this->tickets->slugify($data['title']), $id);
        }
        $this->tickets->updateEvent($id, $data);
        return Response::redirect($r->basePath() . '/manage/tickets/events/' . $id . '/edit?saved=1');
    }

    public function eventDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->tickets->deleteEvent((int) $r->getAttribute('id'));
        return Response::redirect($r->basePath() . '/manage/tickets/events?deleted=1');
    }

    // ── Ticket Types ──────────────────────────────────────────────────────────

    public function ticketTypeCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $eventId = (int) $r->getAttribute('event_id');
        $this->tickets->createTicketType([
            'event_id'      => $eventId,
            'name'          => trim((string) $r->post('name', 'General')),
            'description'   => trim((string) $r->post('description', '')),
            'price'         => max(0.0, (float) $r->post('price', '0')),
            'quantity'      => $r->post('quantity') !== '' ? max(1, (int) $r->post('quantity')) : null,
            'max_per_order' => $r->post('max_per_order') !== '' ? max(1, (int) $r->post('max_per_order')) : null,
            'status'        => $r->post('status', 'active') === 'active' ? 'active' : 'inactive',
            'sort_order'    => (int) $r->post('sort_order', '0'),
        ]);
        return Response::redirect($r->basePath() . '/manage/tickets/events/' . $eventId . '/edit?saved=1');
    }

    public function ticketTypeUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id      = (int) $r->getAttribute('id');
        $tt      = $this->tickets->getTicketType($id);
        $eventId = $tt ? (int) $tt['event_id'] : 0;
        $this->tickets->updateTicketType($id, [
            'name'          => trim((string) $r->post('name', 'General')),
            'description'   => trim((string) $r->post('description', '')),
            'price'         => max(0.0, (float) $r->post('price', '0')),
            'quantity'      => $r->post('quantity') !== '' ? max(1, (int) $r->post('quantity')) : null,
            'max_per_order' => $r->post('max_per_order') !== '' ? max(1, (int) $r->post('max_per_order')) : null,
            'status'        => $r->post('status', 'active') === 'active' ? 'active' : 'inactive',
            'sort_order'    => (int) $r->post('sort_order', '0'),
        ]);
        return Response::redirect($r->basePath() . '/manage/tickets/events/' . $eventId . '/edit?saved=1');
    }

    public function ticketTypeDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id      = (int) $r->getAttribute('id');
        $tt      = $this->tickets->getTicketType($id);
        $eventId = $tt ? (int) $tt['event_id'] : 0;
        $this->tickets->deleteTicketType($id);
        return Response::redirect($r->basePath() . '/manage/tickets/events/' . $eventId . '/edit?saved=1');
    }

    // ── Bookings ──────────────────────────────────────────────────────────────

    public function bookingsList(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $page    = max(1, (int) ($r->query('page', '1')));
        $eventId = (int) ($r->query('event', '0'));
        $data    = $this->tickets->allBookings($page, 25, $eventId);
        $events  = $this->tickets->allEvents(1, 200)['events'];
        return $this->renderPage('bookings', $data + [
            'base'    => $r->basePath(),
            'page'    => $page,
            'eventId' => $eventId,
            'events'  => $events,
            'tickets' => $this->tickets,
        ]);
    }

    public function bookingView(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id      = (int) $r->getAttribute('id');
        $booking = $this->tickets->getBooking($id);
        if (!$booking) return Response::redirect($r->basePath() . '/manage/tickets/bookings');
        return $this->renderPage('booking', [
            'base'    => $r->basePath(),
            'booking' => $booking,
            'tickets' => $this->tickets,
            'flash'   => $r->query('msg', ''),
            'error'   => $r->query('err', ''),
        ]);
    }

    public function bookingUpdateStatus(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id     = (int) $r->getAttribute('id');
        $status = (string) $r->post('status', 'pending');
        $allowed = ['pending', 'confirmed', 'cancelled', 'refunded'];
        if (in_array($status, $allowed, true)) {
            if ($status === 'cancelled') {
                $this->tickets->cancelBooking($id);
            } else {
                $payStatus = $status === 'confirmed' ? 'paid' : null;
                $upd = ['status' => $status];
                if ($payStatus) $upd['payment_status'] = $payStatus;
                $this->tickets->updateBooking($id, $upd);
            }
        }
        return Response::redirect($r->basePath() . '/manage/tickets/bookings/' . $id . '?msg=Status+updated.');
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function categoriesList(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $categories = $this->tickets->allCategories();
        $saved      = $r->query('saved') === '1';
        $deleted    = $r->query('deleted') === '1';
        return $this->renderPage('categories', compact('categories','saved','deleted') + ['base' => $r->basePath()]);
    }

    public function categoryNew(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('category_form', ['base' => $r->basePath(), 'category' => null]);
    }

    public function categoryCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $data = $this->extractCategoryData($r);
        $data['slug'] = $this->uniqueCategorySlug($this->tickets->slugify($data['label']));
        if ($data['slug']) $this->tickets->createCategory($data);
        return Response::redirect($r->basePath() . '/manage/tickets/categories?saved=1');
    }

    public function categoryEdit(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id       = (int) $r->getAttribute('id');
        $category = $this->tickets->getCategory($id);
        if (!$category) return Response::redirect($r->basePath() . '/manage/tickets/categories');
        $saved = $r->query('saved') === '1';
        return $this->renderPage('category_form', compact('category','saved') + ['base' => $r->basePath()]);
    }

    public function categoryUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id   = (int) $r->getAttribute('id');
        $data = $this->extractCategoryData($r);
        unset($data['slug']); // slug immutable after create
        $this->tickets->updateCategory($id, $data);
        return Response::redirect($r->basePath() . '/manage/tickets/categories/' . $id . '/edit?saved=1');
    }

    public function categoryDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->tickets->deleteCategory((int) $r->getAttribute('id'));
        return Response::redirect($r->basePath() . '/manage/tickets/categories?deleted=1');
    }

    // ── Organizers ────────────────────────────────────────────────────────────

    public function organizersList(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $organizers = $this->tickets->allOrganizers();
        $saved      = $r->query('saved') === '1';
        $deleted    = $r->query('deleted') === '1';
        return $this->renderPage('organizers', compact('organizers','saved','deleted') + ['base' => $r->basePath(), 'tickets' => $this->tickets]);
    }

    public function organizerNew(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('organizer_form', ['base' => $r->basePath(), 'organizer' => null]);
    }

    public function organizerCreate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $data = $this->extractOrganizerData($r);
        $data['slug'] = $this->uniqueOrganizerSlug($this->tickets->slugify($data['name']));
        $this->tickets->createOrganizer($data);
        return Response::redirect($r->basePath() . '/manage/tickets/organizers?saved=1');
    }

    public function organizerEdit(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id        = (int) $r->getAttribute('id');
        $organizer = $this->tickets->getOrganizer($id);
        if (!$organizer) return Response::redirect($r->basePath() . '/manage/tickets/organizers');
        $saved = $r->query('saved') === '1';
        return $this->renderPage('organizer_form', compact('organizer','saved') + ['base' => $r->basePath()]);
    }

    public function organizerUpdate(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id   = (int) $r->getAttribute('id');
        $data = $this->extractOrganizerData($r);
        unset($data['slug']);
        $this->tickets->updateOrganizer($id, $data);
        return Response::redirect($r->basePath() . '/manage/tickets/organizers/' . $id . '/edit?saved=1');
    }

    public function organizerDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->tickets->deleteOrganizer((int) $r->getAttribute('id'));
        return Response::redirect($r->basePath() . '/manage/tickets/organizers?deleted=1');
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    public function usersList(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $page   = max(1, (int) ($r->query('page', '1')));
        $limit  = 25;
        $search = trim((string) $r->query('q', ''));
        $qb = $this->qb->table('gt_users');
        if ($search !== '') {
            $qb = $qb->where('email', 'LIKE', '%' . $search . '%');
        }
        $total = (int) ($qb->count() ?? 0);
        $users = $qb->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get() ?: [];
        $pages = max(1, (int) ceil($total / $limit));
        // Attach booking counts
        foreach ($users as &$u) {
            $u['booking_count'] = (int) ($this->qb->table('gt_bookings')
                ->where('customer_email', '=', (string) $u['email'])
                ->count() ?? 0);
        }
        unset($u);
        return $this->renderPage('users', compact('users', 'total', 'pages', 'page', 'search') + [
            'base'    => $r->basePath(),
            'tickets' => $this->tickets,
        ]);
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public function settingsForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('settings', [
            'base'    => $r->basePath(),
            'tickets' => $this->tickets,
            'saved'   => $r->query('saved') === '1',
        ]);
    }

    public function settingsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        foreach (['currency', 'currency_symbol', 'events_page_slug', 'from_email'] as $k) {
            $this->tickets->setSetting($k, trim((string) $r->post($k, '')));
        }
        return Response::redirect($r->basePath() . '/manage/tickets/settings?saved=1');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function guard(Request $r): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($r->basePath() . '/login');
        }
        return null;
    }

    private function extractEventData(Request $r): array
    {
        return [
            'title'             => trim((string) $r->post('title', '')),
            'slug'              => trim((string) $r->post('slug', '')),
            'short_description' => trim((string) $r->post('short_description', '')),
            'description'       => (string) $r->post('description', ''),
            'location'          => trim((string) $r->post('location', '')),
            'venue'             => trim((string) $r->post('venue', '')),
            'organizer_id'      => ($r->post('organizer_id') !== '' && $r->post('organizer_id') !== null) ? (int) $r->post('organizer_id') : null,
            'category'          => trim((string) $r->post('category', 'other')),
            'event_date'        => (string) $r->post('event_date', date('Y-m-d H:i:s')),
            'event_end_date'    => $r->post('event_end_date') ?: null,
            'image'             => trim((string) $r->post('image', '')),
            'status'            => in_array($r->post('status'), ['draft','published','cancelled']) ? $r->post('status') : 'draft',
            'featured'          => $r->post('featured') === '1' ? 1 : 0,
            'sort_order'        => (int) $r->post('sort_order', '0'),
        ];
    }

    private function extractCategoryData(Request $r): array
    {
        return [
            'slug'       => trim((string) $r->post('slug', '')),
            'label'      => trim((string) $r->post('label', '')),
            'icon'       => trim((string) $r->post('icon', '🎟')),
            'accent'     => trim((string) $r->post('accent', '#a78bfa')),
            'grad_from'  => trim((string) $r->post('grad_from', '#0a0812')),
            'grad_to'    => trim((string) $r->post('grad_to', '#4c1d95')),
            'sort_order' => (int) $r->post('sort_order', '0'),
        ];
    }

    private function extractOrganizerData(Request $r): array
    {
        return [
            'name'        => trim((string) $r->post('name', '')),
            'slug'        => trim((string) $r->post('slug', '')),
            'description' => trim((string) $r->post('description', '')),
            'cover'       => trim((string) $r->post('cover', '')),
            'logo'        => trim((string) $r->post('logo', '')),
            'website'     => trim((string) $r->post('website', '')),
            'sort_order'  => (int) $r->post('sort_order', '0'),
        ];
    }

    private function uniqueCategorySlug(string $slug): string
    {
        $base = $slug; $c = 1;
        while ($this->qb->table('gt_categories')->where('slug','=',$slug)->first()) {
            $slug = $base . '-' . $c++;
        }
        return $slug;
    }

    private function uniqueOrganizerSlug(string $slug): string
    {
        $base = $slug; $c = 1;
        while ($this->qb->table('gt_organizers')->where('slug','=',$slug)->first()) {
            $slug = $base . '-' . $c++;
        }
        return $slug;
    }

    private function uniqueSlug(string $slug, int $excludeId = 0): string
    {
        $base    = $slug;
        $counter = 1;
        while (true) {
            $qb = $this->qb->table('gt_events')->where('slug', '=', $slug);
            if ($excludeId) $qb = $qb->where('id', '!=', $excludeId);
            if (!$qb->first()) break;
            $slug = $base . '-' . $counter++;
        }
        return $slug;
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
