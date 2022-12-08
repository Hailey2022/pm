<?php










namespace think;

use think\exception\ValidateException;
use traits\controller\Jump;

class Controller
{
    use Jump;

    
    protected $view;

    
    protected $request;

    
    protected $failException = false;

    
    protected $batchValidate = false;

    
    protected $beforeActionList = [];

    
    protected $middleware = [];

    
    public function __construct(App $app = null)
    {
        $this->app     = $app ?: Container::get('app');
        $this->request = $this->app['request'];
        $this->view    = $this->app['view'];

        
        $this->initialize();

        $this->registerMiddleware();

        
        foreach ((array) $this->beforeActionList as $method => $options) {
            is_numeric($method) ?
            $this->beforeAction($options) :
            $this->beforeAction($method, $options);
        }
    }

    
    protected function initialize()
    {}

    
    public function registerMiddleware()
    {
        foreach ($this->middleware as $key => $val) {
            if (!is_int($key)) {
                $only = $except = null;

                if (isset($val['only'])) {
                    $only = array_map(function ($item) {
                        return strtolower($item);
                    }, $val['only']);
                } elseif (isset($val['except'])) {
                    $except = array_map(function ($item) {
                        return strtolower($item);
                    }, $val['except']);
                }

                if (isset($only) && !in_array($this->request->action(), $only)) {
                    continue;
                } elseif (isset($except) && in_array($this->request->action(), $except)) {
                    continue;
                } else {
                    $val = $key;
                }
            }

            $this->app['middleware']->controller($val);
        }
    }

    
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only'])) {
            if (is_string($options['only'])) {
                $options['only'] = explode(',', $options['only']);
            }

            $only = array_map(function ($val) {
                return strtolower($val);
            }, $options['only']);

            if (!in_array($this->request->action(), $only)) {
                return;
            }
        } elseif (isset($options['except'])) {
            if (is_string($options['except'])) {
                $options['except'] = explode(',', $options['except']);
            }

            $except = array_map(function ($val) {
                return strtolower($val);
            }, $options['except']);

            if (in_array($this->request->action(), $except)) {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    
    protected function fetch($template = '', $vars = [], $config = [])
    {
        return Response::create($template, 'view')->assign($vars)->config($config);
    }

    
    protected function display($content = '', $vars = [], $config = [])
    {
        return Response::create($content, 'view')->assign($vars)->config($config)->isContent(true);
    }

    
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);

        return $this;
    }

    
    protected function filter($filter)
    {
        $this->view->filter($filter);

        return $this;
    }

    
    protected function engine($engine)
    {
        $this->view->engine($engine);

        return $this;
    }

    
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate)) {
            $v = $this->app->validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                
                list($validate, $scene) = explode('.', $validate);
            }
            $v = $this->app->validate($validate);
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        if (is_array($message)) {
            $v->message($message);
        }

        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            }
            return $v->getError();
        }

        return true;
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['app'], $data['request']);

        return $data;
    }
}
