# GoniCore — ძრავის დოკუმენტაცია

GoniCore არის მცირე, ფრეიმვორქისგან დამოუკიდებელი PHP CMS, WordPress-ისებური
plugin/hook არქიტექტურით, ნულიდან აგებული (Laravel/Symfony გარეშე). იგი
API-first და მკაცრად მოდულურია: თხელი ბირთვი (`src/Core`) აერთიანებს დომენურ
მოდულებსა (`src/Modules`) და შეუზღუდავ რაოდენობის პლაგინს (`plugins/*`).

- **სტეკი:** PHP 8.2+, MySQL/MariaDB (PDO), vanilla JS UI-სთვის, Composer (PSR-4).
- **შესვლის წერტილი:** `public/index.php` (web root მიუთითე `/public`-ზე).
- **Namespace-ები:** `GoniCore\Core\*`, `GoniCore\Modules\*`, `GoniCore\Shared\*`.

> ეს დოკუმენტი აღწერს **ძრავას**. თითო პლაგინის ქცევა მის საქაღალდეშია. ყველა
> საჯარო იდენტიფიკატორი (კლასები, hook-ები, settings-ის გასაღებები) ინგლისურია —
> კოდს რომ ემთხვეოდეს. (ინგლისური ვერსია: [`ENGINE.md`](ENGINE.md).)

## სარჩევი

1. არქიტექტურის მიმოხილვა
2. დირექტორიების სტრუქტურა
3. Request-ის სასიცოცხლო ციკლი
4. კონფიგურაცია და გარემო
5. Dependency injection კონტეინერი
6. Routing — Router, Request, Response
7. ბაზა — Connection, QueryBuilder, migrations
8. Hooks და filters
9. პლაგინები
10. ბირთვის მოდულები
11. თემები (theming)
12. Settings
13. ნოტიფიკაციები
14. ინტერნაციონალიზაცია (i18n)
15. ავთენტიფიკაცია, სესიები, RBAC და უსაფრთხოება
16. ადმინ პანელის კონვენციები
17. CLI
18. კონვენციები და ხაფანგები

---

## 1. არქიტექტურის მიმოხილვა

```
HTTP request
   │
   ▼
public/index.php ──> bootstrap/app.php ──> Application::run()
   (gate + autoload)   (ყველაფრის აგება)      (dispatch + send)
        │                    │
        │                    ├── Env + Config
        │                    ├── Container (DI)  ← ყველა binding აქ
        │                    ├── Router          ← ყველა route აქ
        │                    ├── HookManager (+ global) + functions.php
        │                    ├── PluginLoader.load(plugins/)
        │                    └── return Application(container, router, config)
        ▼
   Router::dispatch(Request) ──> controller ──> Response ──> send()
```

ბირთვი მიზანმიმართულად **plugin-agnostic**-ია: ის არასდროს hardcode-ავს პლაგინს.
პლაგინები ძრავას აფართოებენ მხოლოდ **hook-სისტემით** (`gc_on` / `gc_filter`) და
მათი `bootstrap.php`-ისთვის გადაცემულ ობიექტებზე route-ების/სერვისების
რეგისტრაციით.

მთავარი დიზაინ-თვისებები:

- **Immutable value objects** — `Request`, `Response` და `QueryBuilder` ყოველ
  მუტაციაზე ახალ ეგზემპლარს აბრუნებენ; ორიგინალი არ იცვლება.
- **Lazy DB** — PDO კავშირი იხსნება პირველ query-ზე, არა boot-ზე.
- **Fail-soft პლაგინები** — boot-ზე ჩავარდნილი პლაგინი ლოგდება და გამოტოვდება;
  საიტი არ ვარდება.

---

## 2. დირექტორიების სტრუქტურა

```
public/                 Web root (index.php front controller, install.php, assets)
bootstrap/app.php       კომპოზიციის ფესვი: DI bindings + routes + plugin load
config/                 app.php, auth.php, database.php (.env-დან იკითხება)
src/
  Core/                 ძრავა
    Application.php      Kernel (handle/run, შეცდომების დამუშავება)
    Container/          PSR-11 DI კონტეინერი (auto-wiring)
    Http/               Router, Route, Request, Response, Middleware, ErrorPage
    Hooks/              HookManager, PluginLoader
    Database/           Connection, QueryBuilder, Migration, Migrator
    Config/             Config, Env
    Mail/               MailService (php mail() + SMTP)
    Logging/            ErrorLogger (storage/logs)
    Shortcodes/         ShortcodeManager
    Widgets/            WidgetManager
    Validation/         Validator
    functions.php       გლობალური gc_* plugin API (Hooks/Settings/Auth helpers)
  Modules/              დომენური მოდულები (Post, Category, User, Auth, Manage, …)
  Shared/               Contracts + Support (Str, Paginator)
plugins/<slug>/         თითო პლაგინი; სავალდებულოა bootstrap.php
themes/default/         აქტიური თემა (views, partials, helpers, lang)
database/migrations/    ბირთვის სქემის migration-ები (0001_…php …)
lang/                   ძრავის (admin) თარგმანები: en.php, ka.php
storage/                Runtime: media uploads, logs (git-ignored)
bin/gonicore            CLI შესვლის წერტილი
```

---

## 3. Request-ის სასიცოცხლო ციკლი

1. **`public/index.php`** — ინსტალაციის gate (გადაამისამართებს `install.php`-ზე
   სანამ აპი არ დაინსტალირდება), მერე `require vendor/autoload.php`, მერე
   `$app = require bootstrap/app.php; $app->run();`.
2. **`bootstrap/app.php`** (კომპოზიციის ფესვი) თანმიმდევრობით:
   1. `Env::load(.env)` და `config/*.php` → `Config`.
   2. ქმნის `Container`-ს და **არეგისტრირებს გლობალურად**
      (`Container::setGlobalInstance($container)`).
   3. აბაინდებს infrastructure-ს, repository-ებს, service-ებსა და controller-ებს.
   4. არეგისტრირებს ყველა route-ს Router-ზე.
   5. **აყენებს გლობალურ HookManager-ს და ტვირთავს plugin API-ს:**
      `HookManager::setGlobalInstance($hooks); require src/Core/functions.php;` —
      ეს **აუცილებლად** პლაგინების ჩატვირთვამდე, თორემ ყოველი `gc_on()` fatal-ს იძლევა.
   6. `PluginLoader->load(plugins/, …)` — ტვირთავს ყველა ჩართულ პლაგინს.
   7. არეგისტრირებს ბირთვის `admin.notify` listener-ს (admin-მეილი `MailService`-ით).
   8. `return new Application($container, $router, $config)`.
3. **`Application::run()`** → `handle(Request::capture())` → `Router::dispatch()`
   → controller აბრუნებს `Response`-ს → `Response::send()` → `exit`.
4. **შეცდომები:** `HttpException` (404/405/…) → web-ზე თემის 404, API-ზე JSON;
   სხვა `Throwable` → ლოგდება და 500 ბრუნდება (იხ. `Application` /
   `Core/Http/ErrorPage.php`).

---

## 4. კონფიგურაცია და გარემო

`.env` (git-ignored) ინახავს საიდუმლოებს; `config/*.php` მათ `Env`-ით კითხულობს:

```php
// config/database.php
return [
    'driver'   => Env::get('DB_DRIVER', 'mysql'),
    'host'     => Env::get('DB_HOST', '127.0.0.1'),
    'port'     => (int) Env::get('DB_PORT', '3306'),
    'dbname'   => Env::require('DB_NAME'),     // აკლია → throw
    'username' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASS', ''),
    'charset'  => Env::get('DB_CHARSET', 'utf8mb4'),
];
```

კონფიგის წაკითხვა კონტეინერით:

```php
$debug = (bool) $container->get(Config::class)->get('app.debug', false);
$secret = $container->get(Config::class)->require('auth.jwt_secret');
```

სავალდებულო `.env` გასაღებები: `DB_NAME`, `JWT_SECRET` (≥32 სიმბოლო).
არასავალდებულო: `APP_NAME`, `APP_ENV`, `APP_URL`, `APP_DEBUG`, `DB_*`,
`JWT_TTL`, `MEDIA_STORAGE_PATH`.

---

## 5. Dependency injection კონტეინერი

`GoniCore\Core\Container\Container` — PSR-11, რეგისტრაციის სამი სტილით + reflection auto-wiring.

```php
$c->instance(Config::class, $config);                 // უკვე აგებული singleton
$c->singleton(Connection::class, fn($c) => Connection::fromConfig(...)); // ერთხელ
$c->bind(PostController::class, fn($c) => new PostController(...));       // ყოველ get()-ზე
$obj = $c->get(SomeClass::class);                     // resolve (auto-wire თუ unbound)
$has = $c->has(SomeClass::class);
```

Resolution-ის თანმიმდევრობა: cached instance → singleton factory (cached) →
bind factory (fresh) → **auto-wiring** (constructor-ის reflection, type-hint-იანი
კლას-პარამეტრების რეკურსიული resolve, default/null სხვა შემთხვევაში).

გლობალური წვდომა (`functions.php` და პლაგინები იყენებენ):

```php
Container::setGlobalInstance($c);   // bootstrap-ში ერთხელ
$svc = Container::global()->get(SettingsService::class);
$svc = gc_container()->get(...);    // მოსახერხებელი wrapper
```

---

## 6. Routing — Router, Request, Response

### Router

ყველა route inline-ად რეგისტრირდება `bootstrap/app.php`-ში (ფაილი
`routes/web.php` **არ გამოიყენება**).

```php
$router->get('/post/{slug}',  [ThemeController::class, 'post']);
$router->post('/login',       [LoginController::class, 'processLogin']);
$router->group('/manage', function (Router $r) {
    $r->get('',          [ManageController::class, 'dashboard']);
    $r->post('/posts',   [ManageController::class, 'postCreate']);
});
$router->get('/api/v1/me', [AuthController::class, 'me'])->middleware($authMw);
```

- მეთოდები: `get/post/put/patch/delete/options`, + `group(prefix, cb)` (nestable).
- **Path პარამეტრები** `{name}` და მხოლოდ **ერთ სეგმენტს** ემთხვევა
  (`/posts/{id}` ემთხვევა `/posts/5`-ს, არა `/posts/5/edit`-ს — ის ცალკე
  დაარეგისტრირე). წაიკითხე `$request->getAttribute('id')`-ით.
- Handler — `[Class, 'method']` (კონტეინერით იხსნება) ან ნებისმიერი callable.
- **Subdirectory-aware:** `Request::path()` აცილებს install base-path-ს
  (`/goni/GoniCore`), ანუ route-ები root-relative იწერება; `Request::basePath()`
  აბრუნებს ამ პრეფიქსს view-ებში URL-ების ასაგებად.
- ბოლო `/` იგნორირდება (გარდა `/`-ისა). არცერთი → `HttpException(404)`;
  path ემთხვევა, მეთოდი — არა → `HttpException(405)`.

### Request (immutable)

```php
$r->method(); $r->path(); $r->basePath(); $r->uri();
$r->query('page', '1'); $r->post('title'); $r->input('q');   // query→post→json
$r->json();                 // დეკოდირებული JSON body (array)
$r->header('X-Webhook-Token'); $r->isJson(); $r->cookie('gc_lang');
$r->server('HTTP_HOST'); $r->ip(); $r->files();
$r->getAttribute('id');     // route param
$r2 = $r->withAttribute('k', $v);  // clone-ს აბრუნებს
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

გამოიყენე **მხოლოდ სტანდარტული HTTP კოდები**. არასტანდარტული (მაგ. 419)
Apache-ს 500-ს ამოაგდებინებს — CSRF-ზე `403`, ვალიდაციაზე `422`.

---

## 7. ბაზა — Connection, QueryBuilder, migrations

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

PDO: `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES = false`; შეცდომები
`DatabaseException`-ში იხვევა.

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

- დაშვებული ოპერატორები: `= != <> < > <= >= LIKE "NOT LIKE" IN "NOT IN"`.
- `IN`/`NOT IN` იღებს მასივს და placeholders-ად იშლება (ცარიელი მასივი →
  ყოველთვის-false / ყოველთვის-true).
- **NULL დამუშავება:** `where('col', '=', null)` → `col IS NULL`, ხოლო
  `'!='`/`'<>'` → `IS NOT NULL` (`col = NULL` SQL-ში არასდროს ემთხვევა).
- ყოველი მეთოდი clone-ს აბრუნებს — builder-ები თავისუფლად გამოიყენე/დააფორკე.

### Migrations

ბირთვის სქემის migration-ები `database/migrations/NNNN_name.php`-შია და
ეშვება installer-ით (`public/install.php`, რომელშიც სრული სქემაა) და CLI-ით
(`bin/gonicore migrate`); გაშვებული სახელები `_migrations` ცხრილში ინახება.
**პლაგინის ცხრილებს installer არ ქმნის** — თითო პლაგინი თავის სქემას ფლობს
(იხ. [პლაგინები](#9-პლაგინები)).

---

## 8. Hooks და filters

Hook-სისტემა მთავარი გაფართოების წერტილია. `HookManager` ინახავს ორ რეესტრს —
**actions** (fire-and-forget) და **filters** (მნიშვნელობის გარდაქმნა).

> **კანონიკური API.** ძრავა იყენებს უნიკალურ ზმნებს `on / emit / off / has`
> (actions) და `filter / apply / unfilter / hasFilter` (filters).
> `addAction`/`doAction` **არ არსებობს** — ეს უცხო ვარიანტია და არ უნდა
> დაბრუნდეს.

### გლობალური ფუნქციები (`src/Core/functions.php`)

ხელმისაწვდომია ყველა პლაგინში და bootstrap-ის შემდეგ:

```php
// Actions
gc_on(string $tag, callable $fn, int $priority = 10): void   // უფრო დაბალი ჯერ
gc_emit(string $tag, mixed ...$args): void
gc_off(string $tag, ?int $priority = null): void
gc_has(string $tag): bool

// Filters (callback იღებს value + extra args, აუცილებლად აბრუნებს value-ს)
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

ობიექტური ფორმა (როცა `$hooks` გაქვს): `$hooks->on(...)`, `$hooks->emit(...)`,
`$hooks->filter(...)`, `$hooks->apply(...)`, `HookManager::global()`.

### ბირთვის hook-ების ცნობარი

ძრავის მიერ გაშვებული actions — `gc_on('tag', fn)` მოსასმენად:

| Action | Args | სად ეშვება | დანიშნულება |
|--------|------|------------|-------------|
| `manage.sidebar.nav` | `$base, $activeNav` | admin layout | admin sidebar-ის პუნქტები (echo `<li>`) |
| `theme.head` | — | თემის `<head>` | CSS/meta front-end head-ში |
| `theme.footer` | — | `</body>`-მდე | JS/widgets (chat, loaders) |
| `theme.nav.extra` | `$base` | header-ის მარჯვენა ჯგუფი | header-ის აიქონები (მაგ. კალათა) |
| `the_content` *(filter)* | `$html` | post/page render | body HTML-ის გარდაქმნა |
| `page.render` *(filter)* | `$carry, $post, $request` | Theme page/home | `Response`-ის დაბრუნება გვერდის სრულად დასარენდერებლად (builder) |
| `page.intercept` *(filter)* | `$existing, $post, $request` | Theme page | slug-ის redirect-ტიპის გადაჭერა |
| `login.success` | `$userId` | login-ის შემდეგ | post-login side-effects (2FA, audit) |
| `login.redirect` *(filter)* | `$redirect, $userId, $base` | login-ის შემდეგ | post-login მისამართის შეცვლა |
| `login_form_buttons` | — | login ფორმა | login ფორმაში ღილაკები/ბმულები |
| `post.created` / `post.updated` / `post.deleted` | `$id, …` | post CRUD | კონტენტის ცვლილებებზე რეაგირება |
| `user.created` | `$data` | user create | ახალ მომხმარებლებზე რეაგირება |
| `settings.saved` | `$data` | settings save | settings-ის ცვლილებებზე რეაგირება |
| `admin.notify` | `$subject, $html, $ctaUrl?, $ctaText?` | ნებისმიერ ადგილას | admin-ისთვის მეილის გაგზავნა |
| `manage.profile.cards` | `$user, $base` | profile გვერდი | admin profile-ზე ბარათები |
| `manage.page_form.topbar` | `$post, $base` | page editor | toolbar ღილაკები (builder) |
| `user.panel.nav` | `$base, $activeNav` | user panel | front-end user-panel nav |
| `user.password.changed` / `user.profile.updated` | `$userId, …` | profile | ანგარიშის ცვლილებებზე რეაგირება |

admin-მეილის გაგზავნა ნებისმიერ ადგილას:

```php
gc_emit('admin.notify', 'New order #123', '<p>Total: 50 GEL</p>',
        gc_setting('site_url').'/manage/store', 'View order');
```

---

## 9. პლაგინები

### ანატომია

```
plugins/my-plugin/
  bootstrap.php          სავალდებულო — ყოველ request-ზე იტვირთება
  plugin.json            მეტამონაცემები (name, description, version, author, requires)
  database/migration.php აბრუნებს ობიექტს up(Connection)/down(Connection)-ით
  src/                   PSR-4-ისებური კლასები (ლოკალური spl_autoload-ით)
  views/                 PHP view-ები
  lang/en.php, ka.php    პლაგინის თარგმანები (არასავალდებულო)
```

`PluginLoader` ასკანერებს `plugins/`-ს, ტოვებს `.disabled` მარკერიან საქაღალდეებს
და თითო `bootstrap.php`-ს `require`-ს try/catch-ში. `bootstrap.php`-ში scope-შია
ოთხი ცვლადი:

| ცვლადი | ტიპი | დანიშნულება |
|--------|------|-------------|
| `$router` | `Router` | route-ების რეგისტრაცია |
| `$container` | `Container` | სერვისების bind/resolve |
| `$hooks` | `HookManager` | `$hooks->on(...)` (ან `gc_on`) |
| `$pluginDir` | `string` | პლაგინის საქაღალდის აბსოლუტური გზა |

### Self-migration (ცხრილების შექმნა, თუ აკლია)

რადგან force-enabled პლაგინები პანელიდან არ "აქტიურდება", შექმენი ცხრილები
თავდაცვითად `bootstrap.php`-ში (იდემპოტენტური `CREATE TABLE IF NOT EXISTS`
`up()`-ში):

```php
try {
    $conn = $container->get(Connection::class);
    if (empty($conn->query("SHOW TABLES LIKE 'myplugin_items'"))) {
        (require $pluginDir . '/database/migration.php')->up($conn);
    }
} catch (\Throwable) {}
```

### მინიმალური პლაგინის მაგალითი

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

// front-end hook: <head>-ში ჩამატება
gc_on('theme.head', static function (): void {
    echo "<meta name=\"hello\" content=\"1\">";
});

// admin sidebar პუნქტი
gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $cls = $activeNav === 'hello' ? 'active' : '';
    echo '<li><a href="'.htmlspecialchars($base.'/manage/hello').'" class="'.$cls.'">'
       . '<span class="nav-icon material-symbols-outlined">waving_hand</span> Hello</a></li>';
}, 50);
```

პლაგინიდან admin გვერდის რენდერი: ააგე შიდა HTML `$content`-ში, გადაეცი
shared admin layout-ის მოსალოდნელი ცვლადები (იხ.
[ადმინ პანელის კონვენციები](#16-ადმინ-პანელის-კონვენციები)), მერე
`include themes/default/views/manage/layout.php`. `gc-lazyloader` და `gcsms`
კარგი reference-პლაგინებია.

---

## 10. ბირთვის მოდულები

`src/Modules/*`-შია. თითო — Repository (data) + Service (logic) + Controller
(HTTP), რომელიც `bootstrap/app.php`-ში იბაინდება.

| მოდული | პასუხისმგებლობა |
|--------|------------------|
| `Auth` | JWT REST API (`/api/v1/auth/*`), `JwtService`, `AuthMiddleware` |
| `Login` | Cookie/session admin login, `SessionManager`, login throttling |
| `Post` | პოსტები **და** გვერდები (`type` სვეტი), `PostController`, `PageController` |
| `Category` | კატეგორიების CRUD |
| `User` | მომხმარებლები + admin profile (`UserProfileController`) |
| `Role` | RBAC — roles, permissions, `AuthorizationService` |
| `Manage` | admin პანელი (`ManageController`), activity log, todos, plugins, logs |
| `Settings` | key/value site settings (`SettingsService`) |
| `Language` | მრავალენოვნება: languages, post translations, language switch |
| `Media` | upload ვალიდაცია + შენახვა (`MediaService`, WebP კონვერტაცია) |
| `Widget` | widget areas + ეგზემპლარები |
| `Menu` | ნავიგაციის მენიუები + locations |
| `Notifications` | in-app ზარის ნოტიფიკაციები |
| `Theme` | front-end რენდერი (`ThemeController`: home/post/page/category/404) |

---

## 11. თემები (theming)

აქტიური თემა — `themes/default/`.

```
themes/default/
  functions.php          არეგისტრირებს widget areas + menu locations (boot-ზე)
  views/
    helpers.php          e(), t(), excerpt(), fmt_date(), flag_img() — view-მდე იტვირთება
    layout.php           front-end shell ($content, $siteName, $base, $menuService, …)
    home.php post.php category.php 404.php
    partials/header.php  <head> + site header (logo/brand, nav, lang, theme.nav.extra)
    partials/footer.php  footer + theme.footer
    manage/layout.php    admin shell (sidebar, topbar, flash, CSRF, manage.sidebar.nav)
    manage/*.php         admin გვერდები
  templates/             page templates: default, blank, landing, full-width, builder
  lang/en.php, ka.php    front-end თარგმანი
```

### View helpers

`require_once $viewsDir.'/helpers.php'` ყოველთვის კეთდება view-მდე:

- `e($s)` — HTML-escape. **view-ში ყველა დინამიური მნიშვნელობა escape-ი.**
- `t($key, $replace=[])` — თარგმანი აქტიური `LanguageService`-ით (key-ზე fallback).
- `excerpt($html, $len)`, `fmt_date($iso, $format=null)`, `flag_img($code)`.

### Page templates & რენდერი

`ThemeController::page()`/`home()` template-ს ირჩევს post-ის `template` სვეტიდან:
`default`, `blank` (მხოლოდ კონტენტი), `landing`, `full-width`, `builder`.
`builder`-ისთვის ძრავა `page.render` filter-ს იძახებს, რომ GoniBuilder-მა
დაარენდეროს; თუ პლაგინი არ ამუშავებს — default თემა რენდერავს. Post/page body
HTML გადის `the_content` filter-ში.

### Header branding

Header აჩვენებს, პრიორიტეტით: ატვირთულ **ლოგოს** და/ან **საიტის სახელს +
სლოგანს** გვერდიგვერდ; თუ არც ლოგო, არც სახელი/სლოგანია — ჩაშენებული
**GoniCore** ნიშანი. Favicon `site_favicon` setting-იდანაა. ორივე
**Manage → Settings → General**-ში დგება.

---

## 12. Settings

key/value საცავი `settings` ცხრილში, request-ზე cache-ით.

```php
$settings = $container->get(SettingsService::class);
$name = $settings->get('site_name', 'GoniCore');
$settings->set('site_name', 'My Site');
$settings->bulk(['k1' => 'v1', 'k2' => 'v2']);
$all = $settings->all();

// typed helpers
$settings->siteName(); $settings->siteTagline(); $settings->siteLogo();
$settings->siteFavicon(); $settings->postsPerPage(); $settings->timezone();

// პლაგინიდან / გლობალურად
$v = gc_setting('site_url', '');  gc_set_setting('site_url', 'https://…');
```

ჩაწერა upsert-ია (განაახლებს თუ key არსებობს, თორემ insert). `SettingsService::boot()`
PHP-ს timezone-ს ანიჭებს startup-ზე.

---

## 13. ნოტიფიკაციები

In-app "ზარის" ნოტიფიკაციები admin topbar-ში.

```php
$notif = $container->get(NotificationService::class);
$notif->postCreated($title, $authorId);           // broadcast ყველა admin-ზე
$notif->userRegistered($name, $email);            // broadcast
$notif->system('Title', 'Message', $userId=null); // გენერიკული; null = broadcast
$list   = $notif->forUser($userId);               // own + broadcast, ახლები ჯერ
$unread = $notif->unreadCount($userId);
```

`user_id = null` ნიშნავს **broadcast**-ს (ყველა admin ხედავს). წამკითხავი
მეთოდები აერთიანებენ მომხმარებლის own row-ებს broadcast-ებთან. (Broadcast-ის
დამთხვევა QueryBuilder-ის `IS NULL` ქცევას ეყრდნობა — იხ. §7.)

---

## 14. ინტერნაციონალიზაცია (i18n)

თარგმანის სამი დამოუკიდებელი domain:

1. **ძრავის pack** `lang/{code}.php` — admin-ის სტრიქონები, `t()`-ით.
2. **თემის pack** `themes/default/lang/{code}.php` — front-end სტრიქონები;
   `LanguageService` ძრავის pack-ზე ალაგებს, ანუ `t()` ორივეს ხსნის.
3. **პლაგინის pack** `plugins/<x>/lang/{code}.php` — პლაგინებმა ძრავის pack
   **არ** უნდა გამოიყენონ. ააგე პლაგინის pack-ზე მიბმული translator:

```php
$t = gc_plugin_translator($pluginDir);   // საიტის ენას მიჰყვება, fallback en→key
echo $t('settings.title');
echo $t('greeting', ['name' => 'Ana']);  // ":name" placeholder ჩანაცვლება
```

`en.php` და `ka.php` **გასაღების paritet-ში** უნდა იყოს. ენები იმართება
**Manage → Languages**-ში; front-end გადართვა — `/lang/switch/{code}`.

---

## 15. ავთენტიფიკაცია, სესიები, RBAC და უსაფრთხოება

- **Admin login** — session-ზე (`SessionManager`, cookie `gc_session`).
  `gc_is_logged_in()`, `gc_current_user_id()`, `gc_current_user()`. Login
  rate-limited-ია (`LoginThrottle` + `login_attempts` ცხრილი).
- **REST API** — stateless JWT (`JwtService`); route-ები დაიცავი
  `->middleware($container->get(AuthMiddleware::class))`-ით. Endpoint-ები
  `/api/v1/*`-ში.
- **RBAC** — `roles`, `permissions`, `role_permissions`; იკითხე
  `gc_user_can('posts.delete')` ან `AuthorizationService::can($userId, $perm)`.
  დაიცავი თავი RBAC-სქემის არარსებობისგან (fallback `admin` როლის შემოწმებაზე).
- **CSRF** — `SessionManager::csrfToken()` / `verifyCsrf()`. Admin layout
  ავტომატურად ამატებს `_csrf` hidden-ს ყველა POST ფორმაში და `window.gcCsrf`-ს
  fetch-ისთვის. შეამოწმე state-changing POST-ებზე; უარყავი **403**-ით.
- **Uploads** — `MediaService` ამოწმებს რეალურ MIME-ს (`finfo`-ით, არა client
  header-ით), ამოქმედებს extension allow-list-ს და 20 MB ლიმიტს, ინახავს
  `storage/media/Y/m/<random>.<ext>`-ში და სურათებს WebP-ად აქცევს.
- **საჯარო webhooks/endpoints** — ავთენტიფიკაცია shared token-ით (მაგ. gcsms-ის
  `X-Webhook-Token`), არა session-ით; დააბრუნე სტანდარტული კოდები.
- **საიდუმლოები** არასდროს git-ში: `.env`, API keys და token-ები `.env`-ში ან
  ბაზაში. `.gitignore` რიცხავს `.env`, `/vendor/`, `/storage/`, `*.zip`, uploads.

---

## 16. ადმინ პანელის კონვენციები

ყველა admin გვერდი რენდერდება `themes/default/views/manage/layout.php`-ში.
ააგე შიდა HTML `$content`-ში, მერე include layout. ცვლადები, რომელსაც layout
კითხულობს:

| ცვლადი | დანიშნულება |
|--------|-------------|
| `$pageTitle` | topbar/`<title>` |
| `$activeNav` | შესაბამისი sidebar-პუნქტის გამოყოფა |
| `$base` | app base path URL-ებისთვის |
| `$siteName` | ბრენდი/სათაური |
| `$content` | გვერდის შიდა HTML |
| `$user` | მიმდინარე მომხმარებელი (bell/profile) |
| `$notifList`, `$notifUnread` | ზარის dropdown |
| `$panelLangs`, `$currentLangCode` | ენის გადამრთველი |
| `$flashMsg`, `$flashIcon` | ერთჯერადი SweetAlert toast |
| `$csrfToken` | POST ფორმებში / `window.gcCsrf` |
| `$topbarActions` | დამატებითი HTML topbar-ის მარჯვნივ |

კონვენციები:

- **Flash:** `$this->flash($msg, $icon)` → SessionManager → SweetAlert toast
  (`gcToast`). არა `?success=`/`?error=`. Controller-ის flash სტრიქონები
  ლიტერალურია (არა `t()` — `t()` view helper-ია).
- **Confirm:** `gcConfirm(btn, title, text, confirmText, color)` დადასტურებაზე
  `btn.closest('form')`-ს გზავნის.
- **Icons:** Material Symbols (`<span class="material-symbols-outlined">name</span>`).
- **პლაგინის sidebar:** `manage.sidebar.nav`-ზე დაარეგისტრირე (layout თვითონ
  ხსნის გლობალურ HookManager-ს, ანუ პუნქტები ყველა admin გვერდზე ჩანს).
- **Full width:** admin გვერდები ეკრანს ავსებენ — root container-ს `max-width`
  ნუ დაუდებ; grid-ები გამოიყენე.
- ახალი admin action = `flash()` + შესაბამისი `t()` გასაღებები ორივე lang-ფაილში
  (view-ის სტრიქონებისთვის) + POST route controller-ის `guard()`-ით.

---

## 17. CLI

`bin/gonicore` უშვებს console-ბრძანებებს (`src/Core/Console`):

```bash
php bin/gonicore migrate        # მიმდინარე migration-ები (database/migrations/*)
php bin/gonicore user:create    # მომხმარებლის შექმნა
php bin/gonicore install        # install helper
```

`ConsoleKernel` dispatch-ს უკეთებს `InstallCommand`, `MigrateCommand`,
`UserCreateCommand`-ს.

---

## 18. კონვენციები და ხაფანგები

- **`bootstrap/app.php` ავტორიტეტულია** DI-სა და route-ებისთვის;
  `routes/web.php` მკვდარია. ახალ URL-ზე POST-ის controller-მეთოდს ახალი
  route სჭირდება აქ.
- **Route პარამეტრები ერთსეგმენტიანია.** ფორმა, რომელიც "create"-ზე
  `/manage/posts`-ს და "update"-ზე `/manage/posts/{id}`-ს პოსტავს, **ორივე**
  route-ს საჭიროებს.
- **Hooks-ი პლაგინებამდე უნდა ჩაიტვირთოს.** `bootstrap`-მა აუცილებლად უნდა
  დაიძახოს `HookManager::setGlobalInstance($hooks)` და `require
  src/Core/functions.php` `PluginLoader::load()`-ამდე, თორემ ყოველი `gc_on()`
  fatal-ია და ყველა პლაგინი ჩუმად გამოტოვდება.
- **გამოიყენე კანონიკური hook ზმნები** (`on/emit/filter/apply`); არასდროს `addAction`.
- **მხოლოდ სტანდარტული HTTP კოდები** (403 CSRF, 422 ვალიდაცია; 419 თავიდან აიცილე).
- **`where('col','=',null)` ⇒ `IS NULL`** (და `'!='` ⇒ `IS NOT NULL`).
- **პლაგინი თავის ცხრილებს ფლობს** (self-migrate); installer მათ არ ქმნის.
- **view-ში escape** `e()`-ით; CSS/SQL-ში ჩასმული ნებისმიერი მნიშვნელობა **whitelist**.
- **პლაგინები თარგმნიან `gc_plugin_translator`-ით**, არასდროს ძრავის `t()`-ით.
- `install.php`-ის რედაქტირების შემდეგ (autoloader არ აქვს, მხოლოდ გაშვებისას
  იპარსება) — `php -l public/install.php` (truncation მხოლოდ პარსზე ჩანს).
```
