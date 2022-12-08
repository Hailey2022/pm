<?php










//------------------------

//-------------------------

use think\Container;
use think\Db;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Cookie;
use think\facade\Debug;
use think\facade\Env;
use think\facade\Hook;
use think\facade\Lang;
use think\facade\Log;
use think\facade\Request;
use think\facade\Route;
use think\facade\Session;
use think\facade\Url;
use think\Response;
use think\route\RuleItem;

if (!function_exists('abort')) {
    
    function abort($code, $message = null, $header = [])
    {
        if ($code instanceof Response) {
            throw new HttpResponseException($code);
        } else {
            throw new HttpException($code, $message, null, $header);
        }
    }
}

if (!function_exists('action')) {
    
    function action($url, $vars = [], $layer = 'controller', $appendSuffix = false)
    {
        return app()->action($url, $vars, $layer, $appendSuffix);
    }
}

if (!function_exists('app')) {
    
    function app($name = 'think\App', $args = [], $newInstance = false)
    {
        return Container::get($name, $args, $newInstance);
    }
}

if (!function_exists('behavior')) {
    
    function behavior($behavior, $args = null)
    {
        return Hook::exec($behavior, $args);
    }
}

if (!function_exists('bind')) {
    
    function bind($abstract, $concrete = null)
    {
        return Container::getInstance()->bindTo($abstract, $concrete);
    }
}

if (!function_exists('cache')) {
    
    function cache($name, $value = '', $options = null, $tag = null)
    {
        if (is_array($options)) {
            
            Cache::connect($options);
        } elseif (is_array($name)) {
            
            return Cache::connect($name);
        }

        if ('' === $value) {
            
            return 0 === strpos($name, '?') ? Cache::has(substr($name, 1)) : Cache::get($name);
        } elseif (is_null($value)) {
            
            return Cache::rm($name);
        }

        
        if (is_array($options)) {
            $expire = isset($options['expire']) ? $options['expire'] : null; //修复查询缓存无法设置过期时间
        } else {
            $expire = is_numeric($options) ? $options : null; //默认快捷缓存设置过期时间
        }

        if (is_null($tag)) {
            return Cache::set($name, $value, $expire);
        } else {
            return Cache::tag($tag)->set($name, $value, $expire);
        }
    }
}

if (!function_exists('call')) {
    
    function call($callable, $args = [])
    {
        return Container::getInstance()->invoke($callable, $args);
    }
}

if (!function_exists('class_basename')) {
    
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('class_uses_recursive')) {
    
    function class_uses_recursive($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];
        $classes = array_merge([$class => $class], class_parents($class));
        foreach ($classes as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (!function_exists('config')) {
    
    function config($name = '', $value = null)
    {
        if (is_null($value) && is_string($name)) {
            if ('.' == substr($name, -1)) {
                return Config::pull(substr($name, 0, -1));
            }

            return 0 === strpos($name, '?') ? Config::has(substr($name, 1)) : Config::get($name);
        } else {
            return Config::set($name, $value);
        }
    }
}

if (!function_exists('container')) {
    
    function container()
    {
        return Container::getInstance();
    }
}

if (!function_exists('controller')) {
    
    function controller($name, $layer = 'controller', $appendSuffix = false)
    {
        return app()->controller($name, $layer, $appendSuffix);
    }
}

if (!function_exists('cookie')) {
    
    function cookie($name, $value = '', $option = null)
    {
        if (is_array($name)) {
            
            Cookie::init($name);
        } elseif (is_null($name)) {
            
            Cookie::clear($value);
        } elseif ('' === $value) {
            
            return 0 === strpos($name, '?') ? Cookie::has(substr($name, 1), $option) : Cookie::get($name);
        } elseif (is_null($value)) {
            
            return Cookie::delete($name);
        } else {
            
            return Cookie::set($name, $value, $option);
        }
    }
}

if (!function_exists('db')) {
    
    function db($name = '', $config = [], $force = true)
    {
        return Db::connect($config, $force)->name($name);
    }
}

if (!function_exists('debug')) {
    
    function debug($start, $end = '', $dec = 6)
    {
        if ('' == $end) {
            Debug::remark($start);
        } else {
            return 'm' == $dec ? Debug::getRangeMem($start, $end) : Debug::getRangeTime($start, $end, $dec);
        }
    }
}

if (!function_exists('download')) {
    
    function download($filename, $name = '', $content = false, $expire = 360, $openinBrowser = false)
    {
        return Response::create($filename, 'download')->name($name)->isContent($content)->expire($expire)->openinBrowser($openinBrowser);
    }
}

if (!function_exists('dump')) {
    
    function dump($var, $echo = true, $label = null)
    {
        return Debug::dump($var, $echo, $label);
    }
}

if (!function_exists('env')) {
    
    function env($name = null, $default = null)
    {
        return Env::get($name, $default);
    }
}

if (!function_exists('exception')) {
    
    function exception($msg, $code = 0, $exception = '')
    {
        $e = $exception ?: '\think\Exception';
        throw new $e($msg, $code);
    }
}

if (!function_exists('halt')) {
    
    function halt($var)
    {
        dump($var);

        throw new HttpResponseException(new Response);
    }
}

if (!function_exists('input')) {
    
    function input($key = '', $default = null, $filter = '')
    {
        if (0 === strpos($key, '?')) {
            $key = substr($key, 1);
            $has = true;
        }

        if ($pos = strpos($key, '.')) {
            
            $method = substr($key, 0, $pos);
            if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'route', 'param', 'request', 'session', 'cookie', 'server', 'env', 'path', 'file'])) {
                $key = substr($key, $pos + 1);
            } else {
                $method = 'param';
            }
        } else {
            
            $method = 'param';
        }

        if (isset($has)) {
            return request()->has($key, $method, $default);
        } else {
            return request()->$method($key, $default, $filter);
        }
    }
}

if (!function_exists('json')) {
    
    function json($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'json', $code, $header, $options);
    }
}

if (!function_exists('jsonp')) {
    
    function jsonp($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'jsonp', $code, $header, $options);
    }
}

if (!function_exists('lang')) {
    
    function lang($name, $vars = [], $lang = '')
    {
        return Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('model')) {
    
    function model($name = '', $layer = 'model', $appendSuffix = false)
    {
        return app()->model($name, $layer, $appendSuffix);
    }
}

if (!function_exists('parse_name')) {
    
    function parse_name($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);

            return $ucfirst ? ucfirst($name) : lcfirst($name);
        } else {
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
    }
}

if (!function_exists('redirect')) {
    
    function redirect($url = [], $params = [], $code = 302)
    {
        if (is_integer($params)) {
            $code   = $params;
            $params = [];
        }

        return Response::create($url, 'redirect', $code)->params($params);
    }
}

if (!function_exists('request')) {
    
    function request()
    {
        return app('request');
    }
}

if (!function_exists('response')) {
    
    function response($data = '', $code = 200, $header = [], $type = 'html')
    {
        return Response::create($data, $type, $code, $header);
    }
}

if (!function_exists('route')) {
    
    function route($rule, $route, $option = [], $pattern = [])
    {
        return Route::rule($rule, $route, '*', $option, $pattern);
    }
}

if (!function_exists('session')) {
    
    function session($name, $value = '', $prefix = null)
    {
        if (is_array($name)) {
            
            Session::init($name);
        } elseif (is_null($name)) {
            
            Session::clear($value);
        } elseif ('' === $value) {
            
            return 0 === strpos($name, '?') ? Session::has(substr($name, 1), $prefix) : Session::get($name, $prefix);
        } elseif (is_null($value)) {
            
            return Session::delete($name, $prefix);
        } else {
            
            return Session::set($name, $value, $prefix);
        }
    }
}

if (!function_exists('token')) {
    
    function token($name = '__token__', $type = 'md5')
    {
        $token = Request::token($name, $type);

        return '<input type="hidden" name="' . $name . '" value="' . $token . '" />';
    }
}

if (!function_exists('trace')) {
    
    function trace($log = '[think]', $level = 'log')
    {
        if ('[think]' === $log) {
            return Log::getLog();
        } else {
            Log::record($log, $level);
        }
    }
}

if (!function_exists('trait_uses_recursive')) {
    
    function trait_uses_recursive($trait)
    {
        $traits = class_uses($trait);
        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (!function_exists('url')) {
    
    function url($url = '', $vars = '', $suffix = true, $domain = false)
    {
        return Url::build($url, $vars, $suffix, $domain);
    }
}

if (!function_exists('validate')) {
    
    function validate($name = '', $layer = 'validate', $appendSuffix = false)
    {
        return app()->validate($name, $layer, $appendSuffix);
    }
}

if (!function_exists('view')) {
    
    function view($template = '', $vars = [], $code = 200, $filter = null)
    {
        return Response::create($template, 'view', $code)->assign($vars)->filter($filter);
    }
}

if (!function_exists('widget')) {
    
    function widget($name, $data = [])
    {
        $result = app()->action($name, $data, 'widget');

        if (is_object($result)) {
            $result = $result->getContent();
        }

        return $result;
    }
}

if (!function_exists('xml')) {
    
    function xml($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'xml', $code, $header, $options);
    }
}

if (!function_exists('yaconf')) {
    
    function yaconf($name, $default = null)
    {
        return Config::yaconf($name, $default);
    }
}
