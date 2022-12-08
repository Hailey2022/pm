<?php









define('TEST_PATH', __DIR__ . '/');

require __DIR__ . '/../thinkphp/base.php';
\think\Loader::addNamespace('tests', TEST_PATH);
\think\Loader::addNamespace('think', __DIR__ . '/../src/');