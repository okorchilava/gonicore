<?php

declare(strict_types=1);

namespace GoniCore\Shared\Contracts;

/**
 * Contract for all headless CMS widgets.
 *
 * A widget encapsulates a self-contained data block (e.g. latest posts,
 * weather, newsletter form) that is returned as a raw PHP array.
 * The WidgetManager is responsible for JSON-encoding the result.
 *
 * Example implementation:
 *
 *   final class LatestPostsWidget implements WidgetInterface
 *   {
 *       public function getId(): string { return 'latest-posts'; }
 *
 *       public function execute(array $context = []): array
 *       {
 *           $limit = (int) ($context['limit'] ?? 5);
 *           return ['posts' => $this->repo->latest($limit)];
 *       }
 *   }
 */
interface WidgetInterface
{
    /**
     * Return the unique string identifier of this widget.
     * Used by WidgetManager to look up and dispatch the widget.
     */
    public function getId(): string;

    /**
     * Execute the widget logic and return its raw data payload.
     *
     * The returned array will be passed directly to json_encode() by
     * the caller — do NOT encode it here.
     *
     * @param  array<string, mixed> $context  Optional runtime parameters
     *                                         (e.g. current user, locale, filters).
     * @return array<string, mixed>            The widget's data payload.
     */
    public function execute(array $context = []): array;
}
