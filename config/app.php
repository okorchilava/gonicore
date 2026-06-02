<?php

declare(strict_types=1);

use GoniCore\Core\Config\Env;

return [
    'name'  => Env::get('APP_NAME',  'GoniCore'),
    'env'   => Env::get('APP_ENV',   'production'),
    'debug' => Env::get('APP_DEBUG', 'false') === 'true',
    'url'   => Env::get('APP_URL',   ''),
];
