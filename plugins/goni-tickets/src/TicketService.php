<?php
declare(strict_types=1);

namespace GoniTickets;

use GoniCore\Core\Database\QueryBuilder;

final class TicketService
{
    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Settings ──────────────────────────────────────────────────────────────

    public function setting(string $key, string $default = ''): string
    {
        $row = $this->qb->table('gt_settings')->where('key', '=', $key)->first();
        return $row ? (string) $row['value'] : $default;
    }

    public function setSetting(string $key, string $value): void
    {
        $exists = $this->qb->table('gt_settings')->where('key', '=', $key)->first();
        if ($exists) {
            $this->qb->table('gt_settings')->where('key', '=', $key)->update(['value' => $value]);
        } else {
            $this->qb->table('gt_settings')->insert(['key' => $key, 'value' => $value]);
        }
    }

    // ── Events ────────────────────────────────────────────────────────────────

    public function allEvents(int $page = 1, int $perPage = 20, bool $publishedOnly = false, string $search = ''): array
    {
        $qb = $this->qb->table('gt_events');
        if ($publishedOnly) $qb = $qb->where('status', '=', 'published');
        if ($search !== '') $qb = $qb->where('title', 'LIKE', '%' . $search . '%');
        $total  = (int) ($qb->count() ?? 0);
        $events = $qb->orderBy('event_date', 'ASC')->limit($perPage)->offset(($page - 1) * $perPage)->get() ?: [];
        return ['events' => $events, 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function upcomingEvents(int $limit = 12): array
    {
        return $this->qb->table('gt_events')
            ->where('status', '=', 'published')
            ->where('event_date', '>=', date('Y-m-d H:i:s'))
            ->orderBy('event_date', 'ASC')
            ->limit($limit)
            ->get() ?: [];
    }

    public function searchTransport(string $from, string $to, string $date, int $limit = 40, string $transport = 'all'): array
    {
        $qb = $this->qb->table('gt_events')->where('status', '=', 'published');

        if ($transport === 'train') {
            $qb = $qb->where('category', '=', 'train');
        } elseif ($transport === 'bus') {
            $qb = $qb->where('category', '=', 'bus');
        } elseif ($transport === 'other') {
            // anything that is not bus or train
            $qb = $qb->where('category', '!=', 'bus')->where('category', '!=', 'train');
        } else {
            $qb = $qb->where('category', 'IN', ['bus', 'train']);
        }

        if ($date !== '') {
            $qb = $qb->where('event_date', '>=', $date . ' 00:00:00');
        } else {
            $qb = $qb->where('event_date', '>=', date('Y-m-d H:i:s'));
        }

        $results = $qb->orderBy('event_date', 'ASC')->limit($limit)->get() ?: [];

        // PHP-side filter for from/to (venue = departure, location = destination)
        if ($from !== '') {
            $f = mb_strtolower($from);
            $results = array_filter($results, static fn($e) =>
                str_contains(mb_strtolower((string)($e['venue'] ?? '')), $f) ||
                str_contains(mb_strtolower((string)($e['location'] ?? '')), $f)
            );
        }
        if ($to !== '') {
            $t = mb_strtolower($to);
            $results = array_filter($results, static fn($e) =>
                str_contains(mb_strtolower((string)($e['location'] ?? '')), $t) ||
                str_contains(mb_strtolower((string)($e['venue'] ?? '')), $t)
            );
        }

        return array_values($results);
    }

    public function featuredEvents(int $limit = 8): array
    {
        return $this->qb->table('gt_events')
            ->where('status', '=', 'published')
            ->where('featured', '=', 1)
            ->where('event_date', '>=', date('Y-m-d H:i:s'))
            ->orderBy('event_date', 'ASC')
            ->limit($limit)
            ->get() ?: [];
    }

    public function getEvent(int $id): ?array
    {
        return $this->qb->table('gt_events')->where('id', '=', $id)->first();
    }

    public function getEventBySlug(string $slug): ?array
    {
        return $this->qb->table('gt_events')->where('slug', '=', $slug)->first();
    }

    public function createEvent(array $data): int
    {
        return (int) $this->qb->table('gt_events')->insert($data);
    }

    public function updateEvent(int $id, array $data): void
    {
        $this->qb->table('gt_events')->where('id', '=', $id)->update($data);
    }

    public function deleteEvent(int $id): void
    {
        $this->qb->table('gt_events')->where('id', '=', $id)->delete();
    }

    // ── Ticket Types ──────────────────────────────────────────────────────────

    public function ticketTypesForEvent(int $eventId): array
    {
        return $this->qb->table('gt_ticket_types')
            ->where('event_id', '=', $eventId)
            ->orderBy('sort_order', 'ASC')
            ->get() ?: [];
    }

    public function activeTicketTypesForEvent(int $eventId): array
    {
        return $this->qb->table('gt_ticket_types')
            ->where('event_id', '=', $eventId)
            ->where('status', '=', 'active')
            ->orderBy('sort_order', 'ASC')
            ->get() ?: [];
    }

    public function getTicketType(int $id): ?array
    {
        return $this->qb->table('gt_ticket_types')->where('id', '=', $id)->first();
    }

    public function createTicketType(array $data): int
    {
        return (int) $this->qb->table('gt_ticket_types')->insert($data);
    }

    public function updateTicketType(int $id, array $data): void
    {
        $this->qb->table('gt_ticket_types')->where('id', '=', $id)->update($data);
    }

    public function deleteTicketType(int $id): void
    {
        $this->qb->table('gt_ticket_types')->where('id', '=', $id)->delete();
    }

    public function availableCount(int $ticketTypeId): ?int
    {
        $tt = $this->getTicketType($ticketTypeId);
        if (!$tt) return 0;
        if ($tt['quantity'] === null) return null; // unlimited
        return max(0, (int) $tt['quantity'] - (int) $tt['sold']);
    }

    // ── Bookings ──────────────────────────────────────────────────────────────

    public function allBookings(int $page = 1, int $perPage = 25, int $eventId = 0): array
    {
        $qb = $this->qb->table('gt_bookings');
        if ($eventId) $qb = $qb->where('event_id', '=', $eventId);
        $total = (int) ($qb->count() ?? 0);
        $items = $qb->orderBy('created_at', 'DESC')->limit($perPage)->offset(($page - 1) * $perPage)->get() ?: [];

        // Attach ticket counts
        foreach ($items as &$b) {
            $tix = $this->qb->table('gt_booking_tickets')
                ->where('booking_id', '=', (int) $b['id'])
                ->get() ?: [];
            $b['ticket_count'] = array_sum(array_column($tix, 'quantity'));
        }
        unset($b);

        return ['items' => $items, 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function getBooking(int $id): ?array
    {
        $b = $this->qb->table('gt_bookings')->where('id', '=', $id)->first();
        if (!$b) return null;
        $b['tickets'] = $this->qb->table('gt_booking_tickets')->where('booking_id', '=', $id)->get() ?: [];
        $b['event']   = $this->getEvent((int) $b['event_id']);
        return $b;
    }

    public function bookingsByEmail(string $email): array
    {
        $rows = $this->qb->table('gt_bookings')
            ->where('customer_email', '=', mb_strtolower(trim($email)))
            ->orderBy('created_at', 'DESC')
            ->get() ?: [];
        foreach ($rows as &$b) {
            $b['event'] = $this->getEvent((int) $b['event_id']);
        }
        unset($b);
        return $rows;
    }

    public function getBookingByNumber(string $number): ?array
    {
        $b = $this->qb->table('gt_bookings')->where('booking_number', '=', $number)->first();
        if (!$b) return null;
        $b['tickets'] = $this->qb->table('gt_booking_tickets')->where('booking_id', '=', (int) $b['id'])->get() ?: [];
        $b['event']   = $this->getEvent((int) $b['event_id']);
        return $b;
    }

    public function getBookingByTransactionId(string $txId): ?array
    {
        return $this->qb->table('gt_bookings')->where('transaction_id', '=', $txId)->first();
    }

    /**
     * @param array{ticket_type_id:int, quantity:int}[] $selections
     */
    public function createBooking(array $booking, array $selections): int
    {
        $total = 0.0;
        $lines = [];

        foreach ($selections as $sel) {
            $qty = max(0, (int) $sel['quantity']);
            if ($qty === 0) continue;
            $tt = $this->getTicketType((int) $sel['ticket_type_id']);
            if (!$tt || $tt['status'] !== 'active') continue;

            // availability check
            if ($tt['quantity'] !== null) {
                $available = max(0, (int) $tt['quantity'] - (int) $tt['sold']);
                if ($qty > $available) $qty = $available;
            }
            if ($qty === 0) continue;

            $lineTotal = round((float) $tt['price'] * $qty, 2);
            $total    += $lineTotal;
            $lines[]   = [
                'ticket_type_id'   => (int) $tt['id'],
                'ticket_type_name' => (string) $tt['name'],
                'quantity'         => $qty,
                'unit_price'       => (float) $tt['price'],
                'total'            => $lineTotal,
            ];
        }

        if (empty($lines)) return 0;

        $booking['total']          = round($total, 2);
        $booking['booking_number'] = $this->generateBookingNumber();
        $bookingId = (int) $this->qb->table('gt_bookings')->insert($booking);

        foreach ($lines as $line) {
            $line['booking_id'] = $bookingId;
            $this->qb->table('gt_booking_tickets')->insert($line);
            // Reserve tickets
            $this->qb->table('gt_ticket_types')
                ->where('id', '=', $line['ticket_type_id'])
                ->update(['sold' => $this->qb->table('gt_ticket_types')
                    ->where('id', '=', $line['ticket_type_id'])
                    ->first()['sold'] + $line['quantity']]);
        }

        return $bookingId;
    }

    public function updateBooking(int $id, array $data): void
    {
        $this->qb->table('gt_bookings')->where('id', '=', $id)->update($data);
    }

    public function cancelBooking(int $id): void
    {
        $b = $this->getBooking($id);
        if (!$b) return;

        // Release reserved tickets
        foreach ($b['tickets'] as $t) {
            $tt = $this->getTicketType((int) $t['ticket_type_id']);
            if ($tt) {
                $newSold = max(0, (int) $tt['sold'] - (int) $t['quantity']);
                $this->qb->table('gt_ticket_types')->where('id', '=', (int) $tt['id'])->update(['sold' => $newSold]);
            }
        }

        $this->qb->table('gt_bookings')->where('id', '=', $id)->update([
            'status'         => 'cancelled',
            'payment_status' => 'unpaid',
        ]);
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function statsForEvent(int $eventId): array
    {
        $types    = $this->ticketTypesForEvent($eventId);
        $capacity = null;
        $sold     = 0;
        $revenue  = 0.0;

        foreach ($types as $tt) {
            if ($tt['quantity'] !== null) {
                $capacity = ($capacity ?? 0) + (int) $tt['quantity'];
            }
            $sold    += (int) $tt['sold'];
            $revenue += (float) $tt['price'] * (int) $tt['sold'];
        }

        $bookings = (int) ($this->qb->table('gt_bookings')
            ->where('event_id', '=', $eventId)
            ->where('status', '!=', 'cancelled')
            ->count() ?? 0);

        return compact('capacity', 'sold', 'revenue', 'bookings');
    }

    public function globalStats(): array
    {
        $events   = (int) ($this->qb->table('gt_events')->count() ?? 0);
        $bookings = (int) ($this->qb->table('gt_bookings')->where('status', '!=', 'cancelled')->count() ?? 0);
        $rows     = $this->qb->table('gt_bookings')->where('status', '=', 'confirmed')->get() ?: [];
        $revenue  = (float) array_sum(array_column($rows, 'total'));
        return compact('events', 'bookings', 'revenue');
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function allCategories(): array
    {
        return $this->qb->table('gt_categories')->orderBy('sort_order','ASC')->get() ?: [];
    }

    public function getCategory(int $id): ?array
    {
        return $this->qb->table('gt_categories')->where('id','=',$id)->first();
    }

    public function getCategoryBySlug(string $slug): ?array
    {
        return $this->qb->table('gt_categories')->where('slug','=',$slug)->first();
    }

    public function createCategory(array $data): int
    {
        return (int) $this->qb->table('gt_categories')->insert($data);
    }

    public function updateCategory(int $id, array $data): void
    {
        $this->qb->table('gt_categories')->where('id','=',$id)->update($data);
    }

    public function deleteCategory(int $id): void
    {
        $this->qb->table('gt_categories')->where('id','=',$id)->delete();
    }

    /** Returns [slug => [...fields]] map for use in views */
    public function categoryMap(): array
    {
        $map = [];
        foreach ($this->allCategories() as $c) {
            $map[$c['slug']] = $c;
        }
        return $map;
    }

    public function hexToRgba(string $hex, float $alpha = .07): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        [$r,$g,$b] = [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
        return "rgba($r,$g,$b,$alpha)";
    }

    // ── Organizers ────────────────────────────────────────────────────────────

    public function allOrganizers(): array
    {
        return $this->qb->table('gt_organizers')->orderBy('sort_order','ASC')->orderBy('name','ASC')->get() ?: [];
    }

    public function getOrganizer(int $id): ?array
    {
        return $this->qb->table('gt_organizers')->where('id','=',$id)->first();
    }

    public function getOrganizerBySlug(string $slug): ?array
    {
        return $this->qb->table('gt_organizers')->where('slug','=',$slug)->first();
    }

    public function createOrganizer(array $data): int
    {
        return (int) $this->qb->table('gt_organizers')->insert($data);
    }

    public function updateOrganizer(int $id, array $data): void
    {
        $this->qb->table('gt_organizers')->where('id','=',$id)->update($data);
    }

    public function deleteOrganizer(int $id): void
    {
        $this->qb->table('gt_organizers')->where('id','=',$id)->delete();
    }

    public function eventsForOrganizer(int $organizerId): array
    {
        return $this->qb->table('gt_events')
            ->where('organizer_id','=',$organizerId)
            ->where('status','=','published')
            ->orderBy('event_date','ASC')
            ->get() ?: [];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function generateBookingNumber(): string
    {
        $year = date('Y');
        $last = $this->qb->table('gt_bookings')->orderBy('id', 'DESC')->first();
        $next = $last ? (int) $last['id'] + 1 : 1;
        return 'GT-' . $year . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function formatPrice(float $price): string
    {
        $symbol   = $this->setting('currency_symbol', '₾');
        $decimals = 2;
        return number_format($price, $decimals, '.', ',') . ' ' . $symbol;
    }

    public function minPriceForEvent(int $eventId): ?float
    {
        $types = $this->activeTicketTypesForEvent($eventId);
        if (empty($types)) return null;
        return (float) min(array_map('floatval', array_column($types, 'price')));
    }

    public function formatDate(string $datetime): string
    {
        $ts = strtotime($datetime);
        return $ts ? date('d M Y, H:i', $ts) : $datetime;
    }

    public function isSoldOut(int $eventId): bool
    {
        $types = $this->activeTicketTypesForEvent($eventId);
        if (empty($types)) return true;
        foreach ($types as $tt) {
            if ($tt['quantity'] === null) return false; // unlimited type exists
            if ((int) $tt['quantity'] - (int) $tt['sold'] > 0) return false;
        }
        return true;
    }

    public function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\w\s-]/u', '', $text) ?? $text;
        $text = preg_replace('/[\s_-]+/', '-', $text) ?? $text;
        return trim($text, '-');
    }
}
