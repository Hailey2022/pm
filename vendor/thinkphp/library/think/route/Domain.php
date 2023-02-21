<?php
namespace think\route;
use think\Container;
use think\Loader;
use think\Request;
use think\Route;
use think\route\dispatch\Callback as CallbackDispatch;
use think\route\dispatch\Controller as ControllerDispatch;
use think\route\dispatch\Module as ModuleDispatch;
class Domain extends RuleGroup
{
    public function __construct(Route $router, $name = '', $rule = null, $option = [], $pattern = [])
    {
        $this->router  = $router;
        $this->domain  = $name;
        $this->option  = $option;
        $this->rule    = $rule;
        $this->pattern = $pattern;
    }
    public function check($request, $url, $completeMatch = false)
    {
        $result = $this->checkRouteAlias($request, $url);
        if (false !== $result) {
            return $result;
        }
        $result = $this->checkUrlBind($request, $url);
        if (!empty($this->option['append'])) {
            $request->setRouteVars($this->option['append']);
            unset($this->option['append']);
        }
        if (false !== $result) {
            return $result;
        }
        if (!empty($this->option['middleware'])) {
            Container::get('middleware')->import($this->option['middleware']);
            unset($this->option['middleware']);
        }
        return parent::check($request, $url, $completeMatch);
    }
    public function bind($bind)
    {
        $this->router->bind($bind, $this->domain);
        return $this;
    }
    private function checkRouteAlias($request, $url)
    {
        $alias = strpos($url, '|') ? strstr($url, '|', true) : $url;
        $item = $this->router->getAlias($alias);
        return $item ? $item->check($request, $url) : false;
    }
    private function checkUrlBind($request, $url)
    {
        $bind = $this->router->getBind($this->domain);
        if (!empty($bind)) {
            $this->parseBindAppendParam($bind);
            Container::get('app')->log('[ BIND ] ' . var_export($bind, true));
            $type = substr($bind, 0, 1);
            $bind = substr($bind, 1);
            $bindTo = [
                '\\' => 'bindToClass',
                '@'  => 'bindToController',
                ':'  => 'bindToNamespace',
            ];
            if (isset($bindTo[$type])) {
                return $this->{$bindTo[$type]}($request, $url, $bind);
            }
        }
        return false;
    }
    protected function parseBindAppendParam(&$bind)
    {
        if (false !== strpos($bind, '?')) {
            list($bind, $query) = explode('?', $bind);
            parse_str($query, $vars);
            $this->append($vars);
        }
    }
    protected function bindToClass($request, $url, $class)
    {
        $array  = explode('|', $url, 2);
        $action = !empty($array[0]) ? $array[0] : $this->router->config('default_action');
        $param  = [];
        if (!empty($array[1])) {
            $this->parseUrlParams($request, $array[1], $param);
        }
        return new CallbackDispatch($request, $this, [$class, $action], $param);
    }
    protected function bindToNamespace($request, $url, $namespace)
    {
        $array  = explode('|', $url, 3);
        $class  = !empty($array[0]) ? $array[0] : $this->router->config('default_controller');
        $method = !empty($array[1]) ? $array[1] : $this->router->config('default_action');
        $param  = [];
        if (!empty($array[2])) {
            $this->parseUrlParams($request, $array[2], $param);
        }
        return new CallbackDispatch($request, $this, [$namespace . '\\' . Loader::parseName($class, 1), $method], $param);
    }
    protected function bindToController($request, $url, $controller)
    {
        $array  = explode('|', $url, 2);
        $action = !empty($array[0]) ? $array[0] : $this->router->config('default_action');
        $param  = [];
        if (!empty($array[1])) {
            $this->parseUrlParams($request, $array[1], $param);
        }
        return new ControllerDispatch($request, $this, $controller . '/' . $action, $param);
    }
    protected function bindToModule($request, $url, $controller)
    {
        $array  = explode('|', $url, 2);
        $action = !empty($array[0]) ? $array[0] : $this->router->config('default_action');
        $param  = [];
        if (!empty($array[1])) {
            $this->parseUrlParams($request, $array[1], $param);
        }
        return new ModuleDispatch($request, $this, $controller . '/' . $action, $param);
    }
}
