<?php
declare(strict_types=1);

namespace GoniTickets;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniTickets\GtUserService;

final class FrontendController
{
    private string $viewsDir;

    public function __construct(
        private readonly TicketService  $tickets,
        private readonly GtUserService  $users,
    ) {
        $this->viewsDir = dirname(__DIR__) . '/views/frontend';
    }

    // ── Events listing ────────────────────────────────────────────────────────

    public function events(Request $r): Response
    {
        $events   = $this->tickets->upcomingEvents(24);
        $featured = $this->tickets->featuredEvents(8);
        $catMap   = $this->tickets->categoryMap();
        $allCats  = $this->tickets->allCategories();
        $orgMap   = [];
        foreach ($this->tickets->allOrganizers() as $o) {
            $orgMap[(int) $o['id']] = $o;
        }
        return $this->view($r, 'events', compact('events', 'featured', 'catMap', 'allCats', 'orgMap'), 'Events');
    }

    // ── Organizers list ───────────────────────────────────────────────────────

    public function organizersList(Request $r): Response
    {
        $organizers = $this->tickets->allOrganizers();
        // enrich with event count
        foreach ($organizers as &$org) {
            $org['event_count'] = count($this->tickets->eventsForOrganizer((int) $org['id']));
        }
        unset($org);
        return $this->view($r, 'organizers', compact('organizers'), 'Organizers');
    }

    // ── Organizer page ────────────────────────────────────────────────────────

    public function organizerPage(Request $r): Response
    {
        $slug      = (string) $r->getAttribute('slug');
        $organizer = $this->tickets->getOrganizerBySlug($slug);

        if (!$organizer) {
            return Response::redirect($r->basePath() . '/' . $this->tickets->setting('events_page_slug', 'events'));
        }

        $orgEvents = $this->tickets->eventsForOrganizer((int) $organizer['id']);
        $catMap    = $this->tickets->categoryMap();

        return $this->view($r, 'organizer', compact('organizer', 'orgEvents', 'catMap'), $organizer['name']);
    }

    // ── Event detail + booking form ───────────────────────────────────────────

    public function event(Request $r): Response
    {
        $slug  = (string) $r->getAttribute('slug');
        $event = $this->tickets->getEventBySlug($slug);

        if (!$event || $event['status'] !== 'published') {
            return Response::redirect($r->basePath() . '/' . $this->tickets->setting('events_page_slug', 'events'));
        }

        $ticketTypes = $this->tickets->activeTicketTypesForEvent((int) $event['id']);
        $soldOut     = $this->tickets->isSoldOut((int) $event['id']);
        $error       = $r->query('error', '');
        $currentUser = $this->users->currentUser();

        return $this->view($r, 'event', compact('event', 'ticketTypes', 'soldOut', 'error', 'currentUser'), $event['title']);
    }

    // ── Process booking ───────────────────────────────────────────────────────

    public function book(Request $r): Response
    {
        $slug  = (string) $r->getAttribute('slug');
        $event = $this->tickets->getEventBySlug($slug);
        $base  = $r->basePath();
        $eventUrl = $base . '/' . $this->tickets->setting('events_page_slug', 'events') . '/' . $slug;

        if (!$event || $event['status'] !== 'published') {
            return Response::redirect($base . '/' . $this->tickets->setting('events_page_slug', 'events'));
        }

        // Validate contact
        $name    = trim((string) $r->post('name', ''));
        $email   = trim((string) $r->post('email', ''));
        $phone   = trim((string) $r->post('phone', ''));
        $note    = trim((string) $r->post('note', ''));
        $payment = (string) $r->post('payment_method', 'cash');

        if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::redirect($eventUrl . '?error=' . urlencode('Please fill in name and a valid email.'));
        }

        // Parse ticket selections
        $rawQtys = (array) ($r->post('qty') ?? []);
        $selections = [];
        foreach ($rawQtys as $ttId => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                $selections[] = ['ticket_type_id' => (int) $ttId, 'quantity' => $qty];
            }
        }

        if (empty($selections)) {
            return Response::redirect($eventUrl . '?error=' . urlencode('Please select at least one ticket.'));
        }

        $currency = $this->tickets->setting('currency', 'GEL');

        $bookingId = $this->tickets->createBooking([
            'event_id'       => (int) $event['id'],
            'customer_name'  => $name,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'customer_note'  => $note,
            'currency'       => $currency,
            'status'         => 'pending',
            'payment_method' => $payment,
            'payment_status' => 'unpaid',
            'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? '',
        ], $selections);

        if (!$bookingId) {
            return Response::redirect($eventUrl . '?error=' . urlencode('Selected tickets are no longer available.'));
        }

        $booking = $this->tickets->getBooking($bookingId);

        // ── BOG payment ───────────────────────────────────────────────────────
        if ($payment === 'bog') {
            try {
                $bog = gc_container()->get(\BogPayment\BogService::class);
                $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host    = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $absBase = rtrim($scheme . '://' . $host . $base, '/');

                $basket = [];
                foreach ($booking['tickets'] as $t) {
                    $basket[] = [
                        'product_id'  => 'ticket-' . $t['ticket_type_id'],
                        'description' => $event['title'] . ' — ' . $t['ticket_type_name'],
                        'quantity'    => (int) $t['quantity'],
                        'unit_price'  => round((float) $t['unit_price'], 2),
                        'total_price' => round((float) $t['total'], 2),
                    ];
                }

                $result = $bog->createOrder(
                    externalOrderId: 'gt-' . $bookingId,
                    total:           (float) $booking['total'],
                    currency:        $currency,
                    basket:          $basket,
                    callbackUrl:     $absBase . '/tickets/bog-callback',
                    successUrl:      $absBase . '/tickets/confirmation/' . urlencode($booking['booking_number']) . '?payment=bog',
                    failUrl:         $absBase . '/' . $this->tickets->setting('events_page_slug', 'events') . '/' . $slug . '?error=' . urlencode('Payment was not completed.'),
                );

                if ($result) {
                    $this->tickets->updateBooking($bookingId, ['transaction_id' => $result['bog_order_id']]);
                    return Response::redirect($result['redirect_url']);
                }
            } catch (\Throwable $e) {
                error_log('[GoniTickets] BOG payment failed: ' . $e->getMessage());
            }
            // BOG failed — cancel booking and return error
            $this->tickets->cancelBooking($bookingId);
            return Response::redirect($eventUrl . '?error=' . urlencode('Could not connect to payment gateway. Please try again.'));
        }

        // Cash — confirm immediately
        $this->tickets->updateBooking($bookingId, ['status' => 'confirmed']);
        return Response::redirect($base . '/tickets/confirmation/' . urlencode($booking['booking_number']));
    }

    // ── Plugin auth ───────────────────────────────────────────────────────────

    public function userLogin(Request $r): Response
    {
        if ($this->users->isLoggedIn()) {
            return Response::redirect($r->basePath() . '/tickets/account');
        }
        $error    = $r->query('error', '');
        $redirect = $r->query('redirect', '');
        return $this->view($r, 'user_login', compact('error', 'redirect'), 'შესვლა');
    }

    public function userLoginPost(Request $r): Response
    {
        $base     = $r->basePath();
        $email    = (string) $r->post('email', '');
        $password = (string) $r->post('password', '');
        $redirect = trim((string) $r->post('redirect', ''));

        $result = $this->users->login($email, $password);
        if (is_string($result)) {
            return Response::redirect($base . '/tickets/login?error=' . urlencode($result));
        }

        $dest = ($redirect && str_starts_with($redirect, '/')) ? $redirect : $base . '/tickets/account';
        return Response::redirect($dest);
    }

    public function userRegister(Request $r): Response
    {
        if ($this->users->isLoggedIn()) {
            return Response::redirect($r->basePath() . '/tickets/account');
        }
        $error = $r->query('error', '');
        return $this->view($r, 'user_register', compact('error'), 'რეგისტრაცია');
    }

    public function userRegisterPost(Request $r): Response
    {
        $base     = $r->basePath();
        $email    = (string) $r->post('email', '');
        $name     = (string) $r->post('name', '');
        $phone    = trim((string) $r->post('phone', ''));
        $password = (string) $r->post('password', '');
        $confirm  = (string) $r->post('confirm_password', '');

        if ($password !== $confirm) {
            return Response::redirect($base . '/tickets/register?error=' . urlencode('პაროლი არ ემთხვევა.'));
        }

        $result = $this->users->register($email, $name, $password, $phone);
        if (is_string($result)) {
            return Response::redirect($base . '/tickets/register?error=' . urlencode($result));
        }

        return Response::redirect($base . '/tickets/account');
    }

    public function userLogout(Request $r): Response
    {
        $this->users->logout();
        $eventsSlug = $this->tickets->setting('events_page_slug', 'events');
        return Response::redirect($r->basePath() . '/' . $eventsSlug);
    }

    public function userAccount(Request $r): Response
    {
        if (!$this->users->isLoggedIn()) {
            return Response::redirect($r->basePath() . '/tickets/login?redirect=' . urlencode($r->path()));
        }

        $user     = $this->users->currentUser();
        $bookings = $this->tickets->bookingsByEmail((string) ($user['email'] ?? ''));

        return $this->view($r, 'user_account', compact('user', 'bookings'), 'ჩემი ანგარიში');
    }

    // ── Ticket lookup ─────────────────────────────────────────────────────────

    public function ticketLookup(Request $r): Response
    {
        $from    = trim((string) $r->query('from', ''));
        $to      = trim((string) $r->query('to', ''));
        $date    = trim((string) $r->query('date', ''));
        $results = null;

        $transport = trim((string) $r->query('transport', 'all'));

        if ($from !== '' || $to !== '' || $date !== '') {
            $results = $this->tickets->searchTransport($from, $to, $date, 40, $transport);
        }

        $orgMap = [];
        foreach ($this->tickets->allOrganizers() as $o) {
            $orgMap[(int) $o['id']] = $o;
        }

        return $this->view($r, 'ticket_lookup', compact('from', 'to', 'date', 'transport', 'results', 'orgMap'), 'ბილეთების ძებნა');
    }

    public function ticketLookupPost(Request $r): Response
    {
        $from = trim((string) $r->post('from', ''));
        $to   = trim((string) $r->post('to', ''));
        $date = trim((string) $r->post('date', ''));
        $base = $r->basePath();

        $qs = http_build_query(array_filter(['from' => $from, 'to' => $to, 'date' => $date]));
        return Response::redirect($base . '/tickets/my-ticket' . ($qs ? '?' . $qs : ''));
    }

    // ── Ticket view (public shareable) ────────────────────────────────────────

    public function ticketView(Request $r): Response
    {
        $number  = (string) $r->getAttribute('number');
        $booking = $this->tickets->getBookingByNumber($number);

        if (!$booking) {
            return Response::redirect($r->basePath() . '/tickets/my-ticket?error=' . urlencode('ბრონირება ვერ მოიძებნა.'));
        }

        return $this->view($r, 'ticket_view', compact('booking'), 'ბილეთი · ' . $booking['booking_number']);
    }

    // ── Confirmation page ─────────────────────────────────────────────────────

    public function confirmation(Request $r): Response
    {
        $number  = (string) $r->getAttribute('number');
        $booking = $this->tickets->getBookingByNumber($number);

        if (!$booking) {
            return Response::redirect($r->basePath() . '/' . $this->tickets->setting('events_page_slug', 'events'));
        }

        // If returning from BOG payment, verify and confirm
        if ($r->query('payment') === 'bog' && $booking['status'] === 'pending') {
            try {
                $bog = gc_container()->get(\BogPayment\BogService::class);
                $receipt = $booking['transaction_id'] ? $bog->getReceipt($booking['transaction_id']) : null;
                if ($receipt && in_array($receipt['order_status']['key'] ?? '', ['completed', 'blocked'], true)) {
                    $this->tickets->updateBooking((int) $booking['id'], [
                        'status'         => 'confirmed',
                        'payment_status' => 'paid',
                    ]);
                    $booking['status']         = 'confirmed';
                    $booking['payment_status'] = 'paid';
                }
            } catch (\Throwable) {}
        }

        return $this->view($r, 'confirmation', compact('booking'), 'Booking Confirmed');
    }

    // ── BOG callback ──────────────────────────────────────────────────────────

    public function bogCallback(Request $r): Response
    {
        $rawBody   = (string) file_get_contents('php://input');
        $sigHeader = (string) ($_SERVER['HTTP_CALLBACK_SIGNATURE'] ?? '');

        try {
            $bog = gc_container()->get(\BogPayment\BogService::class);
            if (!$bog->verifySignature($rawBody, $sigHeader)) {
                return Response::html('', 400);
            }
        } catch (\Throwable) {
            return Response::html('', 400);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload) || ($payload['event'] ?? '') !== 'order_payment') {
            return Response::html('', 200);
        }

        $body        = $payload['body'] ?? [];
        $externalId  = (string) ($body['external_order_id'] ?? '');
        $statusKey   = (string) ($body['order_status']['key'] ?? '');

        if (!str_starts_with($externalId, 'gt-')) return Response::html('', 200);

        $bookingId = (int) substr($externalId, 3);
        $booking   = $this->tickets->getBooking($bookingId);
        if (!$booking) return Response::html('', 200);

        if ($statusKey === 'completed') {
            $this->tickets->updateBooking($bookingId, [
                'status'         => 'confirmed',
                'payment_status' => 'paid',
            ]);
        } elseif (in_array($statusKey, ['rejected', 'expired'], true)) {
            $this->tickets->cancelBooking($bookingId);
        }

        return Response::html('', 200);
    }

    // ── Theme-aware view renderer ─────────────────────────────────────────────

    private function view(Request $r, string $tpl, array $data = [], string $pageTitle = ''): Response
    {
        $file = $this->viewsDir . '/' . $tpl . '.php';
        if (!is_file($file)) return Response::error("Ticket view not found: $tpl", 500);

        $themeViews = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeViews . '/helpers.php';

        $base      = $r->basePath();
        $tickets   = $this->tickets;

        try {
            $c             = gc_container();
            $siteName      = $c->get(\GoniCore\Modules\Settings\SettingsService::class)->siteName() ?: 'GoniCore';
            $langService   = $c->get(\GoniCore\Modules\Language\LanguageService::class);
            $langService->boot($r);
            $menuService   = $c->get(\GoniCore\Modules\Menu\MenuService::class);
            $widgetService = $c->get(\GoniCore\Modules\Widget\WidgetService::class);
            $categories    = $c->get(\GoniCore\Modules\Category\CategoryRepository::class)->findAll();
        } catch (\Throwable) {
            $siteName = 'GoniCore'; $langService = null; $menuService = null;
            $widgetService = null; $categories = [];
        }

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $file;
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        ob_start();
        try {
            include $themeViews . '/layout.php';
            return Response::html((string) ob_get_clean());
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
