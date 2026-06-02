<?php

declare(strict_types=1);

namespace GoniCore\Modules\Theme;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Shortcodes\ShortcodeManager;
use GoniCore\Modules\Category\CategoryRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Menu\MenuService;
use GoniCore\Modules\Post\PostRepository;
use GoniCore\Modules\Settings\SettingsService;
use GoniCore\Modules\Widget\WidgetService;

/**
 * Renders the default front-end theme.
 *
 * IMPORTANT: helpers.php is loaded via require_once BEFORE any view
 * template is included. This guarantees e(), excerpt(), fmt_date()
 * are defined when home.php, post.php, etc. call them.
 */
final class ThemeController
{
    private readonly string $viewsDir;

    public function __construct(
        private readonly PostRepository     $posts,
        private readonly CategoryRepository $categories,
        private readonly LanguageService    $langService,
        private readonly SettingsService    $settings,
        private readonly ShortcodeManager   $shortcodes,
        private readonly WidgetService      $widgetService,
        private readonly MenuService        $menuService,
        private readonly string             $siteName = 'GoniCore',
    ) {
        $this->viewsDir = dirname(__DIR__, 3) . '/themes/default/views';
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function home(Request $request): Response
    {
        // Static page as homepage
        if ($this->settings->homepageType() === 'page') {
            $pageId = $this->settings->homepagePageId();
            if ($pageId) {
                $post = $this->posts->findById($pageId);
                if ($post && $post['status'] === 'published') {
                    $template = (string) ($post['template'] ?? 'default');

                    if ($template === 'blank') {
                        require_once $this->viewsDir . '/helpers.php';
                        return Response::html($this->processShortcodes((string) $post['content']));
                    }

                    if ($template === 'landing') {
                        return $this->view($request, 'page_landing', compact('post', 'template'));
                    }

                    if ($template === 'builder' && !empty($post['builder_data'])) {
                        require_once $this->viewsDir . '/helpers.php';
                        $base        = $request->basePath();
                        $siteName    = (string) ($this->settings->siteName() ?: $this->siteName);
                        $siteTagline = $this->settings->siteTagline();
                        $categories  = $this->categories->findAll();
                        $lang        = $this->langService->currentCode();
                        $languages   = $this->langService->activeLanguages();
                        $langService = $this->langService;
                        global $widgetServiceInstance, $menuServiceInstance, $shortcodeManagerInstance, $goni_builder_service;
                        $widgetServiceInstance    = $this->widgetService;
                        $menuServiceInstance      = $this->menuService;
                        $shortcodeManagerInstance = $this->shortcodes;
                        $builderHtml = $goni_builder_service
                            ? $goni_builder_service->render((string) $post['builder_data'], $base)
                            : '<div style="padding:40px;text-align:center;color:#ef4444">Goni Builder plugin is not active.</div>';
                        $builderView = dirname(__DIR__, 3) . '/plugins/goni-builder/views/page_builder.php';
                        if (!is_file($builderView)) {
                            $builderView = $this->viewsDir . '/page_builder.php';
                        }
                        ob_start();
                        include $builderView;
                        return Response::html((string) ob_get_clean());
                    }

                    $isPage          = true;
                    $category        = null;
                    $post['content'] = $this->processShortcodes((string) $post['content']);
                    return $this->view($request, 'post', compact('post', 'category', 'isPage'));
                }
            }
        }

        $page    = max(1, (int) $request->query('page', '1'));
        $perPage = 9;
        $total   = $this->posts->countPublished();
        $posts   = $this->posts->paginate($page, $perPage);
        $pages   = (int) ceil($total / $perPage);
        $categories = $this->categories->findAll();
        return $this->view($request, 'home', compact('posts', 'page', 'pages', 'total', 'categories'));
    }

    public function page(Request $request): Response
    {
        $slug = (string) $request->getAttribute('slug');
        $post = $this->posts->findBySlug($slug);

        if (!$post || $post['status'] !== 'published') {
            return $this->view($request, '404', []);
        }

        $template = (string) ($post['template'] ?? 'default');

        if ($template === 'blank') {
            require_once $this->viewsDir . '/helpers.php';
            return Response::html($this->processShortcodes((string) $post['content']));
        }

        if ($template === 'builder' && !empty($post['builder_data'])) {
            require_once $this->viewsDir . '/helpers.php';
            $base        = $request->basePath();
            $siteName    = (string) ($this->settings->siteName() ?: $this->siteName);
            $siteTagline = $this->settings->siteTagline();
            $categories  = $this->categories->findAll();
            $lang        = $this->langService->currentCode();
            $languages   = $this->langService->activeLanguages();
            $langService = $this->langService;
            global $widgetServiceInstance, $menuServiceInstance, $shortcodeManagerInstance, $goni_builder_service;
            $widgetServiceInstance    = $this->widgetService;
            $menuServiceInstance      = $this->menuService;
            $shortcodeManagerInstance = $this->shortcodes;
            $builderHtml = $goni_builder_service
                ? $goni_builder_service->render((string) $post['builder_data'], $base)
                : '<div style="padding:40px;text-align:center;color:#ef4444">Goni Builder plugin is not active.</div>';
            $builderView = dirname(__DIR__, 3) . '/plugins/goni-builder/views/page_builder.php';
            if (!is_file($builderView)) {
                $builderView = $this->viewsDir . '/page_builder.php';
            }
            ob_start();
            include $builderView;
            return Response::html((string) ob_get_clean());
        }

        $isPage              = true;
        $category            = null;
        $post['content']     = $this->processShortcodes((string) $post['content']);
        return $this->view($request, 'post', compact('post', 'category', 'isPage'));
    }

    public function post(Request $request): Response
    {
        $slug = (string) $request->getAttribute('slug');
        $post = $this->posts->findBySlug($slug);

        if (!$post || $post['status'] !== 'published') {
            return $this->view($request, '404', []);
        }

        $post['content'] = $this->processShortcodes((string) $post['content']);

        $category = null;
        if (!empty($post['category_id'])) {
            $all = $this->categories->findAll();
            foreach ($all as $c) {
                if ((int) $c['id'] === (int) $post['category_id']) {
                    $category = $c;
                    break;
                }
            }
        }

        $isPage = false;
        return $this->view($request, 'post', compact('post', 'category', 'isPage'));
    }

    public function category(Request $request): Response
    {
        $slug     = (string) $request->getAttribute('slug');
        $category = null;
        foreach ($this->categories->findAll() as $c) {
            if ($c['slug'] === $slug) { $category = $c; break; }
        }

        if (!$category) return $this->view($request, '404', []);

        $page    = max(1, (int) $request->query('page', '1'));
        $perPage = 9;
        $total   = $this->posts->countByCategory((int) $category['id']);
        $posts   = $this->posts->paginateByCategory((int) $category['id'], $page, $perPage);
        $pages   = (int) ceil($total / $perPage);

        return $this->view($request, 'category', compact('category', 'posts', 'page', 'pages', 'total'));
    }

    public function notFound(Request $request): Response
    {
        return $this->view($request, '404', []);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /** @param array<string,mixed> $data */
    private function view(Request $request, string $template, array $data = []): Response
    {
        require_once $this->viewsDir . '/helpers.php';

        // Boot language detection
        $this->langService->boot($request);

        $base          = $request->basePath();
        $siteName      = (string) ($this->settings->siteName() ?: $this->siteName);
        $categories    = $this->categories->findAll();
        $langService   = $this->langService;
        $menuService   = $this->menuService;
        $widgetService = $this->widgetService;

        global $menuServiceInstance, $widgetServiceInstance, $shortcodeManagerInstance;
        $menuServiceInstance      = $this->menuService;
        $widgetServiceInstance    = $this->widgetService;
        $shortcodeManagerInstance = $this->shortcodes;

        $viewFile = $this->viewsDir . '/' . $template . '.php';
        if (!is_file($viewFile)) {
            $viewFile = $this->viewsDir . '/404.php';
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $viewFile;
        $content = (string) ob_get_clean();

        ob_start();
        include $this->viewsDir . '/layout.php';
        return Response::html((string) ob_get_clean());
    }

    /**
     * Return available page templates from the active theme's /templates directory.
     * Each entry: ['value' => 'default', 'label' => 'Default']
     *
     * @return list<array{value:string,label:string}>
     */
    public function availableTemplates(): array
    {
        $templatesDir = dirname(__DIR__, 3) . '/themes/default/templates';
        $templates    = [
            ['slug' => 'default', 'name' => 'Default'],
        ];

        if (!is_dir($templatesDir)) return $templates;

        foreach (scandir($templatesDir) ?: [] as $file) {
            if (!str_ends_with($file, '.php')) continue;
            $slug = basename($file, '.php');
            if ($slug === 'default') continue; // already added

            // Try to read Template Name from file comment
            $name = ucfirst(str_replace(['-', '_'], ' ', $slug));
            $src  = file_get_contents($templatesDir . '/' . $file);
            if ($src && preg_match('/Template Name:\s*(.+)/i', $src, $m)) {
                $name = trim($m[1]);
            }

            $templates[] = ['slug' => $slug, 'name' => $name];
        }

        return $templates;
    }

    private function processShortcodes(string $content): string
    {
        return $this->shortcodes->process($content);
    }
}
