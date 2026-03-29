<?php
function env($key, $default = null) {
    static $env = null;

    if ($env === null) {
        $path = __DIR__ . '/.env';
        if (!file_exists($path)) {
            throw new Exception(".env file not found at $path");
        }
        $env = parse_ini_file($path);
    }

    return $env[$key] ?? $default;
}
