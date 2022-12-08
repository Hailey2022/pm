<?php










namespace think\view\driver;

use think\App;
use think\exception\TemplateNotFoundException;
use think\Loader;

class Php
{
    
    protected $config = [
        
        'auto_rule'   => 1,
        
        'view_base'   => '',
        
        'view_path'   => '',
        
        'view_suffix' => 'php',
        
        'view_depr'   => DIRECTORY_SEPARATOR,
    ];

    protected $template;
    protected $app;
    protected $content;

    public function __construct(App $app, $config = [])
    {
        $this->app    = $app;
        $this->config = array_merge($this->config, (array) $config);
    }

    
    public function exists($template)
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            
            $template = $this->parseTemplate($template);
        }

        return is_file($template);
    }

    
    public function fetch($template, $data = [])
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            
            $template = $this->parseTemplate($template);
        }

        
        if (!is_file($template)) {
            throw new TemplateNotFoundException('template not exists:' . $template, $template);
        }

        $this->template = $template;

        
        $this->app
            ->log('[ VIEW ] ' . $template . ' [ ' . var_export(array_keys($data), true) . ' ]');

        extract($data, EXTR_OVERWRITE);
        include $this->template;
    }

    
    public function display($content, $data = [])
    {
        $this->content = $content;

        extract($data, EXTR_OVERWRITE);
        eval('?>' . $this->content);
    }

    
    private function parseTemplate($template)
    {
        if (empty($this->config['view_path'])) {
            $this->config['view_path'] = $this->app->getModulePath() . 'view' . DIRECTORY_SEPARATOR;
        }

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
            $this->config = array_merge($this->config, $name);
        } elseif (is_null($value)) {
            return isset($this->config[$name]) ? $this->config[$name] : null;
        } else {
            $this->config[$name] = $value;
        }
    }

    public function __debugInfo()
    {
        return ['config' => $this->config];
    }
}
