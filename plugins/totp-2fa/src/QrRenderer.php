<?php

declare(strict_types=1);

namespace GoniTotp;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;

/**
 * Thin wrapper around chillerlan/php-qrcode for server-side PNG generation.
 * No CDN dependency — works fully offline.
 */
final class QrRenderer
{
    /**
     * Generate a QR code and return the raw PNG binary.
     *
     * @param string $data  The data to encode (e.g. an otpauth:// URL)
     * @param int    $scale Pixel size per module (default 5 → ~200 px for typical TOTP QR)
     */
    public static function png(string $data, int $scale = 5): string
    {
        $options                   = new QROptions;
        $options->outputType       = QRGdImagePNG::class;
        $options->scale            = $scale;
        $options->imageBase64      = false;
        $options->imageTransparent = false;

        return (string) (new QRCode($options))->render($data);
    }
}
