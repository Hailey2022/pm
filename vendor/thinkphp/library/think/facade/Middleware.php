<?php










namespace think\facade;

use think\Facade;


class Middleware extends Facade
{
    
    protected static function getFacadeClass()
    {
        return 'middleware';
    }
}
