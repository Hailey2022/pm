<?php










namespace think;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use think\exception\ClassNotFoundException;


class Container implements ArrayAccess, IteratorAggregate, Countable
{
    
    protected static $instance;

    
    protected $instances = [];

    
    protected $bind = [
        'app'                   => App::class,
        'build'                 => Build::class,
        'cache'                 => Cache::class,
        'config'                => Config::class,
        'cookie'                => Cookie::class,
        'debug'                 => Debug::class,
        'env'                   => Env::class,
        'hook'                  => Hook::class,
        'lang'                  => Lang::class,
        'log'                   => Log::class,
        'middleware'            => Middleware::class,
        'request'               => Request::class,
        'response'              => Response::class,
        'route'                 => Route::class,
        'session'               => Session::class,
        'template'              => Template::class,
        'url'                   => Url::class,
        'validate'              => Validate::class,
        'view'                  => View::class,
        'rule_name'             => route\RuleName::class,
        
        'think\LoggerInterface' => Log::class,
    ];

    
    protected $name = [];

    
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    
    public static function setInstance($instance)
    {
        static::$instance = $instance;
    }

    
    public static function get($abstract, $vars = [], $newInstance = false)
    {
        return static::getInstance()->make($abstract, $vars, $newInstance);
    }

    
    public static function set($abstract, $concrete = null)
    {
        return static::getInstance()->bindTo($abstract, $concrete);
    }

    
    public static function remove($abstract)
    {
        return static::getInstance()->delete($abstract);
    }

    
    public static function clear()
    {
        return static::getInstance()->flush();
    }

    
    public function bindTo($abstract, $concrete = null)
    {
        if (is_array($abstract)) {
            $this->bind = array_merge($this->bind, $abstract);
        } elseif ($concrete instanceof Closure) {
            $this->bind[$abstract] = $concrete;
        } elseif (is_object($concrete)) {
            if (isset($this->bind[$abstract])) {
                $abstract = $this->bind[$abstract];
            }
            $this->instances[$abstract] = $concrete;
        } else {
            $this->bind[$abstract] = $concrete;
        }

        return $this;
    }

    
    public function instance($abstract, $instance)
    {
        if ($instance instanceof \Closure) {
            $this->bind[$abstract] = $instance;
        } else {
            if (isset($this->bind[$abstract])) {
                $abstract = $this->bind[$abstract];
            }

            $this->instances[$abstract] = $instance;
        }

        return $this;
    }

    
    public function bound($abstract)
    {
        return isset($this->bind[$abstract]) || isset($this->instances[$abstract]);
    }

    
    public function exists($abstract)
    {
        if (isset($this->bind[$abstract])) {
            $abstract = $this->bind[$abstract];
        }

        return isset($this->instances[$abstract]);
    }

    
    public function has($name)
    {
        return $this->bound($name);
    }

    
    public function make($abstract, $vars = [], $newInstance = false)
    {
        if (true === $vars) {
            
            $newInstance = true;
            $vars        = [];
        }

        $abstract = isset($this->name[$abstract]) ? $this->name[$abstract] : $abstract;

        if (isset($this->instances[$abstract]) && !$newInstance) {
            return $this->instances[$abstract];
        }

        if (isset($this->bind[$abstract])) {
            $concrete = $this->bind[$abstract];

            if ($concrete instanceof Closure) {
                $object = $this->invokeFunction($concrete, $vars);
            } else {
                $this->name[$abstract] = $concrete;
                return $this->make($concrete, $vars, $newInstance);
            }
        } else {
            $object = $this->invokeClass($abstract, $vars);
        }

        if (!$newInstance) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    
    public function delete($abstract)
    {
        foreach ((array) $abstract as $name) {
            $name = isset($this->name[$name]) ? $this->name[$name] : $name;

            if (isset($this->instances[$name])) {
                unset($this->instances[$name]);
            }
        }
    }

    
    public function all()
    {
        return $this->instances;
    }

    
    public function flush()
    {
        $this->instances = [];
        $this->bind      = [];
        $this->name      = [];
    }

    
    public function invokeFunction($function, $vars = [])
    {
        try {
            $reflect = new ReflectionFunction($function);

            $args = $this->bindParams($reflect, $vars);

            return call_user_func_array($function, $args);
        } catch (ReflectionException $e) {
            throw new Exception('function not exists: ' . $function . '()');
        }
    }

    
    public function invokeMethod($method, $vars = [])
    {
        try {
            if (is_array($method)) {
                $class   = is_object($method[0]) ? $method[0] : $this->invokeClass($method[0]);
                $reflect = new ReflectionMethod($class, $method[1]);
            } else {
                
                $reflect = new ReflectionMethod($method);
            }

            $args = $this->bindParams($reflect, $vars);

            return $reflect->invokeArgs(isset($class) ? $class : null, $args);
        } catch (ReflectionException $e) {
            if (is_array($method) && is_object($method[0])) {
                $method[0] = get_class($method[0]);
            }

            throw new Exception('method not exists: ' . (is_array($method) ? $method[0] . '::' . $method[1] : $method) . '()');
        }
    }

    
    public function invokeReflectMethod($instance, $reflect, $vars = [])
    {
        $args = $this->bindParams($reflect, $vars);

        return $reflect->invokeArgs($instance, $args);
    }

    
    public function invoke($callable, $vars = [])
    {
        if ($callable instanceof Closure) {
            return $this->invokeFunction($callable, $vars);
        }

        return $this->invokeMethod($callable, $vars);
    }

    
    public function invokeClass($class, $vars = [])
    {
        try {
            $reflect = new ReflectionClass($class);

            if ($reflect->hasMethod('__make')) {
                $method = new ReflectionMethod($class, '__make');

                if ($method->isPublic() && $method->isStatic()) {
                    $args = $this->bindParams($method, $vars);
                    return $method->invokeArgs(null, $args);
                }
            }

            $constructor = $reflect->getConstructor();

            $args = $constructor ? $this->bindParams($constructor, $vars) : [];

            return $reflect->newInstanceArgs($args);

        } catch (ReflectionException $e) {
            throw new ClassNotFoundException('class not exists: ' . $class, $class);
        }
    }

    
    protected function bindParams($reflect, $vars = [])
    {
        if ($reflect->getNumberOfParameters() == 0) {
            return [];
        }

        
        reset($vars);
        $type   = key($vars) === 0 ? 1 : 0;
        $params = $reflect->getParameters();

        if (PHP_VERSION > 8.0) {
            $args = $this->parseParamsForPHP8($params, $vars, $type);
        } else {
            $args = $this->parseParams($params, $vars, $type);
        }

        return $args;
    }

    
    protected function parseParams($params, $vars, $type)
    {
        foreach ($params as $param) {
            $name      = $param->getName();
            $lowerName = Loader::parseName($name);
            $class     = $param->getClass();

            if ($class) {
                $args[] = $this->getObjectParam($class->getName(), $vars);
            } elseif (1 == $type && !empty($vars)) {
                $args[] = array_shift($vars);
            } elseif (0 == $type && isset($vars[$name])) {
                $args[] = $vars[$name];
            } elseif (0 == $type && isset($vars[$lowerName])) {
                $args[] = $vars[$lowerName];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException('method param miss:' . $name);
            }
        }
        return $args;
    }

    
    protected function parseParamsForPHP8($params, $vars, $type)
    {
        foreach ($params as $param) {
            $name           = $param->getName();
            $lowerName      = Loader::parseName($name);
            $reflectionType = $param->getType();

            if ($reflectionType && $reflectionType->isBuiltin() === false) {
                $args[] = $this->getObjectParam($reflectionType->getName(), $vars);
            } elseif (1 == $type && !empty($vars)) {
                $args[] = array_shift($vars);
            } elseif (0 == $type && array_key_exists($name, $vars)) {
                $args[] = $vars[$name];
            } elseif (0 == $type && array_key_exists($lowerName, $vars)) {
                $args[] = $vars[$lowerName];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException('method param miss:' . $name);
            }
        }
        return $args;
    }

    
    protected function getObjectParam($className, &$vars)
    {
        $array = $vars;
        $value = array_shift($array);

        if ($value instanceof $className) {
            $result = $value;
            array_shift($vars);
        } else {
            $result = $this->make($className);
        }

        return $result;
    }

    public function __set($name, $value)
    {
        $this->bindTo($name, $value);
    }

    public function __get($name)
    {
        return $this->make($name);
    }

    public function __isset($name)
    {
        return $this->bound($name);
    }

    public function __unset($name)
    {
        $this->delete($name);
    }

    public function offsetExists($key)
    {
        return $this->__isset($key);
    }

    public function offsetGet($key)
    {
        return $this->__get($key);
    }

    public function offsetSet($key, $value)
    {
        $this->__set($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->__unset($key);
    }

    //Countable
    public function count()
    {
        return count($this->instances);
    }

    //IteratorAggregate
    public function getIterator()
    {
        return new ArrayIterator($this->instances);
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['instances'], $data['instance']);

        return $data;
    }
}
