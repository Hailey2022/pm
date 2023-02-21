<?php
namespace think\facade;
use think\Facade;
class Response extends Facade
{
    protected static function getFacadeClass()
    {
        return 'response';
    }
}
