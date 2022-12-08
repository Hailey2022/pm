<?php










namespace think;

use think\console\Output as ConsoleOutput;
use think\exception\ErrorException;
use think\exception\Handle;
use think\exception\ThrowableError;

class Error
{
    
    protected static $exceptionHandler;

    
    public static function register()
    {
        error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'appError']);
        set_exception_handler([__CLASS__, 'appException']);
        register_shutdown_function([__CLASS__, 'appShutdown']);
    }

    
    public static function appException($e)
    {
        if (!$e instanceof \Exception) {
            $e = new ThrowableError($e);
        }

        self::getExceptionHandler()->report($e);

        if (PHP_SAPI == 'cli') {
            self::getExceptionHandler()->renderForConsole(new ConsoleOutput, $e);
        } else {
            self::getExceptionHandler()->render($e)->send();
        }
    }

    
    public static function appError($errno, $errstr, $errfile = '', $errline = 0)
    {
        $exception = new ErrorException($errno, $errstr, $errfile, $errline);
        if (error_reporting() & $errno) {
            
            throw $exception;
        }

        self::getExceptionHandler()->report($exception);
    }

    
    public static function appShutdown()
    {
        if (!is_null($error = error_get_last()) && self::isFatal($error['type'])) {
            
            $exception = new ErrorException($error['type'], $error['message'], $error['file'], $error['line']);

            self::appException($exception);
        }

        
        Container::get('log')->save();
    }

    
    protected static function isFatal($type)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

    
    public static function setExceptionHandler($handle)
    {
        self::$exceptionHandler = $handle;
    }

    
    public static function getExceptionHandler()
    {
        static $handle;

        if (!$handle) {
            
            $class = self::$exceptionHandler;

            if ($class && is_string($class) && class_exists($class) && is_subclass_of($class, "\\think\\exception\\Handle")) {
                $handle = new $class;
            } else {
                $handle = new Handle;
                if ($class instanceof \Closure) {
                    $handle->setRender($class);
                }
            }
        }

        return $handle;
    }
}
