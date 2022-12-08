<?php










namespace think\model\concern;

use think\Container;
use think\Loader;


trait ModelEvent
{
    
    private static $event = [];

    
    protected static $observe = ['before_write', 'after_write', 'before_insert', 'after_insert', 'before_update', 'after_update', 'before_delete', 'after_delete', 'before_restore', 'after_restore'];

    
    protected $observerClass;

    
    private $withEvent = true;

    
    public static function event($event, $callback, $override = false)
    {
        $class = static::class;

        if ($override) {
            self::$event[$class][$event] = [];
        }

        self::$event[$class][$event][] = $callback;
    }

    
    public static function flushEvent()
    {
        self::$event[static::class] = [];
    }

    
    public static function observe($class)
    {
        self::flushEvent();

        foreach (static::$observe as $event) {
            $eventFuncName = Loader::parseName($event, 1, false);

            if (method_exists($class, $eventFuncName)) {
                static::event($event, [$class, $eventFuncName]);
            }
        }
    }

    
    public function withEvent($event)
    {
        $this->withEvent = $event;
        return $this;
    }

    
    protected function trigger($event)
    {
        $class = static::class;

        if ($this->withEvent && isset(self::$event[$class][$event])) {
            foreach (self::$event[$class][$event] as $callback) {
                $result = Container::getInstance()->invoke($callback, [$this]);

                if (false === $result) {
                    return false;
                }
            }
        }

        return true;
    }

    
    protected static function beforeInsert($callback, $override = false)
    {
        self::event('before_insert', $callback, $override);
    }

    
    protected static function afterInsert($callback, $override = false)
    {
        self::event('after_insert', $callback, $override);
    }

    
    protected static function beforeUpdate($callback, $override = false)
    {
        self::event('before_update', $callback, $override);
    }

    
    protected static function afterUpdate($callback, $override = false)
    {
        self::event('after_update', $callback, $override);
    }

    
    protected static function beforeWrite($callback, $override = false)
    {
        self::event('before_write', $callback, $override);
    }

    
    protected static function afterWrite($callback, $override = false)
    {
        self::event('after_write', $callback, $override);
    }

    
    protected static function beforeDelete($callback, $override = false)
    {
        self::event('before_delete', $callback, $override);
    }

    
    protected static function afterDelete($callback, $override = false)
    {
        self::event('after_delete', $callback, $override);
    }

    
    protected static function beforeRestore($callback, $override = false)
    {
        self::event('before_restore', $callback, $override);
    }

    
    protected static function afterRestore($callback, $override = false)
    {
        self::event('after_restore', $callback, $override);
    }
}
