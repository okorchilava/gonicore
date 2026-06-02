<?php

declare(strict_types=1);

namespace GoniCore\Shared\Contracts;

/**
 * Contract for all GoniCore shortcodes.
 *
 * A shortcode transforms a bracketed tag inside content into an output string.
 * Self-closing and wrapping forms are both supported by the ShortcodeManager:
 *
 *   [gallery ids="1,2,3"]                   ← self-closing, $content = ''
 *   [button url="/contact"]Click me[/button] ← wrapping,  $content = 'Click me'
 *
 * Example implementation:
 *
 *   final class ButtonShortcode implements ShortcodeInterface
 *   {
 *       public function getTag(): string { return 'button'; }
 *
 *       public function render(array $attrs, string $content): string
 *       {
 *           $url = htmlspecialchars($attrs['url'] ?? '#', ENT_QUOTES, 'UTF-8');
 *           return '<a href="' . $url . '" class="btn">' . $content . '</a>';
 *       }
 *   }
 */
interface ShortcodeInterface
{
    /**
     * Return the tag name that triggers this shortcode (lowercase, no brackets).
     * E.g. "gallery", "button", "recent-posts".
     */
    public function getTag(): string;

    /**
     * Render the shortcode and return the replacement HTML/text string.
     *
     * @param  array<string, string> $attrs    Parsed key=value attributes from the tag.
     *                                          Valueless flags are stored as $attrs['flag'] = 'flag'.
     * @param  string                $content  Inner content for wrapping shortcodes; empty string
     *                                          for self-closing ones.
     * @return string                           The replacement string (may contain HTML).
     */
    public function render(array $attrs, string $content): string;
}
