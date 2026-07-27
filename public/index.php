<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// This app is reachable both directly (familytree.local) and behind a
// reverse proxy that strips a /familytree URL prefix before forwarding
// (see httpd-vhosts.conf). Symfony's relative-URL generation derives the
// base path from SCRIPT_NAME, which the proxy has no way to set correctly
// on its own, so we restore it here from the X-Forwarded-Prefix header the
// proxy adds.
if (! empty($_SERVER['HTTP_X_FORWARDED_PREFIX'])) {
    $prefix = rtrim($_SERVER['HTTP_X_FORWARDED_PREFIX'], '/');
    $_SERVER['SCRIPT_NAME'] = $prefix.$_SERVER['SCRIPT_NAME'];
    if (isset($_SERVER['REQUEST_URI']) && ! str_starts_with($_SERVER['REQUEST_URI'], $prefix.'/')) {
        $_SERVER['REQUEST_URI'] = $prefix.$_SERVER['REQUEST_URI'];
    }
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
