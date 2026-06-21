<?php
declare(strict_types=1);

namespace GCCounter;

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;

final class GCCounterService
{
    private static ?self $instance = null;

    /** Prevent injecting CSS/JS more than once per page render. */
    private static bool $assetsInjected = false;

    public function __construct(
        private readonly QueryBuilder $qb,
        private readonly Connection   $conn,
    ) {}

    public static function register(self $s): void { self::$instance = $s; }
    public static function getInstance(): ?self     { return self::$instance; }

    /** Reset the per-request asset flag (useful for tests / multiple renders). */
    public static function resetAssets(): void { self::$assetsInjected = false; }

    // ── Group CRUD ─────────────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    public function allGroups(): array
    {
        return $this->qb->table('gccounter_groups')->orderBy('name', 'ASC')->get();
    }

    public function group(int $id): ?array
    {
        return $this->qb->table('gccounter_groups')->where('id', '=', $id)->first() ?: null;
    }

    public function groupBySlug(string $slug): ?array
    {
        return $this->qb->table('gccounter_groups')->where('slug', '=', $slug)->first() ?: null;
    }

    public function saveGroup(array $data, ?int $id = null): int
    {
        if ($id) {
            $this->qb->table('gccounter_groups')->where('id', '=', $id)->update($data);
            return $id;
        }
        $this->qb->table('gccounter_groups')->insert($data);
        return (int) $this->conn->pdo()->lastInsertId();
    }

    public function deleteGroup(int $id): void
    {
        // Items are deleted via ON DELETE CASCADE
        $this->qb->table('gccounter_groups')->where('id', '=', $id)->delete();
    }

    // ── Item CRUD ──────────────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    public function itemsByGroup(int $groupId): array
    {
        return $this->qb->table('gccounter_items')
            ->where('group_id', '=', $groupId)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /**
     * Replace all items for a group.
     * Used on save: delete old items, insert new ones.
     *
     * @param list<array<string,mixed>> $items
     */
    public function replaceItems(int $groupId, array $items): void
    {
        $this->qb->table('gccounter_items')->where('group_id', '=', $groupId)->delete();
        foreach ($items as $i => $item) {
            $num = (int) preg_replace('/[^0-9\-]/', '', (string)($item['number'] ?? '0'));
            if ($num < 0) $num = 0;
            $this->qb->table('gccounter_items')->insert([
                'group_id'    => $groupId,
                'number'      => $num,
                'prefix'      => mb_substr(trim((string)($item['prefix']      ?? '')), 0, 20),
                'suffix'      => mb_substr(trim((string)($item['suffix']      ?? '')), 0, 20),
                'label'       => mb_substr(trim((string)($item['label']       ?? '')), 0, 200),
                'description' => mb_substr(trim((string)($item['description'] ?? '')), 0, 500),
                'color'       => $this->sanitizeColor((string)($item['color'] ?? '#10B27C')),
                'sort_order'  => $i,
            ]);
        }
    }

    // ── Stats ──────────────────────────────────────────────────────────────────

    public function stats(): array
    {
        try {
            $row = $this->conn->pdo()->query("
                SELECT
                    (SELECT COUNT(*) FROM `gccounter_groups`) AS group_count,
                    (SELECT COUNT(*) FROM `gccounter_items`)  AS item_count
            ")->fetch(\PDO::FETCH_ASSOC);
            return $row ?: [];
        } catch (\Throwable) { return []; }
    }

    // ── Rendering ──────────────────────────────────────────────────────────────

    public function renderById(int $id): string
    {
        $group = $this->group($id);
        return $group ? $this->renderGroup($group) : '';
    }

    public function renderBySlug(string $slug): string
    {
        $group = $this->groupBySlug($slug);
        return $group ? $this->renderGroup($group) : '';
    }

    private function renderGroup(array $group): string
    {
        $items = $this->itemsByGroup((int) $group['id']);
        if (empty($items)) return '';

        $cols    = max(1, min(6, (int)($group['columns']    ?? 4)));
        $dur     = max(200, min(8000, (int)($group['duration_ms'] ?? 2000)));
        $sep     = in_array($group['separator'] ?? ',', [',','.',''], true)
                   ? (string)$group['separator']
                   : ',';
        $align   = in_array($group['align'] ?? 'center', ['left','center','right'], true)
                   ? (string)$group['align']
                   : 'center';

        // Responsive columns:
        // ≥ 2 cols: use requested columns
        // tablet (< 900px): max 3
        // mobile (< 580px): max 2
        $groupId = 'gcc-' . (int)$group['id'];
        $colsCss = "repeat({$cols},1fr)";

        $html  = '<div class="gcc-group gcc-cols-' . $cols . '" id="' . $groupId . '"'
               . ' data-dur="' . $dur . '"'
               . ' style="display:grid;grid-template-columns:' . $colsCss . ';'
               . 'gap:40px 24px;text-align:' . $align . ';align-items:start">';

        foreach ($items as $item) {
            $html .= $this->renderItem($item, $sep);
        }

        $html .= '</div>';
        $html .= $this->renderAssets($cols);
        return $html;
    }

    private function renderItem(array $item, string $sep): string
    {
        $h      = static fn(string $v) => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $target = (int)($item['number'] ?? 0);
        $prefix = (string)($item['prefix'] ?? '');
        $suffix = (string)($item['suffix'] ?? '');
        $label  = trim((string)($item['label'] ?? ''));
        $desc   = trim((string)($item['description'] ?? ''));
        $color  = $this->sanitizeColor((string)($item['color'] ?? '#10B27C'));

        // Formatted initial (no-JS fallback)
        $formatted = $prefix . $this->formatNumber($target, $sep) . $suffix;

        $html  = '<div class="gcc-item">';

        // Number
        $html .= '<div class="gcc-num" style="font-size:3rem;font-weight:900;line-height:1;color:' . $h($color) . '">';
        $html .= '<span class="gcc-count"'
               . ' data-target="' . $target . '"'
               . ' data-prefix="' . $h($prefix) . '"'
               . ' data-suffix="' . $h($suffix) . '"'
               . ' data-sep="'    . $h($sep)    . '">'
               . $h($formatted)
               . '</span>';
        $html .= '</div>';

        // Label
        if ($label !== '') {
            $html .= '<div class="gcc-label" style="font-size:1rem;font-weight:700;margin-top:12px;color:#1e293b">'
                   . $h($label) . '</div>';
        }

        // Description
        if ($desc !== '') {
            $html .= '<div class="gcc-desc" style="font-size:.875rem;color:#64748b;margin-top:6px;line-height:1.6">'
                   . $h($desc) . '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /** Outputs CSS + JS only the first time per page. */
    private function renderAssets(int $cols): string
    {
        if (self::$assetsInjected) return '';
        self::$assetsInjected = true;

        $css = <<<CSS
<style id="gcc-style">
.gcc-group{box-sizing:border-box}
.gcc-item{box-sizing:border-box}
.gcc-count{display:inline-block;transition:none}
@media(max-width:900px){.gcc-group{grid-template-columns:repeat(3,1fr)!important}}
@media(max-width:580px){.gcc-group{grid-template-columns:repeat(2,1fr)!important;gap:28px 16px!important}}
@media(max-width:340px){.gcc-group{grid-template-columns:1fr!important}}
</style>
CSS;

        $js = <<<'JS'
<script id="gcc-script">
(function(){
if(window.__gccReady)return;window.__gccReady=true;

function ease(t){return 1-Math.pow(1-t,4);}

function fmt(n,sep){
  var s=Math.floor(n).toString();
  if(!sep)return s;
  return s.replace(/\B(?=(\d{3})+(?!\d))/g,sep);
}

function animateOne(el){
  var target  =parseInt(el.dataset.target,10)||0;
  var prefix  =el.dataset.prefix||'';
  var suffix  =el.dataset.suffix||'';
  var sep     =el.dataset.sep!==undefined?el.dataset.sep:',';
  var grp     =el.closest('[data-dur]');
  var duration=grp?parseInt(grp.dataset.dur,10)||2000:2000;
  var start=null;
  el.textContent=prefix+'0'+suffix;
  function step(ts){
    if(!start)start=ts;
    var p=Math.min((ts-start)/duration,1);
    el.textContent=prefix+fmt(ease(p)*target,sep)+suffix;
    if(p<1)requestAnimationFrame(step);
    else el.textContent=prefix+fmt(target,sep)+suffix;
  }
  requestAnimationFrame(step);
}

var obs=new IntersectionObserver(function(entries){
  entries.forEach(function(e){
    if(!e.isIntersecting)return;
    animateOne(e.target);
    obs.unobserve(e.target);
  });
},{threshold:0.3});

function init(){
  document.querySelectorAll('.gcc-count').forEach(function(el){
    obs.observe(el);
  });
}

if(document.readyState==='loading'){
  document.addEventListener('DOMContentLoaded',init);
}else{init();}
})();
</script>
JS;

        return "\n" . $css . $js;
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function formatNumber(int $n, string $sep): string
    {
        if ($sep === '') return (string) $n;
        return number_format($n, 0, '.', $sep);
    }

    private function sanitizeColor(string $color): string
    {
        $c = trim($color);
        // Accepts #RGB, #RRGGBB, rgb(...), hsl(...)
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $c)) return $c;
        if (preg_match('/^(?:rgb|hsl)a?\([^)]+\)$/i', $c)) return $c;
        return '#10B27C';
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        // Replace Georgian characters — transliteration not needed, just use id fallback
        $text = preg_replace('/[^\p{L}\p{N}\-]/u', '-', $text) ?? $text;
        $text = preg_replace('/-{2,}/', '-', $text) ?? $text;
        return trim($text, '-') ?: 'group';
    }
}
