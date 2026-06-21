<?php

declare(strict_types=1);

namespace GoniCore\Core\Shortcodes;

use GoniCore\Shared\Contracts\ShortcodeInterface;

/**
 * Registers and processes shortcodes inside content strings.
 *
 * Supported syntax
 * ─────────────────
 *   Self-closing:  [gallery ids="1,2,3" limit=5]
 *   Wrapping:      [button url="/go"]Click[/button]
 *   Nested tags are NOT supported (by design — keeps parsing O(n) and safe).
 *
 * Usage
 * ─────
 *   $mgr->register(new GalleryShortcode());
 *   $html = $mgr->process($post->content);
 *
 * Integration with HookManager
 * ─────────────────────────────
 *   Apply via a filter so any plugin can tap in:
 *
 *   gc_filter('post.content', fn(string $c) => $shortcodes->process($c));
 */
final class ShortcodeManager
{
    /** @var array<string, ShortcodeInterface> */
    private array $shortcodes = [];

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Register a shortcode handler.
     * Re-registering the same tag overwrites the previous handler (plugin override).
     */
    public function register(ShortcodeInterface $shortcode): void
    {
        $this->shortcodes[$shortcode->getTag()] = $shortcode;
    }

    /**
     * Unregister a shortcode by tag name. Silent no-op if not found.
     */
    public function unregister(string $tag): void
    {
        unset($this->shortcodes[$tag]);
    }

    public function has(string $tag): bool
    {
        return isset($this->shortcodes[$tag]);
    }

    /**
     * @return list<string>
     */
    public function registeredTags(): array
    {
        return array_values(array_keys($this->shortcodes));
    }

    // -------------------------------------------------------------------------
    // Direct execution (without scanning content)
    // -------------------------------------------------------------------------

    /**
     * Execute a single shortcode by tag and return its rendered output.
     *
     * @param  array<string, string> $attrs
     * @throws ShortcodeNotFoundException
     */
    public function execute(string $tag, array $attrs = [], string $content = ''): string
    {
        if (!isset($this->shortcodes[$tag])) {
            throw new ShortcodeNotFoundException(
                "Shortcode [{$tag}] is not registered."
            );
        }

        return $this->shortcodes[$tag]->render($attrs, $content);
    }

    // -------------------------------------------------------------------------
    // Content processing
    // -------------------------------------------------------------------------

    /**
     * Scan $content and replace every registered shortcode tag with its output.
     * Unrecognised tags are left untouched.
     *
     * @param  string $content  Raw content (HTML or plain text).
     * @return string           Content with shortcodes replaced.
     */
    public function process(string $content): string
    {
        if ($this->shortcodes === [] || $content === '') {
            return $content;
        }

        $tags    = array_map('preg_quote', array_keys($this->shortcodes), array_fill(0, count($this->shortcodes), '/'));
        $tagList = implode('|', $tags);

        // Matches both wrapping and self-closing forms:
        //   [tag attr="val" ...] inner content [/tag]
        //   [tag attr="val" .../]   (XHTML-style self-close)
        //   [tag attr="val" ...]    (implicit self-close)
        $pattern = '/\[(' . $tagList . ')([^\]]*?)(?:\/\]|\](?:((?:[^\[]*|\[(?!\/?(?:' . $tagList . ')[\s\]\/])[^\]]*\])*?)\[\/\1\])?)/s';

        return (string) preg_replace_callback(
            $pattern,
            function (array $matches): string {
                $tag     = $matches[1];
                $rawAttr = trim($matches[2]);
                $content = isset($matches[3]) ? $matches[3] : '';

                $attrs = $this->parseAttributes($rawAttr);

                return $this->shortcodes[$tag]->render($attrs, $content);
            },
            $content,
        );
    }

    // -------------------------------------------------------------------------
    // Attribute parsing
    // -------------------------------------------------------------------------

    /**
     * Parse a raw attribute string into a key=>value map.
     *
     * Handles:
     *   key="value"     → ['key' => 'value']
     *   key='value'     → ['key' => 'value']
     *   key=value       → ['key' => 'value']
     *   flag            → ['flag' => 'flag']   (valueless boolean attribute)
     *
     * @param  string                $raw
     * @return array<string, string>
     */
    private function parseAttributes(string $raw): array
    {
        $attrs = [];

        if ($raw === '') {
            return $attrs;
        }

        // Double-quoted:  key="value"
        // Single-quoted:  key='value'
        // Unquoted:       key=value
        // Flag:           flagname
        $pattern = '/(\w[\w-]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+)))?/';

        preg_match_all($pattern, $raw, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $key   = $m[1];
            $value = $m[2] !== '' ? $m[2]          // double-quoted
                   : ($m[3] !== '' ? $m[3]          // single-quoted
                   : ($m[4] !== '' ? $m[4]          // unquoted
                   : $key));                        // valueless flag

            $attrs[$key] = $value;
        }

        return $attrs;
    }
}
