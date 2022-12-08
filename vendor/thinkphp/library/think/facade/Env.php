<?php










namespace think\facade;

use think\Facade;


class Env extends Facade
{
    
    protected static function getFacadeClass()
    {
        return 'env';
    }
}
