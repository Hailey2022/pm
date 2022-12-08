<?php










namespace think\route\dispatch;

use ReflectionMethod;
use think\exception\ClassNotFoundException;
use think\exception\HttpException;
use think\Loader;
use think\Request;
use think\route\Dispatch;

class Module extends Dispatch
{
    protected $controller;
    protected $actionName;

    public function init()
    {
        parent::init();

        $result = $this->dispatch;

        if (is_string($result)) {
            $result = explode('/', $result);
        }

        if ($this->rule->getConfig('app_multi_module')) {
            
            $module    = strip_tags(strtolower($result[0] ?: $this->rule->getConfig('default_module')));
            $bind      = $this->rule->getRouter()->getBind();
            $available = false;

            if ($bind && preg_match('/^[a-z]/is', $bind)) {
                
                list($bindModule) = explode('/', $bind);
                if (empty($result[0])) {
                    $module = $bindModule;
                }
                $available = true;
            } elseif (!in_array($module, $this->rule->getConfig('deny_module_list'))) {
                $available = true;
            } elseif ($this->rule->getConfig('empty_module')) {
                $module    = $this->rule->getConfig('empty_module');
                $available = true;
            }

            
            if ($module && $available) {
                
                $this->request->setModule($module);
                $this->app->init($module);
            } else {
                throw new HttpException(404, 'module not exists:' . $module);
            }
        }

        
        $convert = is_bool($this->convert) ? $this->convert : $this->rule->getConfig('url_convert');
        
        $controller = strip_tags($result[1] ?: $this->rule->getConfig('default_controller'));

        $this->controller = $convert ? strtolower($controller) : $controller;

        
        $this->actionName = strip_tags($result[2] ?: $this->rule->getConfig('default_action'));

        
        $this->request
            ->setController(Loader::parseName($this->controller, 1))
            ->setAction($this->actionName);

        return $this;
    }

    public function exec()
    {
        
        $this->app['hook']->listen('module_init');

        try {
            
            $instance = $this->app->controller($this->controller,
                $this->rule->getConfig('url_controller_layer'),
                $this->rule->getConfig('controller_suffix'),
                $this->rule->getConfig('empty_controller'));
        } catch (ClassNotFoundException $e) {
            throw new HttpException(404, 'controller not exists:' . $e->getClass());
        }

        $this->app['middleware']->controller(function (Request $request, $next) use ($instance) {
            
            $action = $this->actionName . $this->rule->getConfig('action_suffix');

            if (is_callable([$instance, $action])) {
                
                $call = [$instance, $action];

                
                $reflect    = new ReflectionMethod($instance, $action);
                $methodName = $reflect->getName();
                $suffix     = $this->rule->getConfig('action_suffix');
                $actionName = $suffix ? substr($methodName, 0, -strlen($suffix)) : $methodName;
                $this->request->setAction($actionName);

                
                $vars = $this->rule->getConfig('url_param_type')
                ? $this->request->route()
                : $this->request->param();
                $vars = array_merge($vars, $this->param);
            } elseif (is_callable([$instance, '_empty'])) {
                
                $call    = [$instance, '_empty'];
                $vars    = [$this->actionName];
                $reflect = new ReflectionMethod($instance, '_empty');
            } else {
                
                throw new HttpException(404, 'method not exists:' . get_class($instance) . '->' . $action . '()');
            }

            $this->app['hook']->listen('action_begin', $call);

            $data = $this->app->invokeReflectMethod($instance, $reflect, $vars);

            return $this->autoResponse($data);
        });

        return $this->app['middleware']->dispatch($this->request, 'controller');
    }
}
