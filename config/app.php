<?php

return [
    'name' => getenv('APP_NAME') ?: 'Gestão de Academia',
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => filter_var(getenv('APP_DEBUG') !== false ? getenv('APP_DEBUG') : false, FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim(getenv('APP_URL') ?: 'http://localhost/GFit/public', '/'),
    'allowed_hosts' => array_filter(array_map('trim', explode(',', getenv('APP_ALLOWED_HOSTS') ?: 'localhost,127.0.0.1'))),
];

