<?php
namespace think\facade;
use think\Facade;
class Cache extends Facade
{
    protected static function getFacadeClass()
    {
        return 'cache';
    }
}
