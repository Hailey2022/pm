<?php



if (function_exists('spl_autoload_register') && function_exists('spl_autoload_unregister')) {
    
    HTMLPurifier_Bootstrap::registerAutoload();
    if (function_exists('__autoload')) {
        
        spl_autoload_register('__autoload');
    }
} elseif (!function_exists('__autoload')) {
    require dirname(__FILE__) . '/HTMLPurifier.autoload-legacy.php';
}

if (ini_get('zend.ze1_compatibility_mode')) {
    trigger_error("HTML Purifier is not compatible with zend.ze1_compatibility_mode; please turn it off", E_USER_ERROR);
}


