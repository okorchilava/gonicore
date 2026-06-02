<?php

declare(strict_types=1);

namespace GoniCore\Core\Widgets;

use GoniCore\Shared\Contracts\WidgetInterface;

/**
 * Registry and executor for headless CMS widgets.
 *
 * Usage:
 *   $manager->register(new LatestPostsWidget($repo));
 *   $data = $manager->renderWidget('latest-posts', ['limit' => 3]);
 *   // $data is a raw array ready for json_encode().
 */
final class WidgetManager
{
    /** @var array<string, WidgetInterface> */
    private array $widgets = [];

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Register a widget. Overwrites any previously registered widget
     * with the same ID — useful for overriding core widgets in plugins.
     */
    public function register(WidgetInterface $widget): void
    {
        $this->widgets[$widget->getId()] = $widget;
    }

    // -------------------------------------------------------------------------
    // Execution
    // -------------------------------------------------------------------------

    /**
     * Execute a widget by ID and return its raw data array.
     *
     * @param  string               $id       The widget's unique identifier.
     * @param  array<string, mixed> $context  Runtime context passed to execute().
     * @return array<string, mixed>
     *
     * @throws WidgetNotFoundException  if no widget is registered for $id.
     */
    public function renderWidget(string $id, array $context = []): array
    {
        if (!isset($this->widgets[$id])) {
            throw new WidgetNotFoundException(
                "Widget \"{$id}\" is not registered. "
                . 'Call WidgetManager::register() before rendering.'
            );
        }

        return $this->widgets[$id]->execute($context);
    }

    // -------------------------------------------------------------------------
    // Introspection
    // -------------------------------------------------------------------------

    public function has(string $id): bool
    {
        return isset($this->widgets[$id]);
    }

    /**
     * @return list<string>  IDs of all registered widgets.
     */
    public function registeredIds(): array
    {
        return array_values(array_keys($this->widgets));
    }
}
