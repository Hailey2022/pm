<?php










namespace think\facade;

use think\Facade;


class Session extends Facade
{
    
    protected static function getFacadeClass()
    {
        return 'session';
    }
}
