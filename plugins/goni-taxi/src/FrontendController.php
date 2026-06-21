<?php
declare(strict_types=1);
namespace GoniTaxi;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

final class FrontendController
{
    private string $viewsDir;
    public function __construct(private readonly TaxiService $taxi)
    {
        $this->viewsDir = dirname(__DIR__).'/views/frontend';
    }

    // ── Session helpers ───────────────────────────────────────────────────────

    private function sessionStart(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('gc_session');
            session_start();
        }
    }

    private function currentCustomer(): ?array
    {
        $this->sessionStart();
        $id = $_SESSION['taxi_customer_id'] ?? null;
        return $id ? $this->taxi->getCustomerById((int)$id) : null;
    }

    private function currentDriverFromSession(): ?array
    {
        $this->sessionStart();
        $id = $_SESSION['taxi_driver_id'] ?? null;
        return $id ? $this->taxi->getDriver((int)$id) : null;
    }

    private function requireCustomer(Request $r): ?Response
    {
        if (!$this->currentCustomer()) {
            $slug = $this->taxi->setting('page_slug','taxi');
            return Response::redirect($r->basePath().'/'.$slug.'/auth');
        }
        return null;
    }

    private function requireDriverSession(Request $r): ?Response
    {
        if (!$this->currentDriverFromSession()) {
            return Response::redirect($r->basePath().'/taxi/driver/login');
        }
        return null;
    }

    // ── Customer Auth ─────────────────────────────────────────────────────────

    public function customerAuthPage(Request $r): Response
    {
        $customer = $this->currentCustomer();
        $slug     = $this->taxi->setting('page_slug','taxi');
        if ($customer) return Response::redirect($r->basePath().'/'.$slug);
        $step  = $r->query('step','phone'); // phone | otp
        $phone = $r->query('phone','');
        $error = $r->query('error','');
        return $this->view($r,'customer_auth',compact('error','step','phone'),'Sign In');
    }

    public function customerLogout(Request $r): Response
    {
        $slug = $this->taxi->setting('page_slug','taxi');
        $this->sessionStart();
        unset($_SESSION['taxi_customer_id']);
        return Response::redirect($r->basePath().'/'.$slug.'/auth');
    }

    // Keep legacy login for backward compat (redirects to OTP)
    public function customerLogin(Request $r): Response
    {
        $slug = $this->taxi->setting('page_slug','taxi');
        return Response::redirect($r->basePath().'/'.$slug.'/auth');
    }

    public function customerRegister(Request $r): Response
    {
        $slug = $this->taxi->setting('page_slug','taxi');
        return Response::redirect($r->basePath().'/'.$slug.'/auth');
    }

    public function apiOtpSend(Request $r): Response
    {
        $phone = trim((string)($r->post('phone') ?? (json_decode((string)file_get_contents('php://input'),true)['phone'] ?? '')));
        if (!$phone) return Response::json(['ok'=>false,'error'=>'Phone required']);

        $code = $this->taxi->generateOtp($phone);

        // Dev mode: return code in response (remove in production, use real SMS)
        $devMode = (bool)($_ENV['APP_DEBUG'] ?? true);
        return Response::json(['ok'=>true,'dev_code'=> $devMode ? $code : null]);
    }

    public function apiOtpVerify(Request $r): Response
    {
        $body  = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $phone = trim((string)($r->post('phone') ?? $body['phone'] ?? ''));
        $code  = trim((string)($r->post('code')  ?? $body['code']  ?? ''));
        $name  = trim((string)($r->post('name')  ?? $body['name']  ?? ''));
        $slug  = $this->taxi->setting('page_slug','taxi');
        $base  = $r->basePath();

        if (!$phone || !$code) return Response::json(['ok'=>false,'error'=>'Phone and code required']);
        if (!$this->taxi->verifyOtp($phone, $code)) {
            return Response::json(['ok'=>false,'error'=>'Invalid or expired code']);
        }

        $customer = $this->taxi->getOrCreateCustomerByPhone($phone, $name);
        $this->sessionStart();
        session_regenerate_id(true);
        $_SESSION['taxi_customer_id'] = (int)$customer['id'];

        return Response::json(['ok'=>true,'redirect'=>$base.'/'.$slug]);
    }

    public function customerProfile(Request $r): Response
    {
        if ($rr = $this->requireCustomer($r)) return $rr;
        $customer = $this->currentCustomer();
        $saved    = $r->query('saved','');
        return $this->view($r,'customer_profile',compact('customer','saved'),'My Profile');
    }

    public function customerProfileUpdate(Request $r): Response
    {
        if ($rr = $this->requireCustomer($r)) return $rr;
        $customer = $this->currentCustomer();
        $slug     = $this->taxi->setting('page_slug','taxi');

        $this->taxi->updateCustomerProfile((int)$customer['id'], [
            'name'         => trim((string)$r->post('name','')),
            'email'        => trim((string)$r->post('email','')),
            'home_address' => trim((string)$r->post('home_address','')),
            'home_lat'     => $r->post('home_lat') !== '' ? (float)$r->post('home_lat') : null,
            'home_lng'     => $r->post('home_lng') !== '' ? (float)$r->post('home_lng') : null,
            'work_address' => trim((string)$r->post('work_address','')),
            'work_lat'     => $r->post('work_lat') !== '' ? (float)$r->post('work_lat') : null,
            'work_lng'     => $r->post('work_lng') !== '' ? (float)$r->post('work_lng') : null,
        ]);

        return Response::redirect($r->basePath().'/taxi/profile?saved=1');
    }

    // ── Driver Auth ───────────────────────────────────────────────────────────

    public function driverAuthPage(Request $r): Response
    {
        if ($this->currentDriverFromSession()) {
            return Response::redirect($r->basePath().'/taxi/driver');
        }
        $error = $r->query('error','');
        return $this->viewDriver('driver_login', compact('error'));
    }

    public function driverLogin(Request $r): Response
    {
        $phone = trim((string)$r->post('phone',''));
        $pass  = (string)$r->post('password','');
        $driver = $this->taxi->verifyDriverByPhone($phone, $pass);
        if (!$driver) {
            return Response::redirect($r->basePath().'/taxi/driver/login?error='.urlencode('Wrong phone or password.'));
        }
        $driverId = (int)$driver['id'];
        // Ensure driver is active and visible to dispatch immediately on login
        $this->taxi->updateDriver($driverId, [
            'status'            => 'active',
            'last_heartbeat_at' => date('Y-m-d H:i:s'),
        ]);
        $this->sessionStart();
        session_regenerate_id(true);
        $_SESSION['taxi_driver_id'] = $driverId;
        return Response::redirect($r->basePath().'/taxi/driver');
    }

    public function driverLogout(Request $r): Response
    {
        $this->sessionStart();
        $driverId = $_SESSION['taxi_driver_id'] ?? null;
        if ($driverId) {
            $activeRide = $this->taxi->getDriverActiveRide((int)$driverId);
            if ($activeRide) {
                // Cannot logout while on an active ride — redirect back
                return Response::redirect($r->basePath().'/taxi/driver');
            }
            $this->taxi->setDriverOnline((int)$driverId, false);
        }
        unset($_SESSION['taxi_driver_id']);
        return Response::redirect($r->basePath().'/taxi/driver/login');
    }

    // ── Session-based driver portal ───────────────────────────────────────────

    public function driverPortalSession(Request $r): Response
    {
        if ($rr = $this->requireDriverSession($r)) return $rr;
        $driver = $this->currentDriverFromSession();
        return $this->renderDriverPortal($r, $driver);
    }

    // ── Booking ───────────────────────────────────────────────────────────────

    public function index(Request $r): Response
    {
        if ($rr = $this->requireCustomer($r)) return $rr;
        $customer = $this->currentCustomer();
        // Block booking if active ride exists
        $activeRide = $this->taxi->getCustomerActiveRide($customer['phone']);
        if ($activeRide) {
            return Response::redirect($r->basePath().'/taxi/track/'.urlencode($activeRide['ride_number']));
        }
        $routes = $this->taxi->allRoutes(true);
        $error  = $r->query('error','');
        return $this->view($r,'index',compact('routes','error','customer'),'Book a Taxi');
    }

    public function estimate(Request $r): Response
    {
        $km      = max(0,(float)$r->post('distance_km','0'));
        $carType = (string)$r->post('car_type','sedan');
        $price   = $km > 0 ? $this->taxi->estimateFare($km,$carType) : 0.0;
        return Response::json(['price'=>$price,'formatted'=>$this->taxi->formatPrice($price)]);
    }

    public function book(Request $r): Response
    {
        if ($rr = $this->requireCustomer($r)) return $rr;
        $slug     = $this->taxi->setting('page_slug','taxi');
        $base     = $r->basePath();
        $customer = $this->currentCustomer();

        $name    = trim((string)$r->post('customer_name','')) ?: ($customer['name'] ?? '');
        $phone   = trim((string)$r->post('customer_phone','')) ?: ($customer['phone'] ?? '');
        $email   = trim((string)$r->post('customer_email','')) ?: ($customer['email'] ?? '');
        $pickup  = trim((string)$r->post('pickup_address',''));
        $dest    = trim((string)$r->post('destination',''));
        $carType = (string)$r->post('car_type','sedan');
        $payment = (string)$r->post('payment_method','cash');
        $note    = trim((string)$r->post('customer_note',''));
        $scheduled= $r->post('scheduled_at') ?: null;
        $passengers= max(1,min(8,(int)$r->post('passengers','1')));
        $routeId   = $r->post('route_id') ? (int)$r->post('route_id') : null;
        $distKm    = $r->post('distance_km') !== '' ? round((float)$r->post('distance_km'), 1) : null;
        $pickupLat = $r->post('pickup_lat') !== '' ? (float)$r->post('pickup_lat') : null;
        $pickupLng = $r->post('pickup_lng') !== '' ? (float)$r->post('pickup_lng') : null;
        $destLat   = $r->post('dest_lat')   !== '' ? (float)$r->post('dest_lat')   : null;
        $destLng   = $r->post('dest_lng')   !== '' ? (float)$r->post('dest_lng')   : null;

        if (!$phone || !$pickup || !$dest) {
            return Response::redirect($base.'/'.$slug.'?error='.urlencode('Please fill required fields.'));
        }

        // Determine price
        if ($routeId) {
            $route = $this->taxi->getRoute($routeId);
            $price = $route ? (float)$route['price'] : $this->taxi->estimateFare($distKm ?? 1, $carType);
            $distKm = $route ? (float)$route['distance_km'] : $distKm;
        } else {
            $price = $this->taxi->estimateFare($distKm ?? 1, $carType);
        }

        $rideId = $this->taxi->createRide([
            'route_id'       => $routeId,
            'customer_name'  => $name,
            'customer_phone' => $phone,
            'customer_email' => $email,
            'pickup_address' => $pickup,
            'destination'    => $dest,
            'scheduled_at'   => $scheduled,
            'passengers'     => $passengers,
            'car_type'       => $carType,
            'distance_km'    => $distKm,
            'estimated_price'=> $price,
            'currency'       => $this->taxi->setting('currency','GEL'),
            'status'         => 'pending',
            'payment_method' => $payment,
            'payment_status' => 'unpaid',
            'customer_note'  => $note,
            'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? '',
            'pickup_lat'     => $pickupLat,
            'pickup_lng'     => $pickupLng,
            'dest_lat'       => $destLat,
            'dest_lng'       => $destLng,
        ]);

        $ride = $this->taxi->getRide($rideId);

        // Auto-dispatch to random driver
        $this->taxi->dispatchDriver($rideId);

        // BOG payment
        if ($payment === 'bog' && $ride) {
            try {
                $bog = gc_container()->get(\BogPayment\BogService::class);
                $scheme  = (!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
                $absBase = rtrim($scheme.'://'.(string)($_SERVER['HTTP_HOST']??'localhost').$base,'/');
                $result  = $bog->createOrder(
                    externalOrderId: 'gtx-'.$rideId,
                    total:           $price,
                    currency:        $this->taxi->setting('currency','GEL'),
                    basket:          [['product_id'=>'taxi','description'=>$pickup.' → '.$dest,'quantity'=>1,'unit_price'=>$price,'total_price'=>$price]],
                    callbackUrl:     $absBase.'/taxi/bog-callback',
                    successUrl:      $absBase.'/taxi/track/'.urlencode($ride['ride_number']).'?payment=bog',
                    failUrl:         $absBase.'/'.$slug.'?error='.urlencode('Payment failed.'),
                );
                if ($result) {
                    $this->taxi->updateRide($rideId,['transaction_id'=>$result['bog_order_id']]);
                    return Response::redirect($result['redirect_url']);
                }
            } catch (\Throwable $e) { error_log('[GoniTaxi] BOG: '.$e->getMessage()); }
        }

        $trackId = $ride['track_token'] ?? urlencode($ride['ride_number']);
        return Response::redirect($base.'/taxi/track/'.$trackId);
    }

    private function findRide(string $token): ?array
    {
        // Try track_token first, then fall back to ride_number (backward compat)
        return $this->taxi->getRideByTrackToken($token)
            ?? $this->taxi->getRideByNumber($token);
    }

    public function track(Request $r): Response
    {
        $number = (string)$r->getAttribute('number');
        $ride   = $this->findRide($number);
        if (!$ride) return Response::redirect($r->basePath().'/'.$this->taxi->setting('page_slug','taxi'));

        if ($r->query('payment')==='bog' && $ride['payment_status']==='unpaid') {
            try {
                $bog     = gc_container()->get(\BogPayment\BogService::class);
                $receipt = $ride['transaction_id'] ? $bog->getReceipt($ride['transaction_id']) : null;
                if ($receipt && ($receipt['order_status']['key']??'')==='completed') {
                    $this->taxi->updateRide((int)$ride['id'],['payment_status'=>'paid','status'=>'accepted']);
                    $ride['payment_status']='paid'; $ride['status']='accepted';
                }
            } catch (\Throwable) {}
        }

        // Trigger re-dispatch check (30-second timeout)
        if ($ride['status'] === 'pending') {
            $this->taxi->checkAndRedispatch((int)$ride['id'], 30);
            // Re-read ride after potential updates
            $ride = $this->taxi->getRideByNumber($number) ?? $ride;
        }

        $currentOffer = $this->taxi->getCurrentOffer((int)$ride['id']);

        return $this->view($r,'track', compact('ride','currentOffer'), 'Track Ride');
    }

    // ── Driver portal ─────────────────────────────────────────────────────────

    public function driverPortal(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $driver = $this->taxi->getDriverByToken($token);
        if (!$driver) return Response::error('Driver portal not found.', 404);
        return $this->renderDriverPortal($r, $driver);
    }

    private function renderDriverPortal(Request $r, array $driver): Response
    {
        $driverId   = (int)$driver['id'];
        $driver     = $this->taxi->getDriver($driverId) ?? $driver; // fresh data
        // Activate + heartbeat on every page load so dispatch always sees this driver
        try { $this->taxi->updateDriver($driverId, ['status' => 'active', 'last_heartbeat_at' => date('Y-m-d H:i:s')]); } catch (\Throwable) {}
        $activeRide = $this->taxi->getDriverActiveRide($driverId);

        $offer = null;
        if (!$activeRide) {
            $offer = $this->taxi->getPendingOfferForDriver($driverId);
            if ($offer && $offer['expired']) {
                $this->taxi->checkAndRedispatch((int)$offer['ride_id'], 30);
                $offer = null;
            }
            if (!$offer) {
                // No active offer → ensure all pending rides have one dispatched
                $this->taxi->sweepPendingRides();
                $offer = $this->taxi->getPendingOfferForDriver($driverId);
                if ($offer && $offer['expired']) { $offer = null; }
            }
        }

        // token for API calls (ensure exists)
        $token        = $this->taxi->ensureDriverToken($driverId);
        $encodedToken = $this->taxi->encodeDriverUrl($token); // safe for public URLs
        $base       = $r->basePath();
        $taxi       = $this->taxi;
        $themeViews = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeViews . '/helpers.php';

        ob_start();
        try {
            include dirname(__DIR__) . '/views/frontend/driver.php';
            return Response::html((string) ob_get_clean());
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    public function driverAccept(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $rideId = (int)$r->getAttribute('ride_id');
        $driver = $this->taxi->getDriverByToken($token);
        if (!$driver) return Response::redirect($r->basePath().'/taxi/driver/'.$token);
        $did = (int)$driver['id'];
        $this->taxi->driverAcceptOffer($did, $rideId);
        $this->taxi->recalcDriverStats($did);
        return Response::redirect($r->basePath().'/taxi/driver/'.$token);
    }

    public function driverDecline(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $rideId = (int)$r->getAttribute('ride_id');
        $driver = $this->taxi->getDriverByToken($token);
        if (!$driver) return Response::redirect($r->basePath().'/taxi/driver/'.$token);
        $did = (int)$driver['id'];
        $this->taxi->driverDeclineOffer($did, $rideId);
        $this->taxi->recalcDriverStats($did);
        return Response::redirect($r->basePath().'/taxi/driver/'.$token);
    }

    public function driverStartRide(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $rideId = (int)$r->getAttribute('ride_id');
        $driver = $this->taxi->getDriverByToken($token);
        if (!$driver) return Response::redirect($r->basePath().'/taxi/driver/'.$token);
        // Freeze waiting seconds before starting ride
        $ride = $this->taxi->getRide($rideId);
        $upd  = ['status' => 'in_progress'];
        if ($ride && !empty($ride['waiting_started_at'])) {
            $secs = max(0, time() - strtotime((string)$ride['waiting_started_at']));
            $upd['waiting_seconds']    = (int)$secs;
            $upd['waiting_started_at'] = null;
        }
        $this->taxi->updateRide($rideId, $upd);
        return Response::redirect($r->basePath().'/taxi/driver/'.$token);
    }

    /** Driver starts/stops waiting for passenger */
    public function apiDriverWaiting(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $rideId = (int)$r->getAttribute('ride_id');
        $driver = $this->taxi->getDriverByToken($token);
        if (!$driver) return Response::json(['ok' => false], 403);

        $body    = json_decode((string)file_get_contents('php://input'), true);
        $waiting = (bool)($body['waiting'] ?? false);

        $ride = $this->taxi->getRide($rideId);
        if (!$ride || (int)$ride['driver_id'] !== (int)$driver['id']) {
            return Response::json(['ok' => false], 403);
        }

        if ($waiting) {
            $this->taxi->updateRide($rideId, ['waiting_started_at' => date('Y-m-d H:i:s')]);
        } else {
            // Save accumulated seconds before clearing
            $prev = !empty($ride['waiting_started_at'])
                ? max(0, time() - strtotime((string)$ride['waiting_started_at'])) : 0;
            $total = (int)($ride['waiting_seconds'] ?? 0) + (int)$prev;
            $this->taxi->updateRide($rideId, ['waiting_started_at' => null, 'waiting_seconds' => $total]);
        }
        return Response::json(['ok' => true, 'waiting' => $waiting]);
    }

    public function driverCompleteRide(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $rideId = (int)$r->getAttribute('ride_id');
        $driver = $this->taxi->getDriverByToken($token);
        if (!$driver) return Response::redirect($r->basePath().'/taxi/driver/'.$token);

        $ride        = $this->taxi->getRide($rideId);
        $actualPrice = $r->post('actual_price') !== null ? (float)$r->post('actual_price') : null;
        $upd         = ['status' => 'completed'];

        // 1. Freeze any still-running waiting timer
        $waitingSecs = (int)($ride['waiting_seconds'] ?? 0);
        if (!empty($ride['waiting_started_at'])) {
            $extra = max(0, time() - strtotime((string)$ride['waiting_started_at']));
            $waitingSecs += $extra;
            $upd['waiting_seconds']    = $waitingSecs;
            $upd['waiting_started_at'] = null;
        }

        // 2. Calculate waiting fee
        $waitingFee = $this->taxi->calcWaitingFee($waitingSecs);
        if ($waitingFee > 0) {
            $upd['waiting_fee'] = $waitingFee;
        }

        // 3. Base fare (manual override takes priority, else estimated)
        $baseFare = ($actualPrice !== null && $actualPrice > 0)
            ? $actualPrice
            : (float)($ride['estimated_price'] ?? 0);

        // 4. Total = base + waiting; stored as actual_price for commission split
        $totalFare           = round($baseFare + $waitingFee, 2);
        $upd['actual_price'] = $totalFare;

        // 5. Cash payment → auto-mark as paid (driver collects in person)
        if (($ride['payment_method'] ?? '') === 'cash' && ($ride['payment_status'] ?? '') !== 'paid') {
            $upd['payment_status'] = 'paid';
        }

        $this->taxi->updateRide($rideId, $upd);
        $this->taxi->settleRide($rideId);   // uses actual_price → commission split
        $this->taxi->updateDriver((int)$driver['id'], ['status' => 'active']);
        $this->taxi->updateDriver((int)$driver['id'], ['current_lat' => null, 'current_lng' => null]);
        return Response::redirect($r->basePath().'/taxi/driver/'.$token.'?done='.$rideId);
    }

    // ── Location APIs ─────────────────────────────────────────────────────────

    /** Driver POSTs their GPS coordinates */
    public function apiUpdateDriverLocation(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $driver = $this->taxi->getDriverByToken($token);
        if (!$driver) return Response::json(['ok' => false], 403);

        $body  = json_decode((string)file_get_contents('php://input'), true);
        $lat   = isset($body['lat'])   ? (float)$body['lat']   : null;
        $lng   = isset($body['lng'])   ? (float)$body['lng']   : null;
        $speed = isset($body['speed']) ? (float)$body['speed'] : null; // km/h

        if ($lat === null || $lng === null) {
            return Response::json(['ok' => false, 'error' => 'missing lat/lng'], 400);
        }

        $this->taxi->updateDriverLocation((int)$driver['id'], $lat, $lng, $speed);
        return Response::json(['ok' => true]);
    }

    /** Customer polls for driver's current location */
    public function apiGetDriverLocation(Request $r): Response
    {
        $number = (string)$r->getAttribute('number');
        $ride   = $this->findRide($number);
        if (!$ride || !$ride['driver_id']) return Response::json(['location' => null]);

        $loc = $this->taxi->getDriverLocation((int)$ride['driver_id']);

        // Waiting info
        $waitingSecs = null;
        if (!empty($ride['waiting_started_at'])) {
            $waitingSecs = max(0, time() - strtotime((string)$ride['waiting_started_at']));
        }

        return Response::json([
            'location'        => $loc,
            'status'          => $ride['status'],
            'waiting_seconds' => $waitingSecs,
            'waiting_total'   => (int)($ride['waiting_seconds'] ?? 0),
        ]);
    }

    // ── Polling APIs ──────────────────────────────────────────────────────────

    /** Driver portal polling: returns current pending offer JSON */
    public function apiDriverOffer(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $driver = $this->taxi->getDriverByToken($token);
        if (!$driver) return Response::json(['offer'=>null,'driver_status'=>'unknown']);
        try { $this->taxi->updateDriver((int)$driver['id'], ['last_heartbeat_at' => date('Y-m-d H:i:s')]); } catch (\Throwable) {}

        $offer = $this->taxi->getPendingOfferForDriver((int)$driver['id']);

        if ($offer && $offer['expired']) {
            $this->taxi->checkAndRedispatch((int)$offer['ride_id'], 30);
            $offer = null;
        }
        if (!$offer) {
            // No active offer → sweep all pending rides, then re-check
            $this->taxi->sweepPendingRides();
            $offer = $this->taxi->getPendingOfferForDriver((int)$driver['id']);
            if ($offer && $offer['expired']) { $offer = null; }
        }

        $ride = $offer ? $offer['ride'] : null;
        return Response::json([
            'offer'         => $offer ? [
                'ride_id'    => $offer['ride_id'],
                'expires_in' => $offer['expires_in'],
                'pickup'     => $ride['pickup_address'] ?? '',
                'destination'=> $ride['destination'] ?? '',
                'car_type'   => $ride['car_type'] ?? '',
                'passengers' => $ride['passengers'] ?? 1,
                'price'      => $ride['estimated_price'] ?? 0,
            ] : null,
            'driver_status' => $driver['status'],
        ]);
    }

    /** Customer track page polling: returns ride status JSON */
    public function apiRideStatus(Request $r): Response
    {
        $number = (string)$r->getAttribute('number');
        $ride   = $this->findRide($number);
        if (!$ride) return Response::json(['status'=>'not_found']);

        if ($ride['status'] === 'pending') {
            $this->taxi->checkAndRedispatch((int)$ride['id'], 30);
            $ride = $this->taxi->getRideByNumber($number) ?? $ride;
        }

        $offer = $this->taxi->getCurrentOffer((int)$ride['id']);

        $waitingSecs = null;
        if (!empty($ride['waiting_started_at'])) {
            $waitingSecs = max(0, time() - strtotime((string)$ride['waiting_started_at']));
        }

        return Response::json([
            'status'          => $ride['status'],
            'driver_name'     => !empty($ride['driver']) ? $ride['driver']['name'] : null,
            'driver_phone'    => !empty($ride['driver']) ? $ride['driver']['phone'] : null,
            'offer_expires'   => $offer ? $offer['expires_in'] : null,
            'waiting_seconds' => $waitingSecs,
        ]);
    }

    public function cancelRide(Request $r): Response
    {
        $number = (string)$r->getAttribute('number');
        $reason = trim((string)$r->post('reason', ''));
        $ride   = $this->findRide($number);
        if ($ride) {
            $this->taxi->cancelRide((int)$ride['id'], 'customer', $reason);
        }
        return Response::redirect($r->basePath().'/taxi/track/'.$number);
    }

    public function rateRide(Request $r): Response
    {
        $number = (string)$r->getAttribute('number');
        $stars  = max(1, min(5, (int)$r->post('rating', '5')));
        $ride   = $this->findRide($number);
        if ($ride) {
            $this->taxi->rateRide((int)$ride['id'], $stars);
        }
        return Response::redirect($r->basePath().'/taxi/track/'.$number.'?rated=1');
    }

    /** Driver polls this while on an active ride to detect customer cancellation */
    public function apiDriverRideStatus(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $driver = $this->taxi->getDriverByToken($token);
        if (!$driver) return Response::json(['ok' => false], 403);

        $driverId = (int)$driver['id'];
        $ride     = $this->taxi->getDriverActiveRide($driverId);
        if ($ride) {
            return Response::json(['status' => $ride['status'], 'ride_id' => (int)$ride['id']]);
        }

        $cancelled = $this->taxi->getDriverLastCancelledRide($driverId);
        if ($cancelled) {
            $elapsed = time() - strtotime((string)$cancelled['updated_at']);
            if ($elapsed < 300) {
                return Response::json(['status' => 'cancelled', 'ride_id' => (int)$cancelled['id']]);
            }
        }

        return Response::json(['status' => 'none']);
    }

    public function apiOnlineDrivers(Request $r): Response
    {
        $drivers = $this->taxi->allDrivers(true);
        $list = [];
        foreach ($drivers as $d) {
            if (empty($d['is_online']) || empty($d['current_lat'])) continue;
            $updated = strtotime((string)($d['location_updated_at'] ?? ''));
            if (!$updated || (time() - $updated) > 300) continue; // stale > 5min
            $speedKph = isset($d['current_speed']) && $d['current_speed'] !== null
            ? (float)$d['current_speed'] : null;
        $list[] = [
            'id'        => (int)$d['id'],
            'name'      => $d['name'],
            'lat'       => (float)$d['current_lat'],
            'lng'       => (float)$d['current_lng'],
            'car_type'  => $d['car_type'],
            'car'       => $d['car_model']  ?? '',
            'car_color' => $d['car_color']  ?? '',
            'car_number'=> $d['car_number'] ?? '',
            'status'    => $d['status'],
            'speed'     => $speedKph !== null ? round($speedKph, 1) : null,
        ];
        }
        return Response::json(['drivers' => $list]);
    }

    public function apiDriverToggleOnline(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $driver = $this->taxi->getDriverByToken($token);
        if (!$driver) return Response::json(['ok' => false], 403);
        $body   = json_decode((string)file_get_contents('php://input'), true);
        $online = (bool)($body['online'] ?? false);

        // Block going offline while driver has an active ride
        if (!$online) {
            $activeRide = $this->taxi->getDriverActiveRide((int)$driver['id']);
            if ($activeRide) {
                return Response::json([
                    'ok'      => false,
                    'blocked' => true,
                    'reason'  => 'მგზავრობა მიმდინარეობს — დასრულების შემდეგ შეძლებ გათიშვას.',
                ], 200);
            }
        }

        $this->taxi->setDriverOnline((int)$driver['id'], $online);
        return Response::json(['ok' => true, 'online' => $online]);
    }

    /** Debug info endpoint — returns full dispatch state as JSON */
    public function apiDebugInfo(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $driver = $this->taxi->getDriverByToken($token);
        if (!$driver) return Response::json(['error' => 'driver not found'], 403);

        $driverId = (int)$driver['id'];
        $offer    = $this->taxi->getPendingOfferForDriver($driverId);
        $pending  = $this->taxi->allRides(1, 10, 'pending');
        $recent   = $this->taxi->getRecentOffersForDriver($driverId, 8);

        $pendingRidesInfo = array_map(function ($ride) {
            return [
                'id'         => $ride['id'],
                'number'     => $ride['ride_number'],
                'status'     => $ride['status'],
                'driver_id'  => $ride['driver_id'],
                'total_offers' => $this->taxi->countPendingOffers((int)$ride['id']),
                'created_at' => $ride['created_at'],
            ];
        }, $pending['items']);

        // ── Verbose: reproduce the exact getPendingOfferForDriver logic ──────────
        $rawOffers = $this->taxi->getRecentOffersForDriver($driverId, 3);
        $verboseOffers = array_map(function ($o) {
            $elapsed = time() - strtotime((string)$o['offered_at']);
            return [
                'id'              => $o['id'],
                'status_in_db'    => $o['status'],
                'offered_at'      => $o['offered_at'],
                'php_time()'      => time(),
                'strtotime_val'   => strtotime((string)$o['offered_at']),
                'elapsed_seconds' => $elapsed,
                'would_expire'    => $elapsed >= 30,
                'visible_to_api'  => ($o['status'] === 'pending' && $elapsed < 30),
            ];
        }, $rawOffers);

        return Response::json([
            'php_time'    => date('Y-m-d H:i:s'),
            'php_unix'    => time(),
            'verbose_offers' => $verboseOffers,
            'driver' => [
                'id'              => $driver['id'],
                'status'          => $driver['status'],
                'is_online'       => (int)$driver['is_online'],
                'last_heartbeat'  => $driver['last_heartbeat_at'] ?? null,
                'token_ok'        => !empty($driver['driver_token']),
            ],
            'current_offer'  => $offer ? [
                'offer_id'   => $offer['offer_id'],
                'ride_id'    => $offer['ride_id'],
                'offered_at' => $offer['offered_at'],
                'expires_in' => $offer['expires_in'],
                'expired'    => $offer['expired'],
            ] : null,
            'pending_rides'  => $pendingRidesInfo,
            'recent_offers'  => $recent,
        ]);
    }

    public function apiLiveMapData(Request $r): Response
    {
        // Admin-only: check session
        $drivers = $this->taxi->allDrivers();
        $rides   = $this->taxi->allRides(1, 200, '')['items'];
        return Response::json(['drivers' => $drivers, 'rides' => $rides]);
    }

    public function apiDriverNotifications(Request $r): Response
    {
        $driver = $this->taxi->getDriverByToken((string)$r->getAttribute('token'));
        if (!$driver) return Response::json(['error' => 'unauthorized'], 403);

        $driverId = (int)$driver['id'];
        $notifs   = $this->taxi->getDriverNotifications($driverId, 30);
        $unread   = $this->taxi->countUnreadNotifications($driverId);

        return Response::json(['notifications' => $notifs, 'unread' => $unread]);
    }

    public function apiDriverNotificationsReadAll(Request $r): Response
    {
        $driver = $this->taxi->getDriverByToken((string)$r->getAttribute('token'));
        if (!$driver) return Response::json(['error' => 'unauthorized'], 403);

        $this->taxi->markAllNotificationsRead((int)$driver['id']);
        return Response::json(['ok' => true]);
    }

    public function bogCallback(Request $r): Response
    {
        $rawBody  =(string)file_get_contents('php://input');
        $sigHeader=(string)($_SERVER['HTTP_CALLBACK_SIGNATURE']??'');
        try {
            $bog=gc_container()->get(\BogPayment\BogService::class);
            if (!$bog->verifySignature($rawBody,$sigHeader)) return Response::html('',400);
        } catch(\Throwable){return Response::html('',400);}
        $payload=json_decode($rawBody,true);
        if (!is_array($payload)||($payload['event']??'')!=='order_payment') return Response::html('',200);
        $body=($payload['body']??[]); $ext=(string)($body['external_order_id']??''); $st=(string)($body['order_status']['key']??'');
        if (!str_starts_with($ext,'gtx-')) return Response::html('',200);
        $id=(int)substr($ext,4);
        if ($st==='completed') $this->taxi->updateRide($id,['payment_status'=>'paid','status'=>'accepted']);
        elseif(in_array($st,['rejected','expired'],true)) $this->taxi->updateRide($id,['status'=>'cancelled']);
        return Response::html('',200);
    }

    private function viewDriver(string $tpl, array $data = []): Response
    {
        $file = $this->viewsDir.'/'.$tpl.'.php';
        if (!is_file($file)) return Response::error("Driver view not found: $tpl", 500);
        $taxi = $this->taxi;
        $themeViews = dirname(__DIR__, 3).'/themes/default/views';
        require_once $themeViews.'/helpers.php';
        extract($data, EXTR_SKIP);
        ob_start();
        try { include $file; return Response::html((string)ob_get_clean()); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
    }

    private function view(Request $r, string $tpl, array $data=[], string $pageTitle=''): Response
    {
        $file=$this->viewsDir.'/'.$tpl.'.php';
        if (!is_file($file)) return Response::error("Taxi view not found: $tpl",500);
        $themeViews=dirname(__DIR__,3).'/themes/default/views';
        require_once $themeViews.'/helpers.php';
        $base=$r->basePath(); $taxi=$this->taxi;
        try {
            $c=gc_container();
            $siteName=$c->get(\GoniCore\Modules\Settings\SettingsService::class)->siteName()?:'GoniCore';
            $langService=$c->get(\GoniCore\Modules\Language\LanguageService::class); $langService->boot($r);
            $menuService=$c->get(\GoniCore\Modules\Menu\MenuService::class);
            $widgetService=$c->get(\GoniCore\Modules\Widget\WidgetService::class);
            $categories=$c->get(\GoniCore\Modules\Category\CategoryRepository::class)->findAll();
        } catch(\Throwable){$siteName='GoniCore';$langService=null;$menuService=null;$widgetService=null;$categories=[];}
        extract($data,EXTR_SKIP);
        ob_start();
        try{include $file;$content=(string)ob_get_clean();}catch(\Throwable $e){ob_end_clean();throw $e;}
        ob_start();
        try{include $themeViews.'/layout.php';return Response::html((string)ob_get_clean());}catch(\Throwable $e){ob_end_clean();throw $e;}
    }
}
