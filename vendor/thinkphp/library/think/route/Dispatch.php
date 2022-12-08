<?php










namespace think\route;

use think\App;
use think\Container;
use think\exception\ValidateException;
use think\Request;
use think\Response;

abstract class Dispatch
{
    
    protected $app;

    
    protected $request;

    
    protected $rule;

    
    protected $dispatch;

    
    protected $param;

    
    protected $code;

    
    protected $convert;

    public function __construct(Request $request, Rule $rule, $dispatch, $param = [], $code = null)
    {
        $this->request  = $request;
        $this->rule     = $rule;
        $this->app      = Container::get('app');
        $this->dispatch = $dispatch;
        $this->param    = $param;
        $this->code     = $code;

        if (isset($param['convert'])) {
            $this->convert = $param['convert'];
        }
    }

    public function init()
    {
        
        if ($this->rule->doAfter()) {
            

            
            $this->request->setRouteVars($this->rule->getVars());
            $this->request->routeInfo([
                'rule'   => $this->rule->getRule(),
                'route'  => $this->rule->getRoute(),
                'option' => $this->rule->getOption(),
                'var'    => $this->rule->getVars(),
            ]);

            $this->doRouteAfter();
        }

        return $this;
    }

    
    protected function doRouteAfter()
    {
        
        $option  = $this->rule->getOption();
        $matches = $this->rule->getVars();

        
        if (!empty($option['middleware'])) {
            $this->app['middleware']->import($option['middleware']);
        }

        
        if (!empty($option['model'])) {
            $this->createBindModel($option['model'], $matches);
        }

        
        if (!empty($option['header'])) {
            $header = $option['header'];
            $this->app['hook']->add('response_send', function ($response) use ($header) {
                $response->header($header);
            });
        }

        
        if (!empty($option['response'])) {
            foreach ($option['response'] as $response) {
                $this->app['hook']->add('response_send', $response);
            }
        }

        
        if (isset($option['cache']) && $this->request->isGet()) {
            $this->parseRequestCache($option['cache']);
        }

        if (!empty($option['append'])) {
            $this->request->setRouteVars($option['append']);
        }
    }

    
    public function run()
    {
        $option = $this->rule->getOption();

        
        if (!empty($option['after'])) {
            $dispatch = $this->checkAfter($option['after']);

            if ($dispatch instanceof Response) {
                return $dispatch;
            }
        }

        
        if (isset($option['validate'])) {
            $this->autoValidate($option['validate']);
        }

        $data = $this->exec();

        return $this->autoResponse($data);
    }

    protected function autoResponse($data)
    {
        if ($data instanceof Response) {
            $response = $data;
        } elseif (!is_null($data)) {
            
            $isAjax = $this->request->isAjax();
            $type   = $isAjax ? $this->rule->getConfig('default_ajax_return') : $this->rule->getConfig('default_return_type');

            $response = Response::create($data, $type);
        } else {
            $data    = ob_get_clean();
            $content = false === $data ? '' : $data;
            $status  = '' === $content && $this->request->isJson() ? 204 : 200;

            $response = Response::create($content, '', $status);
        }

        return $response;
    }

    
    protected function checkAfter($after)
    {
        $this->app['log']->notice('路由后置行为建议使用中间件替代！');

        $hook = $this->app['hook'];

        $result = null;

        foreach ((array) $after as $behavior) {
            $result = $hook->exec($behavior);

            if (!is_null($result)) {
                break;
            }
        }

        
        if ($result instanceof Response) {
            return $result;
        }

        return false;
    }

    
    protected function autoValidate($option)
    {
        list($validate, $scene, $message, $batch) = $option;

        if (is_array($validate)) {
            
            $v = $this->app->validate();
            $v->rule($validate);
        } else {
            
            $v = $this->app->validate($validate);
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        if (!empty($message)) {
            $v->message($message);
        }

        
        if ($batch) {
            $v->batch(true);
        }

        if (!$v->check($this->request->param())) {
            throw new ValidateException($v->getError());
        }
    }

    
    protected function parseRequestCache($cache)
    {
        if (is_array($cache)) {
            list($key, $expire, $tag) = array_pad($cache, 3, null);
        } else {
            $key    = str_replace('|', '/', $this->request->url());
            $expire = $cache;
            $tag    = null;
        }

        $cache = $this->request->cache($key, $expire, $tag);
        $this->app->setResponseCache($cache);
    }

    
    protected function createBindModel($bindModel, $matches)
    {
        foreach ($bindModel as $key => $val) {
            if ($val instanceof \Closure) {
                $result = $this->app->invokeFunction($val, $matches);
            } else {
                $fields = explode('&', $key);

                if (is_array($val)) {
                    list($model, $exception) = $val;
                } else {
                    $model     = $val;
                    $exception = true;
                }

                $where = [];
                $match = true;

                foreach ($fields as $field) {
                    if (!isset($matches[$field])) {
                        $match = false;
                        break;
                    } else {
                        $where[] = [$field, '=', $matches[$field]];
                    }
                }

                if ($match) {
                    $query  = strpos($model, '\\') ? $model::where($where) : $this->app->model($model)->where($where);
                    $result = $query->failException($exception)->find();
                }
            }

            if (!empty($result)) {
                
                $this->app->instance(get_class($result), $result);
            }
        }
    }

    public function convert($convert)
    {
        $this->convert = $convert;

        return $this;
    }

    public function getDispatch()
    {
        return $this->dispatch;
    }

    public function getParam()
    {
        return $this->param;
    }

    abstract public function exec();

    public function __sleep()
    {
        return ['rule', 'dispatch', 'convert', 'param', 'code', 'controller', 'actionName'];
    }

    public function __wakeup()
    {
        $this->app     = Container::get('app');
        $this->request = $this->app['request'];
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['app'], $data['request'], $data['rule']);

        return $data;
    }
}
