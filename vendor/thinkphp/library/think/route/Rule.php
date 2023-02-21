<?php
namespace think\route;
use think\Container;
use think\Request;
use think\Response;
use think\route\dispatch\Callback as CallbackDispatch;
use think\route\dispatch\Controller as ControllerDispatch;
use think\route\dispatch\Module as ModuleDispatch;
use think\route\dispatch\Redirect as RedirectDispatch;
use think\route\dispatch\Response as ResponseDispatch;
use think\route\dispatch\View as ViewDispatch;
abstract class Rule
{
    protected $name;
    protected $router;
    protected $parent;
    protected $rule;
    protected $route;
    protected $method;
    protected $vars = [];
    protected $option = [];
    protected $pattern = [];
    protected $mergeOptions = ['after', 'model', 'header', 'response', 'append', 'middleware'];
    protected $doAfter;
    protected $lockOption = false;
    abstract public function check($request, $url, $completeMatch = false);
    public function getName()
    {
        return $this->name;
    }
    public function getRule()
    {
        return $this->rule;
    }
    public function getRoute()
    {
        return $this->route;
    }
    public function getMethod()
    {
        return strtolower($this->method);
    }
    public function getVars()
    {
        return $this->vars;
    }
    public function getRouter()
    {
        return $this->router;
    }
    public function doAfter()
    {
        return $this->doAfter;
    }
    public function getParent()
    {
        return $this->parent;
    }
    public function getDomain()
    {
        return $this->parent->getDomain();
    }
    public function getPattern($name = '')
    {
        if ('' === $name) {
            return $this->pattern;
        }
        return isset($this->pattern[$name]) ? $this->pattern[$name] : null;
    }
    public function getConfig($name = '')
    {
        return $this->router->config($name);
    }
    public function getOption($name = '')
    {
        if ('' === $name) {
            return $this->option;
        }
        return isset($this->option[$name]) ? $this->option[$name] : null;
    }
    public function option($name, $value = '')
    {
        if (is_array($name)) {
            $this->option = array_merge($this->option, $name);
        } else {
            $this->option[$name] = $value;
        }
        return $this;
    }
    public function pattern($name, $rule = '')
    {
        if (is_array($name)) {
            $this->pattern = array_merge($this->pattern, $name);
        } else {
            $this->pattern[$name] = $rule;
        }
        return $this;
    }
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }
    public function vars($vars)
    {
        $this->vars = $vars;
        return $this;
    }
    public function method($method)
    {
        return $this->option('method', strtolower($method));
    }
    public function before($before)
    {
        return $this->option('before', $before);
    }
    public function after($after)
    {
        return $this->option('after', $after);
    }
    public function ext($ext = '')
    {
        return $this->option('ext', $ext);
    }
    public function denyExt($ext = '')
    {
        return $this->option('deny_ext', $ext);
    }
    public function domain($domain)
    {
        return $this->option('domain', $domain);
    }
    public function filter($name, $value = null)
    {
        if (is_array($name)) {
            $this->option['filter'] = $name;
        } else {
            $this->option['filter'][$name] = $value;
        }
        return $this;
    }
    public function model($var, $model = null, $exception = true)
    {
        if ($var instanceof \Closure) {
            $this->option['model'][] = $var;
        } elseif (is_array($var)) {
            $this->option['model'] = $var;
        } elseif (is_null($model)) {
            $this->option['model']['id'] = [$var, true];
        } else {
            $this->option['model'][$var] = [$model, $exception];
        }
        return $this;
    }
    public function append(array $append = [])
    {
        if (isset($this->option['append'])) {
            $this->option['append'] = array_merge($this->option['append'], $append);
        } else {
            $this->option['append'] = $append;
        }
        return $this;
    }
    public function validate($validate, $scene = null, $message = [], $batch = false)
    {
        $this->option['validate'] = [$validate, $scene, $message, $batch];
        return $this;
    }
    public function response($response)
    {
        $this->option['response'][] = $response;
        return $this;
    }
    public function header($header, $value = null)
    {
        if (is_array($header)) {
            $this->option['header'] = $header;
        } else {
            $this->option['header'][$header] = $value;
        }
        return $this;
    }
    public function middleware($middleware, $param = null)
    {
        if (is_null($param) && is_array($middleware)) {
            $this->option['middleware'] = $middleware;
        } else {
            foreach ((array) $middleware as $item) {
                $this->option['middleware'][] = [$item, $param];
            }
        }
        return $this;
    }
    public function cache($cache)
    {
        return $this->option('cache', $cache);
    }
    public function depr($depr)
    {
        return $this->option('param_depr', $depr);
    }
    public function mergeExtraVars($merge = true)
    {
        return $this->option('merge_extra_vars', $merge);
    }
    public function mergeOptions($option = [])
    {
        $this->mergeOptions = array_merge($this->mergeOptions, $option);
        return $this;
    }
    public function https($https = true)
    {
        return $this->option('https', $https);
    }
    public function ajax($ajax = true)
    {
        return $this->option('ajax', $ajax);
    }
    public function pjax($pjax = true)
    {
        return $this->option('pjax', $pjax);
    }
    public function mobile($mobile = true)
    {
        return $this->option('mobile', $mobile);
    }
    public function view($view = true)
    {
        return $this->option('view', $view);
    }
    public function redirect($redirect = true)
    {
        return $this->option('redirect', $redirect);
    }
    public function completeMatch($match = true)
    {
        return $this->option('complete_match', $match);
    }
    public function removeSlash($remove = true)
    {
        return $this->option('remove_slash', $remove);
    }
    public function allowCrossDomain($allow = true, $header = [])
    {
        if (!empty($header)) {
            $this->header($header);
        }
        if ($allow && $this->parent) {
            $this->parent->addRuleItem($this, 'options');
        }
        return $this->option('cross_domain', $allow);
    }
    protected function checkCrossDomain($request)
    {
        if (!empty($this->option['cross_domain'])) {
            $header = [
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE',
                'Access-Control-Allow-Headers'     => 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With',
            ];
            if (!empty($this->option['header'])) {
                $header = array_merge($header, $this->option['header']);
            }
            if (!isset($header['Access-Control-Allow-Origin'])) {
                $httpOrigin = $request->header('origin');
                if ($httpOrigin && strpos(config('cookie.domain'), $httpOrigin)) {
                    $header['Access-Control-Allow-Origin'] = $httpOrigin;
                } else {
                    $header['Access-Control-Allow-Origin'] = '*';
                }
            }
            $this->option['header'] = $header;
            if ($request->method(true) == 'OPTIONS') {
                return new ResponseDispatch($request, $this, Response::create()->code(204)->header($header));
            }
        }
    }
    public function crossDomainRule()
    {
        if ($this instanceof RuleGroup) {
            $method = '*';
        } else {
            $method = $this->method;
        }
        $this->router->setCrossDomainRule($this, $method);
        return $this;
    }
    public function mergeGroupOptions()
    {
        if (!$this->lockOption) {
            $parentOption = $this->parent->getOption();
            foreach ($this->mergeOptions as $item) {
                if (isset($parentOption[$item]) && isset($this->option[$item])) {
                    $this->option[$item] = array_merge($parentOption[$item], $this->option[$item]);
                }
            }
            $this->option     = array_merge($parentOption, $this->option);
            $this->lockOption = true;
        }
        return $this->option;
    }
    public function parseRule($request, $rule, $route, $url, $option = [], $matches = [])
    {
        if (is_string($route) && isset($option['prefix'])) {
            $route = $option['prefix'] . $route;
        }
        if (is_string($route) && !empty($matches)) {
            $search = $replace = [];
            foreach ($matches as $key => $value) {
                $search[]  = '<' . $key . '>';
                $replace[] = $value;
                $search[]  = ':' . $key;
                $replace[] = $value;
            }
            $route = str_replace($search, $replace, $route);
        }
        $count = substr_count($rule, '/');
        $url   = array_slice(explode('|', $url), $count + 1);
        $this->parseUrlParams($request, implode('|', $url), $matches);
        $this->vars    = $matches;
        $this->option  = $option;
        $this->doAfter = true;
        return $this->dispatch($request, $route, $option);
    }
    protected function checkBefore($before)
    {
        $hook = Container::get('hook');
        foreach ((array) $before as $behavior) {
            $result = $hook->exec($behavior);
            if (false === $result) {
                return false;
            }
        }
    }
    protected function dispatch($request, $route, $option)
    {
        if ($route instanceof \Closure) {
            $result = new CallbackDispatch($request, $this, $route);
        } elseif ($route instanceof Response) {
            $result = new ResponseDispatch($request, $this, $route);
        } elseif (isset($option['view']) && false !== $option['view']) {
            $result = new ViewDispatch($request, $this, $route, is_array($option['view']) ? $option['view'] : []);
        } elseif (!empty($option['redirect']) || 0 === strpos($route, '/') || strpos($route, '://')) {
            $result = new RedirectDispatch($request, $this, $route, [], isset($option['status']) ? $option['status'] : 301);
        } elseif (false !== strpos($route, '\\')) {
            $result = $this->dispatchMethod($request, $route);
        } elseif (0 === strpos($route, '@')) {
            $result = $this->dispatchController($request, substr($route, 1));
        } else {
            $result = $this->dispatchModule($request, $route);
        }
        return $result;
    }
    protected function dispatchMethod($request, $route)
    {
        list($path, $var) = $this->parseUrlPath($route);
        $route  = str_replace('/', '@', implode('/', $path));
        $method = strpos($route, '@') ? explode('@', $route) : $route;
        return new CallbackDispatch($request, $this, $method, $var);
    }
    protected function dispatchController($request, $route)
    {
        list($route, $var) = $this->parseUrlPath($route);
        $result = new ControllerDispatch($request, $this, implode('/', $route), $var);
        $request->setAction(array_pop($route));
        $request->setController($route ? array_pop($route) : $this->getConfig('default_controller'));
        $request->setModule($route ? array_pop($route) : $this->getConfig('default_module'));
        return $result;
    }
    protected function dispatchModule($request, $route)
    {
        list($path, $var) = $this->parseUrlPath($route);
        $action     = array_pop($path);
        $controller = !empty($path) ? array_pop($path) : null;
        $module     = $this->getConfig('app_multi_module') && !empty($path) ? array_pop($path) : null;
        $method     = $request->method();
        if ($this->getConfig('use_action_prefix') && $this->router->getMethodPrefix($method)) {
            $prefix = $this->router->getMethodPrefix($method);
            $action = 0 !== strpos($action, $prefix) ? $prefix . $action : $action;
        }
        $request->setRouteVars($var);
        return new ModuleDispatch($request, $this, [$module, $controller, $action], ['convert' => false]);
    }
    protected function checkOption($option, Request $request)
    {
        if (!empty($option['method'])) {
            if (is_string($option['method']) && false === stripos($option['method'], $request->method())) {
                return false;
            }
        }
        foreach (['ajax', 'pjax', 'mobile'] as $item) {
            if (isset($option[$item])) {
                $call = 'is' . $item;
                if ($option[$item] && !$request->$call() || !$option[$item] && $request->$call()) {
                    return false;
                }
            }
        }
        if ($request->url() != '/' && ((isset($option['ext']) && false === stripos('|' . $option['ext'] . '|', '|' . $request->ext() . '|'))
            || (isset($option['deny_ext']) && false !== stripos('|' . $option['deny_ext'] . '|', '|' . $request->ext() . '|')))) {
            return false;
        }
        if ((isset($option['domain']) && !in_array($option['domain'], [$request->host(true), $request->subDomain()]))) {
            return false;
        }
        if ((isset($option['https']) && $option['https'] && !$request->isSsl())
            || (isset($option['https']) && !$option['https'] && $request->isSsl())) {
            return false;
        }
        if (isset($option['filter'])) {
            foreach ($option['filter'] as $name => $value) {
                if ($request->param($name, '', null) != $value) {
                    return false;
                }
            }
        }
        return true;
    }
    protected function parseUrlParams($request, $url, &$var = [])
    {
        if ($url) {
            if ($this->getConfig('url_param_type')) {
                $var += explode('|', $url);
            } else {
                preg_replace_callback('/(\w+)\|([^\|]+)/', function ($match) use (&$var) {
                    $var[$match[1]] = strip_tags($match[2]);
                }, $url);
            }
        }
    }
    public function parseUrlPath($url)
    {
        $url = str_replace('|', '/', $url);
        $url = trim($url, '/');
        $var = [];
        if (false !== strpos($url, '?')) {
            $info = parse_url($url);
            $path = explode('/', $info['path']);
            parse_str($info['query'], $var);
        } elseif (strpos($url, '/')) {
            $path = explode('/', $url);
        } elseif (false !== strpos($url, '=')) {
            $path = [];
            parse_str($url, $var);
        } else {
            $path = [$url];
        }
        return [$path, $var];
    }
    protected function buildRuleRegex($rule, $match, $pattern = [], $option = [], $completeMatch = false, $suffix = '')
    {
        foreach ($match as $name) {
            $replace[] = $this->buildNameRegex($name, $pattern, $suffix);
        }
        if ('/' != $rule) {
            if (!empty($option['remove_slash'])) {
                $rule = rtrim($rule, '/');
            } elseif (substr($rule, -1) == '/') {
                $rule     = rtrim($rule, '/');
                $hasSlash = true;
            }
        }
        $regex = str_replace(array_unique($match), array_unique($replace), $rule);
        $regex = str_replace([')?/', ')/', ')?-', ')-', '\\\\/'], [')\/', ')\/', ')\-', ')\-', '\/'], $regex);
        if (isset($hasSlash)) {
            $regex .= '\/';
        }
        return $regex . ($completeMatch ? '$' : '');
    }
    protected function buildNameRegex($name, $pattern, $suffix)
    {
        $optional = '';
        $slash    = substr($name, 0, 1);
        if (in_array($slash, ['/', '-'])) {
            $prefix = '\\' . $slash;
            $name   = substr($name, 1);
            $slash  = substr($name, 0, 1);
        } else {
            $prefix = '';
        }
        if ('<' != $slash) {
            return $prefix . preg_quote($name, '/');
        }
        if (strpos($name, '?')) {
            $name     = substr($name, 1, -2);
            $optional = '?';
        } elseif (strpos($name, '>')) {
            $name = substr($name, 1, -1);
        }
        if (isset($pattern[$name])) {
            $nameRule = $pattern[$name];
            if (0 === strpos($nameRule, '/') && '/' == substr($nameRule, -1)) {
                $nameRule = substr($nameRule, 1, -1);
            }
        } else {
            $nameRule = $this->getConfig('default_route_pattern');
        }
        return '(' . $prefix . '(?<' . $name . $suffix . '>' . $nameRule . '))' . $optional;
    }
    protected function parseVar($rule)
    {
        $var = [];
        if (preg_match_all('/<\w+\??>/', $rule, $matches)) {
            foreach ($matches[0] as $name) {
                $optional = false;
                if (strpos($name, '?')) {
                    $name     = substr($name, 1, -2);
                    $optional = true;
                } else {
                    $name = substr($name, 1, -1);
                }
                $var[$name] = $optional ? 2 : 1;
            }
        }
        return $var;
    }
    public function __call($method, $args)
    {
        if (count($args) > 1) {
            $args[0] = $args;
        }
        array_unshift($args, $method);
        return call_user_func_array([$this, 'option'], $args);
    }
    public function __sleep()
    {
        return ['name', 'rule', 'route', 'method', 'vars', 'option', 'pattern', 'doAfter'];
    }
    public function __wakeup()
    {
        $this->router = Container::get('route');
    }
    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['parent'], $data['router'], $data['route']);
        return $data;
    }
}
