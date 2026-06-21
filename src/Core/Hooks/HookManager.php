<?php

declare(strict_types=1);

namespace GoniCore\Core\Hooks;

/**
 * GoniCore Hook System — central action/filter dispatcher.
 *
 * Actions  (fire-and-forget side effects):
 *   $hooks->on('post.created', fn(int $id, array $data) => ...);
 *   $hooks->emit('post.created', $id, $data);
 *
 * Filters  (transform a value through a chain of callbacks):
 *   $hooks->filter('the_content', fn(string $html) => strtoupper($html));
 *   $html = $hooks->apply('the_content', $rawHtml);
 *
 * Global functions (use anywhere, no injection needed):
 *   gc_on('post.created', fn($id, $data) => ...);
 *   gc_emit('post.created', $id, $data);
 *   gc_filter('the_content', fn($html) => $html);
 *   $html = gc_apply('the_content', $rawHtml);
 *
 * Callbacks registered with a lower priority number run first (default 10).
 */
final class HookManager
{
    // ── Global instance (for WordPress-style global functions) ────────────────

    private static ?self $globalInstance = null;

    public static function setGlobalInstance(self $instance): void
    {
        self::$globalInstance = $instance;
    }

    public static function global(): self
    {
        if (self::$globalInstance === null) {
            throw new \RuntimeException(
                'Global HookManager instance not initialised. '
                . 'Call HookManager::setGlobalInstance() in bootstrap before using gc_on/gc_emit/gc_filter/gc_apply.'
            );
        }
        return self::$globalInstance;
    }

    // ── Storage ───────────────────────────────────────────────────────────────

    /** @var array<string, array<int, list<callable>>> */
    private array $actions = [];

    /** @var array<string, array<int, list<callable>>> */
    private array $filters = [];

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Register a callback to run when $tag is emitted.
     *
     * @param callable $fn       Receives the arguments passed to emit().
     * @param int      $priority Lower runs first. Default 10.
     */
    public function on(string $tag, callable $fn, int $priority = 10): void
    {
        $this->actions[$tag][$priority][] = $fn;
    }

    /**
     * Fire all callbacks registered for $tag, in priority order.
     * Return values of callbacks are discarded.
     */
    public function emit(string $tag, mixed ...$args): void
    {
        if (!isset($this->actions[$tag])) {
            return;
        }

        ksort($this->actions[$tag]);

        foreach ($this->actions[$tag] as $callbacks) {
            foreach ($callbacks as $fn) {
                $fn(...$args);
            }
        }
    }

    /**
     * Remove all action callbacks for $tag (optionally at a specific priority).
     */
    public function off(string $tag, ?int $priority = null): void
    {
        if ($priority !== null) {
            unset($this->actions[$tag][$priority]);
        } else {
            unset($this->actions[$tag]);
        }
    }

    /** Return true if at least one action callback is registered for $tag. */
    public function has(string $tag): bool
    {
        return !empty($this->actions[$tag]);
    }

    // ── Filters ───────────────────────────────────────────────────────────────

    /**
     * Register a callback that receives and returns a (modified) $value.
     *
     * @param callable $fn       Receives ($value, ...$extraArgs) and MUST return a value.
     * @param int      $priority Lower runs first. Default 10.
     */
    public function filter(string $tag, callable $fn, int $priority = 10): void
    {
        $this->filters[$tag][$priority][] = $fn;
    }

    /**
     * Pass $value through all filter callbacks registered for $tag.
     * Returns $value unchanged if no filters are registered.
     */
    public function apply(string $tag, mixed $value, mixed ...$args): mixed
    {
        if (!isset($this->filters[$tag])) {
            return $value;
        }

        ksort($this->filters[$tag]);

        foreach ($this->filters[$tag] as $callbacks) {
            foreach ($callbacks as $fn) {
                $value = $fn($value, ...$args);
            }
        }

        return $value;
    }

    /**
     * Remove all filter callbacks for $tag (optionally at a specific priority).
     */
    public function unfilter(string $tag, ?int $priority = null): void
    {
        if ($priority !== null) {
            unset($this->filters[$tag][$priority]);
        } else {
            unset($this->filters[$tag]);
        }
    }

    /** Return true if at least one filter callback is registered for $tag. */
    public function hasFilter(string $tag): bool
    {
        return !empty($this->filters[$tag]);
    }
}
