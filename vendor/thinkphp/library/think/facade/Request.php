<?php










namespace think\facade;

use think\Facade;


class Request extends Facade
{
    
    protected static function getFacadeClass()
    {
        return 'request';
    }
}
