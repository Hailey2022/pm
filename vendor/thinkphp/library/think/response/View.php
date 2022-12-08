<?php










namespace think\response;

use think\Response;

class View extends Response
{
    
    protected $options = [];
    protected $vars    = [];
    protected $config  = [];
    protected $filter;
    protected $contentType = 'text/html';

    
    protected $isContent = false;

    
    protected function output($data)
    {
        
        return $this->app['view']
            ->filter($this->filter)
            ->fetch($data, $this->vars, $this->config, $this->isContent);
    }

    
    public function isContent($content = true)
    {
        $this->isContent = $content;
        return $this;
    }

    
    public function getVars($name = null)
    {
        if (is_null($name)) {
            return $this->vars;
        } else {
            return isset($this->vars[$name]) ? $this->vars[$name] : null;
        }
    }

    
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->vars = array_merge($this->vars, $name);
        } else {
            $this->vars[$name] = $value;
        }

        return $this;
    }

    public function config($config)
    {
        $this->config = $config;
        return $this;
    }

    
    public function filter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    
    public function exists($name)
    {
        return $this->app['view']->exists($name);
    }

}
