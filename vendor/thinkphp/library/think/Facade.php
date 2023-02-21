<?php
namespace think;
class Facade
{
    protected static $bind = [];
    protected static $alwaysNewInstance;
    public static function bind($name, $class = null)
    {
        if (__CLASS__ != static::class) {
            return self::__callStatic('bind', func_get_args());
        }
        if (is_array($name)) {
            self::$bind = array_merge(self::$bind, $name);
        } else {
            self::$bind[$name] = $class;
        }
    }
    protected static function createFacade($class = '', $args = [], $newInstance = false)
    {
        $class = $class ?: static::class;
        $facadeClass = static::getFacadeClass();
        if ($facadeClass) {
            $class = $facadeClass;
        } elseif (isset(self::$bind[$class])) {
            $class = self::$bind[$class];
        }
        if (static::$alwaysNewInstance) {
            $newInstance = true;
        }
        return Container::getInstance()->make($class, $args, $newInstance);
    }
    protected static function getFacadeClass()
    {}
    public static function instance(...$args)
    {
        if (__CLASS__ != static::class) {
            return self::createFacade('', $args);
        }
    }
    public static function make($class, $args = [], $newInstance = false)
    {
        if (__CLASS__ != static::class) {
            return self::__callStatic('make', func_get_args());
        }
        if (true === $args) {
            $newInstance = true;
            $args        = [];
        }
        return self::createFacade($class, $args, $newInstance);
    }
    public static function __callStatic($method, $params)
    {
        return call_user_func_array([static::createFacade(), $method], $params);
    }
}
