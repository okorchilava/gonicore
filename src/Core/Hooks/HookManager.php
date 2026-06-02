<?php

declare(strict_types=1);

namespace GoniCore\Core\Hooks;

/**
 * Central action/filter dispatcher — WordPress-style hook system.
 *
 * Actions:  fire-and-forget side effects.
 *   $hooks->addAction('post.created', fn(int $id) => sendEmail($id));
 *   $hooks->doAction('post.created', $postId);
 *
 * Filters:  transform a value through a chain of callbacks.
 *   $hooks->addFilter('post.title', fn(string $t) => strtoupper($t));
 *   $title = $hooks->applyFilters('post.title', $rawTitle);
 *
 * Callbacks registered with a lower priority number run first (default 10).
 */
final class HookManager
{
    /**
     * @var array<string, array<int, list<callable>>>
     *          tag         priority  callbacks
     */
    private array $actions = [];

    /**
     * @var array<string, array<int, list<callable>>>
     */
    private array $filters = [];

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /**
     * Register a callback to be run when $tag is fired.
     *
     * @param callable $function  Receives the arguments passed to doAction().
     * @param int      $priority  Lower runs first. Default 10.
     */
    public function addAction(string $tag, callable $function, int $priority = 10): void
    {
        $this->actions[$tag][$priority][] = $function;
    }

    /**
     * Fire all callbacks registered for $tag, in priority order.
     * Return values of callbacks are discarded.
     */
    public function doAction(string $tag, mixed ...$args): void
    {
        if (!isset($this->actions[$tag])) {
            return;
        }

        ksort($this->actions[$tag]);

        foreach ($this->actions[$tag] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    /**
     * Return true if at least one action callback is registered for $tag.
     */
    public function hasAction(string $tag): bool
    {
        return !empty($this->actions[$tag]);
    }

    /**
     * Remove all action callbacks for $tag (optionally at a specific priority).
     */
    public function removeAction(string $tag, ?int $priority = null): void
    {
        if ($priority !== null) {
            unset($this->actions[$tag][$priority]);
        } else {
            unset($this->actions[$tag]);
        }
    }

    // -------------------------------------------------------------------------
    // Filters
    // -------------------------------------------------------------------------

    /**
     * Register a callback that receives and returns (a modified) $value.
     *
     * @param callable $function  Receives ($value, ...$args) and MUST return a value.
     * @param int      $priority  Lower runs first. Default 10.
     */
    public function addFilter(string $tag, callable $function, int $priority = 10): void
    {
        $this->filters[$tag][$priority][] = $function;
    }

    /**
     * Pass $value through all filter callbacks registered for $tag.
     *
     * Each callback receives the current $value (plus optional extra $args)
     * and must return the (potentially modified) value.
     * If no filters are registered, $value is returned unchanged.
     */
    public function applyFilters(string $tag, mixed $value, mixed ...$args): mixed
    {
        if (!isset($this->filters[$tag])) {
            return $value;
        }

        ksort($this->filters[$tag]);

        foreach ($this->filters[$tag] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }

    /**
     * Return true if at least one filter callback is registered for $tag.
     */
    public function hasFilter(string $tag): bool
    {
        return !empty($this->filters[$tag]);
    }

    /**
     * Remove all filter callbacks for $tag (optionally at a specific priority).
     */
    public function removeFilter(string $tag, ?int $priority = null): void
    {
        if ($priority !== null) {
            unset($this->filters[$tag][$priority]);
        } else {
            unset($this->filters[$tag]);
        }
    }
}
