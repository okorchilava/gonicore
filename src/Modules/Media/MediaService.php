<?php

declare(strict_types=1);

namespace GoniCore\Modules\Media;

use finfo;
use GoniCore\Core\Http\HttpException;
use RuntimeException;

/**
 * Handles file upload validation and storage.
 *
 * Files are stored under:
 *   {storageDir}/{YYYY}/{MM}/{randomhex}.{ext}
 *
 * MIME types are validated from the actual file bytes (via finfo),
 * NOT from the client-supplied Content-Type header.
 */
final class MediaService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'video/mp4',
        'audio/mpeg',
        'application/pdf',
        'text/plain',
    ];

    /** 20 MB */
    private const MAX_SIZE = 20 * 1024 * 1024;

    public function __construct(private readonly string $storageDir) {}

    /**
     * Validate and persist an uploaded file.
     *
     * @param  array<string, mixed> $file    A single element from $_FILES.
     * @param  int                  $userId  The uploader's user ID.
     * @return array<string, mixed>          Data ready for insertion into the `media` table.
     * @throws HttpException on any validation failure.
     * @throws RuntimeException on filesystem errors.
     */
    public function store(array $file, int $userId): array
    {
        $this->validateUpload($file);

        $originalName = basename((string) $file['name']);
        $size         = (int)    $file['size'];
        $tmpPath      = (string) $file['tmp_name'];

        // Re-read MIME type from the actual file content — never trust the client.
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpPath);

        if ($mimeType === false || !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new HttpException(422, "File type \"{$mimeType}\" is not permitted.");
        }

        $ext      = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . ($ext !== '' ? ".{$ext}" : '');
        $destDir  = rtrim($this->storageDir, '/') . '/' . date('Y/m');

        if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            throw new RuntimeException("Cannot create storage directory: {$destDir}");
        }

        $destPath = $destDir . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            throw new RuntimeException('Failed to move uploaded file to storage.');
        }

        // Store the path relative to storageDir so the base path can change.
        $relativePath = ltrim(str_replace(rtrim($this->storageDir, '/'), '', $destPath), '/');

        return [
            'filename'      => $filename,
            'original_name' => $originalName,
            'mime_type'     => $mimeType,
            'size'          => $size,
            'path'          => $relativePath,
            'uploaded_by'   => $userId,
        ];
    }

    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $file */
    private function validateUpload(array $file): void
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($error !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload_max_filesize.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Temporary upload directory missing.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension.',
            ];

            throw new HttpException(422, $messages[$error] ?? "Upload error code {$error}.");
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_SIZE) {
            $maxMb = self::MAX_SIZE / (1024 * 1024);
            throw new HttpException(422, "File size exceeds the {$maxMb} MB limit.");
        }

        if (empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
            throw new HttpException(422, 'Invalid file upload.');
        }
    }
}
