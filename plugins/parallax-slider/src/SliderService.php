<?php
declare(strict_types=1);

namespace ParallaxSlider;

use GoniCore\Core\Database\QueryBuilder;

final class SliderService
{
    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Sliders ───────────────────────────────────────────────────────────────

    public function allSliders(): array
    {
        return $this->qb->table('ps_sliders')->orderBy('id', 'DESC')->get() ?: [];
    }

    public function getSlider(int $id): ?array
    {
        return $this->qb->table('ps_sliders')->where('id', '=', $id)->first() ?: null;
    }

    public function createSlider(string $name): int
    {
        return (int) $this->qb->table('ps_sliders')->insert([
            'name'     => $name,
            'settings' => json_encode($this->defaultSliderSettings()),
        ]);
    }

    public function updateSlider(int $id, string $name, array $settings): void
    {
        $this->qb->table('ps_sliders')->where('id', '=', $id)->update([
            'name'     => $name,
            'settings' => json_encode($settings),
        ]);
    }

    public function deleteSlider(int $id): void
    {
        $this->qb->table('ps_sliders')->where('id', '=', $id)->delete();
    }

    // ── Slides ────────────────────────────────────────────────────────────────

    public function getSlides(int $sliderId): array
    {
        return $this->qb->table('ps_slides')
            ->where('slider_id', '=', $sliderId)
            ->orderBy('order_index', 'ASC')
            ->get() ?: [];
    }

    public function getSlide(int $id): ?array
    {
        return $this->qb->table('ps_slides')->where('id', '=', $id)->first() ?: null;
    }

    public function addSlide(int $sliderId): int
    {
        $maxOrder = (int) ($this->qb->table('ps_slides')
            ->where('slider_id', '=', $sliderId)
            ->orderBy('order_index', 'DESC')
            ->first()['order_index'] ?? -1);

        return (int) $this->qb->table('ps_slides')->insert([
            'slider_id'   => $sliderId,
            'title'       => 'New Slide',
            'order_index' => $maxOrder + 1,
            'bg_type'     => 'color',
            'bg_value'    => '#1e293b',
        ]);
    }

    public function updateSlide(int $id, array $data): void
    {
        $allowed = ['title','bg_type','bg_value','bg_overlay','bg_overlay_color',
                    'duration','link','link_target','kenburns','active'];
        $update = array_intersect_key($data, array_flip($allowed));
        if ($update) {
            $this->qb->table('ps_slides')->where('id', '=', $id)->update($update);
        }
    }

    public function deleteSlide(int $id): void
    {
        $this->qb->table('ps_slides')->where('id', '=', $id)->delete();
    }

    public function reorderSlides(array $ids): void
    {
        foreach ($ids as $i => $id) {
            $this->qb->table('ps_slides')->where('id', '=', (int)$id)->update(['order_index' => $i]);
        }
    }

    // ── Layers ────────────────────────────────────────────────────────────────

    public function getLayers(int $slideId): array
    {
        return $this->qb->table('ps_layers')
            ->where('slide_id', '=', $slideId)
            ->orderBy('order_index', 'ASC')
            ->get() ?: [];
    }

    /** Replace all layers for a slide in one shot (from editor save). */
    public function saveLayers(int $slideId, array $layers): void
    {
        $this->qb->table('ps_layers')->where('slide_id', '=', $slideId)->delete();
        foreach ($layers as $i => $l) {
            $settings = isset($l['settings']) && is_array($l['settings'])
                ? json_encode($l['settings'])
                : (is_string($l['settings'] ?? null) ? $l['settings'] : '{}');

            $this->qb->table('ps_layers')->insert([
                'slide_id'      => $slideId,
                'type'          => $l['type']         ?? 'text',
                'content'       => $l['content']      ?? '',
                'x'             => (float)($l['x']    ?? 50),
                'y'             => (float)($l['y']    ?? 50),
                'width'         => $l['width']        ?? 'auto',
                'height'        => $l['height']       ?? 'auto',
                'depth'         => (float)($l['depth'] ?? 0.5),
                'anim_in'       => $l['anim_in']      ?? 'fadeIn',
                'anim_out'      => $l['anim_out']     ?? 'fadeOut',
                'anim_delay'    => (int)($l['anim_delay']    ?? 300),
                'anim_duration' => (int)($l['anim_duration'] ?? 700),
                'anim_out_delay'=> (int)($l['anim_out_delay'] ?? 0),
                'settings'      => $settings,
                'order_index'   => $i,
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function defaultSliderSettings(): array
    {
        return [
            'height'            => '560px',
            'autoplay'          => true,
            'autoplay_speed'    => 6000,
            'transition'        => 'fade',       // fade|slide|zoom|flip|cube
            'transition_speed'  => 900,
            'mouse_parallax'    => true,
            'mouse_strength'    => 25,
            'show_arrows'       => true,
            'show_dots'         => true,
            'loop'              => true,
            'pause_on_hover'    => true,
        ];
    }

    /** Decode slider settings JSON, fill defaults for missing keys. */
    public function decodeSettings(string $json): array
    {
        $decoded = json_decode($json, true);
        return array_merge($this->defaultSliderSettings(), is_array($decoded) ? $decoded : []);
    }

    public function decodeLayerSettings(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function animationsIn(): array
    {
        return [
            'fadeIn'       => 'Fade In',
            'fadeInLeft'   => 'Fade In Left',
            'fadeInRight'  => 'Fade In Right',
            'fadeInUp'     => 'Fade In Up',
            'fadeInDown'   => 'Fade In Down',
            'zoomIn'       => 'Zoom In',
            'zoomInLeft'   => 'Zoom In Left',
            'zoomInRight'  => 'Zoom In Right',
            'bounceIn'     => 'Bounce In',
            'bounceInLeft' => 'Bounce In Left',
            'bounceInRight'=> 'Bounce In Right',
            'slideInLeft'  => 'Slide In Left',
            'slideInRight' => 'Slide In Right',
            'slideInUp'    => 'Slide In Up',
            'slideInDown'  => 'Slide In Down',
            'flipInX'      => 'Flip In X',
            'flipInY'      => 'Flip In Y',
            'rotateIn'     => 'Rotate In',
            'none'         => 'None',
        ];
    }

    public static function animationsOut(): array
    {
        return [
            'fadeOut'        => 'Fade Out',
            'fadeOutLeft'    => 'Fade Out Left',
            'fadeOutRight'   => 'Fade Out Right',
            'fadeOutUp'      => 'Fade Out Up',
            'fadeOutDown'    => 'Fade Out Down',
            'zoomOut'        => 'Zoom Out',
            'slideOutLeft'   => 'Slide Out Left',
            'slideOutRight'  => 'Slide Out Right',
            'slideOutUp'     => 'Slide Out Up',
            'slideOutDown'   => 'Slide Out Down',
            'bounceOut'      => 'Bounce Out',
            'flipOutX'       => 'Flip Out X',
            'flipOutY'       => 'Flip Out Y',
            'rotateOut'      => 'Rotate Out',
            'none'           => 'None',
        ];
    }
}
