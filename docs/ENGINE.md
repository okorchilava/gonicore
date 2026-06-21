# GoniCore — Engine Documentation

GoniCore is a small, framework‑agnostic PHP CMS with a WordPress‑style
plugin/hook architecture, built from scratch (no Laravel/Symfony). It is
API‑first and strictly modular: a thin core (`src/Core`) wires together
domain modules (`src/Modules`) and an unlimited number of plugins
(`plugins/*`).

- **Stack:** PHP 8.2+, MySQL/MariaDB (PDO), vanilla JS for UI, Composer (PSR‑4).
- **Entry point:** `public/index.php` (point your web root at `/public`).
- **Namespaces:** `GoniCore\Core\*`, `GoniCore\Modules\*`, `GoniCore\Shared\*`.

> This document describes the **engine**. Per‑plugin behaviour lives in each
> plugin's own folder. All public identifiers (classes, hooks, settings keys)
> are in English to match the code. Georgian version: [`ENGINE.ka.md`](ENGINE.ka.md).

## Table of contents

1. [Architecture overview](#1-architecture-overview)
2. [Directory layout](#2-directory-layout)
3. [Request lifecycle](#3-request-lifecycle)
4. [Configuration & environment](#4-configuration--environment)
5. [Dependency injection container](#5-dependency-injection-container)
6. [Routing — Router, Request, Response](#6-routing--router-request-response)
7. [Database — Connection, QueryBuilder, migrations](#7-database--connection-querybuilder-migrations)
8. [Hooks & filters](#8-hooks--filters)
9. [Plugins](#9-plugins)
10. [Core modules](#10-core-modules)
11. [Theming](#11-theming)
12. [Settings](#12-settings)
13. [Notifications](#13-notifications)
14. [Internationalization (i18n)](#14-internationalization-i18n)
15. [Auth, sessions, RBAC & security](#15-auth-sessions-rbac--security)
16. [Admin panel conventions](#16-admin-panel-conventions)
17. [CLI](#17-cli)
18. [Conventions & gotchas](#18-conventions--gotchas)

---

## 1. Architecture overview

```
HTTP request
   │
   ▼
public/index.php ──> bootstrap/app.php ──> Application::run()
   (gate + autoload)   (build everything)     (dispatch + send)
        │                    │
        │                    ├── Env + Config
        │                    ├── Container (DI)  ← all bindings live here
        │                    ├── Router          ← all routes live here
        │                    ├── HookManager (+ global) + functions.php
        │                    ├── PluginLoader.load(plugins/)
        │                    └── return Application(container, router, config)
        ▼
   Router::dispatch(Request) ──> controller ──> Response ──> send()
```

The core is intentionally **plugin‑agnostic**: it never hard‑codes a plugin.
Plugins extend the engine purely through the **hook system** (`gc_on` /
`gc_filter`) and by registering routes/services on the objects passed to their
`bootstrap.php`.

Key design properties:

- **Immutable value objects** — `Request`, `Response` and `QueryBuilder` return
  new instances on every mutation; the original is never changed.
- **Lazy DB** — the PDO connection opens on first query, not at boot.
- **Fail‑soft plugins** — a plugin that throws during boot is logged and
  skipped; it never takes down the site.

---

## 2. Directory layout

```
public/                 Web root (index.php front controller, install.php, assets)
bootstrap/app.php       Composition root: DI bindings + routes + plugin load
config/                 app.php, auth.php, database.php (read from .env)
src/
  Core/                 The engine
    Application.php      Kernel (handle/run, error handling)
    Container/          PSR-11 DI container (auto-wiring)
    Http/               Router, Route, Request, Response, Middleware, ErrorPage
    Hooks/              HookManager, PluginLoader
    Database/           Connection, QueryBuilder, Migration, Migrator
    Config/             Config, Env
    Mail/               MailService (php mail() + SMTP)
    Logging/            ErrorLogger (storage/logs)
    Shortcodes/         ShortcodeManager
    Widgets/            WidgetManager
    Validation/         Validator
    functions.php       Global gc_* plugin API (Hooks/Settings/Auth helpers)
  Modules/              Domain modules (Post, Category, User, Auth, Manage, …)
  Shared/               Contracts + Support (Str, Paginator)
plugins/<slug>/         One folder per plugin; must contain bootstrap.php
themes/default/         The active theme (views, partials, helpers, lang)
database/migrations/    Core schema migrations (0001_…php …)
lang/                   Engine (admin) translation packs: en.php, ka.php
storage/                Runtime: media uploads, logs (git-ignored)
bin/gonicore            CLI entry point
```

---

## 3. Request lifecycle

1. **`public/index.php`** — installation gate (redirects to `install.php` until
   the app is installed), then `require vendor/autoload.php`, then
   `$app = require bootstrap/app.php; $app->run();`.
2. **`bootstrap/app.php`** (the composition root) runs, in order:
   1. `Env::load(.env)` and load `config/*.php` into `Config`.
   2. Create the `Container` and **register it globally**
      (`Container::setGlobalInstance($container)`).
   3. Bind infrastructure, repositories, services and controllers.
   4. Register all routes on the `Router`.
   5. **Set the global HookManager and load the plugin API:**
      `HookManager::setGlobalInstance($hooks); require src/Core/functions.php;`
      — this must happen **before** plugins load, or every `gc_on()` call fatals.
   6. `PluginLoader->load(plugins/, …)` — boots each enabled plugin.
   7. Register the core `admin.notify` listener (sends admin email via `MailService`).
   8. `return new Application($container, $router, $config)`.
3. **`Application::run()`** → `handle(Request::capture())` → `Router::dispatch()`
   → controller returns a `Response` → `Response::send()` → `exit`.
4. **Errors:** `HttpException` (404/405/…) → themed 404 for web, JSON for API;
   any other `Throwable` → logged and a 500 returned (see `Application` /
   `Core/Http/ErrorPage.php`).

---

## 4. Configuration & environment

`.env` (git‑ignored) holds secrets; `config/*.php` read them via `Env`:

```php
// config/database.php
return [
    'driver'   => Env::get('DB_DRIVER', 'mysql'),
    'host'     => Env::get('DB_HOST', '127.0.0.1'),
    'port'     => (int) Env::get('DB_PORT', '3306'),
    'dbname'   => Env::require('DB_NAME'),     // throws if missing
    'username' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASS', ''),
    'charset'  => Env::get('DB_CHARSET', 'utf8mb4'),
];
```

Read config anywhere via the container:

```php
$debug = (bool) $container->get(Config::class)->get('app.debug', false);
$secret = $container->get(Config::class)->require('auth.jwt_secret');
```

Required `.env` keys: `DB_NAME`, `JWT_SECRET` (≥32 chars). Optional:
`APP_NAME`, `APP_ENV`, `APP_URL`, `APP_DEBUG`, `DB_*`, `JWT_TTL`,
`MEDIA_STORAGE_PATH`.

---

## 5. Dependency injection container

`GoniCore\Core\Container\Container` is PSR‑11 with three registration styles
plus reflection auto‑wiring.

```php
$c->instance(Config::class, $config);                 // pre-built singleton
$c->singleton(Connection::class, fn($c) => Connection::fromConfig(...)); // once
$c->bind(PostController::class, fn($c) => new PostController(...));       // every get()
$obj = $c->get(SomeClass::class);                     // resolve (auto-wires if unbound)
$has = $c->has(SomeClass::class);
```

Resolution order: cached instance → singleton factory (cached) → bind factory
(fresh) → **auto‑wiring** (reflect the constructor, recursively resolve
type‑hinted class params, use defaults/null otherwise).

Global access (used by `functions.php` and plugins):

```php
Container::setGlobalInstance($c);   // called once in bootstrap
$svc = Container::global()->get(SettingsService::class);
$svc = gc_container()->get(...);    // convenience wrapper
```

---

## 6. Routing — Router, Request, Response

### Router

All routes are registered inline in `bootstrap/app.php` (the file `routes/web.php`
is **not** used).

```php
$router->get('/post/{slug}',  [ThemeController::class, 'post']);
$router->post('/login',       [LoginController::class, 'processLogin']);
$router->group('/manage', function (Router $r) {
    $r->get('',          [ManageController::class, 'dashboard']);
    $r->post('/posts',   [ManageController::class, 'postCreate']);
});
$router->get('/api/v1/me', [AuthController::class, 'me'])->middleware($authMw);
```

- Methods: `get/post/put/patch/delete/options`, plus `group(prefix, cb)` (nestable).
- **Path params** use `{name}` and match a **single segment** only
  (`/posts/{id}` matches `/posts/5`, not `/posts/5/edit` — register that
  separately). Read them with `$request->getAttribute('id')`.
- Handlers are `[Class, 'method']` (resolved through the container) or any callable.
- **Subdirectory aware:** `Request::path()` strips the install base path
  (`/goni/GoniCore`) so routes are written root‑relative; `Request::basePath()`
  returns the stripped prefix for building URLs in views.
- A trailing slash is ignored (except `/`). No match → `HttpException(404)`;
  path matched but wrong method → `HttpException(405)`.

### Request (immutable)

```php
$r->method(); $r->path(); $r->basePath(); $r->uri();
$r->query('page', '1'); $r->post('title'); $r->input('q');   // query→post→json
$r->json();                 // decoded JSON body (array)
$r->header('X-Webhook-Token'); $r->isJson(); $r->cookie('gc_lang');
$r->server('HTTP_HOST'); $r->ip(); $r->files();
$r->getAttribute('id');     // route param
$r2 = $r->withAttribute('k', $v);  // returns a clone
```

### Response (immutable)

```php
return Response::json(['ok' => true], 200);
return Response::html($html);
return Response::error('Not found', 404, $fieldErrors);   // {error,message,errors}
return Response::redirect($base.'/manage');
return Response::notFound(); Response::unauthorized(); Response::forbidden();
$res = Response::json($d)->withHeader('X-Foo','bar')->withStatus(201);
```

Use **standard HTTP status codes** only. Non‑standard codes (e.g. 419) make
Apache emit a 500 — use `403` for CSRF rejection and `422` for validation.

---

## 7. Database — Connection, QueryBuilder, migrations

### Connection (lazy PDO wrapper)

```php
$conn = $container->get(Connection::class);
$rows = $conn->query('SELECT * FROM posts WHERE status = ?', ['published']);
$row  = $conn->queryOne('SELECT * FROM posts WHERE id = ?', [$id]);   // ?array
$n    = $conn->scalar('SELECT COUNT(*) FROM posts');
$conn->execute('UPDATE posts SET status = ? WHERE id = ?', ['draft', $id]);
$id   = $conn->lastInsertId();
$conn->transact(function (Connection $c) { /* … */ });   // commit/rollback
```

PDO runs with `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES = false`;
errors are wrapped in `DatabaseException`.

### QueryBuilder (fluent, immutable)

```php
$qb = $container->get(QueryBuilder::class);

$posts = $qb->table('posts')
    ->select('id', 'title', 'created_at')
    ->where('status', '=', 'published')
    ->where('category_id', '=', $catId)
    ->orderBy('created_at', 'DESC')
    ->limit(10)->offset(20)
    ->get();

$one  = $qb->table('posts')->where('slug', '=', $slug)->first();   // ?array
$num  = $qb->table('posts')->where('status', '=', 'published')->count();
$id   = $qb->table('posts')->insert(['title' => 'Hi', 'status' => 'draft']);
$rows = $qb->table('posts')->where('id', '=', $id)->update(['status' => 'published']);
$rows = $qb->table('posts')->where('id', '=', $id)->delete();
```

- Allowed operators: `= != <> < > <= >= LIKE "NOT LIKE" IN "NOT IN"`.
- `IN`/`NOT IN` accept an array and expand to placeholders (empty array →
  always‑false / always‑true).
- **NULL handling:** `where('col', '=', null)` compiles to `col IS NULL` and
  `'!='`/`'<>'` to `IS NOT NULL` (a plain `col = NULL` never matches in SQL).
- Every method returns a clone — reuse/fork builders freely.

### Migrations

Core schema migrations live in `database/migrations/NNNN_name.php` and are
applied by the installer (`public/install.php`, which contains the full schema)
and by the CLI migrator (`bin/gonicore migrate`); applied names are recorded in
the `_migrations` table. **Plugin tables are not created by the installer** —
each plugin owns its schema (see [Plugins](#9-plugins)).

---

## 8. Hooks & filters

The hook system is the primary extension point. `HookManager` keeps two
registries — **actions** (fire‑and‑forget) and **filters** (transform a value).

> **Canonical API.** The engine uses the unique verbs
> `on / emit / off / has` (actions) and `filter / apply / unfilter / hasFilter`
> (filters). There is **no** `addAction`/`doAction` — that is a foreign variant
> and must not be reintroduced.

### Global functions (`src/Core/functions.php`)

Available in every plugin and after bootstrap:

```php
// Actions
gc_on(string $tag, callable $fn, int $priority = 10): void   // lower runs first
gc_emit(string $tag, mixed ...$args): void
gc_off(string $tag, ?int $priority = null): void
gc_has(string $tag): bool

// Filters (callback receives the value + extra args, MUST return the value)
gc_filter(string $tag, callable $fn, int $priority = 10): void
gc_apply(string $tag, mixed $value, mixed ...$args): mixed
gc_unfilter(string $tag, ?int $priority = null): void

// Settings
gc_setting(string $key, mixed $default = null): mixed
gc_set_setting(string $key, mixed $value): void

// Auth / authorization
gc_is_logged_in(): bool
gc_current_user_id(): ?int
gc_current_user(): ?array
gc_user_can(string $permission, ?int $userId = null): bool

// Helpers
gc_plugins_url(string $path = '', string $pluginFile = ''): string
gc_plugin_translator(string $pluginDir): callable
gc_container(): Container
```

Object form (when you hold the `$hooks` instance): `$hooks->on(...)`,
`$hooks->emit(...)`, `$hooks->filter(...)`, `$hooks->apply(...)`,
`HookManager::global()`.

### Core hook reference

Actions fired by the engine — `gc_on('tag', fn)` to listen:

| Action | Args | Fired where | Purpose |
|--------|------|-------------|---------|
| `manage.sidebar.nav` | `$base, $activeNav` | admin layout | add admin sidebar items (echo `<li>`) |
| `theme.head` | — | theme `<head>` | inject CSS/meta into the front‑end head |
| `theme.footer` | — | before `</body>` | inject JS/widgets (chat, loaders) |
| `theme.nav.extra` | `$base` | header right cluster | add header icons (e.g. cart) |
| `the_content` *(filter)* | `$html` | post/page render | transform body HTML |
| `page.render` *(filter)* | `$carry, $post, $request` | Theme page/home | return a `Response` to fully render a page (e.g. builder) |
| `page.intercept` *(filter)* | `$existing, $post, $request` | Theme page | redirect‑style takeover for a page slug |
| `login.success` | `$userId` | after login | post‑login side effects (2FA, audit) |
| `login.redirect` *(filter)* | `$redirect, $userId, $base` | after login | change the post‑login destination |
| `login_form_buttons` | — | login form | add buttons/links to the login form |
| `post.created` / `post.updated` / `post.deleted` | `$id, …` | post CRUD | react to content changes |
| `user.created` | `$data` | user create | react to new users |
| `settings.saved` | `$data` | settings save | react to settings changes |
| `admin.notify` | `$subject, $html, $ctaUrl?, $ctaText?` | anywhere | send an email to the admin |
| `manage.profile.cards` | `$user, $base` | profile page | add cards to the admin profile |
| `manage.page_form.topbar` | `$post, $base` | page editor | add toolbar buttons (builder) |
| `user.panel.nav` | `$base, $activeNav` | user panel | add front‑end user‑panel nav |
| `user.password.changed` / `user.profile.updated` | `$userId, …` | profile | react to account changes |

Send an admin email from anywhere:

```php
gc_emit('admin.notify', 'New order #123', '<p>Total: 50 GEL</p>',
        gc_setting('site_url').'/manage/store', 'View order');
```

---

## 9. Plugins

### Anatomy

```
plugins/my-plugin/
  bootstrap.php          required — auto-required on every request
  plugin.json            metadata (name, description, version, author, requires)
  database/migration.php returns an object with up(Connection)/down(Connection)
  src/                   PSR-4-ish classes (registered via a local spl_autoload)
  views/                 PHP view templates
  lang/en.php, ka.php    plugin-owned translations (optional)
```

`PluginLoader` scans `plugins/`, skips folders containing a `.disabled` marker,
and `require`s each `bootstrap.php` inside a try/catch. Four variables are in
scope inside `bootstrap.php`:

| Variable | Type | Use |
|----------|------|-----|
| `$router` | `Router` | register routes |
| `$container` | `Container` | bind/resolve services |
| `$hooks` | `HookManager` | `$hooks->on(...)` (or use `gc_on`) |
| `$pluginDir` | `string` | absolute path to the plugin folder |

### Self‑migration (create tables when missing)

Because force‑enabled plugins are not "activated" through the panel, create
your tables defensively in `bootstrap.php` (idempotent `CREATE TABLE IF NOT
EXISTS` in `up()`):

```php
try {
    $conn = $container->get(Connection::class);
    if (empty($conn->query("SHOW TABLES LIKE 'myplugin_items'"))) {
        (require $pluginDir . '/database/migration.php')->up($conn);
    }
} catch (\Throwable) {}
```

### Minimal plugin example

```php
<?php
// plugins/hello/bootstrap.php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use Hello\HelloController;

spl_autoload_register(static function (string $c) use ($pluginDir): void {
    if (!str_starts_with($c, 'Hello\\')) return;
    $f = $pluginDir . '/src/' . str_replace('\\', '/', substr($c, 6)) . '.php';
    if (is_file($f)) require_once $f;
});

// self-migration
try {
    $conn = $container->get(Connection::class);
    if (empty($conn->query("SHOW TABLES LIKE 'hello_notes'"))) {
        (require $pluginDir.'/database/migration.php')->up($conn);
    }
} catch (\Throwable) {}

// DI + routes
$container->bind(HelloController::class, fn($c) => new HelloController($c->get(Connection::class)));
$router->get('/hello', [HelloController::class, 'index']);

// front-end hook: inject into <head>
gc_on('theme.head', static function (): void {
    echo "<meta name=\"hello\" content=\"1\">";
});

// admin sidebar entry
gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $cls = $activeNav === 'hello' ? 'active' : '';
    echo '<li><a href="'.htmlspecialchars($base.'/manage/hello').'" class="'.$cls.'">'
       . '<span class="nav-icon material-symbols-outlined">waving_hand</span> Hello</a></li>';
}, 50);
```

Rendering an admin page from a plugin: build the inner HTML into `$content`,
inject the variables the shared admin layout expects (see
[Admin panel conventions](#16-admin-panel-conventions)), then
`include themes/default/views/manage/layout.php`. `gc-lazyloader` and `gcsms`
are good reference plugins.

---

## 10. Core modules

Located in `src/Modules/*`. Each is a small package of
Repository (data) + Service (logic) + Controller (HTTP), bound in
`bootstrap/app.php`.

| Module | Responsibility |
|--------|----------------|
| `Auth` | JWT REST API (`/api/v1/auth/*`), `JwtService`, `AuthMiddleware` |
| `Login` | Cookie/session admin login, `SessionManager`, login throttling |
| `Post` | Posts **and** pages (`type` column), `PostController`, `PageController` |
| `Category` | Categories CRUD |
| `User` | Users + admin profile (`UserProfileController`) |
| `Role` | RBAC — roles, permissions, `AuthorizationService` |
| `Manage` | The admin panel (`ManageController`), activity log, todos, plugins, logs |
| `Settings` | Key/value site settings (`SettingsService`) |
| `Language` | Multi‑language: languages, post translations, language switch |
| `Media` | Upload validation + storage (`MediaService`, WebP conversion) |
| `Widget` | Widget areas + instances |
| `Menu` | Navigation menus + locations |
| `Notifications` | In‑app bell notifications |
| `Theme` | Front‑end rendering (`ThemeController`: home/post/page/category/404) |

---

## 11. Theming

The active theme is `themes/default/`.

```
themes/default/
  functions.php          registers widget areas + menu locations (loaded at boot)
  views/
    helpers.php          e(), t(), excerpt(), fmt_date(), flag_img() — required before any view
    layout.php           front-end shell ($content, $siteName, $base, $menuService, …)
    home.php post.php category.php 404.php
    partials/header.php  <head> + site header (logo/brand, nav, lang, theme.nav.extra)
    partials/footer.php  footer + theme.footer
    manage/layout.php    admin shell (sidebar, topbar, flash, CSRF, manage.sidebar.nav)
    manage/*.php         admin pages
  templates/             page templates: default, blank, landing, full-width, builder
  lang/en.php, ka.php    front-end translation pack
```

### View helpers

`require_once $viewsDir.'/helpers.php'` is always done before a view renders:

- `e($s)` — HTML‑escape. **Escape every dynamic value in views.**
- `t($key, $replace=[])` — translate via the active `LanguageService`
  (falls back to the key).
- `excerpt($html, $len)`, `fmt_date($iso, $format=null)`, `flag_img($code)`.

### Page templates & rendering

`ThemeController::page()`/`home()` choose a template from the post's `template`
column: `default`, `blank` (content only), `landing`, `full-width`, and
`builder`. For `builder`, the engine calls the `page.render` filter so the
GoniBuilder plugin can render the page; if no plugin handles it, the default
theme renders. Post/page body HTML is passed through the `the_content` filter.

### Header branding

The site header shows, in priority order: an uploaded **logo** and/or the
**site name + tagline** next to it; if neither a logo nor a name/tagline is
configured, the built‑in **GoniCore** mark is shown. The favicon is taken from
the `site_favicon` setting. Both are set in **Manage → Settings → General**.

---

## 12. Settings

Key/value store in the `settings` table, cached per request.

```php
$settings = $container->get(SettingsService::class);
$name = $settings->get('site_name', 'GoniCore');
$settings->set('site_name', 'My Site');
$settings->bulk(['k1' => 'v1', 'k2' => 'v2']);
$all = $settings->all();

// typed helpers
$settings->siteName(); $settings->siteTagline(); $settings->siteLogo();
$settings->siteFavicon(); $settings->postsPerPage(); $settings->timezone();

// from a plugin / global scope
$v = gc_setting('site_url', '');  gc_set_setting('site_url', 'https://…');
```

Writes upsert (update if the key exists, insert otherwise). `SettingsService::boot()`
applies the timezone to PHP at startup.

---

## 13. Notifications

In‑app "bell" notifications shown in the admin topbar.

```php
$notif = $container->get(NotificationService::class);
$notif->postCreated($title, $authorId);           // broadcast to all admins
$notif->userRegistered($name, $email);            // broadcast
$notif->system('Title', 'Message', $userId=null); // generic; null = broadcast
$list   = $notif->forUser($userId);               // own + broadcast, newest first
$unread = $notif->unreadCount($userId);
```

A `user_id` of `null` means **broadcast** (every admin sees it). The reader
methods merge a user's own rows with broadcasts. (Broadcast matching relies on
the QueryBuilder `IS NULL` behaviour — see §7.)

---

## 14. Internationalization (i18n)

Three independent translation domains:

1. **Engine pack** `lang/{code}.php` — admin‑only strings, resolved by `t()`.
2. **Theme pack** `themes/default/lang/{code}.php` — front‑end strings; merged
   over the engine pack by `LanguageService` so `t()` resolves both.
3. **Plugin packs** `plugins/<x>/lang/{code}.php` — plugins must **not** use the
   engine pack. Build a translator bound to the plugin's own pack:

```php
$t = gc_plugin_translator($pluginDir);   // follows the site language, falls back en→key
echo $t('settings.title');
echo $t('greeting', ['name' => 'Ana']);  // ":name" placeholder replacement
```

`en.php` and `ka.php` must stay in **key parity**. Languages are managed at
**Manage → Languages**; the front‑end switch is `/lang/switch/{code}`.

---

## 15. Auth, sessions, RBAC & security

- **Admin login** — session based (`SessionManager`, cookie `gc_session`).
  `gc_is_logged_in()`, `gc_current_user_id()`, `gc_current_user()`. Login is
  rate‑limited (`LoginThrottle` + `login_attempts` table).
- **REST API** — stateless JWT (`JwtService`); protect routes with
  `->middleware($container->get(AuthMiddleware::class))`. Endpoints under
  `/api/v1/*`.
- **RBAC** — `roles`, `permissions`, `role_permissions`; ask
  `gc_user_can('posts.delete')` or `AuthorizationService::can($userId, $perm)`.
  Guard against a missing RBAC schema (fall back to an `admin` role check).
- **CSRF** — `SessionManager::csrfToken()` / `verifyCsrf()`. The admin layout
  auto‑injects a `_csrf` hidden field into every POST form and exposes
  `window.gcCsrf` for fetch. Verify on state‑changing POSTs; reject with **403**.
- **Uploads** — `MediaService` validates the real MIME (via `finfo`, not the
  client header), enforces an extension allow‑list and a 20 MB cap, stores under
  `storage/media/Y/m/<random>.<ext>`, and re‑encodes images to WebP.
- **Public webhooks/endpoints** — authenticate with a shared token
  (e.g. gcsms' `X-Webhook-Token`), not the session; return standard codes.
- **Secrets** never go in git: `.env`, API keys and tokens live in `.env` or the
  DB. `.gitignore` excludes `.env`, `/vendor/`, `/storage/`, `*.zip` and uploads.

---

## 16. Admin panel conventions

All admin pages render inside `themes/default/views/manage/layout.php`. Build
the inner HTML into `$content`, then include the layout. Variables the layout
reads:

| Variable | Purpose |
|----------|---------|
| `$pageTitle` | topbar/`<title>` |
| `$activeNav` | highlights the matching sidebar item |
| `$base` | app base path for URLs |
| `$siteName` | brand/title |
| `$content` | the inner page HTML |
| `$user` | current user (bell/profile) |
| `$notifList`, `$notifUnread` | bell dropdown |
| `$panelLangs`, `$currentLangCode` | language switcher |
| `$flashMsg`, `$flashIcon` | one‑shot SweetAlert toast |
| `$csrfToken` | injected into POST forms / `window.gcCsrf` |
| `$topbarActions` | extra HTML on the right of the topbar |

Conventions:

- **Flash messages:** `$this->flash($msg, $icon)` → SessionManager → SweetAlert
  toast (`gcToast`). No `?success=`/`?error=` query params. Controller flash
  strings are literal (not `t()` — `t()` is a view helper).
- **Confirm dialogs:** `gcConfirm(btn, title, text, confirmText, color)` submits
  `btn.closest('form')` on confirm.
- **Icons:** Material Symbols (`<span class="material-symbols-outlined">name</span>`).
- **Plugin sidebar items:** register on `manage.sidebar.nav` (the layout resolves
  the global HookManager itself, so items appear on every admin page).
- **Full width:** admin pages fill the screen — do not cap the root container
  with `max-width`; use grids instead.
- New admin action = `flash()` + matching `t()` keys in both lang files (for view
  strings) + a POST route through the controller's `guard()`.

---

## 17. CLI

`bin/gonicore` runs console commands (`src/Core/Console`):

```bash
php bin/gonicore migrate        # run pending migrations (database/migrations/*)
php bin/gonicore user:create    # create a user
php bin/gonicore install        # install helper
```

`ConsoleKernel` dispatches to `InstallCommand`, `MigrateCommand`,
`UserCreateCommand`.

---

## 18. Conventions & gotchas

- **`bootstrap/app.php` is authoritative** for DI bindings and routes;
  `routes/web.php` is dead. Adding a controller method that posts to a new URL
  requires registering that route here.
- **Route params are single‑segment.** A form that posts to `/manage/posts` for
  "create" and `/manage/posts/{id}` for "update" needs **both** routes.
- **Hooks must be loaded before plugins.** `bootstrap` must call
  `HookManager::setGlobalInstance($hooks)` and `require src/Core/functions.php`
  before `PluginLoader::load()`, or every `gc_on()` fatals and all plugins are
  silently skipped.
- **Use the canonical hook verbs** (`on/emit/filter/apply`); never `addAction`.
- **Standard HTTP codes only** (403 for CSRF, 422 for validation; avoid 419).
- **`where('col','=',null)` ⇒ `IS NULL`** (and `'!='` ⇒ `IS NOT NULL`).
- **Plugins own their tables** (self‑migrate); the installer never creates them.
- **Escape in views** with `e()`; **whitelist** any value inlined into CSS/SQL.
- **Plugins translate via `gc_plugin_translator`**, never the engine `t()`.
- After editing `install.php` (no autoloader, only parsed when run),
  `php -l public/install.php` — a truncation only surfaces at parse time.
```
