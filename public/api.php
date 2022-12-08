<?php


namespace think;




define('APP_DEBUG', true); //...............


define('CMF_ROOT', dirname(__DIR__) . '/');


define('CMF_DATA', CMF_ROOT . 'data/');


define('APP_PATH', CMF_ROOT . 'api/');


define('ROUTE_PATH', APP_PATH . 'route.php');


define('CONFIG_PATH', CMF_ROOT . 'data/config/');


define('APP_NAMESPACE', 'api');


define('WEB_ROOT', __DIR__ . '/');


require __DIR__ . '/../vendor/thinkphp/base.php';


Container::get('app', [APP_PATH])->run()->send();
