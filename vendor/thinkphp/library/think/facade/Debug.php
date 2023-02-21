<?php
namespace think\facade;
use think\Facade;
class Debug extends Facade
{
    protected static function getFacadeClass()
    {
        return 'debug';
    }
}
