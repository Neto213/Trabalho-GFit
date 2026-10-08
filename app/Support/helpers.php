<?php

declare(strict_types=1);

use App\Support\Config;
use App\Support\Csrf;
use App\Support\Session;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        $baseUrl = rtrim(Config::get('app.url', 'http://localhost/GFit/public'), '/');
        
        if (!empty($_SERVER['HTTP_HOST'])) {
            $rawHost = $_SERVER['HTTP_HOST'];
            $hostOnly = explode(':', $rawHost)[0];
            $allowedHosts = Config::get('app.allowed_hosts', ['localhost', '127.0.0.1']);

            if (in_array(strtolower($hostOnly), array_map('strtolower', $allowedHosts), true)) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
                $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
                $fullBase = "{$scheme}://{$rawHost}{$base}";
                return $path === '' ? $fullBase : "{$fullBase}/{$path}";
            }
        }

        return $path === '' ? $baseUrl : "{$baseUrl}/{$path}";
    }
}


if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        return Session::getOld($key, $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $type): ?string
    {
        return Session::getFlash($type);
    }
}
