<?php










namespace think\facade;

use think\Facade;


class App extends Facade
{
    
    protected static function getFacadeClass()
    {
        return 'app';
    }
}
