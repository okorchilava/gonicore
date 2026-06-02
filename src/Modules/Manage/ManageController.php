<?php

declare(strict_types=1);

namespace GoniCore\Modules\Manage;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Category\CategoryRepository;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Language\LanguageRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Notifications\NotificationService;
use GoniCore\Modules\Settings\SettingsService;
use GoniCore\Modules\Theme\ThemeController as ThemeCtrl;
use GoniCore\Modules\Post\PostRepository;
use GoniCore\Modules\Post\PostService;
use GoniCore\Modules\User\UserRepository;
use GoniCore\Modules\Media\MediaService;
use GoniCore\Modules\Menu\MenuService;
use GoniCore\Modules\Widget\WidgetRepository;
use GoniCore\Modules\Widget\WidgetService;

final class ManageController
{
    private readonly string $viewsDir;

    public function __construct(
        private readonly LoginService         $auth,
        private readonly PostRepository       $posts,
        private readonly PostService          $postService,
        private readonly CategoryRepository   $categories,
        private readonly UserRepository       $users,
        private readonly ActivityLogger       $logger,
        private readonly TodoRepository       $todos,
        private readonly NotificationService  $notifications,
        private readonly LanguageRepository   $langRepo,
        private readonly LanguageService      $langService,
        private readonly SettingsService      $settingsService,
        private readonly ThemeCtrl            $themeCtrl,
        private readonly PluginManager        $pluginManager,
        private readonly WidgetRepository     $widgetRepo,
        private readonly WidgetService        $widgetService,
        private readonly MediaService         $mediaService,
        private readonly MenuService          $menuService,
        private readonly HookManager          $hookManager,
        private readonly QueryBuilder         $qb,
        private readonly string               $siteName = 'GoniCore',
    ) {
        $this->viewsDir = dirname(__DIR__, 3) . '/themes/default/views/manage';
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    private function guard(Request $request): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login?redirect=' . urlencode($request->path()));
        }
        return null;
    }

    private function currentUser(): ?array
    {
        $id = $this->auth->currentUserId();
        return $id ? $this->users->findById($id) : null;
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();

        $stats = [
            'posts_total'     => $this->posts->query()->count(),
            'posts_published' => $this->posts->query()->where('status', '=', 'published')->count(),
            'posts_draft'     => $this->posts->query()->where('status', '=', 'draft')->count(),
            'categories'      => $this->qb->table('categories')->count(),
            'users'           => $this->qb->table('users')->count(),
        ];

        $server = $this->serverStats();
        $activity = $this->logger->recent(15);
        $todoList = $user ? $this->todos->allForUser((int) $user['id']) : [];
        $recentPosts = $this->posts->query()->orderBy('created_at', 'DESC')->limit(5)->get();

        return $this->render('dashboard', compact(
            'user', 'stats', 'server', 'activity', 'todoList', 'recentPosts'
        ), $request);
    }

    // ── Posts ─────────────────────────────────────────────────────────────────

    public function postsList(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user   = $this->currentUser();
        $page   = max(1, (int) $request->query('page', '1'));
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        $qb = $this->posts->query()
            ->where('type', '=', 'post')   // exclude pages
            ->orderBy('created_at', 'DESC');
        if ($status !== '') $qb->where('status', '=', $status);

        $total = $qb->count();
        $perPage = 20;
        $posts = $qb->limit($perPage)->offset(($page - 1) * $perPage)->get();
        $pages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        $catMap = [];
        foreach ($this->categories->findAll() as $c) {
            $catMap[(int) $c['id']] = $c['name'];
        }

        return $this->render('posts', compact(
            'user', 'posts', 'total', 'page', 'pages', 'status', 'search', 'catMap'
        ), $request);
    }

    public function postNew(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        $cats = $this->categories->findAll();
        $post = null;
        $error = null;
        return $this->render('post_form', compact('user', 'cats', 'post', 'error'), $request);
    }

    public function postCreate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();

        $title   = trim((string) $request->post('title', ''));
        $content = trim((string) $request->post('content', ''));
        $status  = (string) $request->post('status', 'draft');
        $catId   = $request->post('category_id') ? (int) $request->post('category_id') : null;
        $slug    = trim((string) $request->post('slug', ''));

        if ($slug === '') $slug = $this->slugify($title);

        $excerpt       = trim((string) $request->post('excerpt', ''));
        $featuredImage = trim((string) $request->post('featured_image', '')) ?: null;

        $id = $this->qb->table('posts')->insert([
            'type'           => 'post',
            'title'          => $title,
            'slug'           => $slug,
            'content'        => $content,
            'excerpt'        => $excerpt ?: null,
            'featured_image' => $featuredImage,
            'status'         => $status,
            'category_id'    => $catId,
            'author_id'      => $user ? (int) $user['id'] : 0,
        ]);

        $this->logger->log('post.created', $user ? (int) $user['id'] : null, 'post', (int) $id, ['title' => $title]);
        $this->notifications->postCreated($title, $user ? (int) $user['id'] : 0);

        return Response::redirect($request->basePath() . '/manage/posts');
    }

    public function postEdit(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        $id   = (int) $request->getAttribute('id');
        $post = $this->posts->findById($id);

        if ($post === null) return Response::redirect($request->basePath() . '/manage/posts');

        $cats  = $this->categories->findAll();
        $error = null;

        // Languages & existing translations for the translate panel
        $languages    = $this->langRepo->all();
        $trRows       = $this->langRepo->getTranslationsForPost($id);
        $translations = [];
        foreach ($trRows as $tr) { $translations[(string)$tr['language_code']] = $tr; }

        return $this->render('post_form', compact('user', 'cats', 'post', 'error', 'languages', 'translations'), $request);
    }

    public function postUpdate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        $id   = (int) $request->getAttribute('id');

        $excerpt = trim((string) $request->post('excerpt', ''));
        $data = [
            'title'          => trim((string) $request->post('title', '')),
            'slug'           => trim((string) $request->post('slug', '')),
            'content'        => trim((string) $request->post('content', '')),
            'excerpt'        => $excerpt ?: null,
            'featured_image' => trim((string) $request->post('featured_image', '')) ?: null,
            'status'         => (string) $request->post('status', 'draft'),
            'category_id'    => $request->post('category_id') ? (int) $request->post('category_id') : null,
        ];

        if ($data['slug'] === '') $data['slug'] = $this->slugify($data['title']);

        $this->qb->table('posts')->where('id', '=', $id)->update($data);
        $this->logger->log('post.updated', $user ? (int) $user['id'] : null, 'post', $id, ['title' => $data['title']]);
        $this->notifications->postUpdated($data['title'], $user ? (int) $user['id'] : 0);

        return Response::redirect($request->basePath() . '/manage/posts');
    }

    public function postDelete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        $id   = (int) $request->getAttribute('id');

        $post = $this->posts->findById($id);
        $this->qb->table('posts')->where('id', '=', $id)->delete();
        $this->logger->log('post.deleted', $user ? (int) $user['id'] : null, 'post', $id, ['title' => $post['title'] ?? '']);
        $this->notifications->postDeleted((string)($post['title'] ?? ''), $user ? (int) $user['id'] : 0);

        return Response::redirect($request->basePath() . '/manage/posts');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public function pagesList(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user  = $this->currentUser();
        $pages = $this->qb->table('posts')
            ->where('type', '=', 'page')
            ->orderBy('title', 'ASC')
            ->get();
        return $this->render('pages', compact('user', 'pages'), $request);
    }

    public function pageNew(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user      = $this->currentUser();
        $post      = null;
        $error     = null;
        $allPages  = $this->qb->table('posts')->where('type', '=', 'page')->orderBy('title', 'ASC')->get();
        $templates = $this->themeCtrl->availableTemplates();
        return $this->render('page_form', compact('user', 'post', 'error', 'allPages', 'templates'), $request);
    }

    public function pageCreate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user    = $this->currentUser();
        $title   = trim((string) $request->post('title', ''));
        $slug    = trim((string) $request->post('slug', '')) ?: $this->slugify($title);
        $content = trim((string) $request->post('content', ''));
        $status  = (string) $request->post('status', 'draft');
        $parentId = $request->post('parent_id') ? (int) $request->post('parent_id') : null;

        $template = (string) $request->post('template', 'default');

        $this->qb->table('posts')->insert([
            'type'           => 'page',
            'template'       => $template,
            'title'          => $title,
            'slug'           => $slug,
            'content'        => $content,
            'featured_image' => trim((string) $request->post('featured_image', '')) ?: null,
            'status'         => $status,
            'parent_id'      => $parentId,
            'author_id'      => $user ? (int) $user['id'] : 0,
        ]);
        return Response::redirect($request->basePath() . '/manage/pages');
    }

    public function pageEdit(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        $id   = (int) $request->getAttribute('id');
        $post = $this->qb->table('posts')->where('id', '=', $id)->where('type', '=', 'page')->first();
        if (!$post) return Response::redirect($request->basePath() . '/manage/pages');
        $allPages  = $this->qb->table('posts')->where('type', '=', 'page')->orderBy('title', 'ASC')->get();
        $templates = $this->themeCtrl->availableTemplates();
        $error     = null;
        return $this->render('page_form', compact('user', 'post', 'error', 'allPages', 'templates'), $request);
    }

    public function pageUpdate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        $id   = (int) $request->getAttribute('id');
        $slug = trim((string) $request->post('slug', '')) ?: $this->slugify(trim((string) $request->post('title', '')));

        $this->qb->table('posts')->where('id', '=', $id)->update([
            'title'          => trim((string) $request->post('title', '')),
            'slug'           => $slug,
            'content'        => trim((string) $request->post('content', '')),
            'featured_image' => trim((string) $request->post('featured_image', '')) ?: null,
            'status'         => (string) $request->post('status', 'draft'),
            'template'       => (string) $request->post('template', 'default'),
            'parent_id'      => $request->post('parent_id') ? (int) $request->post('parent_id') : null,
        ]);
        $this->logger->log('page.updated', $user ? (int) $user['id'] : null, 'page', $id);
        return Response::redirect($request->basePath() . '/manage/pages');
    }

    public function pageDelete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        $id   = (int) $request->getAttribute('id');
        $this->qb->table('posts')->where('id', '=', $id)->where('type', '=', 'page')->delete();
        $this->logger->log('page.deleted', $user ? (int) $user['id'] : null, 'page', $id);
        return Response::redirect($request->basePath() . '/manage/pages');
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    public function usersList(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user        = $this->currentUser();
        $users       = $this->users->findAll();
        $currentUser = $user;
        $success     = $request->query('success');
        $error       = $request->query('error');
        return $this->render('users', compact('user', 'users', 'currentUser', 'success', 'error'), $request);
    }

    public function userNew(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user  = $this->currentUser();
        $error = $request->query('error');
        return $this->render('user_form', compact('user', 'error'), $request);
    }

    public function userCreate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;

        $name     = trim((string) $request->post('name', ''));
        $email    = trim((string) $request->post('email', ''));
        $username = trim((string) $request->post('username', '')) ?: null;
        $phone    = trim((string) $request->post('phone', '')) ?: null;
        $role     = (string) $request->post('role', 'viewer');
        $password = (string) $request->post('password', '');
        $confirm  = (string) $request->post('password_confirm', '');

        if (!$name || !$email || !$password) {
            return Response::redirect($request->basePath() . '/manage/users/new?error=' . urlencode('Name, email and password are required.'));
        }
        if (strlen($password) < 8) {
            return Response::redirect($request->basePath() . '/manage/users/new?error=' . urlencode('Password must be at least 8 characters.'));
        }
        if ($password !== $confirm) {
            return Response::redirect($request->basePath() . '/manage/users/new?error=' . urlencode('Passwords do not match.'));
        }
        if ($this->users->findByEmail($email)) {
            return Response::redirect($request->basePath() . '/manage/users/new?error=' . urlencode('Email already in use.'));
        }

        $this->qb->table('users')->insert([
            'name'     => $name,
            'email'    => $email,
            'username' => $username,
            'phone'    => $phone,
            'role'     => in_array($role, ['admin','editor','viewer']) ? $role : 'viewer',
            'password' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        return Response::redirect($request->basePath() . '/manage/users?success=' . urlencode("User \"{$name}\" created."));
    }

    public function userEdit(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user     = $this->currentUser();
        $id       = (int) $request->getAttribute('id');
        $editUser = $this->users->findById($id);
        if (!$editUser) {
            return Response::redirect($request->basePath() . '/manage/users?error=User+not+found.');
        }
        $error = $request->query('error');
        return $this->render('user_form', compact('user', 'editUser', 'error'), $request);
    }

    public function userUpdate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id       = (int) $request->getAttribute('id');
        $editUser = $this->users->findById($id);
        if (!$editUser) {
            return Response::redirect($request->basePath() . '/manage/users');
        }

        $name     = trim((string) $request->post('name', ''));
        $email    = trim((string) $request->post('email', ''));
        $username = trim((string) $request->post('username', '')) ?: null;
        $phone    = trim((string) $request->post('phone', '')) ?: null;
        $role     = (string) $request->post('role', $editUser['role']);
        $password = (string) $request->post('password', '');
        $confirm  = (string) $request->post('password_confirm', '');

        if ($password && $password !== $confirm) {
            return Response::redirect($request->basePath() . '/manage/users/' . $id . '/edit?error=' . urlencode('Passwords do not match.'));
        }
        if ($password && strlen($password) < 8) {
            return Response::redirect($request->basePath() . '/manage/users/' . $id . '/edit?error=' . urlencode('Password must be at least 8 characters.'));
        }

        $data = [
            'name'     => $name ?: $editUser['name'],
            'email'    => $email ?: $editUser['email'],
            'username' => $username,
            'phone'    => $phone,
            'role'     => in_array($role, ['admin','editor','viewer']) ? $role : $editUser['role'],
        ];
        if ($password) {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $this->qb->table('users')->where('id', '=', $id)->update($data);
        return Response::redirect($request->basePath() . '/manage/users?success=' . urlencode('User updated.'));
    }

    public function userDelete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $current = $this->currentUser();
        $id      = (int) $request->getAttribute('id');
        if ((int)($current['id'] ?? 0) === $id) {
            return Response::redirect($request->basePath() . '/manage/users?error=' . urlencode('Cannot delete your own account.'));
        }
        $this->qb->table('users')->where('id', '=', $id)->delete();
        return Response::redirect($request->basePath() . '/manage/users?success=User+deleted.');
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function categoriesList(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user       = $this->currentUser();
        $cats       = $this->categories->findAll();
        $postCounts = [];
        foreach ($this->qb->table('posts')->get() as $row) {
            if ($row['category_id']) {
                $postCounts[(int)$row['category_id']] = ($postCounts[(int)$row['category_id']] ?? 0) + 1;
            }
        }
        $success = $request->query('success');
        $error   = $request->query('error');
        return $this->render('categories', compact('user', 'cats', 'postCounts', 'success', 'error'), $request);
    }

    public function categoryCreate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $name      = trim((string) $request->post('name', ''));
        $parentId  = (int) $request->post('parent_id', 0) ?: null;
        if (!$name) {
            return Response::redirect($request->basePath() . '/manage/categories?error=' . urlencode('Name is required.'));
        }
        $slug = \GoniCore\Shared\Support\Str::slug($name);
        if ($this->categories->findBySlug($slug)) {
            return Response::redirect($request->basePath() . '/manage/categories?error=' . urlencode('A category with this name already exists.'));
        }
        $this->categories->save(['name' => $name, 'slug' => $slug, 'parent_id' => $parentId]);
        return Response::redirect($request->basePath() . '/manage/categories?success=' . urlencode("Category \"{$name}\" created."));
    }

    public function categoryUpdate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id       = (int) $request->getAttribute('id');
        $name     = trim((string) $request->post('name', ''));
        $parentId = (int) $request->post('parent_id', 0) ?: null;
        if (!$name) {
            return Response::redirect($request->basePath() . '/manage/categories?error=' . urlencode('Name is required.'));
        }
        $slug = \GoniCore\Shared\Support\Str::slug($name);
        $this->categories->save(['id' => $id, 'name' => $name, 'slug' => $slug, 'parent_id' => $parentId]);
        return Response::redirect($request->basePath() . '/manage/categories?success=Category+updated.');
    }

    public function categoryDelete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id = (int) $request->getAttribute('id');
        $this->qb->table('posts')->where('category_id', '=', $id)->update(['category_id' => null]);
        $this->categories->delete($id);
        return Response::redirect($request->basePath() . '/manage/categories?success=Category+deleted.');
    }

    // ── Menus ─────────────────────────────────────────────────────────────────

    public function menusList(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user      = $this->currentUser();
        $menus     = $this->menuService->allMenus();
        $locations = MenuService::registeredLocations();
        $assigned  = $this->menuService->locationAssignments();
        $pages     = $this->qb->table('posts')->where('type','=','page')->where('status','=','published')->select('id','title','slug')->get();
        $posts     = $this->qb->table('posts')->where('type','=','post')->where('status','=','published')->select('id','title','slug')->orderBy('created_at','DESC')->limit(50)->get();
        $cats      = $this->categories->findAll();
        $success   = $request->query('success');
        $error     = $request->query('error');

        // Active menu (from ?menu= or first menu)
        $activeMenuId = (int) ($request->query('menu', '0'));
        if (!$activeMenuId && !empty($menus)) {
            $activeMenuId = (int) $menus[0]['id'];
        }
        $activeMenu  = $activeMenuId ? $this->menuService->findMenu($activeMenuId) : null;
        $activeItems = $activeMenuId ? $this->menuService->itemsForMenu($activeMenuId) : [];

        return $this->render('menus', compact(
            'user','menus','locations','assigned','pages','posts','cats',
            'success','error','activeMenuId','activeMenu','activeItems'
        ), $request);
    }

    public function menuCreate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $name = trim((string) $request->post('name',''));
        if (!$name) return Response::redirect($request->basePath().'/manage/menus?error='.urlencode('Menu name required.'));
        $id = $this->menuService->createMenu($name);
        return Response::redirect($request->basePath().'/manage/menus?menu='.$id.'&success='.urlencode("Menu \"{$name}\" created."));
    }

    public function menuDelete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id = (int) $request->getAttribute('id');
        $this->menuService->deleteMenu($id);
        return Response::redirect($request->basePath().'/manage/menus?success=Menu+deleted.');
    }

    public function menuRename(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id   = (int) $request->getAttribute('id');
        $name = trim((string) $request->post('name',''));
        if ($name) $this->menuService->renameMenu($id, $name);
        return Response::redirect($request->basePath().'/manage/menus?menu='.$id.'&success=Menu+renamed.');
    }

    public function menuAssignLocations(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $locations = MenuService::registeredLocations();
        foreach (array_keys($locations) as $loc) {
            $menuId = $request->post('location_' . $loc);
            $this->menuService->assignMenuToLocation($loc, $menuId ? (int)$menuId : null);
        }
        return Response::redirect($request->basePath().'/manage/menus?success='.urlencode('Menu locations saved.'));
    }

    public function menuItemAdd(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $menuId = (int) $request->getAttribute('id');
        $type   = (string) $request->post('type', 'custom');
        $items  = [];

        if ($type === 'custom') {
            $items[] = [
                'type'  => 'custom',
                'label' => trim((string) $request->post('label','Link')),
                'url'   => trim((string) $request->post('url','')),
                'target'=> $request->post('target') === '_blank' ? '_blank' : '_self',
            ];
        } elseif (in_array($type, ['page','post','category'])) {
            $ids    = (array) $request->post('object_ids', []);
            $labels = $this->resolveObjectLabels($type, array_map('intval', $ids));
            foreach ($ids as $oid) {
                $oid = (int)$oid;
                $items[] = [
                    'type'      => $type,
                    'object_id' => $oid,
                    'label'     => $labels[$oid] ?? ucfirst($type).' '.$oid,
                    'url'       => $this->resolveObjectUrl($type, $oid, $request->basePath()),
                ];
            }
        }

        foreach ($items as $item) {
            $this->menuService->addItem($menuId, $item);
        }

        return Response::redirect($request->basePath().'/manage/menus?menu='.$menuId.'&success='.urlencode('Items added.'));
    }

    public function menuItemUpdate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $itemId = (int) $request->getAttribute('item_id');
        $menuId = (int) $request->post('menu_id', '0');
        $this->menuService->updateItem($itemId, [
            'label'     => trim((string) $request->post('label','')),
            'url'       => trim((string) $request->post('url','')),
            'target'    => $request->post('target') === '_blank' ? '_blank' : '_self',
            'parent_id' => $request->post('parent_id') ? (int) $request->post('parent_id') : null,
        ]);
        return Response::redirect($request->basePath().'/manage/menus?menu='.$menuId.'&success=Item+updated.');
    }

    public function menuItemDelete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $itemId = (int) $request->getAttribute('item_id');
        $menuId = (int) $request->post('menu_id', '0');
        $this->menuService->deleteItem($itemId);
        return Response::redirect($request->basePath().'/manage/menus?menu='.$menuId.'&success=Item+removed.');
    }

    public function menuItemReorder(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $ids = (array) $request->post('ids', []);
        $this->menuService->reorderItems(array_map('intval', $ids));
        return Response::json(['ok' => true]);
    }

    /** @return array<int, string> */
    private function resolveObjectLabels(string $type, array $ids): array
    {
        if (!$ids) return [];
        $table = match($type) { 'category' => 'categories', default => 'posts' };
        $map   = [];
        foreach ($ids as $oid) {
            $r = $this->qb->table($table)->where('id', '=', $oid)->first();
            if ($r) $map[$oid] = (string)($r['name'] ?? $r['title'] ?? '');
        }
        return $map;
    }

    private function resolveObjectUrl(string $type, int $id, string $base): string
    {
        return match($type) {
            'page'     => $base . '/page/' . ($this->qb->table('posts')->where('id','=',$id)->first()['slug'] ?? $id),
            'post'     => $base . '/post/' . ($this->qb->table('posts')->where('id','=',$id)->first()['slug'] ?? $id),
            'category' => $base . '/category/' . ($this->qb->table('categories')->where('id','=',$id)->first()['slug'] ?? $id),
            default    => '#',
        };
    }

    public function profileForm(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user        = $this->currentUser();
        $profileUser = $this->users->findById((int)($user['id'] ?? 0));
        $success     = $request->query('success');
        $error       = $request->query('error');
        return $this->render('profile', compact('user', 'profileUser', 'success', 'error'), $request);
    }

    public function profileSave(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user    = $this->currentUser();
        $id      = (int)($user['id'] ?? 0);
        $section = (string) $request->post('_section', 'info');

        if ($section === 'password') {
            $current = (string) $request->post('current_password', '');
            $new     = (string) $request->post('password', '');
            $confirm = (string) $request->post('password_confirm', '');
            $row     = $this->users->findById($id);

            if (!$row || !password_verify($current, (string)$row['password'])) {
                return Response::redirect($request->basePath() . '/manage/profile?error=' . urlencode('Current password is incorrect.'));
            }
            if (strlen($new) < 8) {
                return Response::redirect($request->basePath() . '/manage/profile?error=' . urlencode('New password must be at least 8 characters.'));
            }
            if ($new !== $confirm) {
                return Response::redirect($request->basePath() . '/manage/profile?error=' . urlencode('Passwords do not match.'));
            }
            $this->qb->table('users')->where('id', '=', $id)->update([
                'password' => password_hash($new, PASSWORD_BCRYPT),
            ]);
            return Response::redirect($request->basePath() . '/manage/profile?success=' . urlencode('Password updated.'));
        }

        // section = info
        $name     = trim((string) $request->post('name', ''));
        $email    = trim((string) $request->post('email', ''));
        $username = trim((string) $request->post('username', '')) ?: null;
        $phone    = trim((string) $request->post('phone', '')) ?: null;

        if (!$name || !$email) {
            return Response::redirect($request->basePath() . '/manage/profile?error=' . urlencode('Name and email are required.'));
        }

        $this->qb->table('users')->where('id', '=', $id)->update(array_filter([
            'name'     => $name,
            'email'    => $email,
            'username' => $username,
            'phone'    => $phone,
        ], fn($v) => $v !== null));

        return Response::redirect($request->basePath() . '/manage/profile?success=' . urlencode('Profile updated.'));
    }

    // ── Widgets ───────────────────────────────────────────────────────────────

    public function widgetsList(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user        = $this->currentUser();
        $widgets     = $this->widgetRepo->all();
        $widgetAreas = $this->widgetService->areas();
        $widgetTypes = $this->widgetService->types();
        return $this->render('widgets', compact('user', 'widgets', 'widgetAreas', 'widgetTypes'), $request);
    }

    public function widgetCreate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $area     = (string) $request->post('area', 'sidebar');
        $type     = (string) $request->post('type', 'html');
        $title    = trim((string) $request->post('title', '')) ?: null;
        $settings = $request->post('settings') ?? [];
        if (!is_array($settings)) $settings = [];
        $this->widgetRepo->create($area, $type, $title, $settings);
        return Response::redirect($request->basePath() . '/manage/widgets');
    }

    public function widgetUpdate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id       = (int) $request->getAttribute('id');
        $title    = trim((string) $request->post('title', '')) ?: null;
        $settings = $request->post('settings') ?? [];
        if (!is_array($settings)) $settings = [];
        $this->widgetRepo->update($id, $title, $settings);
        return Response::redirect($request->basePath() . '/manage/widgets');
    }

    public function widgetToggle(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $this->widgetRepo->toggle((int) $request->getAttribute('id'));
        return Response::redirect($request->basePath() . '/manage/widgets');
    }

    public function widgetDelete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $this->widgetRepo->delete((int) $request->getAttribute('id'));
        return Response::redirect($request->basePath() . '/manage/widgets');
    }

    // ── Plugins ───────────────────────────────────────────────────────────────

    public function pluginsList(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user    = $this->currentUser();
        $plugins = $this->pluginManager->all();
        return $this->render('plugins', compact('user', 'plugins'), $request);
    }

    public function pluginUpload(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $file = $request->files()['plugin_zip'] ?? null;

        if (!$file || ($file['error'] ?? 1) !== 0 || empty($file['tmp_name'])) {
            return Response::redirect($request->basePath() . '/manage/plugins?error=upload_failed');
        }

        try {
            $this->pluginManager->uploadZip($file['tmp_name'], $file['name']);
            return Response::redirect($request->basePath() . '/manage/plugins?uploaded=1');
        } catch (\Throwable $e) {
            return Response::redirect($request->basePath() . '/manage/plugins?error=' . urlencode($e->getMessage()));
        }
    }

    public function pluginActivate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $this->pluginManager->activate((string) $request->getAttribute('slug'));
        return Response::redirect($request->basePath() . '/manage/plugins');
    }

    public function pluginDeactivate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $this->pluginManager->deactivate((string) $request->getAttribute('slug'));
        return Response::redirect($request->basePath() . '/manage/plugins');
    }

    public function pluginDelete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $this->pluginManager->delete((string) $request->getAttribute('slug'));
        return Response::redirect($request->basePath() . '/manage/plugins');
    }

    // ── Gallery ───────────────────────────────────────────────────────────────

    public function galleryList(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user  = $this->currentUser();
        $media = $this->qb->table('media')->orderBy('created_at', 'DESC')->get();
        return $this->render('gallery', compact('user', 'media'), $request);
    }

    /** GET /manage/gallery/json — AJAX endpoint for editor gallery modal */
    public function galleryJson(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $media = $this->qb->table('media')->orderBy('created_at', 'DESC')->get();
        return Response::json(['media' => $media]);
    }

    public function galleryUpload(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user  = $this->currentUser();
        $files = $request->files();

        // Support both single 'file' and multiple 'files[]'
        $uploads = [];
        if (isset($files['files'])) {
            $raw = $files['files'];
            // Normalise PHP's multi-file array structure
            if (is_array($raw['name'])) {
                for ($i = 0; $i < count($raw['name']); $i++) {
                    $uploads[] = [
                        'name'     => $raw['name'][$i],
                        'type'     => $raw['type'][$i],
                        'tmp_name' => $raw['tmp_name'][$i],
                        'error'    => $raw['error'][$i],
                        'size'     => $raw['size'][$i],
                    ];
                }
            } else {
                $uploads[] = $raw;
            }
        } elseif (isset($files['file'])) {
            $uploads[] = $files['file'];
        }

        $success = 0;
        $lastError = '';
        foreach ($uploads as $file) {
            try {
                $data = $this->mediaService->store($file, (int) ($user['id'] ?? 0));
                $this->qb->table('media')->insert($data);
                $success++;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        $isAjax = str_contains($request->header('Accept') ?? '', 'application/json')
                  || str_contains($request->header('X-Requested-With') ?? '', 'XMLHttpRequest');

        if ($isAjax) {
            return $success > 0
                ? Response::json(['uploaded' => $success])
                : Response::json(['error' => $lastError], 422);
        }

        $qs = $success > 0 ? '?uploaded=' . $success : '?error=' . urlencode($lastError);
        return Response::redirect($request->basePath() . '/manage/gallery' . $qs);
    }

    public function galleryDelete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $id  = (int) $request->getAttribute('id');
        $row = $this->qb->table('media')->where('id', '=', $id)->first();
        if ($row) {
            // Delete physical file
            $storageDir = dirname(__DIR__, 3) . '/storage/media';
            $full = rtrim($storageDir, '/') . '/' . ltrim((string)$row['path'], '/');
            if (is_file($full)) @unlink($full);
            $this->qb->table('media')->where('id', '=', $id)->delete();
        }
        return Response::redirect($request->basePath() . '/manage/gallery');
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public function settingsForm(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user     = $this->currentUser();
        $settings = $this->settingsService->all();
        $allPosts = $this->posts->query()
            ->where('status', '=', 'published')
            ->orderBy('title', 'ASC')
            ->get();
        $saved = $request->query('saved') === '1';
        return $this->render('settings', compact('user', 'settings', 'allPosts', 'saved'), $request);
    }

    public function settingsSave(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();

        $allowed = [
            'site_name', 'site_tagline', 'site_url',
            'posts_per_page', 'homepage_type', 'homepage_page_id', 'posts_page_id',
            'timezone', 'date_format', 'time_format',
        ];

        $data = [];
        foreach ($allowed as $key) {
            $val = $request->post($key);
            $data[$key] = $val !== null ? trim((string) $val) : null;
        }

        // homepage_page_id only relevant when homepage_type = page
        if (($data['homepage_type'] ?? '') !== 'page') {
            $data['homepage_page_id'] = '';
        }

        $this->settingsService->bulk($data);

        // Apply timezone immediately
        $tz = $data['timezone'] ?? 'UTC';
        if (in_array($tz, \DateTimeZone::listIdentifiers(), true)) {
            date_default_timezone_set($tz);
        }

        $this->logger->log('settings.updated', $user ? (int) $user['id'] : null);

        // Redirect with saved flag
        return Response::redirect($request->basePath() . '/manage/settings?saved=1');
    }

    // ── Notifications ─────────────────────────────────────────────────────────

    public function notificationRead(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        $id   = (int) $request->getAttribute('id');
        if ($user) $this->notifications->markRead($id, (int) $user['id']);
        return Response::redirect($request->basePath() . '/manage');
    }

    public function notificationReadAll(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        if ($user) $this->notifications->markAllRead((int) $user['id']);
        return Response::redirect($request->basePath() . '/manage');
    }

    // ── Todos (AJAX-friendly POST) ────────────────────────────────────────────

    public function todoCreate(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user  = $this->currentUser();
        $title = trim((string) $request->post('title', ''));
        if ($title !== '' && $user) {
            $this->todos->create((int) $user['id'], $title);
        }
        return Response::redirect($request->basePath() . '/manage');
    }

    public function todoToggle(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        $id   = (int) $request->getAttribute('id');
        if ($user) $this->todos->toggle($id, (int) $user['id']);
        return Response::redirect($request->basePath() . '/manage');
    }

    public function todoDelete(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;
        $user = $this->currentUser();
        $id   = (int) $request->getAttribute('id');
        if ($user) $this->todos->delete($id, (int) $user['id']);
        return Response::redirect($request->basePath() . '/manage');
    }

    // ── Server stats ──────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function serverStats(): array
    {
        $memLimit  = ini_get('memory_limit');
        $memUsage  = memory_get_usage(true);
        $memPeak   = memory_get_peak_usage(true);

        $diskTotal = @disk_total_space('/') ?: 0;
        $diskFree  = @disk_free_space('/')  ?: 0;
        $diskUsed  = $diskTotal - $diskFree;

        return [
            'php_version'   => PHP_VERSION,
            'php_sapi'      => PHP_SAPI,
            'os'            => php_uname('s') . ' ' . php_uname('r'),
            'server_sw'     => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'mem_limit'     => $memLimit,
            'mem_usage'     => $this->formatBytes($memUsage),
            'mem_peak'      => $this->formatBytes($memPeak),
            'disk_total'    => $this->formatBytes($diskTotal),
            'disk_free'     => $this->formatBytes($diskFree),
            'disk_used'     => $this->formatBytes($diskUsed),
            'disk_pct'      => $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0,
            'opcache'       => function_exists('opcache_get_status') && opcache_get_status(false) !== false,
            'extensions'    => count(get_loaded_extensions()),
        ];
    }

    private function formatBytes(int|float $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1)    . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1)       . ' KB';
        return $bytes . ' B';
    }

    private function slugify(string $text): string
    {
        // Use Str::slug for consistent Unicode handling (intl transliteration).
        $slug = \GoniCore\Shared\Support\Str::slug($text);

        // Str::slug already guarantees a non-empty result, but make uniqueness
        // explicit: append a suffix if this slug already exists in the DB.
        $base = $slug;
        $i    = 1;
        while ($this->qb->table('posts')->where('slug', '=', $slug)->first() !== null) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    // ── Renderer ──────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $data */
    private function render(string $template, array $data, Request $request): Response
    {
        require_once dirname(__DIR__, 3) . '/themes/default/views/helpers.php';

        $viewFile = $this->viewsDir . '/' . $template . '.php';
        if (!is_file($viewFile)) return Response::error("Manage view not found: {$template}", 500);

        $base     = $request->basePath();
        $siteName = $this->siteName;
        $hooks    = $this->hookManager;

        // Notifications available in every view
        $currentUser     = $data['user'] ?? null;
        $notifList       = $currentUser ? $this->notifications->forUser((int) $currentUser['id']) : [];
        $notifUnread     = $currentUser ? $this->notifications->unreadCount((int) $currentUser['id']) : 0;

        // Language switcher in panel topbar
        $panelLangs      = $this->langRepo->allActive();
        $currentLangCode = $this->langService->currentCode();

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $viewFile;
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        ob_start();
        try {
            include $this->viewsDir . '/layout.php';
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return Response::html($html);
    }
}
