<?php

declare(strict_types=1);

namespace GoniCore\Modules\Widget;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Http\HttpException;
use GoniCore\Core\Validation\Validator;
use GoniCore\Core\Widgets\WidgetManager;
use GoniCore\Core\Widgets\WidgetNotFoundException;

/**
 * REST controller for the widget system.
 *
 * Public headless route:
 *   GET  /api/v1/widgets/render/{id}      Execute a registered WidgetInterface by ID → JSON payload
 *
 * Admin CRUD routes (auth required):
 *   GET    /api/v1/widgets                List all DB widgets
 *   GET    /api/v1/widgets/area/{area}    Widgets in a specific area
 *   POST   /api/v1/widgets               Create a widget
 *   PATCH  /api/v1/widgets/{id}          Update title/settings
 *   DELETE /api/v1/widgets/{id}          Delete a widget
 *   POST   /api/v1/widgets/reorder       Reorder widgets in an area
 *   POST   /api/v1/widgets/{id}/toggle   Toggle active state
 */
final class WidgetController
{
    public function __construct(
        private readonly WidgetService  $service,
        private readonly WidgetRepository $repo,
        private readonly WidgetManager  $manager,
        private readonly Validator      $validator,
    ) {}

    // -------------------------------------------------------------------------
    // Headless — execute a core WidgetInterface
    // -------------------------------------------------------------------------

    /**
     * GET /api/v1/widgets/render/{id}
     *
     * Execute a WidgetInterface registered in the core WidgetManager and return
     * its raw data payload as JSON. Query-string parameters are forwarded as context.
     *
     * Example:  GET /api/v1/widgets/render/latest-posts?limit=3
     */
    public function render(Request $request): Response
    {
        $id      = (string) $request->getAttribute('id');
        $context = $request->queryAll();          // forward all query params as context

        try {
            $payload = $this->manager->renderWidget($id, $context);
        } catch (WidgetNotFoundException $e) {
            throw new HttpException(404, $e->getMessage());
        }

        return Response::json([
            'widget'  => $id,
            'payload' => $payload,
        ]);
    }

    // -------------------------------------------------------------------------
    // Admin — list
    // -------------------------------------------------------------------------

    /** GET /api/v1/widgets */
    public function index(Request $request): Response
    {
        return Response::json([
            'data'  => $this->repo->all(),
            'areas' => $this->service->areas(),
            'types' => $this->service->types(),
        ]);
    }

    /** GET /api/v1/widgets/area/{area} */
    public function area(Request $request): Response
    {
        $area = (string) $request->getAttribute('area');
        return Response::json(['data' => $this->repo->forArea($area)]);
    }

    // -------------------------------------------------------------------------
    // Admin — CRUD
    // -------------------------------------------------------------------------

    /** POST /api/v1/widgets */
    public function store(Request $request): Response
    {
        $data = $request->json();

        $this->validator->validate($data, [
            'area'     => 'required|string|max:100',
            'type'     => 'required|string|max:100',
            'title'    => 'nullable|string|max:255',
            'settings' => 'nullable|array',
        ]);

        $type = (string) $data['type'];
        if ($this->service->findType($type) === null) {
            throw new HttpException(422, "Unknown widget type \"{$type}\".");
        }

        $id = $this->repo->create(
            area:     (string) $data['area'],
            type:     $type,
            title:    isset($data['title']) ? (string) $data['title'] : null,
            settings: (array)  ($data['settings'] ?? []),
        );

        return Response::json(['id' => $id], 201);
    }

    /** PATCH /api/v1/widgets/{id} */
    public function update(Request $request): Response
    {
        $id   = (int) $request->getAttribute('id');
        $data = $request->json();

        $this->validator->validate($data, [
            'title'    => 'nullable|string|max:255',
            'settings' => 'nullable|array',
        ]);

        if ($this->repo->findById($id) === null) {
            throw new HttpException(404, "Widget #{$id} not found.");
        }

        $this->repo->update(
            id:       $id,
            title:    isset($data['title']) ? (string) $data['title'] : null,
            settings: (array) ($data['settings'] ?? []),
        );

        return Response::json(['message' => 'Widget updated.']);
    }

    /** DELETE /api/v1/widgets/{id} */
    public function destroy(Request $request): Response
    {
        $id = (int) $request->getAttribute('id');

        if ($this->repo->findById($id) === null) {
            throw new HttpException(404, "Widget #{$id} not found.");
        }

        $this->repo->delete($id);
        return Response::json(['message' => 'Widget deleted.']);
    }

    /** POST /api/v1/widgets/{id}/toggle */
    public function toggle(Request $request): Response
    {
        $id = (int) $request->getAttribute('id');

        if ($this->repo->findById($id) === null) {
            throw new HttpException(404, "Widget #{$id} not found.");
        }

        $this->repo->toggle($id);
        return Response::json(['message' => 'Widget toggled.']);
    }

    /** POST /api/v1/widgets/reorder */
    public function reorder(Request $request): Response
    {
        $data = $request->json();

        $this->validator->validate($data, [
            'ids' => 'required|array',
        ]);

        $this->repo->reorder((array) $data['ids']);
        return Response::json(['message' => 'Widgets reordered.']);
    }
}
