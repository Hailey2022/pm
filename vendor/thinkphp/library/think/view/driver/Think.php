<?php










namespace think\view\driver;

use think\App;
use think\exception\TemplateNotFoundException;
use think\Loader;
use think\Template;

class Think
{
    
    private $template;
    private $app;

    
    protected $config = [
        
        'auto_rule'   => 1,
        
        'view_base'   => '',
        
        'view_path'   => '',
        
        'view_suffix' => 'html',
        
        'view_depr'   => DIRECTORY_SEPARATOR,
        
        'tpl_cache'   => true,
    ];

    public function __construct(App $app, $config = [])
    {
        $this->app    = $app;
        $this->config = array_merge($this->config, (array) $config);

        if (empty($this->config['view_path'])) {
            $this->config['view_path'] = $app->getModulePath() . 'view' . DIRECTORY_SEPARATOR;
        }

        $this->template = new Template($app, $this->config);
    }

    
    public function exists($template)
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            
            $template = $this->parseTemplate($template);
        }

        return is_file($template);
    }

    
    public function fetch($template, $data = [], $config = [])
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            
            $template = $this->parseTemplate($template);
        }

        
        if (!is_file($template)) {
            throw new TemplateNotFoundException('template not exists:' . $template, $template);
        }

        
        $this->app
            ->log('[ VIEW ] ' . $template . ' [ ' . var_export(array_keys($data), true) . ' ]');

        $this->template->fetch($template, $data, $config);
    }

    
    public function display($template, $data = [], $config = [])
    {
        $this->template->display($template, $data, $config);
    }

    
    private function parseTemplate($template)
    {
        
        $request = $this->app['request'];

        
        if (strpos($template, '@')) {
            
            list($module, $template) = explode('@', $template);
        }

        if ($this->config['view_base']) {
            
            $module = isset($module) ? $module : $request->module();
            $path   = $this->config['view_base'] . ($module ? $module . DIRECTORY_SEPARATOR : '');
        } else {
            $path = isset($module) ? $this->app->getAppPath() . $module . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR : $this->config['view_path'];
        }

        $depr = $this->config['view_depr'];

        if (0 !== strpos($template, '/')) {
            $template   = str_replace(['/', ':'], $depr, $template);
            $controller = Loader::parseName($request->controller());

            if ($controller) {
                if ('' == $template) {
                    
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $this->getActionTemplate($request);
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }

        return $path . ltrim($template, '/') . '.' . ltrim($this->config['view_suffix'], '.');
    }

    protected function getActionTemplate($request)
    {
        $rule = [$request->action(true), Loader::parseName($request->action(true)), $request->action()];
        $type = $this->config['auto_rule'];

        return isset($rule[$type]) ? $rule[$type] : $rule[0];
    }

    
    public function config($name, $value = null)
    {
        if (is_array($name)) {
            $this->template->config($name);
            $this->config = array_merge($this->config, $name);
        } elseif (is_null($value)) {
            return $this->template->config($name);
        } else {
            $this->template->$name = $value;
            $this->config[$name]   = $value;
        }
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->template, $method], $params);
    }

    public function __debugInfo()
    {
        return ['config' => $this->config];
    }
}
