<?php
namespace think;
require __DIR__ . '/library/think/Loader.php';
Loader::register();
Error::register();
if (interface_exists('Psr\Log\LoggerInterface')) {
    interface LoggerInterface extends \Psr\Log\LoggerInterface
    {}
} else {
    interface LoggerInterface
    {}
}
Loader::addClassAlias([
    'App'      => facade\App::class,
    'Build'    => facade\Build::class,
    'Cache'    => facade\Cache::class,
    'Config'   => facade\Config::class,
    'Cookie'   => facade\Cookie::class,
    'Db'       => Db::class,
    'Debug'    => facade\Debug::class,
    'Env'      => facade\Env::class,
    'Facade'   => Facade::class,
    'Hook'     => facade\Hook::class,
    'Lang'     => facade\Lang::class,
    'Log'      => facade\Log::class,
    'Request'  => facade\Request::class,
    'Response' => facade\Response::class,
    'Route'    => facade\Route::class,
    'Session'  => facade\Session::class,
    'Url'      => facade\Url::class,
    'Validate' => facade\Validate::class,
    'View'     => facade\View::class,
]);
