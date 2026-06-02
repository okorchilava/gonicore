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

// ── Already installed? ────────────────────────────────────────
if (is_file($lockFile)) {
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
        $app = [
            'app_name'       => trim($_POST['app_name']       ?? 'GoniCore'),
            'app_url'        => rtrim(trim($_POST['app_url']  ?? ''), '/'),
            'admin_name'     => trim($_POST['admin_name']     ?? ''),
            'admin_email'    => trim($_POST['admin_email']    ?? ''),
            'admin_password' => $_POST['admin_password']      ?? '',
        ];

        // Validate
        if ($app['admin_name'] === '')                              { $errors[] = 'Admin name is required.'; }
        if (!filter_var($app['admin_email'], FILTER_VALIDATE_EMAIL)){ $errors[] = 'Admin email is not valid.'; }
        if (strlen($app['admin_password']) < 8)                    { $errors[] = 'Password must be at least 8 characters.'; }
        if ($app['app_url'] === '')                                 { $errors[] = 'Application URL is required.'; }

        if (empty($errors)) {
            $all = array_merge($saved, $app);
            $installErr = runInstall($all, $projectRoot);

            if ($installErr === null) {
                // Lock the installer
                file_put_contents($lockFile, date('c') . PHP_EOL);
                session_destroy();
                $_SESSION = [];
                die(render('complete', ['app_url' => $all['app_url'], 'admin_email' => $all['admin_email']]));
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

function runInstall(array $d, string $root): ?string
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

        // 3. Migrations (inline SQL)
        migrate($pdo);

        // 4. Admin user
        $pdo->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES (?, ?, ?, 'admin')")
            ->execute([$d['admin_name'], $d['admin_email'], password_hash($d['admin_password'], PASSWORD_BCRYPT)]);

        // 5. .env file
        writeEnv($d, $root);

        return null;
    } catch (Throwable $e) {
        return $e->getMessage();
    }
}

function migrate(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS `_migrations` (
        `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL,
        `ran_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `migration_unique` (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS `users` (
            `id`         INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
            `name`       VARCHAR(255)    NOT NULL,
            `email`      VARCHAR(255)    NOT NULL,
            `password`   VARCHAR(255)    NOT NULL,
            `role`       ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `users_email_unique` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'categories' => "CREATE TABLE IF NOT EXISTS `categories` (
            `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name`       VARCHAR(255) NOT NULL,
            `slug`       VARCHAR(255) NOT NULL,
            `parent_id`  INT UNSIGNED NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `categories_slug_unique` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'posts' => "CREATE TABLE IF NOT EXISTS `posts` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title`       VARCHAR(500)  NOT NULL,
            `slug`        VARCHAR(500)  NOT NULL,
            `content`     LONGTEXT      NOT NULL,
            `status`      ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
            `author_id`   INT UNSIGNED  NOT NULL,
            `category_id` INT UNSIGNED  NULL DEFAULT NULL,
            `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `posts_slug_unique` (`slug`),
            INDEX `posts_status_idx` (`status`),
            INDEX `posts_author_idx` (`author_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'media' => "CREATE TABLE IF NOT EXISTS `media` (
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
    ];

    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
        $pdo->prepare("INSERT IGNORE INTO `_migrations` (`migration`) VALUES (?)")
            ->execute(["web_installer_{$name}"]);
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
          <div class="icon">🎉</div>
          <div class="h1">Installation Complete!</div>
          <p class="sub">GoniCore has been successfully set up.</p>
          <a href="' . htmlspecialchars($app_url) . '/api/v1/health" class="btn">Test API ↗</a>
          <a href="' . htmlspecialchars($app_url) . '/api/v1/auth/login" class="btn sec">Login Endpoint</a>
          <div class="warn">
            ⚠️ <strong>Security:</strong> Delete <code>public/install.php</code> from your server now.<br>
            Admin: <code>' . htmlspecialchars($admin_email) . '</code>
          </div>
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
input[type=number] {
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
input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px #eef2ff; }
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
    <div class="brand-title">⚙ GoniCore</div>
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
