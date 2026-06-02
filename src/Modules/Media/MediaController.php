<?php

declare(strict_types=1);

namespace GoniCore\Modules\Media;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Http\HttpException;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

final class MediaController
{
    public function __construct(
        private readonly MediaService  $mediaService,
        private readonly QueryBuilder  $qb,
    ) {}

    /** POST /api/v1/media  — auth required, multipart/form-data, field: "file" */
    public function upload(Request $request): Response
    {
        $files  = $request->files();
        $userId = (int) $request->getAttribute('userId');

        if (!isset($files['file'])) {
            throw new HttpException(422, 'No file provided. Use the multipart field name "file".');
        }

        $data = $this->mediaService->store($files['file'], $userId);
        $id   = $this->qb->table('media')->insert($data);

        $media = $this->qb->table('media')->where('id', '=', $id)->first();

        return Response::json($media, 201);
    }

    /** GET /api/v1/media/{id} */
    public function show(Request $request): Response
    {
        $id    = (int) $request->getAttribute('id');
        $media = $this->qb->table('media')->where('id', '=', $id)->first();

        if ($media === null) {
            throw new HttpException(404, "Media #{$id} not found.");
        }

        return Response::json($media);
    }

    /** DELETE /api/v1/media/{id}  — auth required */
    public function destroy(Request $request): Response
    {
        $id    = (int) $request->getAttribute('id');
        $media = $this->qb->table('media')->where('id', '=', $id)->first();

        if ($media === null) {
            throw new HttpException(404, "Media #{$id} not found.");
        }

        $userId   = (int)    $request->getAttribute('userId');
        $userRole = (string) $request->getAttribute('userRole', 'viewer');

        if ((int) $media['uploaded_by'] !== $userId && $userRole !== 'admin') {
            throw new HttpException(403, 'You do not have permission to delete this file.');
        }

        $this->qb->table('media')->where('id', '=', $id)->delete();

        return Response::json(['message' => 'Media deleted.']);
    }
}
