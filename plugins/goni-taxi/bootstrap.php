<?php
declare(strict_types=1);

use GoniTaxi\AdminController;
use GoniTaxi\FrontendController;
use GoniTaxi\TaxiService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Mail\MailService;
use GoniCore\Modules\Login\LoginService;

spl_autoload_register(function(string $class) use ($pluginDir): void {
    if (!str_starts_with($class,'GoniTaxi\\')) return;
    $file = $pluginDir.'/src/'.str_replace('\\','/',substr($class,9)).'.php';
    if (is_file($file)) require_once $file;
});

// ── v1 Migration ──────────────────────────────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gtaxi_rides'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        (require $pluginDir.'/database/migration.php')->up($conn);
    }
} catch(\Throwable) {}

// ── v2 Migration: driver offers + tariffs + driver_token column ───────────────
try {
    $conn = $container->get(Connection::class);

    // driver_token column
    $col = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gtaxi_drivers' AND COLUMN_NAME = 'driver_token'");
    if ((int)($col[0]['cnt'] ?? 0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_drivers` ADD COLUMN `driver_token` VARCHAR(64) NULL DEFAULT NULL UNIQUE AFTER `car_type`");
    }

    // gtaxi_driver_offers
    $of = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gtaxi_driver_offers'");
    if ((int)($of[0]['cnt'] ?? 0) === 0) {
        $conn->execute("
            CREATE TABLE `gtaxi_driver_offers` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `ride_id`      INT UNSIGNED NOT NULL,
                `driver_id`    INT UNSIGNED NOT NULL,
                `status`       ENUM('pending','accepted','declined','expired') NOT NULL DEFAULT 'pending',
                `offered_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `responded_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `gto_ride_idx`   (`ride_id`),
                INDEX `gto_driver_idx` (`driver_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // gtaxi_tariffs
    $tf = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gtaxi_tariffs'");
    if ((int)($tf[0]['cnt'] ?? 0) === 0) {
        $conn->execute("
            CREATE TABLE `gtaxi_tariffs` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`             VARCHAR(255) NOT NULL,
                `car_type`         VARCHAR(50)  NOT NULL DEFAULT '' COMMENT 'empty = all types',
                `base_fare`        DECIMAL(10,2) NOT NULL DEFAULT 5.00,
                `price_per_km`     DECIMAL(10,4) NOT NULL DEFAULT 1.5000,
                `min_fare`         DECIMAL(10,2) NOT NULL DEFAULT 5.00,
                `surge_multiplier` DECIMAL(5,2)  NOT NULL DEFAULT 1.00,
                `time_from`        TIME NULL DEFAULT NULL,
                `time_to`          TIME NULL DEFAULT NULL,
                `days`             VARCHAR(20) NOT NULL DEFAULT '1234567',
                `active`           TINYINT(1)  NOT NULL DEFAULT 1,
                `priority`         INT         NOT NULL DEFAULT 0,
                `created_at`       TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
} catch(\Throwable) {}

// ── Auto-create taxi page ─────────────────────────────────────────────────────
try {
    $qb   = $container->get(QueryBuilder::class);
    $flag = $qb->table('gtaxi_settings')->where('key','=','pages_created')->first();
    if (!$flag || $flag['value'] !== '1') {
        $taxiSlug = 'taxi';
        try { $s=$qb->table('gtaxi_settings')->where('key','=','page_slug')->first(); if($s) $taxiSlug=$s['value']; } catch(\Throwable){}
        if (!$qb->table('posts')->where('slug','=',$taxiSlug)->first()) {
            $user=$qb->table('users')->orderBy('id','ASC')->first();
            $qb->table('posts')->insert(['type'=>'page','title'=>'Taxi','slug'=>$taxiSlug,'content'=>'','status'=>'published','author_id'=>$user?(int)$user['id']:1]);
        }
        if ($flag) $qb->table('gtaxi_settings')->where('key','=','pages_created')->update(['value'=>'1']);
        else $qb->table('gtaxi_settings')->insert(['key'=>'pages_created','value'=>'1']);
    }
} catch(\Throwable) {}

// ── v_phase1 Migration: OTP codes + customer saved addresses + driver stats ───
try {
    $conn = $container->get(Connection::class);

    // OTP codes table
    $co = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_otp_codes'");
    if ((int)($co[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gtaxi_otp_codes` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `phone`      VARCHAR(50) NOT NULL,
                `code`       VARCHAR(10) NOT NULL,
                `expires_at` TIMESTAMP NOT NULL,
                `used`       TINYINT(1) NOT NULL DEFAULT 0,
                `attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gotp_phone` (`phone`, `used`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Customer saved addresses
    $ch = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_customers' AND COLUMN_NAME='home_address'");
    if ((int)($ch[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_customers`
            ADD COLUMN `home_address` VARCHAR(500) NOT NULL DEFAULT '',
            ADD COLUMN `home_lat`     DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `home_lng`     DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `work_address` VARCHAR(500) NOT NULL DEFAULT '',
            ADD COLUMN `work_lat`     DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `work_lng`     DECIMAL(10,7) NULL DEFAULT NULL
        ");
    }

    // Driver acceptance/cancellation rate
    $cdr = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_drivers' AND COLUMN_NAME='total_accepted'");
    if ((int)($cdr[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_drivers`
            ADD COLUMN `total_accepted`     INT UNSIGNED NOT NULL DEFAULT 0,
            ADD COLUMN `total_declined`     INT UNSIGNED NOT NULL DEFAULT 0,
            ADD COLUMN `acceptance_rate`    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            ADD COLUMN `cancellation_rate`  DECIMAL(5,2) NOT NULL DEFAULT 0.00
        ");
    }
} catch(\Throwable) {}

// ── v_notif Migration: driver notifications ───────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $cn = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_driver_notifications'");
    if ((int)($cn[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gtaxi_driver_notifications` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `driver_id`  INT UNSIGNED NOT NULL,
                `type`       VARCHAR(50) NOT NULL DEFAULT 'general',
                `title`      VARCHAR(255) NOT NULL,
                `body`       TEXT NOT NULL,
                `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gdn_driver` (`driver_id`, `is_read`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
} catch(\Throwable) {}

// ── DI ────────────────────────────────────────────────────────────────────────
$container->singleton(TaxiService::class, static fn($c) => new TaxiService($c->get(QueryBuilder::class)));
$container->bind(AdminController::class, static fn($c) => new AdminController(
    $c->get(TaxiService::class), $c->get(QueryBuilder::class),
    $c->get(LoginService::class), $c->get(HookManager::class),
    $c->get(MailService::class),
    (string)$c->get(\GoniCore\Core\Config\Config::class)->get('app.name','GoniCore'),
));
$container->bind(FrontendController::class, static fn($c) => new FrontendController($c->get(TaxiService::class)));

// ── Admin routes ──────────────────────────────────────────────────────────────
$router->group('/manage/taxi', static function($r) use ($container): void {
    $r->get('',                          [AdminController::class, 'dashboard']);
    $r->get('/rides',                    [AdminController::class, 'rides']);
    $r->get('/rides/{id}',               [AdminController::class, 'rideView']);
    $r->post('/rides/{id}/update',       [AdminController::class, 'rideUpdate']);
    $r->post('/rides/{id}/dispatch',     [AdminController::class, 'rideDispatch']);
    $r->get('/drivers',                  [AdminController::class, 'drivers']);
    $r->post('/drivers/create',          [AdminController::class, 'driverCreate']);
    $r->post('/drivers/{id}/update',     [AdminController::class, 'driverUpdate']);
    $r->post('/drivers/{id}/delete',     [AdminController::class, 'driverDelete']);
    $r->post('/drivers/{id}/token',      [AdminController::class, 'driverRegenToken']);
    $r->get('/routes',                   [AdminController::class, 'routes']);
    $r->post('/routes/create',           [AdminController::class, 'routeCreate']);
    $r->post('/routes/{id}/update',      [AdminController::class, 'routeUpdate']);
    $r->post('/routes/{id}/delete',      [AdminController::class, 'routeDelete']);
    $r->get('/tariffs',                  [AdminController::class, 'tariffs']);
    $r->post('/tariffs/create',          [AdminController::class, 'tariffCreate']);
    $r->post('/tariffs/{id}/update',     [AdminController::class, 'tariffUpdate']);
    $r->post('/tariffs/{id}/delete',     [AdminController::class, 'tariffDelete']);
    $r->get('/settings',                  [AdminController::class, 'settingsForm']);
    $r->post('/settings',                 [AdminController::class, 'settingsSave']);
    $r->get('/settlements',               [AdminController::class, 'settlements']);
    $r->post('/settlements/create',       [AdminController::class, 'settlementCreate']);
    $r->post('/settlements/reminder',     [AdminController::class, 'settlementReminderManual']);
    $r->post('/settlements/{id}/paid',    [AdminController::class, 'settlementMarkPaid']);
    $r->get('/settlements/export-csv',    [AdminController::class, 'settlementExportCsv']);
    $r->get('/settlements/export-xml',    [AdminController::class, 'settlementExportXml']);
    $r->post('/drivers/{id}/bank',        [AdminController::class, 'driverUpdateBank']);
    $r->get('/livemap',                   [AdminController::class, 'liveMap']);
});

// ── v3 Migration: driver location columns ─────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $cl = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gtaxi_drivers' AND COLUMN_NAME = 'current_lat'");
    if ((int)($cl[0]['cnt'] ?? 0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_drivers`
            ADD COLUMN `current_lat`         DECIMAL(10,7)  NULL DEFAULT NULL,
            ADD COLUMN `current_lng`         DECIMAL(10,7)  NULL DEFAULT NULL,
            ADD COLUMN `location_updated_at` TIMESTAMP      NULL DEFAULT NULL
        ");
    }
} catch(\Throwable) {}

// ── v15 Migration: driver personal_id + bank_code ────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $cp = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_drivers' AND COLUMN_NAME='personal_id'");
    if ((int)($cp[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_drivers`
            ADD COLUMN `personal_id` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'INN / საიდენტიფიკაციო კოდი',
            ADD COLUMN `bank_code`   VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'ბანკის კოდი'
        ");
    }
} catch(\Throwable) {}

// ── v14 Migration: settlements + driver bank fields ──────────────────────────
try {
    $conn = $container->get(Connection::class);

    // Driver bank account fields
    $cb = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_drivers' AND COLUMN_NAME='bank_account'");
    if ((int)($cb[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_drivers`
            ADD COLUMN `bank_account` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'IBAN',
            ADD COLUMN `bank_name`    VARCHAR(100) NOT NULL DEFAULT ''
        ");
    }

    // Settlements table
    $cs = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_settlements'");
    if ((int)($cs[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gtaxi_settlements` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `driver_id`    INT UNSIGNED NOT NULL,
                `rides_count`  INT UNSIGNED NOT NULL DEFAULT 0,
                `gross_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `commission`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `net_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `period_from`  DATE NULL DEFAULT NULL,
                `period_to`    DATE NULL DEFAULT NULL,
                `bank_account` VARCHAR(100) NOT NULL DEFAULT '',
                `bank_name`    VARCHAR(100) NOT NULL DEFAULT '',
                `status`       ENUM('pending','processing','paid','failed') NOT NULL DEFAULT 'pending',
                `bank_ref`     VARCHAR(255) NOT NULL DEFAULT '',
                `note`         TEXT NOT NULL DEFAULT (''),
                `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `paid_at`      TIMESTAMP NULL DEFAULT NULL,
                INDEX `gs_driver` (`driver_id`),
                INDEX `gs_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
} catch(\Throwable) {}

// ── v13 Migration: track_token on rides ──────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $ct = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_rides' AND COLUMN_NAME='track_token'");
    if ((int)($ct[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_rides` ADD COLUMN `track_token` VARCHAR(32) NULL DEFAULT NULL UNIQUE AFTER `ride_number`");
    }
    // Populate existing rides that have no track_token
    $noTok = $conn->query("SELECT id FROM gtaxi_rides WHERE track_token IS NULL OR track_token = '' LIMIT 500");
    foreach ($noTok as $row) {
        $tok = bin2hex(random_bytes(16));
        $conn->execute("UPDATE `gtaxi_rides` SET `track_token` = '{$tok}' WHERE `id` = {$row['id']}");
    }
} catch(\Throwable) {}

// ── v12 Migration: waiting_fee column ────────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $cf = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_rides' AND COLUMN_NAME='waiting_fee'");
    if ((int)($cf[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_rides` ADD COLUMN `waiting_fee` DECIMAL(10,2) NULL DEFAULT NULL AFTER `waiting_seconds`");
    }
} catch(\Throwable) {}

// ── v11 Migration: ride waiting columns ──────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $cw = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_rides' AND COLUMN_NAME='waiting_started_at'");
    if ((int)($cw[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_rides`
            ADD COLUMN `waiting_started_at` TIMESTAMP NULL DEFAULT NULL,
            ADD COLUMN `waiting_seconds`    INT UNSIGNED NOT NULL DEFAULT 0
        ");
    }
} catch(\Throwable) {}

// ── v10 Migration: driver current_speed ──────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $cs = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_drivers' AND COLUMN_NAME='current_speed'");
    if ((int)($cs[0]['cnt'] ?? 0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_drivers` ADD COLUMN `current_speed` DECIMAL(6,2) NULL DEFAULT NULL");
    }
} catch(\Throwable) {}

// ── v5 Migration: commission & balance ───────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    // driver_earnings on rides
    $c1 = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_rides' AND COLUMN_NAME='driver_earnings'");
    if ((int)($c1[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_rides` ADD COLUMN `driver_earnings` DECIMAL(10,2) NULL DEFAULT NULL");
    }
    // balance on drivers
    $c2 = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_drivers' AND COLUMN_NAME='balance'");
    if ((int)($c2[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_drivers` ADD COLUMN `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    }
    // default commission setting
    $conn->execute("INSERT IGNORE INTO `gtaxi_settings` (`key`,`value`) VALUES ('commission_pct','20')");
} catch(\Throwable) {}

// ── v4 Migration: ride coordinate columns ────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $cl = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gtaxi_rides' AND COLUMN_NAME = 'pickup_lat'");
    if ((int)($cl[0]['cnt'] ?? 0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_rides`
            ADD COLUMN `pickup_lat` DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `pickup_lng` DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `dest_lat`   DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `dest_lng`   DECIMAL(10,7) NULL DEFAULT NULL
        ");
    }
} catch(\Throwable) {}

// ── v9 Migration: driver heartbeat ───────────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $c = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_drivers' AND COLUMN_NAME='last_heartbeat_at'");
    if ((int)($c[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_drivers` ADD COLUMN `last_heartbeat_at` TIMESTAMP NULL DEFAULT NULL");
    }
} catch(\Throwable) {}

// ── v8 Migration: driver car_color ───────────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $c = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_drivers' AND COLUMN_NAME='car_color'");
    if ((int)($c[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_drivers` ADD COLUMN `car_color` VARCHAR(50) NOT NULL DEFAULT '' AFTER `car_number`");
    }
} catch(\Throwable) {}

// ── v7 Migration: customers table + driver password ──────────────────────────
try {
    $conn = $container->get(Connection::class);
    // Customers table
    $conn->execute("CREATE TABLE IF NOT EXISTS `gtaxi_customers` (
        `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name`          VARCHAR(255) NOT NULL DEFAULT '',
        `phone`         VARCHAR(50)  NOT NULL UNIQUE,
        `email`         VARCHAR(255) NOT NULL DEFAULT '',
        `password_hash` VARCHAR(255) NOT NULL,
        `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // Driver password column
    $c1 = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_drivers' AND COLUMN_NAME='password_hash'");
    if ((int)($c1[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_drivers` ADD COLUMN `password_hash` VARCHAR(255) NOT NULL DEFAULT '' AFTER `name`");
    }
} catch(\Throwable) {}

// ── v6 Migration: rating, cancel, driver online ──────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $c1 = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_rides' AND COLUMN_NAME='rating'");
    if ((int)($c1[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_rides`
            ADD COLUMN `rating`        TINYINT UNSIGNED NULL DEFAULT NULL,
            ADD COLUMN `cancelled_by`  VARCHAR(20) NULL DEFAULT NULL,
            ADD COLUMN `cancel_reason` VARCHAR(255) NOT NULL DEFAULT ''
        ");
    }
    $c2 = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gtaxi_drivers' AND COLUMN_NAME='is_online'");
    if ((int)($c2[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gtaxi_drivers`
            ADD COLUMN `is_online`   TINYINT(1)    NOT NULL DEFAULT 0,
            ADD COLUMN `avg_rating`  DECIMAL(3,2)  NOT NULL DEFAULT 0.00,
            ADD COLUMN `total_trips` INT UNSIGNED  NOT NULL DEFAULT 0
        ");
    }
} catch(\Throwable) {}

// ── Frontend routes ───────────────────────────────────────────────────────────
try { $_slug = $container->get(TaxiService::class)->setting('page_slug','taxi'); }
catch(\Throwable) { $_slug = 'taxi'; }

// Customer auth
$router->get('/'.$_slug.'/auth',                                  [FrontendController::class, 'customerAuthPage']);
$router->post('/'.$_slug.'/auth/login',                           [FrontendController::class, 'customerLogin']);
$router->post('/'.$_slug.'/auth/register',                        [FrontendController::class, 'customerRegister']);
$router->get('/'.$_slug.'/auth/logout',                           [FrontendController::class, 'customerLogout']);
// Booking & tracking (require customer login)
$router->get('/'.$_slug,                                          [FrontendController::class, 'index']);
$router->post('/'.$_slug.'/book',                                 [FrontendController::class, 'book']);
$router->post('/'.$_slug.'/estimate',                             [FrontendController::class, 'estimate']);
$router->get('/taxi/track/{number}',                              [FrontendController::class, 'track']);
$router->post('/taxi/track/{number}/cancel',                      [FrontendController::class, 'cancelRide']);
$router->post('/taxi/track/{number}/rate',                        [FrontendController::class, 'rateRide']);
$router->post('/taxi/bog-callback',                               [FrontendController::class, 'bogCallback']);
// Driver auth (session-based)
$router->get('/taxi/driver/login',                                [FrontendController::class, 'driverAuthPage']);
$router->post('/taxi/driver/login',                               [FrontendController::class, 'driverLogin']);
$router->get('/taxi/driver/logout',                               [FrontendController::class, 'driverLogout']);
$router->get('/taxi/driver',                                      [FrontendController::class, 'driverPortalSession']);
// Driver portal via token (backward compat)
$router->get('/taxi/driver/{token}',                              [FrontendController::class, 'driverPortal']);
$router->post('/taxi/driver/{token}/accept/{ride_id}',            [FrontendController::class, 'driverAccept']);
$router->post('/taxi/driver/{token}/decline/{ride_id}',           [FrontendController::class, 'driverDecline']);
$router->post('/taxi/driver/{token}/start/{ride_id}',             [FrontendController::class, 'driverStartRide']);
$router->post('/api/taxi/driver-waiting/{token}/{ride_id}',       [FrontendController::class, 'apiDriverWaiting']);
$router->post('/taxi/driver/{token}/complete/{ride_id}',          [FrontendController::class, 'driverCompleteRide']);
// Location APIs
$router->post('/api/taxi/driver-location/{token}',                [FrontendController::class, 'apiUpdateDriverLocation']);
$router->get('/api/taxi/driver-location/{number}',                [FrontendController::class, 'apiGetDriverLocation']);
// Polling APIs
$router->get('/api/taxi/driver-offer/{token}',                    [FrontendController::class, 'apiDriverOffer']);
$router->get('/api/taxi/driver-ride-status/{token}',              [FrontendController::class, 'apiDriverRideStatus']);
$router->get('/api/taxi/ride-status/{number}',                    [FrontendController::class, 'apiRideStatus']);
$router->post('/api/taxi/driver-online/{token}',                  [FrontendController::class, 'apiDriverToggleOnline']);
$router->get('/api/taxi/online-drivers',                          [FrontendController::class, 'apiOnlineDrivers']);
$router->get('/api/taxi/debug/{token}',                           [FrontendController::class, 'apiDebugInfo']);
// OTP Auth
$router->post('/api/taxi/otp/send',                               [FrontendController::class, 'apiOtpSend']);
$router->post('/api/taxi/otp/verify',                             [FrontendController::class, 'apiOtpVerify']);
// Customer profile
$router->get('/taxi/profile',                                     [FrontendController::class, 'customerProfile']);
$router->post('/taxi/profile/update',                             [FrontendController::class, 'customerProfileUpdate']);
$router->get('/api/taxi/livemap-data',                            [FrontendController::class, 'apiLiveMapData']);
$router->get('/api/taxi/driver-notifications/{token}',            [FrontendController::class, 'apiDriverNotifications']);
$router->post('/api/taxi/driver-notifications/{token}/read-all',  [FrontendController::class, 'apiDriverNotificationsReadAll']);

unset($_slug);

// ── Sidebar ───────────────────────────────────────────────────────────────────
gc_on('manage.sidebar.nav', static function(string $base, string $activeNav): void {
    $h      = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isTaxi = str_starts_with($activeNav, 'taxi');
    $open   = $isTaxi ? ' open' : '';
    $sub    = static function(string $url, string $icon, string $label, string $key) use ($h, $activeNav): string {
        $cls = $activeNav === $key ? ' active' : '';
        return '<li class="nav-sub"><a href="'.$h($url).'" class="'.$cls.'">'
             . '<span class="nav-icon">'.$icon.'</span> '.$label.'</a></li>';
    };
    // Parent toggle (not a link — just opens/closes sub-menu)
    echo '<li>'
       . '<div class="nav-parent-toggle'.$open.'" onclick="navToggle(this)">'
       . '<span class="nav-icon">🚕</span> GoniTaxi'
       . '<span class="nav-arrow">▾</span>'
       . '</div>'
       . '<ul class="nav-children'.$open.'">'
       . $sub($base.'/manage/taxi',          '📊', 'Dashboard', 'taxi')
       . $sub($base.'/manage/taxi/rides',    '🗒', 'Rides',     'taxi-rides')
       . $sub($base.'/manage/taxi/drivers',  '🧑', 'Drivers',   'taxi-drivers')
       . $sub($base.'/manage/taxi/routes',   '🗺', 'Routes',    'taxi-routes')
       . $sub($base.'/manage/taxi/tariffs',  '💵', 'Tariffs',   'taxi-tariffs')
       . $sub($base.'/manage/taxi/settings',     '⚙',  'Settings',    'taxi-settings')
       . $sub($base.'/manage/taxi/settlements', '💳', 'Settlement',  'taxi-settlements')
       . $sub($base.'/manage/taxi/livemap',    '🗺', 'Live Map',    'taxi-livemap')
       . '</ul></li>';
}, 35);

gc_filter('page.intercept', static function(mixed $ex, array $post, \GoniCore\Core\Http\Request $request) use ($container): mixed {
    try {
        $s=$container->get(TaxiService::class)->setting('page_slug','taxi');
        if ($post['slug']===$s) return \GoniCore\Core\Http\Response::redirect($request->basePath().'/'.$s);
    } catch(\Throwable){}
    return $ex;
});
