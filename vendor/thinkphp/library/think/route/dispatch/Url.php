<?php
namespace think\route\dispatch;
use think\exception\HttpException;
use think\Loader;
use think\route\Dispatch;
class Url extends Dispatch
{
    public function init()
    {
        $result = $this->parseUrl($this->dispatch);
        return (new Module($this->request, $this->rule, $result))->init();
    }
    public function exec()
    {}
    protected function parseUrl($url)
    {
        $depr = $this->rule->getConfig('pathinfo_depr');
        $bind = $this->rule->getRouter()->getBind();
        if (!empty($bind) && preg_match('/^[a-z]/is', $bind)) {
            $bind = str_replace('/', $depr, $bind);
            $url = $bind . ('.' != substr($bind, -1) ? $depr : '') . ltrim($url, $depr);
        }
        list($path, $var) = $this->rule->parseUrlPath($url);
        if (empty($path)) {
            return [null, null, null];
        }
        $module = $this->rule->getConfig('app_multi_module') ? array_shift($path) : null;
        if ($this->param['auto_search']) {
            $controller = $this->autoFindController($module, $path);
        } else {
            $controller = !empty($path) ? array_shift($path) : null;
        }
        if ($controller && !preg_match('/^[A-Za-z0-9][\w|\.]*$/', $controller)) {
            throw new HttpException(404, 'controller not exists:' . $controller);
        }
        $action = !empty($path) ? array_shift($path) : null;
        if ($path) {
            if ($this->rule->getConfig('url_param_type')) {
                $var += $path;
            } else {
                preg_replace_callback('/(\w+)\|([^\|]+)/', function ($match) use (&$var) {
                    $var[$match[1]] = strip_tags($match[2]);
                }, implode('|', $path));
            }
        }
        $panDomain = $this->request->panDomain();
        if ($panDomain && $key = array_search('*', $var)) {
            $var[$key] = $panDomain;
        }
        $this->request->setRouteVars($var);
        $route = [$module, $controller, $action];
        if ($this->hasDefinedRoute($route, $bind)) {
            throw new HttpException(404, 'invalid request:' . str_replace('|', $depr, $url));
        }
        return $route;
    }
    protected function hasDefinedRoute($route, $bind)
    {
        list($module, $controller, $action) = $route;
        $name = strtolower($module . '/' . Loader::parseName($controller, 1) . '/' . $action);
        $name2 = '';
        if (empty($module) || $module == $bind) {
            $name2 = strtolower(Loader::parseName($controller, 1) . '/' . $action);
        }
        $host = $this->request->host(true);
        $method = $this->request->method();
        if ($this->rule->getRouter()->getName($name, $host, $method) || $this->rule->getRouter()->getName($name2, $host, $method)) {
            return true;
        }
        return false;
    }
    protected function autoFindController($module, &$path)
    {
        $dir    = $this->app->getAppPath() . ($module ? $module . '/' : '') . $this->rule->getConfig('url_controller_layer');
        $suffix = $this->app->getSuffix() || $this->rule->getConfig('controller_suffix') ? ucfirst($this->rule->getConfig('url_controller_layer')) : '';
        $item = [];
        $find = false;
        foreach ($path as $val) {
            $item[] = $val;
            $file   = $dir . '/' . str_replace('.', '/', $val) . $suffix . '.php';
            $file   = pathinfo($file, PATHINFO_DIRNAME) . '/' . Loader::parseName(pathinfo($file, PATHINFO_FILENAME), 1) . '.php';
            if (is_file($file)) {
                $find = true;
                break;
            } else {
                $dir .= '/' . Loader::parseName($val);
            }
        }
        if ($find) {
            $controller = implode('.', $item);
            $path       = array_slice($path, count($item));
        } else {
            $controller = array_shift($path);
        }
        return $controller;
    }
}
