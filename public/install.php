<?php

declare(strict_types=1);

/**
 * GoniCore Web Installer
 * ──────────────────────────────────────────────────────────────
 * Self-contained — requires NO vendor/ autoloader.
 * Place this file in the public/ directory and open it in your browser.
 *
 * After installation this file locks itself.
 * Delete public/install.php manually for maximum security.
 */

$projectRoot = dirname(__DIR__);
$lockFile    = $projectRoot . '/.installed';
$envFile     = $projectRoot . '/.env';

// ── Already installed? ────────────────────────────────────────
// "Installed" == configured, i.e. .env exists. This stays in sync with
// public/index.php, which sends visitors here whenever .env is missing —
// so deleting .env lets the wizard run again (re-configure / recover).
if (is_file($envFile)) {
    http_response_code(403);
    die(render('locked', []));
}

session_start();

// ── CSRF ──────────────────────────────────────────────────────
if (empty($_SESSION['gc_csrf'])) {
    $_SESSION['gc_csrf'] = bin2hex(random_bytes(24));
}

function csrfToken(): string
{
    return $_SESSION['gc_csrf'];
}

function verifyCsrf(): void
{
    $t = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['gc_csrf'], $t)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

// ── State helpers ─────────────────────────────────────────────
$step   = (int) ($_SESSION['gc_step'] ?? 1);
$saved  = (array) ($_SESSION['gc_data'] ?? []);
$errors = [];

// ── POST handling ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = (string) ($_POST['action'] ?? '');

    // ── Step 1 → 2: requirements check ───────────────────────
    if ($action === 'next_requirements') {
        $ok = true;
        foreach (getRequirements($projectRoot) as $r) {
            if (!$r['pass']) { $ok = false; break; }
        }
        if ($ok) {
            $_SESSION['gc_step'] = $step = 2;
        } else {
            $errors[] = 'Fix the failing requirements before continuing.';
        }
    }

    // ── Step 2 → 3: database ─────────────────────────────────
    elseif ($action === 'next_database') {
        $db = [
            'db_host'    => trim($_POST['db_host']    ?? '127.0.0.1'),
            'db_port'    => trim($_POST['db_port']    ?? '3306'),
            'db_name'    => trim($_POST['db_name']    ?? ''),
            'db_user'    => trim($_POST['db_user']    ?? 'root'),
            'db_pass'    => $_POST['db_pass']         ?? '',
            'db_charset' => 'utf8mb4',
        ];

        if ($db['db_name'] === '') {
            $errors[] = 'Database name is required.';
            $step = 2;
        } else {
            $connErr = dbConnect($db, withDbName: false);
            if ($connErr !== null) {
                $errors[] = 'Connection failed: ' . $connErr;
                $step = 2;
            } else {
                $_SESSION['gc_data'] = array_merge($saved, $db);
                $_SESSION['gc_step'] = $step = 3;
                $saved = $_SESSION['gc_data'];
            }
        }
    }

    // ── Step 3 → 4: install ───────────────────────────────────
    elseif ($action === 'run_install') {
        $mailDriver = (($_POST['mail_driver'] ?? 'php') === 'smtp') ? 'smtp' : 'php';
        $mailEnc    = in_array($_POST['mail_smtp_encryption'] ?? 'tls', ['tls', 'ssl', ''], true)
                        ? (string) ($_POST['mail_smtp_encryption'] ?? 'tls') : 'tls';
        $app = [
            'app_name'       => trim($_POST['app_name']       ?? 'GoniCore'),
            'app_url'        => rtrim(trim($_POST['app_url']  ?? ''), '/'),
            'admin_name'     => trim($_POST['admin_name']     ?? ''),
            'admin_email'    => trim($_POST['admin_email']    ?? ''),
            'admin_password' => $_POST['admin_password']      ?? '',
            // Optional email/SMTP — seeded into settings and used for the welcome mail.
            'mail_driver'          => $mailDriver,
            'mail_from_address'    => trim($_POST['mail_from_address'] ?? ''),
            'mail_from_name'       => trim($_POST['mail_from_name']    ?? ''),
            'mail_smtp_host'       => trim($_POST['mail_smtp_host']    ?? ''),
            'mail_smtp_port'       => (string) (int) ($_POST['mail_smtp_port'] ?? 587),
            'mail_smtp_user'       => trim($_POST['mail_smtp_user']    ?? ''),
            'mail_smtp_pass'       => (string) ($_POST['mail_smtp_pass'] ?? ''),
            'mail_smtp_encryption' => $mailEnc,
        ];

        // Validate
        if ($app['admin_name'] === '')                              { $errors[] = 'Admin name is required.'; }
        if (!filter_var($app['admin_email'], FILTER_VALIDATE_EMAIL)){ $errors[] = 'Admin email is not valid.'; }
        if (strlen($app['admin_password']) < 8)                    { $errors[] = 'Password must be at least 8 characters.'; }
        if ($app['app_url'] === '')                                 { $errors[] = 'Application URL is required.'; }
        if ($app['mail_driver'] === 'smtp' && $app['mail_smtp_host'] === '') { $errors[] = 'SMTP host is required when the mail driver is SMTP.'; }
        if ($app['mail_from_address'] !== '' && !filter_var($app['mail_from_address'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'The "from" email address is not valid.'; }

        if (empty($errors)) {
            $all = array_merge($saved, $app);
            $mailSent   = false;
            $installErr = runInstall($all, $projectRoot, $mailSent);

            if ($installErr === null) {
                // Lock the installer
                file_put_contents($lockFile, date('c') . PHP_EOL);
                session_destroy();
                $_SESSION = [];
                die(render('complete', ['app_url' => $all['app_url'], 'admin_email' => $all['admin_email'], 'mail_sent' => $mailSent]));
            } else {
                $errors[] = $installErr;
                $step = 3;
            }
        } else {
            $step = 3;
        }
    }
}

// ── Business logic ────────────────────────────────────────────

function getRequirements(string $root): array
{
    $reqs = [];

    $reqs[] = ['label' => 'PHP 8.2+',             'value' => PHP_VERSION,    'pass' => version_compare(PHP_VERSION, '8.2.0', '>=')];
    $reqs[] = ['label' => 'extension: pdo_mysql',  'value' => ext('pdo_mysql')  ? 'Loaded' : 'Missing', 'pass' => ext('pdo_mysql')];
    $reqs[] = ['label' => 'extension: mbstring',   'value' => ext('mbstring')   ? 'Loaded' : 'Missing', 'pass' => ext('mbstring')];
    $reqs[] = ['label' => 'extension: json',       'value' => ext('json')       ? 'Loaded' : 'Missing', 'pass' => ext('json')];
    $reqs[] = ['label' => 'extension: openssl',    'value' => ext('openssl')    ? 'Loaded' : 'Missing', 'pass' => ext('openssl')];
    $reqs[] = ['label' => 'extension: fileinfo',   'value' => ext('fileinfo')   ? 'Loaded' : 'Missing', 'pass' => ext('fileinfo')];
    $reqs[] = ['label' => 'Root directory writable','value' => is_writable($root) ? 'Writable' : 'Not writable', 'pass' => is_writable($root)];

    $storageOk = is_writable($root . '/storage') || !is_dir($root . '/storage') && is_writable($root);
    $reqs[] = ['label' => 'storage/ writable',     'value' => $storageOk ? 'OK' : 'Not writable', 'pass' => $storageOk];

    return $reqs;
}

function ext(string $name): bool
{
    return extension_loaded($name);
}

function dbConnect(array $cfg, bool $withDbName): ?string
{
    try {
        $dsn = "mysql:host={$cfg['db_host']};port={$cfg['db_port']};charset={$cfg['db_charset']}";
        if ($withDbName) {
            $dsn .= ";dbname={$cfg['db_name']}";
        }
        $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        // Try creating the DB if it doesn't exist
        if (!$withDbName) {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
        return null;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

function runInstall(array $d, string $root, bool &$mailSent = false): ?string
{
    try {
        // 1. Storage directories
        foreach (['storage', 'storage/media', 'storage/logs'] as $rel) {
            $dir = $root . '/' . $rel;
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                return "Cannot create directory: {$rel}";
            }
        }

        // 2. Connect with DB name
        $dsn = "mysql:host={$d['db_host']};port={$d['db_port']};dbname={$d['db_name']};charset={$d['db_charset']}";
        $pdo = new PDO($dsn, $d['db_user'], $d['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // 3. Migrations (inline SQL — full core schema, NO plugin tables;
        //    plugins create their own tables when activated in the panel)
        migrate($pdo, $d);

        // 4. Admin user
        $pdo->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES (?, ?, ?, 'admin')")
            ->execute([$d['admin_name'], $d['admin_email'], password_hash($d['admin_password'], PASSWORD_BCRYPT)]);

        // 4b. Welcome notification — shown in the admin bell on first login
        //     (broadcast: user_id = NULL reaches every admin).
        try {
            $pdo->prepare(
                "INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `icon`)
                 VALUES (NULL, 'system', ?, ?, ?)"
            )->execute([
                'Installation complete',
                'Your site "' . ($d['app_name'] ?: 'GoniCore') . '" was installed successfully. Welcome to GoniCore!',
                '🎉',
            ]);
        } catch (\Throwable) {
            // A notification failure must never break installation.
        }

        // 5. .env file
        writeEnv($d, $root);

        // 6. Disable every bundled plugin — a fresh install ships with all
        //    plugins OFF. The admin activates the ones they need from the panel
        //    (activation runs each plugin's migration). This also avoids a slow
        //    first request trying to create every plugin's tables at once.
        disableAllPlugins($root);

        // 7. Notify the admin by email (best-effort — never blocks the install).
        //    Native mail() often fails on shared hosting (no MTA / SMTP-only);
        //    we report the outcome so the completion screen can guide the admin.
        $mailSent = sendAdminWelcomeEmail($d);

        return null;
    } catch (Throwable $e) {
        return $e->getMessage();
    }
}

/**
 * Send a "installation complete" email to the admin via PHP's native mail().
 * The installer is self-contained (no SMTP/MailService), so we use mail() and
 * silently ignore any failure so installation always finishes.
 */
function sendAdminWelcomeEmail(array $d): bool
{
    try {
        $to = trim((string) ($d['admin_email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

        $appName = ($d['app_name'] ?? '') !== '' ? (string) $d['app_name'] : 'GoniCore';
        $appUrl  = rtrim((string) ($d['app_url'] ?? ''), '/');
        $name    = htmlspecialchars((string) ($d['admin_name'] ?? ''), ENT_QUOTES);
        $manage  = htmlspecialchars($appUrl . '/manage', ENT_QUOTES);
        $appUrlE = htmlspecialchars($appUrl, ENT_QUOTES);
        $host    = parse_url($appUrl, PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $appNameE = htmlspecialchars($appName, ENT_QUOTES);

        $body = '<!DOCTYPE html><html><body style="margin:0;background:#f1f5f9;font-family:system-ui,-apple-system,Segoe UI,sans-serif">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 0"><tr><td align="center">'
            . '<table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06)">'
            . '<tr><td style="background:linear-gradient(135deg,#0f172a,#1e1b4b);padding:28px 32px;color:#fff">'
            . '<div style="font-size:20px;font-weight:800">' . $appNameE . '</div>'
            . '<div style="font-size:13px;color:#94a3b8;margin-top:4px">Installation complete 🎉</div></td></tr>'
            . '<tr><td style="padding:28px 32px;color:#0f172a;font-size:14px;line-height:1.7">'
            . '<p style="margin:0 0 14px">Hello <strong>' . $name . '</strong>,</p>'
            . '<p style="margin:0 0 14px">Your site <strong>' . $appNameE . '</strong> has been installed successfully and is ready to use.</p>'
            . '<table cellpadding="0" cellspacing="0" style="width:100%;font-size:13.5px;margin:8px 0 18px">'
            . '<tr><td style="padding:6px 12px 6px 0;color:#64748b;white-space:nowrap">Admin email</td><td style="padding:6px 0">' . htmlspecialchars($to, ENT_QUOTES) . '</td></tr>'
            . '<tr><td style="padding:6px 12px 6px 0;color:#64748b">Site URL</td><td style="padding:6px 0"><a href="' . $appUrlE . '" style="color:#10B27C">' . $appUrlE . '</a></td></tr>'
            . '</table>'
            . '<a href="' . $manage . '" style="display:inline-block;background:#10B27C;color:#fff;text-decoration:none;padding:11px 26px;border-radius:8px;font-weight:600;font-size:14px">Open Admin Panel</a>'
            . '<div style="margin-top:22px;background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:12px 14px;font-size:12.5px;color:#713f12">'
            . '⚠️ For security, delete <code>public/install.php</code> from the server. All plugins start <strong>disabled</strong> — activate the ones you need from Manage → Plugins.</div>'
            . '</td></tr>'
            . '<tr><td style="padding:16px 32px;border-top:1px solid #e2e8f0;color:#94a3b8;font-size:12px">&copy; ' . date('Y') . ' ' . $appNameE . '</td></tr>'
            . '</table></td></tr></table></body></html>';

        $subjectRaw = $appName . ' — installation complete';

        // Prefer SMTP when the admin configured it during install — native mail()
        // is usually disabled on shared hosting.
        if (($d['mail_driver'] ?? 'php') === 'smtp' && trim((string) ($d['mail_smtp_host'] ?? '')) !== '') {
            return smtpSendInstall($d, $to, $subjectRaw, $body);
        }

        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $appName . ' <noreply@' . $host . '>',
        ]);

        // Encode the subject so non-ASCII site names survive in mail clients.
        $subject = '=?UTF-8?B?' . base64_encode($subjectRaw) . '?=';

        return @mail($to, $subject, $body, $headers);
    } catch (\Throwable) {
        // Mail failures must never break installation.
        return false;
    }
}

/**
 * Minimal SMTP sender used ONLY by the installer (which is self-contained and
 * cannot use the app's MailService). Mirrors MailService::sendSmtp.
 *
 * @param array<string,mixed> $cfg
 */
function smtpSendInstall(array $cfg, string $to, string $subject, string $html): bool
{
    $host = trim((string) ($cfg['mail_smtp_host'] ?? ''));
    if ($host === '') return false;

    $port = (int) ($cfg['mail_smtp_port'] ?? 587);
    $user = (string) ($cfg['mail_smtp_user'] ?? '');
    $pass = (string) ($cfg['mail_smtp_pass'] ?? '');
    $enc  = (string) ($cfg['mail_smtp_encryption'] ?? 'tls');
    $from = trim((string) ($cfg['mail_from_address'] ?? '')) ?: ('noreply@' . $host);
    $fromName = trim((string) ($cfg['mail_from_name'] ?? '')) ?: ((string) ($cfg['app_name'] ?? '') ?: 'GoniCore');

    $transport = ($enc === 'ssl') ? "ssl://{$host}" : "tcp://{$host}";

    try {
        $ctx  = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $sock = @stream_socket_client("{$transport}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
        if (!$sock) return false;

        $read = fn() => fgets($sock, 515);
        $send = function (string $cmd) use ($sock, $read): string {
            fwrite($sock, $cmd . "\r\n");
            return $read();
        };

        $read(); // greeting
        $send("EHLO " . gethostname());
        if ($enc === 'tls') {
            $send("STARTTLS");
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send("EHLO " . gethostname());
        }
        if ($user !== '') {
            $send("AUTH LOGIN");
            $send(base64_encode($user));
            $send(base64_encode($pass));
        }
        $send("MAIL FROM:<{$from}>");
        $send("RCPT TO:<{$to}>");
        $send("DATA");

        $message = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n"
                 . "To: {$to}\r\n"
                 . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
                 . "MIME-Version: 1.0\r\n"
                 . "Content-Type: text/html; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: base64\r\n\r\n"
                 . chunk_split(base64_encode($html));

        fwrite($sock, $message . "\r\n.\r\n");
        $read();
        $send("QUIT");
        fclose($sock);
        return true;
    } catch (\Throwable) {
        return false;
    }
}

/**
 * Write a `.disabled` marker into every plugin directory so the engine boots
 * with all plugins deactivated after installation.
 */
function disableAllPlugins(string $root): void
{
    $pluginsDir = $root . '/plugins';
    if (!is_dir($pluginsDir)) return;

    foreach (scandir($pluginsDir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $dir = $pluginsDir . '/' . $entry;
        // Only real plugins (a bootstrap.php makes a directory a plugin).
        if (!is_dir($dir) || !is_file($dir . '/bootstrap.php')) continue;
        $marker = $dir . '/.disabled';
        if (!is_file($marker)) {
            @file_put_contents($marker, 'Disabled on install ' . date('c') . PHP_EOL);
        }
    }
}

function migrate(PDO $pdo, array $d): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS `_migrations` (
        `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL,
        `ran_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `migration_unique` (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── Core tables only. Plugin tables are created by each plugin
    //    when it is ACTIVATED in the manage panel — never here. ──────────
    $tables = [
        "CREATE TABLE IF NOT EXISTS `users` (
            `id`                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
            `name`                VARCHAR(255)    NOT NULL,
            `username`            VARCHAR(100)    NULL DEFAULT NULL,
            `phone`               VARCHAR(30)     NULL DEFAULT NULL,
            `email_notifications` TINYINT(1)      NOT NULL DEFAULT 1,
            `email`               VARCHAR(255)    NOT NULL,
            `password`            VARCHAR(255)    NOT NULL,
            `remember_token`      VARCHAR(100)    NULL DEFAULT NULL,
            `role`                ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
            `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `users_email_unique` (`email`),
            UNIQUE KEY `users_username_unique` (`username`),
            UNIQUE KEY `users_phone_unique` (`phone`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `categories` (
            `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name`       VARCHAR(255) NOT NULL,
            `slug`       VARCHAR(255) NOT NULL,
            `parent_id`  INT UNSIGNED NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `categories_slug_unique` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `posts` (
            `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `type`           ENUM('post','page') NOT NULL DEFAULT 'post',
            `template`       VARCHAR(60)   NOT NULL DEFAULT 'default',
            `title`          VARCHAR(500)  NOT NULL,
            `slug`           VARCHAR(500)  NOT NULL,
            `content`        LONGTEXT      NOT NULL,
            `featured_image` VARCHAR(1000) NULL DEFAULT NULL,
            `use_builder`    TINYINT(1)    NOT NULL DEFAULT 0,
            `builder_data`   LONGTEXT      NULL DEFAULT NULL,
            `excerpt`        TEXT          NULL DEFAULT NULL,
            `status`         ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
            `author_id`      INT UNSIGNED  NOT NULL,
            `category_id`    INT UNSIGNED  NULL DEFAULT NULL,
            `parent_id`      INT UNSIGNED  NULL DEFAULT NULL,
            `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `posts_slug_unique` (`slug`),
            INDEX `posts_status_idx` (`status`),
            INDEX `posts_author_idx` (`author_id`),
            INDEX `posts_type_idx` (`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `media` (
            `id`            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
            `filename`      VARCHAR(255)  NOT NULL,
            `original_name` VARCHAR(255)  NOT NULL,
            `mime_type`     VARCHAR(127)  NOT NULL,
            `size`          INT UNSIGNED  NOT NULL,
            `path`          VARCHAR(500)  NOT NULL,
            `uploaded_by`   INT UNSIGNED  NOT NULL,
            `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `media_uploader_idx` (`uploaded_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `activity_log` (
            `id`          INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
            `user_id`     INT UNSIGNED   NULL DEFAULT NULL,
            `action`      VARCHAR(100)   NOT NULL,
            `entity_type` VARCHAR(60)    NULL DEFAULT NULL,
            `entity_id`   INT UNSIGNED   NULL DEFAULT NULL,
            `meta`        JSON           NULL DEFAULT NULL,
            `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_activity_user`   (`user_id`),
            KEY `idx_activity_entity` (`entity_type`, `entity_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `todos` (
            `id`          INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
            `user_id`     INT UNSIGNED   NOT NULL,
            `title`       VARCHAR(500)   NOT NULL,
            `completed`   TINYINT(1)     NOT NULL DEFAULT 0,
            `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_todos_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `notifications` (
            `id`         INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
            `user_id`    INT UNSIGNED   NULL DEFAULT NULL COMMENT 'NULL = broadcast to all admins',
            `type`       VARCHAR(80)    NOT NULL,
            `title`      VARCHAR(255)   NOT NULL,
            `message`    TEXT           NULL DEFAULT NULL,
            `data`       JSON           NULL DEFAULT NULL,
            `icon`       VARCHAR(10)    NOT NULL DEFAULT '🔔',
            `read_at`    TIMESTAMP      NULL DEFAULT NULL,
            `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_notif_user`    (`user_id`),
            KEY `idx_notif_read`    (`user_id`, `read_at`),
            KEY `idx_notif_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `languages` (
            `id`         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
            `code`       VARCHAR(10)   NOT NULL,
            `name`       VARCHAR(100)  NOT NULL,
            `native`     VARCHAR(100)  NOT NULL,
            `flag`       VARCHAR(10)   NOT NULL DEFAULT '🌐',
            `is_default` TINYINT(1)    NOT NULL DEFAULT 0,
            `is_active`  TINYINT(1)    NOT NULL DEFAULT 1,
            `sort_order` SMALLINT      NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `languages_code_unique` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `post_translations` (
            `id`            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
            `post_id`       INT UNSIGNED  NOT NULL,
            `language_code` VARCHAR(10)   NOT NULL,
            `title`         VARCHAR(500)  NOT NULL,
            `slug`          VARCHAR(500)  NOT NULL,
            `content`       LONGTEXT      NOT NULL,
            `status`        ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
            `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `pt_post_lang` (`post_id`, `language_code`),
            KEY `idx_pt_lang_slug` (`language_code`, `slug`),
            CONSTRAINT `fk_pt_post`
                FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `settings` (
            `key`        VARCHAR(120)  NOT NULL,
            `value`      LONGTEXT      NULL DEFAULT NULL,
            `autoload`   TINYINT(1)    NOT NULL DEFAULT 1,
            `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `widgets` (
            `id`         INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
            `area`       VARCHAR(60)    NOT NULL,
            `type`       VARCHAR(60)    NOT NULL DEFAULT 'html',
            `title`      VARCHAR(255)   NULL DEFAULT NULL,
            `settings`   JSON           NULL DEFAULT NULL,
            `sort_order` SMALLINT       NOT NULL DEFAULT 0,
            `is_active`  TINYINT(1)     NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_widgets_area` (`area`, `sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `menus` (
            `id`         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
            `name`       VARCHAR(255)  NOT NULL,
            `slug`       VARCHAR(255)  NOT NULL,
            `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `menus_slug_unique` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `menu_items` (
            `id`        INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
            `menu_id`   INT UNSIGNED  NOT NULL,
            `parent_id` INT UNSIGNED  NULL DEFAULT NULL,
            `type`      ENUM('custom','page','post','category') NOT NULL DEFAULT 'custom',
            `object_id` INT UNSIGNED  NULL DEFAULT NULL COMMENT 'page/post/category id',
            `label`     VARCHAR(255)  NOT NULL,
            `url`       VARCHAR(1000) NULL DEFAULT NULL COMMENT 'For custom links',
            `target`    VARCHAR(20)   NOT NULL DEFAULT '_self',
            `sort_order` SMALLINT     NOT NULL DEFAULT 0,
            KEY `fk_mi_menu` (`menu_id`),
            KEY `fk_mi_parent` (`parent_id`),
            CONSTRAINT `fk_mi_menu`   FOREIGN KEY (`menu_id`)   REFERENCES `menus` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_mi_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `menu_locations` (
            `location` VARCHAR(100) NOT NULL PRIMARY KEY,
            `menu_id`  INT UNSIGNED NULL DEFAULT NULL,
            CONSTRAINT `fk_ml_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }

    // ── Seed: default languages ────────────────────────────────────────
    $pdo->exec("
        INSERT IGNORE INTO `languages` (`code`, `name`, `native`, `flag`, `is_default`, `is_active`, `sort_order`)
        VALUES
            ('en', 'English',   'English',  '🇬🇧', 1, 1, 0),
            ('ka', 'Georgian',  'ქართული',  '🇬🇪', 0, 1, 1)
    ");

    // ── Seed: default settings (site values come from the install form) ─
    $settings = [
        ['site_name',            $d['app_name'] ?: 'GoniCore'],
        ['site_tagline',         'A modern headless CMS'],
        ['site_url',             $d['app_url']],
        ['posts_per_page',       '9'],
        ['homepage_type',        'posts'],
        ['homepage_page_id',     ''],
        ['posts_page_id',        ''],
        ['timezone',             'Asia/Tbilisi'],
        ['date_format',          'M j, Y'],
        ['time_format',          'H:i'],
        ['session_lifetime',     '120'],
        ['admin_email',          $d['admin_email']],
        ['mail_driver',          $d['mail_driver'] ?? 'php'],
        ['mail_from_address',    $d['mail_from_address'] ?? ''],
        ['mail_from_name',       ($d['mail_from_name'] ?? '') !== '' ? $d['mail_from_name'] : ($d['app_name'] ?: 'GoniCore')],
        ['mail_smtp_host',       $d['mail_smtp_host'] ?? ''],
        ['mail_smtp_port',       $d['mail_smtp_port'] ?? '587'],
        ['mail_smtp_user',       $d['mail_smtp_user'] ?? ''],
        ['mail_smtp_pass',       $d['mail_smtp_pass'] ?? ''],
        ['mail_smtp_encryption', $d['mail_smtp_encryption'] ?? 'tls'],
        ['notify_post_new',      '1'],
        ['notify_user_register', '1'],
        ['notify_comment_new',   '1'],
    ];
    $seedStmt = $pdo->prepare("INSERT IGNORE INTO `settings` (`key`, `value`, `autoload`) VALUES (?, ?, 1)");
    foreach ($settings as [$key, $val]) {
        $seedStmt->execute([$key, $val]);
    }

    // ── Mark all core migration files as applied so `migrate` CLI
    //    does not re-run them against the already-final schema. ─────────
    $migrationsDir = dirname(__DIR__) . '/database/migrations';
    $recordStmt    = $pdo->prepare("INSERT IGNORE INTO `_migrations` (`migration`) VALUES (?)");
    foreach (glob($migrationsDir . '/*.php') ?: [] as $file) {
        $recordStmt->execute([basename($file)]);
    }
}

function writeEnv(array $d, string $root): void
{
    $secret = bin2hex(random_bytes(32));
    $env = "APP_NAME=" . ($d['app_name'] ?: 'GoniCore') . "\n"
         . "APP_ENV=production\n"
         . "APP_DEBUG=false\n"
         . "APP_URL=" . $d['app_url'] . "\n\n"
         . "DB_DRIVER=mysql\n"
         . "DB_HOST="    . $d['db_host'] . "\n"
         . "DB_PORT="    . $d['db_port'] . "\n"
         . "DB_NAME="    . $d['db_name'] . "\n"
         . "DB_USER="    . $d['db_user'] . "\n"
         . "DB_PASS="    . $d['db_pass'] . "\n"
         . "DB_CHARSET=utf8mb4\n\n"
         . "JWT_SECRET={$secret}\n"
         . "JWT_TTL=3600\n\n"
         . "MEDIA_STORAGE_PATH=storage/media\n";

    file_put_contents($root . '/.env', $env);
}

// ── HTML renderer ─────────────────────────────────────────────

function render(string $view, array $vars): string
{
    ob_start();
    extract($vars);

    if ($view === 'locked') {
        echo '<html><head><meta charset="utf-8"><title>Already Installed</title>
        <style>body{font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f5f7fb}
        .box{background:#fff;border-radius:12px;padding:48px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.08)}
        h2{color:#dc2626}p{color:#6b7280}</style></head>
        <body><div class="box"><h2>Already Installed</h2>
        <p>GoniCore is already installed. Delete <code>.installed</code> to re-run.</p></div></body></html>';
        return ob_get_clean() ?: '';
    }

    if ($view === 'complete') {
        echo '<html><head><meta charset="utf-8"><title>Installation Complete</title>
        <style>*{box-sizing:border-box}body{font-family:system-ui,-apple-system,sans-serif;margin:0;background:#f0fdf4;display:flex;align-items:center;justify-content:center;min-height:100vh}
        .card{background:#fff;border-radius:16px;padding:48px 56px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.08);max-width:480px;width:100%}
        .icon{font-size:64px;margin-bottom:16px}.h1{font-size:28px;font-weight:700;color:#166534;margin:0 0 8px}
        .sub{color:#6b7280;margin:0 0 32px;font-size:15px}
        .btn{display:inline-block;background:#16a34a;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;font-size:15px;margin:6px}
        .btn.sec{background:#f0fdf4;color:#166534;border:1.5px solid #bbf7d0}
        code{background:#f1f5f9;border-radius:6px;padding:2px 8px;font-size:13px}
        .warn{background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:12px 16px;font-size:13px;color:#713f12;margin-top:24px;text-align:left}
        </style></head><body>
        <div class="card">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100" width="150" height="75" style="margin-bottom:8px" aria-label="GoniCore">
            <rect x="15" y="26" width="48" height="48" rx="10" fill="none" stroke="#0F172A" stroke-width="5"/>
            <rect x="27" y="38" width="24" height="24" rx="6" fill="#10B27C"/>
            <text x="80" y="46" font-family="system-ui,-apple-system,sans-serif" font-size="28" font-weight="900" fill="#0F172A" letter-spacing="-0.5">Goni</text>
            <text x="80" y="74" font-family="system-ui,-apple-system,sans-serif" font-size="28" font-weight="300" fill="#10B27C" letter-spacing="-0.5">Core</text>
          </svg>
          <div class="icon">🎉</div>
          <div class="h1">Installation Complete!</div>
          <p class="sub">GoniCore has been successfully set up.</p>
          <a href="' . htmlspecialchars($app_url) . '/manage" class="btn">Open Admin Panel ↗</a>
          <a href="' . htmlspecialchars($app_url) . '/" class="btn sec">View Site</a>
          <div class="warn">
            ⚠️ <strong>Security:</strong> Delete <code>public/install.php</code> from your server now.<br>
            Admin: <code>' . htmlspecialchars($admin_email) . '</code>
          </div>
          ' . (!empty($mail_sent)
              ? '<div class="warn" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534">✉️ A welcome email was sent to <code>' . htmlspecialchars($admin_email) . '</code>.</div>'
              : '<div class="warn" style="background:#fef2f2;border-color:#fecaca;color:#991b1b">✉️ <strong>The welcome email could not be sent.</strong> Many hosts disable PHP <code>mail()</code> and require authenticated SMTP. To enable email notifications, open <strong>Manage → Settings → Email</strong> and set the driver to <strong>SMTP</strong> with your host\'s mail credentials.</div>') . '
        </div></body></html>';
        return ob_get_clean() ?: '';
    }

    return ob_get_clean() ?: '';
}

// ── Helpers for HTML output ───────────────────────────────────
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }
function old(string $key, array $saved, string $default = ''): string { return h((string)($saved[$key] ?? $default)); }

// ─────────────────────────────────────────────────────────────
// HTML TEMPLATE
// ─────────────────────────────────────────────────────────────
$requirements = getRequirements($projectRoot);
$allPass      = array_reduce($requirements, fn($c, $r) => $c && $r['pass'], true);

$stepLabels = ['Requirements', 'Database', 'Application', 'Installing'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GoniCore Installer</title>
<style>
*, *::before, *::after { box-sizing: border-box; }

body {
    font-family: system-ui, -apple-system, sans-serif;
    margin: 0;
    min-height: 100vh;
    background: linear-gradient(135deg, #f0f4ff 0%, #fafbff 100%);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 40px 16px;
}

.wrapper {
    width: 100%;
    max-width: 560px;
}

/* ── Brand ── */
.brand {
    text-align: center;
    margin-bottom: 32px;
}
.brand-title {
    font-size: 26px;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: -.5px;
}
.brand-sub {
    font-size: 13px;
    color: #94a3b8;
    margin-top: 4px;
}

/* ── Stepper ── */
.stepper {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 28px;
    gap: 0;
}
.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex: 1;
    position: relative;
}
.step-item:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 16px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: #e2e8f0;
    z-index: 0;
}
.step-item.done:not(:last-child)::after,
.step-item.active:not(:last-child)::after {
    background: #6366f1;
}
.step-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    position: relative;
    z-index: 1;
    transition: all .2s;
}
.step-item.done .step-dot { background: #6366f1; color: #fff; }
.step-item.active .step-dot { background: #6366f1; color: #fff; box-shadow: 0 0 0 4px #e0e7ff; }
.step-label {
    font-size: 11px;
    font-weight: 500;
    color: #94a3b8;
    white-space: nowrap;
}
.step-item.done .step-label,
.step-item.active .step-label { color: #6366f1; }

/* ── Card ── */
.card {
    background: #fff;
    border-radius: 16px;
    padding: 36px 40px;
    box-shadow: 0 4px 24px rgba(0,0,0,.07), 0 1px 4px rgba(0,0,0,.04);
}

.card-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px;
}
.card-desc {
    font-size: 14px;
    color: #64748b;
    margin: 0 0 28px;
}

/* ── Errors ── */
.alert {
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 14px;
}
.alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
}
.alert ul { margin: 4px 0 0; padding-left: 18px; }

/* ── Requirements list ── */
.req-list { list-style: none; margin: 0; padding: 0; }
.req-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    border-radius: 8px;
    margin-bottom: 6px;
    background: #f8fafc;
    font-size: 14px;
}
.req-item.pass { background: #f0fdf4; }
.req-item.fail { background: #fef2f2; }
.req-badge {
    font-size: 12px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
}
.req-item.pass .req-badge { background: #dcfce7; color: #16a34a; }
.req-item.fail .req-badge { background: #fee2e2; color: #dc2626; }
.req-name { color: #334155; }
.req-val  { color: #64748b; font-size: 12px; margin: 0 8px; }

/* ── Form ── */
.form-group {
    margin-bottom: 20px;
}
label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
label .hint {
    font-weight: 400;
    color: #9ca3af;
    margin-left: 4px;
}
input[type=text],
input[type=password],
input[type=email],
input[type=url],
input[type=number],
select {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    color: #1e293b;
    background: #fff;
    transition: border-color .15s;
    outline: none;
}
input:focus, select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px #eef2ff; }
.row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

/* ── Actions ── */
.actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 28px;
    gap: 12px;
}
.btn {
    padding: 11px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all .15s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-primary {
    background: #6366f1;
    color: #fff;
}
.btn-primary:hover { background: #4f46e5; }
.btn-primary:disabled { opacity: .55; cursor: not-allowed; }

/* ── Password strength hint ── */
.field-hint {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
}
</style>
</head>
<body>
<div class="wrapper">

  <!-- Brand -->
  <div class="brand">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100" width="150" height="75" aria-label="GoniCore">
      <rect x="15" y="26" width="48" height="48" rx="10" fill="none" stroke="#0F172A" stroke-width="5"/>
      <rect x="27" y="38" width="24" height="24" rx="6" fill="#10B27C"/>
      <text x="80" y="46" font-family="system-ui,-apple-system,sans-serif" font-size="28" font-weight="900" fill="#0F172A" letter-spacing="-0.5">Goni</text>
      <text x="80" y="74" font-family="system-ui,-apple-system,sans-serif" font-size="28" font-weight="300" fill="#10B27C" letter-spacing="-0.5">Core</text>
    </svg>
    <div class="brand-sub">Installation Wizard</div>
  </div>

  <!-- Stepper -->
  <div class="stepper">
    <?php foreach ($stepLabels as $i => $label):
        $n = $i + 1;
        $cls = $n < $step ? 'done' : ($n === $step ? 'active' : '');
        $dot = $n < $step ? '✓' : $n;
    ?>
    <div class="step-item <?= $cls ?>">
      <div class="step-dot"><?= $dot ?></div>
      <div class="step-label"><?= h($label) ?></div>
    </div>
    <?php endforeach ?>
  </div>

  <!-- Card -->
  <div class="card">

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <?php if (count($errors) === 1): ?>
        <?= h($errors[0]) ?>
      <?php else: ?><ul><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach ?></ul><?php endif ?>
    </div>
    <?php endif ?>

    <!-- ════════════ STEP 1: REQUIREMENTS ════════════ -->
    <?php if ($step === 1): ?>
    <div class="card-title">System Requirements</div>
    <div class="card-desc">Checking your server configuration before we begin.</div>

    <ul class="req-list">
      <?php foreach ($requirements as $r): ?>
      <li class="req-item <?= $r['pass'] ? 'pass' : 'fail' ?>">
        <span class="req-name"><?= h($r['label']) ?></span>
        <span class="req-val"><?= h($r['value']) ?></span>
        <span class="req-badge"><?= $r['pass'] ? '✓ OK' : '✗ Fail' ?></span>
      </li>
      <?php endforeach ?>
    </ul>

    <form method="POST">
      <input type="hidden" name="_csrf"  value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="next_requirements">
      <div class="actions">
        <button type="submit" class="btn btn-primary" <?= $allPass ? '' : 'disabled' ?>>
          Continue →
        </button>
      </div>
    </form>

    <!-- ════════════ STEP 2: DATABASE ════════════ -->
    <?php elseif ($step === 2): ?>
    <div class="card-title">Database Configuration</div>
    <div class="card-desc">Enter your MySQL / MariaDB connection details.</div>

    <form method="POST">
      <input type="hidden" name="_csrf"  value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="next_database">

      <div class="row-2">
        <div class="form-group">
          <label>Hostname</label>
          <input type="text" name="db_host" value="<?= old('db_host', $saved, '127.0.0.1') ?>" required>
        </div>
        <div class="form-group">
          <label>Port</label>
          <input type="number" name="db_port" value="<?= old('db_port', $saved, '3306') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label>Database Name <span class="hint">(will be created if not exists)</span></label>
        <input type="text" name="db_name" value="<?= old('db_name', $saved, 'gonicore') ?>" required>
      </div>

      <div class="row-2">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="db_user" value="<?= old('db_user', $saved, 'root') ?>" required>
        </div>
        <div class="form-group">
          <label>Password <span class="hint">(optional)</span></label>
          <input type="password" name="db_pass" value="">
        </div>
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-primary">Test & Continue →</button>
      </div>
    </form>

    <!-- ════════════ STEP 3: APP + ADMIN ════════════ -->
    <?php elseif ($step === 3): ?>
    <div class="card-title">Application &amp; Admin Account</div>
    <div class="card-desc">Configure your CMS and create the first administrator.</div>

    <form method="POST">
      <input type="hidden" name="_csrf"  value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="run_install">

      <div class="row-2">
        <div class="form-group">
          <label>Site Name</label>
          <input type="text" name="app_name" value="<?= old('app_name', $saved, 'GoniCore') ?>" required>
        </div>
        <div class="form-group">
          <label>Site URL</label>
          <input type="url" name="app_url" placeholder="https://example.com" value="<?= old('app_url', $saved, 'http://'.$_SERVER['HTTP_HOST']) ?>" required>
        </div>
      </div>

      <hr style="border:none;border-top:1.5px solid #f1f5f9;margin:4px 0 20px">

      <div class="form-group">
        <label>Admin Full Name</label>
        <input type="text" name="admin_name" value="<?= old('admin_name', $saved) ?>" required>
      </div>
      <div class="form-group">
        <label>Admin Email</label>
        <input type="email" name="admin_email" value="<?= old('admin_email', $saved) ?>" required>
      </div>
      <div class="form-group">
        <label>Admin Password</label>
        <input type="password" name="admin_password" required>
        <div class="field-hint">Minimum 8 characters. Store it safely.</div>
      </div>

      <hr style="border:none;border-top:1.5px solid #f1f5f9;margin:4px 0 18px">
      <label style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:4px">Email <span style="font-weight:400;color:#9ca3af">— optional, can be changed later in Settings</span></label>

      <div class="form-group" style="margin-top:12px">
        <label>Mail Driver</label>
        <select name="mail_driver" id="mailDriver" onchange="document.getElementById('smtpBlock').style.display=this.value==='smtp'?'block':'none'">
          <option value="php"  <?= (($_POST['mail_driver'] ?? 'php') === 'php')  ? 'selected' : '' ?>>PHP mail() — local / default</option>
          <option value="smtp" <?= (($_POST['mail_driver'] ?? '')    === 'smtp') ? 'selected' : '' ?>>SMTP — recommended on hosting</option>
        </select>
        <div class="field-hint">Most shared hosts disable PHP <code>mail()</code> — choose <strong>SMTP</strong> so emails (welcome mail, notifications) actually send.</div>
      </div>

      <div id="smtpBlock" style="<?= (($_POST['mail_driver'] ?? '') === 'smtp') ? '' : 'display:none' ?>">
        <div class="row-2">
          <div class="form-group">
            <label>SMTP Host</label>
            <input type="text" name="mail_smtp_host" value="<?= h($_POST['mail_smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
          </div>
          <div class="form-group">
            <label>SMTP Port</label>
            <input type="number" name="mail_smtp_port" value="<?= h($_POST['mail_smtp_port'] ?? '587') ?>">
          </div>
        </div>
        <div class="row-2">
          <div class="form-group">
            <label>Encryption</label>
            <select name="mail_smtp_encryption">
              <option value="tls" <?= (($_POST['mail_smtp_encryption'] ?? 'tls') === 'tls') ? 'selected' : '' ?>>TLS</option>
              <option value="ssl" <?= (($_POST['mail_smtp_encryption'] ?? '')    === 'ssl') ? 'selected' : '' ?>>SSL</option>
              <option value=""    <?= (($_POST['mail_smtp_encryption'] ?? 'tls') === '')    ? 'selected' : '' ?>>None</option>
            </select>
          </div>
          <div class="form-group">
            <label>From Address</label>
            <input type="email" name="mail_from_address" value="<?= h($_POST['mail_from_address'] ?? '') ?>" placeholder="noreply@example.com">
          </div>
        </div>
        <div class="row-2">
          <div class="form-group">
            <label>SMTP Username</label>
            <input type="text" name="mail_smtp_user" value="<?= h($_POST['mail_smtp_user'] ?? '') ?>" autocomplete="off">
          </div>
          <div class="form-group">
            <label>SMTP Password</label>
            <input type="password" name="mail_smtp_pass" value="<?= h($_POST['mail_smtp_pass'] ?? '') ?>" autocomplete="new-password">
          </div>
        </div>
        <div class="form-group">
          <label>From Name</label>
          <input type="text" name="mail_from_name" value="<?= h($_POST['mail_from_name'] ?? '') ?>" placeholder="<?= h($_POST['app_name'] ?? 'GoniCore') ?>">
        </div>
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-primary">Install GoniCore →</button>
      </div>
    </form>

    <?php endif ?>

  </div><!-- .card -->

  <p style="text-align:center;font-size:12px;color:#cbd5e1;margin-top:20px">
    GoniCore &bull; Open-source Headless CMS
  </p>

</div><!-- .wrapper -->
</body>
</html>
