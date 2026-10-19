<?php

/*
 * Router for PHP's built-in web server inside the BMPC desktop app.
 *
 * Serves files in public/ directly and sends everything else to Laravel.
 * The launcher passes the whole configuration as environment variables
 * (there is no .env file in the read-only app folder), so they are copied
 * into $_SERVER and $_ENV, where Laravel looks for them.
 */

foreach (getenv() as $key => $value) {
    $_ENV[$key] ??= $value;
    $_SERVER[$key] ??= $value;
}

$publicPath = getcwd();
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

if ($uri !== '/' && ! str_contains($uri, '..') && ! str_contains($uri, '/.') && is_file($publicPath.$uri)) {
    return false;
}

require $publicPath.'/index.php';
