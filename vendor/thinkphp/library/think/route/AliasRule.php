<?php










namespace think\route;

use think\Route;

class AliasRule extends Domain
{
    
    public function __construct(Route $router, RuleGroup $parent, $name, $route, $option = [])
    {
        $this->router = $router;
        $this->parent = $parent;
        $this->name   = $name;
        $this->route  = $route;
        $this->option = $option;
    }

    
    public function check($request, $url, $completeMatch = false)
    {
        if ($dispatch = $this->checkCrossDomain($request)) {
            
            return $dispatch;
        }

        
        if (!$this->checkOption($this->option, $request)) {
            return false;
        }

        list($action, $bind) = array_pad(explode('|', $url, 2), 2, '');

        if (isset($this->option['allow']) && !in_array($action, $this->option['allow'])) {
            
            return false;
        } elseif (isset($this->option['except']) && in_array($action, $this->option['except'])) {
            
            return false;
        }

        if (isset($this->option['method'][$action])) {
            $this->option['method'] = $this->option['method'][$action];
        }

        
        $this->afterMatchGroup($request);

        if ($this->parent) {
            
            $this->mergeGroupOptions();
        }

        if (isset($this->option['ext'])) {
            
            $bind = preg_replace('/\.(' . $request->ext() . ')$/i', '', $bind);
        }

        $this->parseBindAppendParam($this->route);

        if (0 === strpos($this->route, '\\')) {
            
            return $this->bindToClass($request, $bind, substr($this->route, 1));
        } elseif (0 === strpos($this->route, '@')) {
            
            return $this->bindToController($request, $bind, substr($this->route, 1));
        } else {
            
            return $this->bindToModule($request, $bind, $this->route);
        }
    }

    
    public function allow($action = [])
    {
        return $this->option('allow', $action);
    }

    
    public function except($action = [])
    {
        return $this->option('except', $action);
    }

}
