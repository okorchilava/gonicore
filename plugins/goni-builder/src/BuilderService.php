<?php

declare(strict_types=1);

namespace GoniBuilder;

use GoniCore\Core\Database\QueryBuilder;

/**
 * GoniBuilder — renders a JSON page-builder data structure to HTML.
 *
 * Data structure:
 * {
 *   "version": "1.0",
 *   "sections": [
 *     {
 *       "id": "sec_xxx",
 *       "settings": { "bg_color": "", "bg_image": "", "padding": "60px 0", "full_width": false },
 *       "columns": [
 *         {
 *           "id": "col_xxx",
 *           "width": "50",     // percent
 *           "elements": [
 *             { "id": "el_xxx", "type": "heading", "settings": { ... } }
 *           ]
 *         }
 *       ]
 *     }
 *   ]
 * }
 */
final class BuilderService
{
    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Registered element types ──────────────────────────────────────────────

    /** @return list<array{type:string, label:string, icon:string, category:string}> */
    public function elementTypes(): array
    {
        return [
            // Basic
            ['type' => 'heading',   'label' => 'Heading',    'icon' => 'H',   'category' => 'Basic'],
            ['type' => 'text',      'label' => 'Text',       'icon' => 'T',   'category' => 'Basic'],
            ['type' => 'image',     'label' => 'Image',      'icon' => '🖼',  'category' => 'Basic'],
            ['type' => 'button',    'label' => 'Button',     'icon' => '⬡',  'category' => 'Basic'],
            ['type' => 'spacer',    'label' => 'Spacer',     'icon' => '↕',   'category' => 'Basic'],
            ['type' => 'divider',   'label' => 'Divider',    'icon' => '─',   'category' => 'Basic'],
            // Media
            ['type' => 'video',     'label' => 'Video',      'icon' => '▶',   'category' => 'Media'],
            ['type' => 'gallery',   'label' => 'Gallery',    'icon' => '⊞',   'category' => 'Media'],
            // Content
            ['type' => 'icon_box',  'label' => 'Icon Box',   'icon' => '⬡',  'category' => 'Content'],
            ['type' => 'counter',   'label' => 'Counter',    'icon' => '123', 'category' => 'Content'],
            ['type' => 'alert',     'label' => 'Alert',      'icon' => '!',   'category' => 'Content'],
            ['type' => 'html',      'label' => 'HTML',       'icon' => '</>',  'category' => 'Content'],
            // Dynamic
            ['type' => 'posts_grid','label' => 'Posts Grid', 'icon' => '⊟',  'category' => 'Dynamic'],
            ['type' => 'slider',    'label' => 'Slider',     'icon' => '🎞',  'category' => 'Dynamic'],
        ];
    }

    /** @return array<string, array{label:string, fields:list<array{name:string,label:string,type:string,default:mixed,options?:array}>}> */
    public function elementSchemas(): array
    {
        return [
            'heading' => ['label' => 'Heading', 'fields' => [
                ['name' => 'text',    'label' => 'Text',      'type' => 'text',   'default' => 'Your Heading'],
                ['name' => 'tag',     'label' => 'HTML Tag',  'type' => 'select', 'default' => 'h2',
                 'options' => ['h1'=>'H1','h2'=>'H2','h3'=>'H3','h4'=>'H4','h5'=>'H5','h6'=>'H6']],
                ['name' => 'align',   'label' => 'Align',     'type' => 'select', 'default' => 'left',
                 'options' => ['left'=>'Left','center'=>'Center','right'=>'Right']],
                ['name' => 'color',   'label' => 'Color',     'type' => 'color',  'default' => ''],
                ['name' => 'size',    'label' => 'Font Size', 'type' => 'text',   'default' => ''],
            ]],
            'text' => ['label' => 'Text', 'fields' => [
                ['name' => 'content', 'label' => 'Content',   'type' => 'textarea', 'default' => 'Enter your text here...'],
                ['name' => 'align',   'label' => 'Align',     'type' => 'select', 'default' => 'left',
                 'options' => ['left'=>'Left','center'=>'Center','right'=>'Right','justify'=>'Justify']],
                ['name' => 'color',   'label' => 'Color',     'type' => 'color',  'default' => ''],
                ['name' => 'size',    'label' => 'Font Size', 'type' => 'text',   'default' => ''],
            ]],
            'image' => ['label' => 'Image', 'fields' => [
                ['name' => 'src',         'label' => 'Image URL',      'type' => 'image',  'default' => ''],
                ['name' => 'alt',         'label' => 'Alt Text',        'type' => 'text',   'default' => ''],
                ['name' => 'title',       'label' => 'Title (tooltip)', 'type' => 'text',   'default' => ''],
                ['name' => 'caption',     'label' => 'Caption',         'type' => 'text',   'default' => ''],
                ['name' => 'link',        'label' => 'Link URL',        'type' => 'text',   'default' => ''],
                ['name' => 'link_target', 'label' => 'Link Target',     'type' => 'select', 'default' => '_self',
                 'options' => ['_self'=>'Same Tab','_blank'=>'New Tab']],
                ['name' => 'align',       'label' => 'Align',           'type' => 'select', 'default' => 'center',
                 'options' => ['left'=>'Left','center'=>'Center','right'=>'Right']],
                ['name' => 'width',       'label' => 'Width',           'type' => 'text',   'default' => '100%'],
                ['name' => 'height',      'label' => 'Height',          'type' => 'text',   'default' => ''],
                ['name' => 'object_fit',  'label' => 'Object Fit',      'type' => 'select', 'default' => 'cover',
                 'options' => ['cover'=>'Cover','contain'=>'Contain','fill'=>'Fill','none'=>'None']],
                ['name' => 'radius',      'label' => 'Border Radius',   'type' => 'text',   'default' => '0px'],
                ['name' => 'box_shadow',  'label' => 'Box Shadow',      'type' => 'select', 'default' => 'none',
                 'options' => ['none'=>'None','sm'=>'Small','md'=>'Medium','lg'=>'Large','xl'=>'X-Large']],
                ['name' => 'opacity',     'label' => 'Opacity (0–1)',   'type' => 'text',   'default' => '1'],
                ['name' => 'filter',      'label' => 'Filter',          'type' => 'select', 'default' => 'none',
                 'options' => ['none'=>'None','grayscale'=>'Grayscale','sepia'=>'Sepia','blur'=>'Blur','brightness'=>'Bright']],
            ]],
            'button' => ['label' => 'Button', 'fields' => [
                ['name' => 'text',    'label' => 'Label',     'type' => 'text',   'default' => 'Click Here'],
                ['name' => 'url',     'label' => 'URL',       'type' => 'text',   'default' => '#'],
                ['name' => 'target',  'label' => 'Target',    'type' => 'select', 'default' => '_self',
                 'options' => ['_self'=>'Same Tab','_blank'=>'New Tab']],
                ['name' => 'align',   'label' => 'Align',     'type' => 'select', 'default' => 'left',
                 'options' => ['left'=>'Left','center'=>'Center','right'=>'Right']],
                ['name' => 'style',   'label' => 'Style',     'type' => 'select', 'default' => 'primary',
                 'options' => ['primary'=>'Primary','outline'=>'Outline','ghost'=>'Ghost','danger'=>'Danger']],
                ['name' => 'size',    'label' => 'Size',      'type' => 'select', 'default' => 'md',
                 'options' => ['sm'=>'Small','md'=>'Medium','lg'=>'Large']],
                ['name' => 'radius',  'label' => 'Radius',    'type' => 'text',   'default' => '8px'],
            ]],
            'spacer' => ['label' => 'Spacer', 'fields' => [
                ['name' => 'height',  'label' => 'Height',    'type' => 'text',   'default' => '40px'],
            ]],
            'divider' => ['label' => 'Divider', 'fields' => [
                ['name' => 'color',   'label' => 'Color',     'type' => 'color',  'default' => '#e2e8f0'],
                ['name' => 'thickness','label'=> 'Thickness', 'type' => 'text',   'default' => '1px'],
                ['name' => 'width',   'label' => 'Width',     'type' => 'text',   'default' => '100%'],
                ['name' => 'align',   'label' => 'Align',     'type' => 'select', 'default' => 'center',
                 'options' => ['left'=>'Left','center'=>'Center','right'=>'Right']],
            ]],
            'video' => ['label' => 'Video', 'fields' => [
                ['name' => 'url',     'label' => 'Video URL', 'type' => 'text',   'default' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                ['name' => 'aspect',  'label' => 'Aspect Ratio','type' => 'select','default' => '16:9',
                 'options' => ['16:9'=>'16:9','4:3'=>'4:3','1:1'=>'1:1','21:9'=>'21:9']],
            ]],
            'gallery' => ['label' => 'Gallery', 'fields' => [
                ['name' => 'images',  'label' => 'Images (URLs, one per line)', 'type' => 'textarea', 'default' => ''],
                ['name' => 'columns', 'label' => 'Columns',   'type' => 'select', 'default' => '3',
                 'options' => ['2'=>'2','3'=>'3','4'=>'4','5'=>'5']],
                ['name' => 'gap',     'label' => 'Gap',       'type' => 'text',   'default' => '8px'],
                ['name' => 'radius',  'label' => 'Radius',    'type' => 'text',   'default' => '6px'],
            ]],
            'icon_box' => ['label' => 'Icon Box', 'fields' => [
                ['name' => 'icon',    'label' => 'Icon (emoji)', 'type' => 'text', 'default' => '⚡'],
                ['name' => 'title',   'label' => 'Title',     'type' => 'text',   'default' => 'Feature Title'],
                ['name' => 'text',    'label' => 'Description','type' => 'textarea','default' => 'A short description.'],
                ['name' => 'align',   'label' => 'Align',     'type' => 'select', 'default' => 'left',
                 'options' => ['left'=>'Left','center'=>'Center','right'=>'Right']],
                ['name' => 'icon_size','label'=> 'Icon Size', 'type' => 'text',   'default' => '36px'],
            ]],
            'counter' => ['label' => 'Counter', 'fields' => [
                ['name' => 'number',  'label' => 'Number',    'type' => 'text',   'default' => '100'],
                ['name' => 'suffix',  'label' => 'Suffix',    'type' => 'text',   'default' => '+'],
                ['name' => 'label',   'label' => 'Label',     'type' => 'text',   'default' => 'Projects Done'],
                ['name' => 'align',   'label' => 'Align',     'type' => 'select', 'default' => 'center',
                 'options' => ['left'=>'Left','center'=>'Center','right'=>'Right']],
                ['name' => 'color',   'label' => 'Number Color','type' => 'color','default' => '#10B27C'],
            ]],
            'alert' => ['label' => 'Alert', 'fields' => [
                ['name' => 'text',    'label' => 'Message',   'type' => 'textarea','default' => 'This is an important message.'],
                ['name' => 'style',   'label' => 'Style',     'type' => 'select', 'default' => 'info',
                 'options' => ['info'=>'Info','success'=>'Success','warning'=>'Warning','danger'=>'Danger']],
                ['name' => 'icon',    'label' => 'Icon',      'type' => 'text',   'default' => 'ℹ'],
            ]],
            'html' => ['label' => 'HTML', 'fields' => [
                ['name' => 'code',    'label' => 'HTML Code', 'type' => 'code',   'default' => '<p>Custom HTML here</p>'],
            ]],
            'posts_grid' => ['label' => 'Posts Grid', 'fields' => [
                ['name' => 'count',   'label' => 'Post Count','type' => 'text',   'default' => '3'],
                ['name' => 'columns', 'label' => 'Columns',   'type' => 'select', 'default' => '3',
                 'options' => ['1'=>'1','2'=>'2','3'=>'3','4'=>'4']],
                ['name' => 'category','label' => 'Category Slug','type' => 'text','default' => ''],
                ['name' => 'show_excerpt','label' => 'Show Excerpt','type' => 'toggle','default' => '1'],
                ['name' => 'show_date',   'label' => 'Show Date',   'type' => 'toggle','default' => '1'],
                ['name' => 'show_image',  'label' => 'Show Image',  'type' => 'toggle','default' => '1'],
            ]],
            'slider' => ['label' => 'Parallax Slider', 'fields' => [
                ['name' => 'slider_id', 'label' => 'Slider ID',  'type' => 'text', 'default' => ''],
                ['name' => 'caption',   'label' => 'Caption (optional)', 'type' => 'text', 'default' => ''],
            ]],
        ];
    }

    // ── Frontend rendering ────────────────────────────────────────────────────

    public function render(string $json, string $basePath = ''): string
    {
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['sections'])) return '';

        $html = '<div class="gb-page">';
        foreach ($data['sections'] as $section) {
            $rendered = $this->renderSection($section, $basePath);
            if ($rendered !== '') $html .= $rendered;
        }
        $html .= '</div>';
        return $html;
    }

    private function renderSection(array $s, string $base): string
    {
        $st        = $s['settings'] ?? [];
        $fullWidth = !empty($st['full_width']);
        $cls       = 'gb-section' . ($fullWidth ? ' gb-full-width' : '');

        $inner = '';
        foreach ($s['columns'] ?? [] as $col) {
            $inner .= $this->renderColumn($col, $base, $fullWidth);
        }

        // Skip sections whose columns rendered no visible content
        if (trim(strip_tags($inner)) === '' && !str_contains($inner, '<img') && !str_contains($inner, 'gb-spacer') && !str_contains($inner, 'gb-slider') && empty($st['bg_image'])) {
            return '';
        }

        $css      = $this->sectionCss($st);
        $innerCss = $fullWidth ? ' style="max-width:100%;width:100%;margin:0;padding:0;box-sizing:border-box;"' : '';
        return "<div class=\"{$cls}\" style=\"{$css}width:100%;margin:0;\"><div class=\"gb-section-inner\"{$innerCss}>{$inner}</div></div>";
    }

    private function renderColumn(array $col, string $base, bool $fullWidth = false): string
    {
        $w   = (int)($col['width'] ?? 100);
        $pad = $fullWidth ? '0' : '0 12px';
        $css = "flex:0 0 {$w}%;max-width:{$w}%;padding:{$pad};margin:0;box-sizing:border-box;";
        if (!empty($col['settings']['padding'])) $css .= "padding:{$col['settings']['padding']};";

        $inner = '';
        foreach ($col['elements'] ?? [] as $el) {
            $inner .= $this->renderElement($el, $base);
        }
        return "<div class=\"gb-column\" style=\"{$css}\">{$inner}</div>";
    }

    private function renderElement(array $el, string $base): string
    {
        $type = $el['type'] ?? 'html';
        $s    = $el['settings'] ?? [];

        return match($type) {
            'heading'    => $this->renderHeading($s),
            'text'       => $this->renderText($s),
            'image'      => $this->renderImage($s),
            'button'     => $this->renderButton($s),
            'spacer'     => $this->renderSpacer($s),
            'divider'    => $this->renderDivider($s),
            'video'      => $this->renderVideo($s),
            'gallery'    => $this->renderGallery($s),
            'icon_box'   => $this->renderIconBox($s),
            'counter'    => $this->renderCounter($s),
            'alert'      => $this->renderAlert($s),
            'html'       => $this->renderHtml($s),
            'posts_grid' => $this->renderPostsGrid($s, $base),
            'slider'     => $this->renderSlider($s),
            default      => '',
        };
    }

    // ── Element renderers ─────────────────────────────────────────────────────

    private function renderHeading(array $s): string
    {
        $tag   = htmlspecialchars($s['tag'] ?? 'h2', ENT_QUOTES);
        $text  = htmlspecialchars($s['text'] ?? '', ENT_QUOTES);
        $align = $s['align'] ?? 'left';
        $css   = "text-align:{$align};";
        if (!empty($s['color'])) $css .= "color:{$s['color']};";
        if (!empty($s['size']))  $css .= "font-size:{$s['size']};";
        return "<{$tag} class=\"gb-heading\" style=\"{$css}\">{$text}</{$tag}>";
    }

    private function renderText(array $s): string
    {
        $content = $s['content'] ?? '';
        $align   = $s['align'] ?? 'left';
        $css     = "text-align:{$align};";
        if (!empty($s['color'])) $css .= "color:{$s['color']};";
        if (!empty($s['size']))  $css .= "font-size:{$s['size']};";
        return "<div class=\"gb-text\" style=\"{$css}\">" . nl2br(htmlspecialchars($content, ENT_QUOTES)) . "</div>";
    }

    private function renderImage(array $s): string
    {
        if (empty($s['src'])) return '';
        $src    = htmlspecialchars($s['src'],             ENT_QUOTES);
        $alt    = htmlspecialchars($s['alt']   ?? '',     ENT_QUOTES);
        $title  = htmlspecialchars($s['title'] ?? '',     ENT_QUOTES);
        $align  = $s['align']      ?? 'center';
        $width  = htmlspecialchars($s['width'] ?? '100%', ENT_QUOTES);
        $height = htmlspecialchars($s['height'] ?? '',    ENT_QUOTES);
        $radius = htmlspecialchars($s['radius'] ?? '0px', ENT_QUOTES);
        $fit    = $s['object_fit'] ?? 'cover';
        $opacity= htmlspecialchars($s['opacity'] ?? '1',  ENT_QUOTES);
        $filter = match($s['filter'] ?? 'none') {
            'grayscale'  => 'grayscale(100%)',
            'sepia'      => 'sepia(100%)',
            'blur'       => 'blur(4px)',
            'brightness' => 'brightness(1.3)',
            default      => 'none',
        };
        $shadow = match($s['box_shadow'] ?? 'none') {
            'sm'  => '0 1px 3px rgba(0,0,0,.12)',
            'md'  => '0 4px 16px rgba(0,0,0,.15)',
            'lg'  => '0 8px 32px rgba(0,0,0,.2)',
            'xl'  => '0 16px 48px rgba(0,0,0,.25)',
            default => 'none',
        };

        $imgStyle = "width:{$width};border-radius:{$radius};display:block;max-width:100%;opacity:{$opacity};";
        if ($height)          $imgStyle .= "height:{$height};object-fit:{$fit};";
        if ($filter !== 'none') $imgStyle .= "filter:{$filter};";
        if ($shadow !== 'none') $imgStyle .= "box-shadow:{$shadow};";

        $titleAttr = $title ? " title=\"{$title}\"" : '';
        $img = "<img src=\"{$src}\" alt=\"{$alt}\"{$titleAttr} style=\"{$imgStyle}\">";

        if (!empty($s['link'])) {
            $href   = htmlspecialchars($s['link'], ENT_QUOTES);
            $target = ($s['link_target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '';
            $img    = "<a href=\"{$href}\"{$target}>{$img}</a>";
        }

        $caption = !empty($s['caption'])
            ? '<figcaption style="font-size:13px;color:#64748b;text-align:center;margin-top:6px">'
              . htmlspecialchars($s['caption'], ENT_QUOTES) . '</figcaption>'
            : '';

        return "<figure class=\"gb-image\" style=\"text-align:{$align};margin:0\">{$img}{$caption}</figure>";
    }

    private function renderButton(array $s): string
    {
        $text   = htmlspecialchars($s['text'] ?? 'Click', ENT_QUOTES);
        $url    = htmlspecialchars($s['url']  ?? '#',     ENT_QUOTES);
        $target = ($s['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '';
        $align  = $s['align']  ?? 'left';
        $style  = $s['style']  ?? 'primary';
        $size   = $s['size']   ?? 'md';
        $radius = htmlspecialchars($s['radius'] ?? '8px', ENT_QUOTES);

        $padding = match($size) { 'sm' => '8px 18px', 'lg' => '16px 36px', default => '11px 28px' };
        $fsize   = match($size) { 'sm' => '13px', 'lg' => '17px', default => '15px' };

        $colors = match($style) {
            'outline' => 'background:transparent;border:2px solid #10B27C;color:#10B27C;',
            'ghost'   => 'background:transparent;border:1px solid #e2e8f0;color:#0f172a;',
            'danger'  => 'background:#ef4444;color:#fff;border:none;',
            default   => 'background:#10B27C;color:#fff;border:none;',
        };

        $css = "{$colors}padding:{$padding};font-size:{$fsize};border-radius:{$radius};display:inline-flex;align-items:center;font-weight:600;text-decoration:none;cursor:pointer;transition:opacity .15s;";

        return "<div class=\"gb-button\" style=\"text-align:{$align}\">"
            . "<a href=\"{$url}\"{$target} style=\"{$css}\">{$text}</a></div>";
    }

    private function renderSpacer(array $s): string
    {
        $h = htmlspecialchars($s['height'] ?? '40px', ENT_QUOTES);
        return "<div class=\"gb-spacer\" style=\"height:{$h}\"></div>";
    }

    private function renderDivider(array $s): string
    {
        $color = htmlspecialchars($s['color'] ?? '#e2e8f0', ENT_QUOTES);
        $thick = htmlspecialchars($s['thickness'] ?? '1px', ENT_QUOTES);
        $width = htmlspecialchars($s['width'] ?? '100%', ENT_QUOTES);
        $align = $s['align'] ?? 'center';
        return "<div class=\"gb-divider\" style=\"text-align:{$align}\">"
            . "<hr style=\"border:none;border-top:{$thick} solid {$color};width:{$width};margin:12px auto\"></div>";
    }

    private function renderVideo(array $s): string
    {
        $url = $s['url'] ?? '';
        if (!$url) return '';

        $embedUrl = $this->toEmbedUrl($url);
        $aspect   = $s['aspect'] ?? '16:9';
        [$w, $h]  = explode(':', $aspect . ':9');
        $padding  = round((int)$h / (int)$w * 100, 2);

        return "<div class=\"gb-video\" style=\"position:relative;padding-bottom:{$padding}%;height:0;overflow:hidden\">"
            . "<iframe src=\"{$embedUrl}\" style=\"position:absolute;top:0;left:0;width:100%;height:100%;border:none\" allowfullscreen loading=\"lazy\"></iframe>"
            . "</div>";
    }

    private function toEmbedUrl(string $url): string
    {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        return htmlspecialchars($url, ENT_QUOTES);
    }

    private function renderGallery(array $s): string
    {
        $images  = array_filter(array_map('trim', explode("\n", $s['images'] ?? '')));
        if (!$images) return '';
        $cols    = (int)($s['columns'] ?? 3);
        $gap     = htmlspecialchars($s['gap'] ?? '8px', ENT_QUOTES);
        $radius  = htmlspecialchars($s['radius'] ?? '6px', ENT_QUOTES);
        $css     = "display:grid;grid-template-columns:repeat({$cols},1fr);gap:{$gap};";
        $items   = '';
        foreach ($images as $img) {
            $src   = htmlspecialchars(trim($img), ENT_QUOTES);
            $items .= "<a href=\"{$src}\" target=\"_blank\"><img src=\"{$src}\" style=\"width:100%;height:200px;object-fit:cover;border-radius:{$radius};display:block\" loading=\"lazy\"></a>";
        }
        return "<div class=\"gb-gallery\" style=\"{$css}\">{$items}</div>";
    }

    private function renderIconBox(array $s): string
    {
        $icon   = htmlspecialchars($s['icon'] ?? '⚡', ENT_QUOTES);
        $title  = htmlspecialchars($s['title'] ?? '', ENT_QUOTES);
        $text   = htmlspecialchars($s['text'] ?? '', ENT_QUOTES);
        $align  = $s['align'] ?? 'left';
        $isize  = htmlspecialchars($s['icon_size'] ?? '36px', ENT_QUOTES);
        return "<div class=\"gb-icon-box\" style=\"text-align:{$align}\">"
            . "<div style=\"font-size:{$isize};margin-bottom:12px\">{$icon}</div>"
            . ($title ? "<h4 style=\"font-size:18px;font-weight:700;margin-bottom:8px\">{$title}</h4>" : '')
            . ($text  ? "<p style=\"color:#64748b;line-height:1.7\">{$text}</p>" : '')
            . "</div>";
    }

    private function renderCounter(array $s): string
    {
        $num    = htmlspecialchars($s['number'] ?? '0', ENT_QUOTES);
        $suffix = htmlspecialchars($s['suffix'] ?? '', ENT_QUOTES);
        $label  = htmlspecialchars($s['label']  ?? '', ENT_QUOTES);
        $align  = $s['align'] ?? 'center';
        $color  = htmlspecialchars($s['color'] ?? '#10B27C', ENT_QUOTES);
        return "<div class=\"gb-counter\" style=\"text-align:{$align}\">"
            . "<div class=\"gb-counter-num\" data-target=\"{$num}\" style=\"font-size:48px;font-weight:900;color:{$color};line-height:1\">{$num}{$suffix}</div>"
            . ($label ? "<div style=\"font-size:14px;color:#64748b;margin-top:6px;font-weight:600\">{$label}</div>" : '')
            . "</div>";
    }

    private function renderAlert(array $s): string
    {
        $text  = htmlspecialchars($s['text'] ?? '', ENT_QUOTES);
        $style = $s['style'] ?? 'info';
        $icon  = htmlspecialchars($s['icon'] ?? 'ℹ', ENT_QUOTES);
        $colors = match($style) {
            'success' => 'background:#dcfce7;border-left:4px solid #22c55e;color:#166534;',
            'warning' => 'background:#fef9c3;border-left:4px solid #eab308;color:#854d0e;',
            'danger'  => 'background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;',
            default   => 'background:#dbeafe;border-left:4px solid #3b82f6;color:#1e40af;',
        };
        return "<div class=\"gb-alert\" style=\"{$colors}padding:14px 18px;border-radius:6px;display:flex;align-items:flex-start;gap:12px;\">"
            . "<span style=\"font-size:20px;flex-shrink:0\">{$icon}</span>"
            . "<div style=\"font-size:14px;line-height:1.6\">{$text}</div>"
            . "</div>";
    }

    private function renderHtml(array $s): string
    {
        $code = (string)($s['code'] ?? '');

        // Process shortcodes inside HTML blocks
        global $shortcodeManagerInstance;
        if ($shortcodeManagerInstance instanceof \GoniCore\Core\Shortcodes\ShortcodeManager) {
            $code = $shortcodeManagerInstance->process($code);
        }

        return "<div class=\"gb-html\">{$code}</div>";
    }

    private function renderPostsGrid(array $s, string $base): string
    {
        $count    = max(1, (int)($s['count'] ?? 3));
        $cols     = (int)($s['columns'] ?? 3);
        $catSlug  = trim($s['category'] ?? '');
        $showExc  = ($s['show_excerpt'] ?? '1') === '1';
        $showDate = ($s['show_date']    ?? '1') === '1';
        $showImg  = ($s['show_image']   ?? '1') === '1';

        $qb = $this->qb->table('posts')
            ->where('type', '=', 'post')
            ->where('status', '=', 'published')
            ->orderBy('created_at', 'DESC')
            ->limit($count);

        if ($catSlug) {
            $cat = $this->qb->table('categories')->where('slug', '=', $catSlug)->first();
            if ($cat) $qb->where('category_id', '=', (int)$cat['id']);
        }

        $posts = $qb->get();
        if (!$posts) return '';

        $css   = "display:grid;grid-template-columns:repeat({$cols},1fr);gap:24px;";
        $items = '';

        foreach ($posts as $p) {
            $url   = htmlspecialchars($base . '/post/' . ($p['slug'] ?? ''), ENT_QUOTES);
            $title = htmlspecialchars((string)($p['title'] ?? ''), ENT_QUOTES);
            $card  = '';

            if ($showImg && !empty($p['featured_image'])) {
                $img   = htmlspecialchars((string)$p['featured_image'], ENT_QUOTES);
                $card .= "<a href=\"{$url}\"><img src=\"{$img}\" style=\"width:100%;height:200px;object-fit:cover;border-radius:8px 8px 0 0\" loading=\"lazy\"></a>";
            }

            $card .= "<div style=\"padding:16px\">";
            $card .= "<h3 style=\"font-size:16px;font-weight:700;margin-bottom:8px\"><a href=\"{$url}\" style=\"color:inherit;text-decoration:none\">{$title}</a></h3>";

            if ($showDate) {
                $date  = htmlspecialchars(date('M j, Y', strtotime((string)($p['created_at'] ?? ''))), ENT_QUOTES);
                $card .= "<div style=\"font-size:12px;color:#94a3b8;margin-bottom:8px\">{$date}</div>";
            }

            if ($showExc && !empty($p['excerpt'])) {
                $exc   = htmlspecialchars(mb_substr(strip_tags((string)$p['excerpt']), 0, 120) . '…', ENT_QUOTES);
                $card .= "<p style=\"font-size:14px;color:#64748b;line-height:1.6\">{$exc}</p>";
            }

            $card .= "</div>";
            $items .= "<div class=\"gb-post-card\" style=\"background:#fff;border-radius:10px;border:1px solid #e2e8f0;overflow:hidden\">{$card}</div>";
        }

        return "<div class=\"gb-posts-grid\" style=\"{$css}\">{$items}</div>";
    }

    private function renderSlider(array $s): string
    {
        $id = (int)($s['slider_id'] ?? 0);
        if (!$id) {
            return '<div class="gb-slider-placeholder" style="background:#f1f5f9;border:2px dashed #e2e8f0;border-radius:8px;padding:40px;text-align:center;color:#64748b">'
                . '<div style="font-size:32px;margin-bottom:8px">🎞</div>'
                . '<p style="font-size:13px">No slider selected. Set a Slider ID in the element settings.</p>'
                . '</div>';
        }

        // Use shortcode rendering to keep logic in one place
        /** @var \GoniCore\Core\Shortcodes\ShortcodeManager $sm */
        global $shortcodeManagerInstance;
        if ($shortcodeManagerInstance instanceof \GoniCore\Core\Shortcodes\ShortcodeManager) {
            try {
                return $shortcodeManagerInstance->execute('parallax_slider', ['id' => (string)$id]);
            } catch (\Throwable) {}
        }

        // Fallback: raw shortcode tag (gets processed later if content goes through ShortcodeManager)
        return '[parallax_slider id="' . $id . '"]';
    }

    // ── Section CSS helper ────────────────────────────────────────────────────

    private function sectionCss(array $st): string
    {
        $css = '';
        if (!empty($st['bg_color']))  $css .= "background-color:{$st['bg_color']};";
        if (!empty($st['bg_image']))  {
            $img  = htmlspecialchars($st['bg_image'], ENT_QUOTES);
            $css .= "background-image:url('{$img}');background-size:cover;background-position:center;";
        }
        // Use stored padding; fall back to 60px 0 only when truly unset (not when explicitly "0" or "0px")
        $pad = isset($st['padding']) ? trim((string)$st['padding']) : null;
        if ($pad === null) $pad = '60px 0';
        $css .= "padding:{$pad};";
        return $css;
    }
}
