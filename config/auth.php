<?php

declare(strict_types=1);

use GoniCore\Core\Config\Env;

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret
    |--------------------------------------------------------------------------
    | Must be at least 32 characters. Generate a strong value with:
    |   php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
    */
    'jwt_secret' => Env::get('JWT_SECRET', '304d5e91869ccd0a23b4697531b3dec469c4ce7240ca1ae8286f6ec23e282fe3'),

    /*
    |--------------------------------------------------------------------------
    | Token lifetime  (seconds)
    |--------------------------------------------------------------------------
    | Default: 3600 (1 hour). Set to 0 for non-expiring tokens (not recommended).
    */
    'jwt_ttl' => (int) Env::get('JWT_TTL', '3600'),

    /*
    |--------------------------------------------------------------------------
    | Media storage path
    |--------------------------------------------------------------------------
    */
    'media_storage' => Env::get('MEDIA_STORAGE_PATH', __DIR__ . '/../storage/media'),
];
