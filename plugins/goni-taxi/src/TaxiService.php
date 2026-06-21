<?php
declare(strict_types=1);
namespace GoniTaxi;

use GoniCore\Core\Database\QueryBuilder;

final class TaxiService
{
    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Settings ──────────────────────────────────────────────────────────────
    public function setting(string $key, string $default = ''): string
    {
        $row = $this->qb->table('gtaxi_settings')->where('key','=',$key)->first();
        return $row ? (string)$row['value'] : $default;
    }
    public function setSetting(string $key, string $value): void
    {
        $exists = $this->qb->table('gtaxi_settings')->where('key','=',$key)->first();
        if ($exists) $this->qb->table('gtaxi_settings')->where('key','=',$key)->update(['value'=>$value]);
        else $this->qb->table('gtaxi_settings')->insert(['key'=>$key,'value'=>$value]);
    }

    // ── Settlements ───────────────────────────────────────────────────────────

    /**
     * Calculate what each driver is owed for completed + paid rides in a period.
     * Returns array of driver rows with computed totals.
     */
    public function calcSettlementData(string $dateFrom, string $dateTo): array
    {
        $rows = $this->qb->table('gtaxi_rides')
            ->where('status',     '=', 'completed')
            ->where('updated_at', '>=', $dateFrom.' 00:00:00')
            ->where('updated_at', '<=', $dateTo.' 23:59:59')
            ->get() ?: [];

        $byDriver = [];
        foreach ($rows as $r) {
            $did = (int)$r['driver_id'];
            if (!$did) continue;
            if (!isset($byDriver[$did])) {
                $byDriver[$did] = [
                    'rides'        => 0,
                    // card
                    'card_gross'   => 0.0,
                    'card_comm'    => 0.0,
                    'card_earned'  => 0.0,
                    // cash
                    'cash_gross'   => 0.0,
                    'cash_comm'    => 0.0,
                    'cash_earned'  => 0.0,
                ];
            }

            $fare   = (float)($r['actual_price'] ?? $r['estimated_price'] ?? 0);
            $earned = $r['driver_earnings'] !== null
                ? (float)$r['driver_earnings']
                : $this->calcDriverEarnings($fare);
            $comm   = round($fare - $earned, 2);
            $method = (string)($r['payment_method'] ?? 'cash');
            $isCash = ($method === 'cash');

            $byDriver[$did]['rides']++;
            if ($isCash) {
                $byDriver[$did]['cash_gross']  += $fare;
                $byDriver[$did]['cash_comm']   += $comm;
                $byDriver[$did]['cash_earned'] += $earned;
            } else {
                $byDriver[$did]['card_gross']  += $fare;
                $byDriver[$did]['card_comm']   += $comm;
                $byDriver[$did]['card_earned'] += $earned;
            }
        }

        // Build result rows
        $result = [];
        foreach ($byDriver as $did => $t) {
            $driver = $this->getDriver($did);
            if (!$driver) continue;

            $cardGross  = round($t['card_gross'], 2);
            $cardComm   = round($t['card_comm'],  2);
            $cardEarned = round($t['card_earned'],2);
            $cashGross  = round($t['cash_gross'], 2);
            $cashComm   = round($t['cash_comm'],  2);
            $cashEarned = round($t['cash_earned'],2);

            // Net bank transfer (platform → driver):
            //   card rides: platform received card_gross, owes driver card_earned  (+)
            //   cash rides: driver has cash_gross, owes platform cash_comm          (−)
            // Positive → platform pays driver; Negative → driver owes platform
            $netTransfer = round($cardEarned - $cashComm, 2);

            $result[] = [
                'driver'       => $driver,
                'rides_count'  => $t['rides'],
                // Totals for display
                'gross_amount' => round($cardGross + $cashGross, 2),
                'commission'   => round($cardComm  + $cashComm,  2),
                // Breakdown
                'card_gross'   => $cardGross,
                'card_comm'    => $cardComm,
                'card_earned'  => $cardEarned,
                'cash_gross'   => $cashGross,
                'cash_comm'    => $cashComm,
                'cash_earned'  => $cashEarned,
                // ← What to actually transfer
                'net_amount'   => $netTransfer,
            ];
        }
        usort($result, fn($a,$b) => $b['net_amount'] <=> $a['net_amount']);
        return $result;
    }

    public function createSettlement(array $data): int
    {
        return (int)$this->qb->table('gtaxi_settlements')->insert($data);
    }

    public function allSettlements(int $page = 1, int $perPage = 25, string $status = ''): array
    {
        $qb = $this->qb->table('gtaxi_settlements');
        if ($status) $qb = $qb->where('status','=',$status);
        $total = (int)($qb->count() ?? 0);
        $items = $qb->orderBy('created_at','DESC')->limit($perPage)->offset(($page-1)*$perPage)->get() ?: [];
        // Attach driver info
        foreach ($items as &$item) {
            $item['driver'] = $this->getDriver((int)$item['driver_id']);
        }
        return ['items'=>$items,'total'=>$total,'pages'=>max(1,(int)ceil($total/$perPage))];
    }

    public function getSettlement(int $id): ?array
    {
        $s = $this->qb->table('gtaxi_settlements')->where('id','=',$id)->first();
        if (!$s) return null;
        $s['driver'] = $this->getDriver((int)$s['driver_id']);
        return $s;
    }

    public function updateSettlement(int $id, array $data): void
    {
        $this->qb->table('gtaxi_settlements')->where('id','=',$id)->update($data);
    }

    // ── URL Security ─────────────────────────────────────────────────────────

    public function appSecret(): string
    {
        static $cache = null;
        if ($cache !== null) return $cache;
        $s = $this->setting('app_secret', '');
        if (!$s) {
            $s = bin2hex(random_bytes(16));
            $this->setSetting('app_secret', $s);
        }
        return $cache = $s;
    }

    /** Encode a driver token for safe use in public URLs */
    public function encodeDriverUrl(string $rawToken): string
    {
        $payload = substr($this->appSecret(), 0, 6) . $rawToken;
        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    /** Decode an encoded driver URL token back to the raw token */
    public function decodeDriverUrl(string $encoded): ?string
    {
        try {
            $pad     = str_repeat('=', (4 - strlen($encoded) % 4) % 4);
            $payload = base64_decode(strtr($encoded . $pad, '-_', '+/'), true);
            if ($payload === false || strlen($payload) < 7) return null;
            $prefix  = substr($this->appSecret(), 0, 6);
            if (!str_starts_with($payload, $prefix)) return null;
            return substr($payload, 6);
        } catch (\Throwable) { return null; }
    }

    /** Generate a random track token for a ride */
    public function generateTrackToken(): string { return bin2hex(random_bytes(16)); }

    /** Lookup a ride by its track token */
    public function getRideByTrackToken(string $token): ?array
    {
        $r = $this->qb->table('gtaxi_rides')->where('track_token', '=', $token)->first();
        if (!$r) return null;
        if ($r['driver_id']) $r['driver'] = $this->getDriver((int)$r['driver_id']);
        if ($r['route_id'])  $r['route']  = $this->getRoute((int)$r['route_id']);
        return $r;
    }

    // ── Drivers ───────────────────────────────────────────────────────────────
    public function allDrivers(bool $activeOnly = false): array
    {
        $qb = $this->qb->table('gtaxi_drivers');
        if ($activeOnly) $qb = $qb->where('status','!=','inactive');
        return $qb->orderBy('name','ASC')->get() ?: [];
    }
    public function getDriver(int $id): ?array
    {
        return $this->qb->table('gtaxi_drivers')->where('id','=',$id)->first();
    }
    public function createDriver(array $d): int
    {
        if (empty($d['driver_token'])) {
            $d['driver_token'] = $this->generateDriverToken();
        }
        return (int)$this->qb->table('gtaxi_drivers')->insert($d);
    }
    public function updateDriver(int $id, array $d): void
    {
        $this->qb->table('gtaxi_drivers')->where('id','=',$id)->update($d);
    }
    public function deleteDriver(int $id): void
    {
        $this->qb->table('gtaxi_drivers')->where('id','=',$id)->delete();
    }
    public function getDriverByToken(string $token): ?array
    {
        if (!$token) return null;
        // Try raw token first
        $d = $this->qb->table('gtaxi_drivers')->where('driver_token','=',$token)->first();
        if ($d) return $d;
        // Try encoded token (URL-safe format)
        $raw = $this->decodeDriverUrl($token);
        if ($raw) return $this->qb->table('gtaxi_drivers')->where('driver_token','=',$raw)->first();
        return null;
    }
    public function regenerateDriverToken(int $id): string
    {
        $token = $this->generateDriverToken();
        $this->qb->table('gtaxi_drivers')->where('id','=',$id)->update(['driver_token'=>$token]);
        return $token;
    }
    public function ensureDriverToken(int $id): string
    {
        $driver = $this->getDriver($id);
        if (!$driver) return '';
        if (!empty($driver['driver_token'])) return (string)$driver['driver_token'];
        return $this->regenerateDriverToken($id);
    }
    public function generateDriverToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    // ── Routes ────────────────────────────────────────────────────────────────
    public function allRoutes(bool $activeOnly = false): array
    {
        $qb = $this->qb->table('gtaxi_routes');
        if ($activeOnly) $qb = $qb->where('active','=','1');
        return $qb->orderBy('sort_order','ASC')->get() ?: [];
    }
    public function getRoute(int $id): ?array { return $this->qb->table('gtaxi_routes')->where('id','=',$id)->first(); }
    public function createRoute(array $d): int { return (int)$this->qb->table('gtaxi_routes')->insert($d); }
    public function updateRoute(int $id, array $d): void { $this->qb->table('gtaxi_routes')->where('id','=',$id)->update($d); }
    public function deleteRoute(int $id): void { $this->qb->table('gtaxi_routes')->where('id','=',$id)->delete(); }

    // ── Rides ─────────────────────────────────────────────────────────────────
    public function allRides(int $page = 1, int $perPage = 25, string $status = ''): array
    {
        $qb = $this->qb->table('gtaxi_rides');
        if ($status !== '') $qb = $qb->where('status','=',$status);
        $total = (int)($qb->count() ?? 0);
        $items = $qb->orderBy('created_at','DESC')->limit($perPage)->offset(($page-1)*$perPage)->get() ?: [];
        return ['items'=>$items,'total'=>$total,'pages'=>max(1,(int)ceil($total/$perPage))];
    }
    public function getRide(int $id): ?array
    {
        $r = $this->qb->table('gtaxi_rides')->where('id','=',$id)->first();
        if (!$r) return null;
        if ($r['driver_id']) $r['driver'] = $this->getDriver((int)$r['driver_id']);
        if ($r['route_id'])  $r['route']  = $this->getRoute((int)$r['route_id']);
        return $r;
    }
    public function getRideByNumber(string $num): ?array
    {
        $r = $this->qb->table('gtaxi_rides')->where('ride_number','=',$num)->first();
        if (!$r) return null;
        if ($r['driver_id']) $r['driver'] = $this->getDriver((int)$r['driver_id']);
        return $r;
    }
    public function createRide(array $data): int
    {
        $data['ride_number']  = $this->generateNumber();
        $data['track_token']  = $this->generateTrackToken();
        return (int)$this->qb->table('gtaxi_rides')->insert($data);
    }
    public function updateRide(int $id, array $data): void
    {
        $this->qb->table('gtaxi_rides')->where('id','=',$id)->update($data);
    }

    // ── Commission & Earnings ─────────────────────────────────────────────────

    public function calcWaitingFee(int $waitingSeconds): float
    {
        if ($waitingSeconds <= 0) return 0.0;
        $freeMin  = (float)$this->setting('waiting_free_minutes', '3');
        $rateMin  = (float)$this->setting('waiting_rate_per_min', '0.3');
        $totalMin = $waitingSeconds / 60.0;
        $billable = max(0.0, $totalMin - $freeMin);
        return round($billable * $rateMin, 2);
    }

    public function commissionPct(): float
    {
        return max(0.0, min(100.0, (float)$this->setting('commission_pct', '20')));
    }

    public function calcDriverEarnings(float $fare): float
    {
        return round($fare * (1 - $this->commissionPct() / 100), 2);
    }

    /** Credit driver earnings for a completed ride (idempotent). */
    public function settleRide(int $rideId): void
    {
        $ride = $this->getRide($rideId);
        if (!$ride || !$ride['driver_id'] || $ride['status'] !== 'completed') return;
        if (isset($ride['driver_earnings']) && $ride['driver_earnings'] !== null) return;

        $actualPrice = (float)($ride['actual_price'] ?? 0);
        $fare        = $actualPrice > 0 ? $actualPrice : (float)($ride['estimated_price'] ?? 0);
        $earnings    = $fare > 0 ? $this->calcDriverEarnings($fare) : 0.0;

        $this->updateRide($rideId, ['driver_earnings' => $earnings]);

        $driver = $this->getDriver((int)$ride['driver_id']);
        if ($driver) {
            $this->updateDriver((int)$ride['driver_id'], [
                'balance' => round((float)($driver['balance'] ?? 0) + $earnings, 2),
            ]);
        }
    }

    /** Last N completed rides for a driver (with earnings). */
    public function getDriverRides(int $driverId, int $limit = 10): array
    {
        return $this->qb->table('gtaxi_rides')
            ->where('driver_id', '=', $driverId)
            ->where('status', '=', 'completed')
            ->orderBy('updated_at', 'DESC')
            ->limit($limit)
            ->get() ?: [];
    }

    // ── Rating ────────────────────────────────────────────────────────────────

    public function rateRide(int $rideId, int $stars): bool
    {
        $stars = max(1, min(5, $stars));
        $ride  = $this->getRide($rideId);
        if (!$ride || $ride['status'] !== 'completed') return false;
        if (!empty($ride['rating'])) return false;

        $this->updateRide($rideId, ['rating' => $stars]);

        if ($ride['driver_id']) {
            $rows = $this->qb->table('gtaxi_rides')
                ->where('driver_id', '=', (int)$ride['driver_id'])
                ->where('status', '=', 'completed')
                ->get() ?: [];
            $ratings = array_filter(array_column($rows, 'rating'));
            $avg     = count($ratings) ? round(array_sum($ratings) / count($ratings), 2) : 0.0;
            $this->updateDriver((int)$ride['driver_id'], [
                'avg_rating'  => $avg,
                'total_trips' => count($rows),
            ]);
        }
        return true;
    }

    // ── Cancellation ──────────────────────────────────────────────────────────

    public function cancelRide(int $rideId, string $by = 'customer', string $reason = ''): bool
    {
        $ride = $this->getRide($rideId);
        if (!$ride) return false;
        if (in_array($ride['status'], ['completed', 'cancelled'], true)) return false;

        $this->updateRide($rideId, [
            'status'        => 'cancelled',
            'cancelled_by'  => $by,
            'cancel_reason' => $reason,
        ]);

        if ($ride['driver_id']) {
            $this->updateDriver((int)$ride['driver_id'], ['status' => 'active']);
        }
        return true;
    }

    // ── Driver Online Status ──────────────────────────────────────────────────

    public function setDriverOnline(int $driverId, bool $online): void
    {
        $update = ['is_online' => $online ? 1 : 0];
        if ($online) {
            $update['status'] = 'active';
        }
        // Going offline → only clears is_online; keeps status='active'
        // so dispatch can still route new rides to this driver.
        $this->updateDriver($driverId, $update);
    }

    // ── Dispatch System ───────────────────────────────────────────────────────

    /**
     * Assign the ride to a random available driver (excluding already-tried ones).
     * Creates a new pending offer. Returns driver ID or null if none available.
     */
    public function dispatchDriver(int $rideId): ?int
    {
        $ride = $this->qb->table('gtaxi_rides')->where('id','=',$rideId)->first();
        if (!$ride) return null;
        if (in_array($ride['status'], ['driver_assigned','in_progress','completed','cancelled'], true)) return null;

        $pickupLat = isset($ride['pickup_lat']) && $ride['pickup_lat'] !== null ? (float)$ride['pickup_lat'] : null;
        $pickupLng = isset($ride['pickup_lng']) && $ride['pickup_lng'] !== null ? (float)$ride['pickup_lng'] : null;

        $triedIds = $this->getTriedDriverIds($rideId);

        // Try untried drivers first (prefers nearby if GPS available)
        $driver = $this->pickRandomDriver((string)$ride['car_type'], $triedIds, $pickupLat, $pickupLng);
        // Reset tried-cycle if needed
        if (!$driver && !empty($triedIds)) {
            $driver = $this->pickRandomDriver((string)$ride['car_type'], [], $pickupLat, $pickupLng);
        }
        // Hard fallback: ignore coords entirely
        if (!$driver) {
            $driver = $this->pickRandomDriver((string)$ride['car_type'], [], null, null);
        }

        if (!$driver) return null;

        $driverId = (int)$driver['id'];

        // Expire any existing pending offer
        $this->qb->table('gtaxi_driver_offers')
            ->where('ride_id','=',$rideId)
            ->where('status','=','pending')
            ->update(['status'=>'expired','responded_at'=>date('Y-m-d H:i:s')]);

        // Create new offer
        $this->qb->table('gtaxi_driver_offers')->insert([
            'ride_id'   => $rideId,
            'driver_id' => $driverId,
            'status'    => 'pending',
            'offered_at'=> date('Y-m-d H:i:s'),
        ]);

        // Ensure driver has a portal token
        $this->ensureDriverToken($driverId);

        return $driverId;
    }

    /**
     * Ensure every pending ride has an active offer.
     * Called from the driver portal so requests are never lost.
     */
    public function sweepPendingRides(): void
    {
        $rows = $this->qb->table('gtaxi_rides')
            ->where('status', '=', 'pending')
            ->get() ?: [];
        foreach ($rows as $row) {
            $rideId = (int)$row['id'];
            // Only dispatch if truly no pending offer exists (prevent double-dispatch)
            $existing = $this->qb->table('gtaxi_driver_offers')
                ->where('ride_id', '=', $rideId)
                ->where('status', '=', 'pending')
                ->first();
            if ($existing) {
                // There is a pending offer — only expire+redispatch if it's timed out
                $elapsed = time() - strtotime((string)$existing['offered_at']);
                if ($elapsed >= 30) {
                    $this->checkAndRedispatch($rideId, 30);
                }
            } else {
                $this->dispatchDriver($rideId);
            }
        }
    }

    /**
     * Called on every customer/admin page load for a pending ride.
     * If current offer is older than $timeoutSecs, expire it and re-dispatch.
     */
    public function checkAndRedispatch(int $rideId, int $timeoutSecs = 30): void
    {
        $ride = $this->qb->table('gtaxi_rides')->where('id','=',$rideId)->first();
        if (!$ride) return;
        if (in_array($ride['status'], ['driver_assigned','in_progress','completed','cancelled'], true)) {
            // Clean up any lingering pending offers for this closed ride
            $this->qb->table('gtaxi_driver_offers')
                ->where('ride_id','=',$rideId)
                ->where('status','=','pending')
                ->update(['status'=>'expired','responded_at'=>date('Y-m-d H:i:s')]);
            return;
        }

        $offer = $this->qb->table('gtaxi_driver_offers')
            ->where('ride_id','=',$rideId)
            ->where('status','=','pending')
            ->first();

        if (!$offer) {
            $this->dispatchDriver($rideId);
            return;
        }

        $elapsed = time() - strtotime((string)$offer['offered_at']);
        if ($elapsed >= $timeoutSecs) {
            $this->qb->table('gtaxi_driver_offers')
                ->where('id','=',(int)$offer['id'])
                ->update(['status'=>'expired','responded_at'=>date('Y-m-d H:i:s')]);
            $this->dispatchDriver($rideId);
        }
    }

    /**
     * Driver accepts the pending offer for a ride.
     */
    public function driverAcceptOffer(int $driverId, int $rideId): bool
    {
        $offer = $this->qb->table('gtaxi_driver_offers')
            ->where('ride_id','=',$rideId)
            ->where('driver_id','=',$driverId)
            ->where('status','=','pending')
            ->first();
        if (!$offer) return false;

        $this->qb->table('gtaxi_driver_offers')
            ->where('id','=',(int)$offer['id'])
            ->update(['status'=>'accepted','responded_at'=>date('Y-m-d H:i:s')]);

        $this->qb->table('gtaxi_rides')->where('id','=',$rideId)->update([
            'driver_id' => $driverId,
            'status'    => 'driver_assigned',
        ]);
        $this->qb->table('gtaxi_drivers')->where('id','=',$driverId)->update(['status'=>'busy']);
        return true;
    }

    /**
     * Driver declines the offer → immediately try another driver.
     */
    public function driverDeclineOffer(int $driverId, int $rideId): bool
    {
        $offer = $this->qb->table('gtaxi_driver_offers')
            ->where('ride_id','=',$rideId)
            ->where('driver_id','=',$driverId)
            ->where('status','=','pending')
            ->first();
        if (!$offer) return false;

        $this->qb->table('gtaxi_driver_offers')
            ->where('id','=',(int)$offer['id'])
            ->update(['status'=>'declined','responded_at'=>date('Y-m-d H:i:s')]);

        $this->dispatchDriver($rideId);
        return true;
    }

    /**
     * Get current pending offer for a ride (with expires_in seconds).
     */
    public function getCurrentOffer(int $rideId): ?array
    {
        $offer = $this->qb->table('gtaxi_driver_offers')
            ->where('ride_id','=',$rideId)
            ->where('status','=','pending')
            ->first();
        if (!$offer) return null;

        $elapsed = time() - strtotime((string)$offer['offered_at']);
        return [
            'offer_id'   => (int)$offer['id'],
            'driver'     => $this->getDriver((int)$offer['driver_id']),
            'offered_at' => $offer['offered_at'],
            'expires_in' => max(0, 30 - $elapsed),
        ];
    }

    /**
     * Get pending offer for a driver (includes ride details). Used in driver portal.
     */
    public function getPendingOfferForDriver(int $driverId): ?array
    {
        // Latest offer first — avoids stale offers for old/cancelled rides blocking new ones
        $offer = $this->qb->table('gtaxi_driver_offers')
            ->where('driver_id','=',$driverId)
            ->where('status','=','pending')
            ->orderBy('id','DESC')
            ->first();
        if (!$offer) return null;

        // Auto-expire offers for rides that are no longer active
        $ride = $this->qb->table('gtaxi_rides')
            ->where('id','=',(int)$offer['ride_id'])
            ->first();
        if (!$ride || in_array($ride['status'], ['completed','cancelled'], true)) {
            $this->qb->table('gtaxi_driver_offers')
                ->where('ride_id','=',(int)$offer['ride_id'])
                ->where('status','=','pending')
                ->update(['status'=>'expired','responded_at'=>date('Y-m-d H:i:s')]);
            return null;
        }

        $elapsed = time() - strtotime((string)$offer['offered_at']);
        return [
            'offer_id'   => (int)$offer['id'],
            'ride_id'    => (int)$offer['ride_id'],
            'ride'       => $this->getRide((int)$offer['ride_id']),
            'offered_at' => $offer['offered_at'],
            'expires_in' => max(0, 30 - $elapsed),
            'expired'    => $elapsed >= 30,
        ];
    }

    // ── Driver Location ───────────────────────────────────────────────────────
    public function updateDriverLocation(int $driverId, float $lat, float $lng, ?float $speedKph = null): void
    {
        $upd = [
            'current_lat'         => $lat,
            'current_lng'         => $lng,
            'location_updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($speedKph !== null) $upd['current_speed'] = round($speedKph, 1);
        $this->qb->table('gtaxi_drivers')->where('id','=',$driverId)->update($upd);
    }

    public function getDriverLocation(int $driverId): ?array
    {
        $d = $this->getDriver($driverId);
        if (!$d || !isset($d['current_lat']) || $d['current_lat'] === null) return null;
        $updated = strtotime((string)($d['location_updated_at'] ?? ''));
        return [
            'lat'       => (float)$d['current_lat'],
            'lng'       => (float)$d['current_lng'],
            'updated'   => $d['location_updated_at'],
            'fresh'     => $updated && (time() - $updated) < 90,
            'speed'     => isset($d['current_speed']) && $d['current_speed'] !== null ? (float)$d['current_speed'] : null,
            'name'      => $d['name'] ?? '',
            'car'       => $d['car_model']  ?? '',
            'car_color' => $d['car_color']  ?? '',
            'car_number'=> $d['car_number'] ?? '',
        ];
    }

    /**
     * Get the active ride for a driver (driver_assigned or in_progress).
     */
    public function getDriverActiveRide(int $driverId): ?array
    {
        $rows = $this->qb->table('gtaxi_rides')
            ->where('driver_id','=',$driverId)
            ->where('status','IN',['driver_assigned','in_progress'])
            ->get() ?: [];
        if (empty($rows)) return null;
        return $this->getRide((int)$rows[0]['id']);
    }

    public function getDriverLastCancelledRide(int $driverId): ?array
    {
        return $this->qb->table('gtaxi_rides')
            ->where('driver_id','=',$driverId)
            ->where('status','=','cancelled')
            ->where('cancelled_by','=','customer')
            ->orderBy('updated_at','DESC')
            ->first();
    }

    /**
     * ETA-based dispatch: score each driver by distance to pickup + heading bonus.
     * Drivers heading towards the pickup point get a 30% ETA discount.
     */
    private function pickRandomDriver(
        string $carType,
        array  $excludeIds,
        ?float $pickupLat = null,
        ?float $pickupLng = null,
        float  $maxKm     = 5.0,
    ): ?array {
        // Online + active drivers
        $all = $this->qb->table('gtaxi_drivers')
            ->where('status','=','active')
            ->where('is_online','=',1)
            ->get() ?: [];
        if (empty($all)) {
            $all = $this->qb->table('gtaxi_drivers')
                ->where('status','=','active')
                ->get() ?: [];
        }

        // Prefer drivers who recently polled (heartbeat)
        $cutoff = time() - 180;
        $recent = array_values(array_filter($all, function ($d) use ($cutoff): bool {
            return !empty($d['last_heartbeat_at'])
                && strtotime((string)$d['last_heartbeat_at']) >= $cutoff;
        }));
        if (!empty($recent)) $all = $recent;

        $available = array_values(array_filter($all, fn($d) => !in_array((int)$d['id'], $excludeIds, true)));
        if (empty($available)) return null;

        // Prefer matching car type
        $matching = array_values(array_filter($available, fn($d) => $d['car_type'] === $carType));
        $pool = $matching ?: $available;

        if ($pickupLat === null || $pickupLng === null) {
            return $pool[array_rand($pool)];
        }

        // Score by estimated distance (km) with heading bonus
        $scored = [];
        foreach ($pool as $d) {
            if (empty($d['current_lat']) || empty($d['current_lng'])) {
                $scored[] = ['driver' => $d, 'score' => 999.0];
                continue;
            }
            $dLat = (float)$d['current_lat'];
            $dLng = (float)$d['current_lng'];
            $km   = $this->haversineKm($dLat, $dLng, $pickupLat, $pickupLng);

            // Skip drivers beyond max radius (only if multiple GPS drivers exist)
            // heading bonus: if driver is moving towards pickup, reduce effective distance
            $headingBonus = 1.0;
            if (!empty($d['current_speed']) && (float)$d['current_speed'] > 5) {
                $bearingToPickup = $this->bearingDeg($dLat, $dLng, $pickupLat, $pickupLng);
                // We don't have actual heading, but speed indicates movement — give slight bonus
                $headingBonus = 0.85;
            }

            $scored[] = ['driver' => $d, 'score' => $km * $headingBonus];
        }

        // Sort by score ascending (nearest first)
        usort($scored, fn($a,$b) => $a['score'] <=> $b['score']);

        // Take top 3 candidates and pick randomly among them (reduces monopoly)
        $top = array_slice($scored, 0, min(3, count($scored)));

        // Filter out > maxKm if there are GPS drivers nearby
        $hasGps = array_filter($top, fn($x) => $x['score'] < 999);
        if (!empty($hasGps)) {
            $nearby = array_filter($top, fn($x) => $x['score'] <= $maxKm);
            if (!empty($nearby)) $top = array_values($nearby);
        }

        $chosen = $top[array_rand($top)];
        return $chosen['driver'];
    }

    private function bearingDeg(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1r = deg2rad($lat1); $lat2r = deg2rad($lat2);
        $dLng  = deg2rad($lng2 - $lng1);
        $y = sin($dLng) * cos($lat2r);
        $x = cos($lat1r) * sin($lat2r) - sin($lat1r) * cos($lat2r) * cos($dLng);
        return fmod(rad2deg(atan2($y, $x)) + 360, 360);
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371;
        $dLat = ($lat2 - $lat1) * M_PI / 180;
        $dLng = ($lng2 - $lng1) * M_PI / 180;
        $a    = sin($dLat / 2) ** 2 + cos($lat1 * M_PI / 180) * cos($lat2 * M_PI / 180) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function getTriedDriverIds(int $rideId): array
    {
        $offers = $this->qb->table('gtaxi_driver_offers')->where('ride_id','=',$rideId)->get() ?: [];
        return array_values(array_unique(array_map('intval', array_column($offers, 'driver_id'))));
    }

    // ── Tariffs ───────────────────────────────────────────────────────────────
    public function allTariffs(bool $activeOnly = false): array
    {
        $qb = $this->qb->table('gtaxi_tariffs');
        if ($activeOnly) $qb = $qb->where('active','=',1);
        return $qb->orderBy('priority','DESC')->orderBy('car_type','ASC')->get() ?: [];
    }
    public function getTariff(int $id): ?array { return $this->qb->table('gtaxi_tariffs')->where('id','=',$id)->first(); }
    public function createTariff(array $d): int { return (int)$this->qb->table('gtaxi_tariffs')->insert($d); }
    public function updateTariff(int $id, array $d): void { $this->qb->table('gtaxi_tariffs')->where('id','=',$id)->update($d); }
    public function deleteTariff(int $id): void { $this->qb->table('gtaxi_tariffs')->where('id','=',$id)->delete(); }

    /**
     * Find best active tariff for car type at current time/day of week.
     * Car-type-specific tariffs beat "all types" (empty car_type).
     */
    public function activeTariffFor(string $carType): ?array
    {
        $tariffs = $this->allTariffs(true);
        $now = date('H:i:s');
        $dow = (int)date('N'); // 1=Mon … 7=Sun

        $best = null;
        foreach ($tariffs as $t) {
            if ($t['car_type'] !== '' && $t['car_type'] !== $carType) continue;
            if (!str_contains((string)$t['days'], (string)$dow)) continue;
            if (!empty($t['time_from']) && !empty($t['time_to'])) {
                if ($now < $t['time_from'] || $now > $t['time_to']) continue;
            }
            if ($best === null) { $best = $t; continue; }
            $isSpecific = ($t['car_type'] === $carType && $best['car_type'] !== $carType);
            $isHigherPrio = ($t['car_type'] === $best['car_type']) && ((int)$t['priority'] > (int)$best['priority']);
            if ($isSpecific || $isHigherPrio) $best = $t;
        }
        return $best;
    }

    // ── Fare calculator ───────────────────────────────────────────────────────
    public function estimateFare(float $distanceKm, string $carType = 'sedan'): float
    {
        $tariff = $this->activeTariffFor($carType);

        if ($tariff) {
            $base    = (float)$tariff['base_fare'];
            $perKm   = (float)$tariff['price_per_km'];
            $minFare = (float)$tariff['min_fare'];
            $surge   = (float)$tariff['surge_multiplier'];
            return max($minFare, round(($base + $distanceKm * $perKm) * $surge, 2));
        }

        // Fallback: global settings + car type multiplier
        $base    = (float)$this->setting('base_fare','5');
        $perKm   = (float)$this->setting('price_per_km','1.5');
        $minFare = (float)$this->setting('min_fare','5');
        $multipliers = ['economy'=>0.8,'sedan'=>1.0,'suv'=>1.3,'minivan'=>1.4];
        $mult = $multipliers[$carType] ?? 1.0;
        return max($minFare, round(($base + $distanceKm * $perKm) * $mult, 2));
    }

    // ── Stats ─────────────────────────────────────────────────────────────────
    public function globalStats(): array
    {
        $total     = (int)($this->qb->table('gtaxi_rides')->count() ?? 0);
        $pending   = (int)($this->qb->table('gtaxi_rides')->where('status','=','pending')->count() ?? 0);
        $completed = (int)($this->qb->table('gtaxi_rides')->where('status','=','completed')->count() ?? 0);
        $completedRows = $this->qb->table('gtaxi_rides')->where('status','=','completed')->get() ?: [];
        $revenue = 0.0;
        foreach ($completedRows as $row) {
            $revenue += (float)($row['actual_price'] ?? $row['estimated_price'] ?? 0);
        }
        $platRevenue = round($revenue * $this->commissionPct() / 100, 2);
        $drivers   = (int)($this->qb->table('gtaxi_drivers')->where('status','IN',['active','busy'])->count() ?? 0);
        return compact('total','pending','completed','revenue','platRevenue','drivers');
    }

    // ── Customers ─────────────────────────────────────────────────────────────

    public function getCustomerActiveRide(string $phone): ?array
    {
        $rows = $this->qb->table('gtaxi_rides')
            ->where('customer_phone', '=', $phone)
            ->where('status', 'IN', ['pending','accepted','driver_assigned','in_progress'])
            ->orderBy('created_at', 'DESC')
            ->limit(1)
            ->get();
        return !empty($rows) ? $rows[0] : null;
    }

    public function getCustomerById(int $id): ?array
    {
        return $this->qb->table('gtaxi_customers')->where('id','=',$id)->first();
    }

    public function getCustomerByPhone(string $phone): ?array
    {
        return $this->qb->table('gtaxi_customers')->where('phone','=',$phone)->first();
    }

    public function createCustomer(string $name, string $phone, string $password, string $email = ''): int
    {
        return (int)$this->qb->table('gtaxi_customers')->insert([
            'name'          => $name,
            'phone'         => $phone,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }

    public function verifyCustomer(string $phone, string $password): ?array
    {
        $row  = $this->getCustomerByPhone($phone);
        $hash = $row['password_hash'] ?? '$2y$10$invalidhash000000000000000000000000000000000000000000000';
        if (!password_verify($password, (string)$hash)) return null;
        return $row;
    }

    // ── OTP Authentication ────────────────────────────────────────────────────

    public function generateOtp(string $phone): string
    {
        // Invalidate previous unused OTPs for this phone
        $this->qb->table('gtaxi_otp_codes')
            ->where('phone', '=', $phone)
            ->where('used',  '=', 0)
            ->update(['used' => 1]);

        $code    = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes

        $this->qb->table('gtaxi_otp_codes')->insert([
            'phone'      => $phone,
            'code'       => $code,
            'expires_at' => $expires,
            'used'       => 0,
            'attempts'   => 0,
        ]);

        // Hook for SMS gateway integration (production use)
        // gc_emit('taxi.otp.send', $phone, $code);

        return $code;
    }

    public function verifyOtp(string $phone, string $code): bool
    {
        $row = $this->qb->table('gtaxi_otp_codes')
            ->where('phone', '=', $phone)
            ->where('used',  '=', 0)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$row) return false;
        if (strtotime((string)$row['expires_at']) < time()) return false;
        if ((int)$row['attempts'] >= 5) return false;

        // Increment attempts
        $this->qb->table('gtaxi_otp_codes')
            ->where('id', '=', (int)$row['id'])
            ->update(['attempts' => (int)$row['attempts'] + 1]);

        if ((string)$row['code'] !== trim($code)) return false;

        // Mark used
        $this->qb->table('gtaxi_otp_codes')
            ->where('id', '=', (int)$row['id'])
            ->update(['used' => 1]);

        return true;
    }

    public function getOrCreateCustomerByPhone(string $phone, string $name = ''): array
    {
        $existing = $this->getCustomerByPhone($phone);
        if ($existing) return $existing;

        $id = $this->qb->table('gtaxi_customers')->insert([
            'name'          => $name ?: $phone,
            'phone'         => $phone,
            'email'         => '',
            'password_hash' => '',
        ]);

        return $this->getCustomerById((int)$id);
    }

    public function updateCustomerProfile(int $id, array $data): void
    {
        $allowed = ['name','email','home_address','home_lat','home_lng','work_address','work_lat','work_lng'];
        $update  = array_intersect_key($data, array_flip($allowed));
        if ($update) {
            $this->qb->table('gtaxi_customers')->where('id','=',$id)->update($update);
        }
    }

    // ── Driver Stats ──────────────────────────────────────────────────────────

    public function recalcDriverStats(int $driverId): void
    {
        $offers = $this->qb->table('gtaxi_driver_offers')
            ->where('driver_id', '=', $driverId)
            ->get() ?: [];

        $total    = count($offers);
        $accepted = count(array_filter($offers, fn($o) => $o['status'] === 'accepted'));
        $declined = count(array_filter($offers, fn($o) => $o['status'] === 'declined'));

        $acceptRate = $total > 0 ? round($accepted / $total * 100, 2) : 0.0;

        // Cancellation rate: rides started then cancelled by driver
        $cancelledRides = $this->qb->table('gtaxi_rides')
            ->where('driver_id', '=', $driverId)
            ->where('status', '=', 'cancelled')
            ->where('cancelled_by', '=', 'driver')
            ->count() ?? 0;
        $assignedRides = $this->qb->table('gtaxi_rides')
            ->where('driver_id', '=', $driverId)
            ->where('status', 'IN', ['completed','cancelled'])
            ->count() ?? 0;

        $cancelRate = $assignedRides > 0 ? round((int)$cancelledRides / (int)$assignedRides * 100, 2) : 0.0;

        $this->updateDriver($driverId, [
            'total_accepted'   => $accepted,
            'total_declined'   => $declined,
            'acceptance_rate'  => $acceptRate,
            'cancellation_rate'=> $cancelRate,
        ]);
    }

    public function getDriverEarningsBreakdown(int $driverId): array
    {
        $today   = date('Y-m-d');
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $monthStart = date('Y-m-01');

        $rows = $this->qb->table('gtaxi_rides')
            ->where('driver_id', '=', $driverId)
            ->where('status', '=', 'completed')
            ->get() ?: [];

        $todayEarn = $weekEarn = $monthEarn = $totalEarn = 0.0;
        $todayRides = $weekRides = $monthRides = 0;

        foreach ($rows as $r) {
            $earn = (float)($r['driver_earnings'] ?? 0);
            $date = substr((string)$r['updated_at'], 0, 10);
            $totalEarn += $earn;
            if ($date === $today)      { $todayEarn  += $earn; $todayRides++; }
            if ($date >= $weekAgo)     { $weekEarn   += $earn; $weekRides++; }
            if ($date >= $monthStart)  { $monthEarn  += $earn; $monthRides++; }
        }

        return [
            'today'       => round($todayEarn, 2),
            'today_rides' => $todayRides,
            'week'        => round($weekEarn, 2),
            'week_rides'  => $weekRides,
            'month'       => round($monthEarn, 2),
            'month_rides' => $monthRides,
            'total'       => round($totalEarn, 2),
        ];
    }

    // ── Driver Password ───────────────────────────────────────────────────────

    public function setDriverPassword(int $driverId, string $password): void
    {
        $this->updateDriver($driverId, ['password_hash' => password_hash($password, PASSWORD_BCRYPT)]);
    }

    public function verifyDriverByPhone(string $phone, string $password): ?array
    {
        $row  = $this->qb->table('gtaxi_drivers')->where('phone','=',$phone)->first();
        $hash = $row['password_hash'] ?? '$2y$10$invalidhash000000000000000000000000000000000000000000000';
        if (!$row || !password_verify($password, (string)$hash)) return null;
        return $row;
    }

    // ── Driver Notifications ──────────────────────────────────────────────────

    public function createDriverNotification(int $driverId, string $type, string $title, string $body): int
    {
        return (int)$this->qb->table('gtaxi_driver_notifications')->insert([
            'driver_id' => $driverId,
            'type'      => $type,
            'title'     => $title,
            'body'      => $body,
            'is_read'   => 0,
        ]);
    }

    public function getDriverNotifications(int $driverId, int $limit = 30): array
    {
        return $this->qb->table('gtaxi_driver_notifications')
            ->where('driver_id', '=', $driverId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get() ?: [];
    }

    public function countUnreadNotifications(int $driverId): int
    {
        return (int)($this->qb->table('gtaxi_driver_notifications')
            ->where('driver_id', '=', $driverId)
            ->where('is_read', '=', 0)
            ->count() ?? 0);
    }

    public function markAllNotificationsRead(int $driverId): void
    {
        $this->qb->table('gtaxi_driver_notifications')
            ->where('driver_id', '=', $driverId)
            ->where('is_read', '=', 0)
            ->update(['is_read' => 1]);
    }

    // ── Debug helpers ─────────────────────────────────────────────────────────

    public function getRecentOffersForDriver(int $driverId, int $limit = 10): array
    {
        return $this->qb->table('gtaxi_driver_offers')
            ->where('driver_id', '=', $driverId)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get() ?: [];
    }

    public function countPendingOffers(int $rideId): int
    {
        return (int)$this->qb->table('gtaxi_driver_offers')
            ->where('ride_id', '=', $rideId)
            ->count();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    public function generateNumber(): string
    {
        $last = $this->qb->table('gtaxi_rides')->orderBy('id','DESC')->first();
        $next = $last ? (int)$last['id'] + 1 : 1;
        return 'TX-'.date('Ym').'-'.str_pad((string)$next,4,'0',STR_PAD_LEFT);
    }
    public function formatPrice(float $p): string
    {
        return number_format($p,2,'.',',').$this->setting('currency_symbol','₾');
    }
    public function statusLabel(string $s): string
    {
        return match($s){
            'pending'         => '⏳ Pending',
            'accepted'        => '✅ Accepted',
            'driver_assigned' => '🧑‍✈️ Driver Assigned',
            'in_progress'     => '🚕 In Progress',
            'completed'       => '🏁 Completed',
            'cancelled'       => '❌ Cancelled',
            default           => ucfirst($s),
        };
    }
    public function statusColor(string $s): string
    {
        return match($s){
            'pending'         => '#f59e0b',
            'accepted'        => '#3b82f6',
            'driver_assigned' => '#8b5cf6',
            'in_progress'     => '#10b981',
            'completed'       => '#059669',
            'cancelled'       => '#ef4444',
            default           => '#94a3b8',
        };
    }
    public function allStatuses(): array
    {
        return ['pending','accepted','driver_assigned','in_progress','completed','cancelled'];
    }
    public function carTypes(): array
    {
        return ['economy'=>'💰 Economy','sedan'=>'🚗 Sedan','suv'=>'🚙 SUV','minivan'=>'🚐 Minivan'];
    }
    public function dayNames(): array
    {
        return [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
    }
}
