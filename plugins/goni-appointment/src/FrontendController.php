<?php
declare(strict_types=1);

namespace GoniAppointment;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

final class FrontendController
{
    private string $viewsDir;

    public function __construct(private readonly AppointmentService $svc)
    {
        $this->viewsDir = dirname(__DIR__) . '/views/frontend';
    }

    // ── Main booking page ─────────────────────────────────────────────────────

    public function booking(Request $r): Response
    {
        $services  = $this->svc->allServices(true);
        $bookSlug  = $this->svc->setting('page_slug', 'book');
        $error     = $r->query('error', '');
        $brandName = $this->svc->setting('brand_name', 'Book Appointment');
        return $this->view($r, 'booking', compact('services', 'bookSlug', 'error', 'brandName'), $brandName);
    }

    // ── Process booking ───────────────────────────────────────────────────────

    public function bookingProcess(Request $r): Response
    {
        $base     = $r->basePath();
        $bookSlug = $this->svc->setting('page_slug', 'book');
        $bookUrl  = $base . '/' . $bookSlug;

        $serviceId = (int) $r->post('service_id', '0');
        $staffId   = (int) $r->post('staff_id', '0');
        $date      = trim((string) $r->post('appointment_date', ''));
        $time      = trim((string) $r->post('start_time', ''));
        $name      = trim((string) $r->post('customer_name', ''));
        $email     = trim((string) $r->post('customer_email', ''));
        $phone     = trim((string) $r->post('customer_phone', ''));
        $note      = trim((string) $r->post('customer_note', ''));

        if (!$serviceId || !$staffId || !$date || !$time) {
            return Response::redirect($bookUrl . '?error=' . urlencode('Please complete all required steps.'));
        }

        $service = $this->svc->getService($serviceId);
        if (!$service) {
            return Response::redirect($bookUrl . '?error=' . urlencode('Selected service not found.'));
        }

        if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::redirect($bookUrl . '?error=' . urlencode('Please enter a valid name and email.'));
        }

        // Verify slot is still available
        $available = $this->svc->availableSlots($staffId, $serviceId, $date);
        if (!in_array($time, $available, true)) {
            return Response::redirect($bookUrl . '?error=' . urlencode('That time slot is no longer available. Please choose another.'));
        }

        $duration = (int) $service['duration_minutes'];
        $endTime  = date('H:i', strtotime($date . ' ' . $time) + $duration * 60);

        $id = $this->svc->createAppointment([
            'service_id'       => $serviceId,
            'staff_id'         => $staffId,
            'customer_name'    => $name,
            'customer_email'   => $email,
            'customer_phone'   => $phone,
            'appointment_date' => $date,
            'start_time'       => $time,
            'end_time'         => $endTime,
            'price'            => (float) $service['price'],
            'currency'         => $this->svc->setting('currency', 'GEL'),
            'status'           => 'pending',
            'payment_method'   => 'on_site',
            'payment_status'   => 'unpaid',
            'customer_note'    => $note,
            'ip_address'       => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $appt = $this->svc->getAppointment($id);
        return Response::redirect($base . '/appointment/confirmation/' . urlencode((string) $appt['appointment_number']));
    }

    // ── Confirmation page ─────────────────────────────────────────────────────

    public function confirmation(Request $r): Response
    {
        $number   = (string) $r->getAttribute('number');
        $appt     = $this->svc->getAppointmentByNumber($number);
        $bookSlug = $this->svc->setting('page_slug', 'book');

        if (!$appt) {
            return Response::redirect($r->basePath() . '/' . $bookSlug);
        }

        return $this->view($r, 'confirmation', compact('appt', 'bookSlug'), 'Booking Confirmed');
    }

    // ── AJAX: staff for service ───────────────────────────────────────────────

    public function apiStaff(Request $r): Response
    {
        $serviceId = (int) $r->getAttribute('service_id');
        $staff     = $this->svc->staffForService($serviceId);
        $out = array_map(static fn($s) => [
            'id'    => (int) $s['id'],
            'name'  => $s['name'],
            'title' => $s['title'],
            'image' => $s['image'],
        ], $staff);
        return Response::json($out);
    }

    // ── AJAX: available time slots ────────────────────────────────────────────

    public function apiSlots(Request $r): Response
    {
        $serviceId = (int) $r->getAttribute('service_id');
        $staffId   = (int) $r->getAttribute('staff_id');
        $date      = (string) $r->getAttribute('date');
        $slots     = $this->svc->availableSlots($staffId, $serviceId, $date);
        return Response::json($slots);
    }

    // ── Theme-aware view renderer ─────────────────────────────────────────────

    private function view(Request $r, string $tpl, array $data = [], string $pageTitle = ''): Response
    {
        $file = $this->viewsDir . '/' . $tpl . '.php';
        if (!is_file($file)) return Response::error("Appointment view not found: $tpl", 500);

        $themeViews = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeViews . '/helpers.php';

        $base = $r->basePath();
        $svc  = $this->svc;

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
