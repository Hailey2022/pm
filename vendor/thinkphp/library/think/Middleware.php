<?php










namespace think;

use InvalidArgumentException;
use LogicException;
use think\exception\HttpResponseException;

class Middleware
{
    protected $queue = [];
    protected $app;
    protected $config = [
        'default_namespace' => 'app\\http\\middleware\\',
    ];

    public function __construct(App $app, array $config = [])
    {
        $this->app    = $app;
        $this->config = array_merge($this->config, $config);
    }

    public static function __make(App $app, Config $config)
    {
        return new static($app, $config->pull('middleware'));
    }

    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    
    public function import(array $middlewares = [], $type = 'route')
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware, $type);
        }
    }

    
    public function add($middleware, $type = 'route')
    {
        if (is_null($middleware)) {
            return;
        }

        $middleware = $this->buildMiddleware($middleware, $type);

        if ($middleware) {
            $this->queue[$type][] = $middleware;
        }
    }

    
    public function controller($middleware)
    {
        return $this->add($middleware, 'controller');
    }

    
    public function unshift($middleware, $type = 'route')
    {
        if (is_null($middleware)) {
            return;
        }

        $middleware = $this->buildMiddleware($middleware, $type);

        if ($middleware) {
            array_unshift($this->queue[$type], $middleware);
        }
    }

    
    public function all($type = 'route')
    {
        return $this->queue[$type] ?: [];
    }

    
    public function clear()
    {
        $this->queue = [];
    }

    
    public function dispatch(Request $request, $type = 'route')
    {
        return call_user_func($this->resolve($type), $request);
    }

    
    protected function buildMiddleware($middleware, $type = 'route')
    {
        if (is_array($middleware)) {
            list($middleware, $param) = $middleware;
        }

        if ($middleware instanceof \Closure) {
            return [$middleware, isset($param) ? $param : null];
        }

        if (!is_string($middleware)) {
            throw new InvalidArgumentException('The middleware is invalid');
        }

        if (false === strpos($middleware, '\\')) {
            if (isset($this->config[$middleware])) {
                $middleware = $this->config[$middleware];
            } else {
                $middleware = $this->config['default_namespace'] . $middleware;
            }
        }

        if (is_array($middleware)) {
            return $this->import($middleware, $type);
        }

        if (strpos($middleware, ':')) {
            list($middleware, $param) = explode(':', $middleware, 2);
        }

        return [[$this->app->make($middleware), 'handle'], isset($param) ? $param : null];
    }

    protected function resolve($type = 'route')
    {
        return function (Request $request) use ($type) {

            $middleware = array_shift($this->queue[$type]);

            if (null === $middleware) {
                throw new InvalidArgumentException('The queue was exhausted, with no response returned');
            }

            list($call, $param) = $middleware;

            try {
                $response = call_user_func_array($call, [$request, $this->resolve($type), $param]);
            } catch (HttpResponseException $exception) {
                $response = $exception->getResponse();
            }

            if (!$response instanceof Response) {
                throw new LogicException('The middleware must return Response instance');
            }

            return $response;
        };
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['app']);

        return $data;
    }
}
