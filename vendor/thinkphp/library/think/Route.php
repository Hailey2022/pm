<?php










namespace think;

use think\exception\RouteNotFoundException;
use think\route\AliasRule;
use think\route\Dispatch;
use think\route\dispatch\Url as UrlDispatch;
use think\route\Domain;
use think\route\Resource;
use think\route\Rule;
use think\route\RuleGroup;
use think\route\RuleItem;

class Route
{
    
    protected $rest = [
        'index'  => ['get', '', 'index'],
        'create' => ['get', '/create', 'create'],
        'edit'   => ['get', '/<id>/edit', 'edit'],
        'read'   => ['get', '/<id>', 'read'],
        'save'   => ['post', '', 'save'],
        'update' => ['put', '/<id>', 'update'],
        'delete' => ['delete', '/<id>', 'delete'],
    ];

    
    protected $methodPrefix = [
        'get'    => 'get',
        'post'   => 'post',
        'put'    => 'put',
        'delete' => 'delete',
        'patch'  => 'patch',
    ];

    
    protected $app;

    
    protected $request;

    
    protected $host;

    
    protected $domain;

    
    protected $group;

    
    protected $config = [];

    
    protected $bind = [];

    
    protected $domains = [];

    
    protected $cross;

    
    protected $alias = [];

    
    protected $lazy = true;

    
    protected $isTest;

    
    protected $mergeRuleRegex = true;

    
    protected $autoSearchController = true;

    public function __construct(App $app, array $config = [])
    {
        $this->app     = $app;
        $this->request = $app['request'];
        $this->config  = $config;

        $this->host = $this->request->host(true) ?: $config['app_host'];

        $this->setDefaultDomain();
    }

    public function config($name = null)
    {
        if (is_null($name)) {
            return $this->config;
        }

        return isset($this->config[$name]) ? $this->config[$name] : null;
    }

    
    public function setConfig(array $config = [])
    {
        $this->config = array_merge($this->config, array_change_key_case($config));
    }

    public static function __make(App $app, Config $config)
    {
        $config = $config->pull('app');
        $route  = new static($app, $config);

        $route->lazy($config['url_lazy_route'])
            ->autoSearchController($config['controller_auto_search'])
            ->mergeRuleRegex($config['route_rule_merge']);

        return $route;
    }

    
    public function setRequest($request)
    {
        $this->request = $request;
    }

    
    public function lazy($lazy = true)
    {
        $this->lazy = $lazy;
        return $this;
    }

    
    public function setTestMode($test)
    {
        $this->isTest = $test;
    }

    
    public function isTest()
    {
        return $this->isTest;
    }

    
    public function mergeRuleRegex($merge = true)
    {
        $this->mergeRuleRegex = $merge;
        $this->group->mergeRuleRegex($merge);

        return $this;
    }

    
    public function autoSearchController($auto = true)
    {
        $this->autoSearchController = $auto;
        return $this;
    }

    
    protected function setDefaultDomain()
    {
        
        $this->domain = $this->host;

        
        $domain = new Domain($this, $this->host);

        $this->domains[$this->host] = $domain;

        
        $this->group = $domain;
    }

    
    public function setGroup(RuleGroup $group)
    {
        $this->group = $group;
    }

    
    public function getGroup()
    {
        return $this->group;
    }

    
    public function pattern($name, $rule = '')
    {
        $this->group->pattern($name, $rule);

        return $this;
    }

    
    public function option($name, $value = '')
    {
        $this->group->option($name, $value);

        return $this;
    }

    
    public function domain($name, $rule = '', $option = [], $pattern = [])
    {
        
        $domainName = is_array($name) ? array_shift($name) : $name;

        if ('*' != $domainName && false === strpos($domainName, '.')) {
            $domainName .= '.' . $this->request->rootDomain();
        }

        if (!isset($this->domains[$domainName])) {
            $domain = (new Domain($this, $domainName, $rule, $option, $pattern))
                ->lazy($this->lazy)
                ->mergeRuleRegex($this->mergeRuleRegex);

            $this->domains[$domainName] = $domain;
        } else {
            $domain = $this->domains[$domainName];
            $domain->parseGroupRule($rule);
        }

        if (is_array($name) && !empty($name)) {
            $root = $this->request->rootDomain();
            foreach ($name as $item) {
                if (false === strpos($item, '.')) {
                    $item .= '.' . $root;
                }

                $this->domains[$item] = $domainName;
            }
        }

        
        return $domain;
    }

    
    public function getDomains()
    {
        return $this->domains;
    }

    
    public function bind($bind, $domain = null)
    {
        $domain = is_null($domain) ? $this->domain : $domain;

        $this->bind[$domain] = $bind;

        return $this;
    }

    
    public function getBind($domain = null)
    {
        if (is_null($domain)) {
            $domain = $this->domain;
        } elseif (true === $domain) {
            return $this->bind;
        } elseif (false === strpos($domain, '.')) {
            $domain .= '.' . $this->request->rootDomain();
        }

        $subDomain = $this->request->subDomain();

        if (strpos($subDomain, '.')) {
            $name = '*' . strstr($subDomain, '.');
        }

        if (isset($this->bind[$domain])) {
            $result = $this->bind[$domain];
        } elseif (isset($name) && isset($this->bind[$name])) {
            $result = $this->bind[$name];
        } elseif (!empty($subDomain) && isset($this->bind['*'])) {
            $result = $this->bind['*'];
        } else {
            $result = null;
        }

        return $result;
    }

    
    public function getName($name = null, $domain = null, $method = '*')
    {
        return $this->app['rule_name']->get($name, $domain, $method);
    }

    
    public function getRule($rule, $domain = null)
    {
        if (is_null($domain)) {
            $domain = $this->domain;
        }

        return $this->app['rule_name']->getRule($rule, $domain);
    }

    
    public function getRuleList($domain = null)
    {
        return $this->app['rule_name']->getRuleList($domain);
    }

    
    public function setName($name)
    {
        $this->app['rule_name']->import($name);
        return $this;
    }

    
    public function import(array $rules, $type = '*')
    {
        
        if (isset($rules['__domain__'])) {
            foreach ($rules['__domain__'] as $key => $rule) {
                $this->domain($key, $rule);
            }
            unset($rules['__domain__']);
        }

        
        if (isset($rules['__pattern__'])) {
            $this->pattern($rules['__pattern__']);
            unset($rules['__pattern__']);
        }

        
        if (isset($rules['__alias__'])) {
            foreach ($rules['__alias__'] as $key => $val) {
                $this->alias($key, $val);
            }
            unset($rules['__alias__']);
        }

        
        if (isset($rules['__rest__'])) {
            foreach ($rules['__rest__'] as $key => $rule) {
                $this->resource($key, $rule);
            }
            unset($rules['__rest__']);
        }

        
        foreach ($rules as $key => $val) {
            if (is_numeric($key)) {
                $key = array_shift($val);
            }

            if (empty($val)) {
                continue;
            }

            if (is_string($key) && 0 === strpos($key, '[')) {
                $key = substr($key, 1, -1);
                $this->group($key, $val);
            } elseif (is_array($val)) {
                $this->rule($key, $val[0], $type, $val[1], isset($val[2]) ? $val[2] : []);
            } else {
                $this->rule($key, $val, $type);
            }
        }
    }

    
    public function rule($rule, $route, $method = '*', array $option = [], array $pattern = [])
    {
        return $this->group->addRule($rule, $route, $method, $option, $pattern);
    }

    
    public function setCrossDomainRule($rule, $method = '*')
    {
        if (!isset($this->cross)) {
            $this->cross = (new RuleGroup($this))->mergeRuleRegex($this->mergeRuleRegex);
        }

        $this->cross->addRuleItem($rule, $method);

        return $this;
    }

    
    public function rules($rules, $method = '*', array $option = [], array $pattern = [])
    {
        $this->group->addRules($rules, $method, $option, $pattern);
    }

    
    public function group($name, $route, array $option = [], array $pattern = [])
    {
        if (is_array($name)) {
            $option = $name;
            $name   = isset($option['name']) ? $option['name'] : '';
        }

        return (new RuleGroup($this, $this->group, $name, $route, $option, $pattern))
            ->lazy($this->lazy)
            ->mergeRuleRegex($this->mergeRuleRegex);
    }

    
    public function any($rule, $route = '', array $option = [], array $pattern = [])
    {
        return $this->rule($rule, $route, '*', $option, $pattern);
    }

    
    public function get($rule, $route = '', array $option = [], array $pattern = [])
    {
        return $this->rule($rule, $route, 'GET', $option, $pattern);
    }

    
    public function post($rule, $route = '', array $option = [], array $pattern = [])
    {
        return $this->rule($rule, $route, 'POST', $option, $pattern);
    }

    
    public function put($rule, $route = '', array $option = [], array $pattern = [])
    {
        return $this->rule($rule, $route, 'PUT', $option, $pattern);
    }

    
    public function delete($rule, $route = '', array $option = [], array $pattern = [])
    {
        return $this->rule($rule, $route, 'DELETE', $option, $pattern);
    }

    
    public function patch($rule, $route = '', array $option = [], array $pattern = [])
    {
        return $this->rule($rule, $route, 'PATCH', $option, $pattern);
    }

    
    public function resource($rule, $route = '', array $option = [], array $pattern = [])
    {
        return (new Resource($this, $this->group, $rule, $route, $option, $pattern, $this->rest))
            ->lazy($this->lazy);
    }

    
    public function controller($rule, $route = '', array $option = [], array $pattern = [])
    {
        $group = new RuleGroup($this, $this->group, $rule, null, $option, $pattern);

        foreach ($this->methodPrefix as $type => $val) {
            $group->addRule('<action>', $val . '<action>', $type);
        }

        return $group->prefix($route . '/');
    }

    
    public function view($rule, $template = '', array $vars = [], array $option = [], array $pattern = [])
    {
        return $this->rule($rule, $template, 'GET', $option, $pattern)->view($vars);
    }

    
    public function redirect($rule, $route = '', $status = 301, array $option = [], array $pattern = [])
    {
        return $this->rule($rule, $route, '*', $option, $pattern)->redirect()->status($status);
    }

    
    public function alias($rule, $route, array $option = [])
    {
        $aliasRule = new AliasRule($this, $this->group, $rule, $route, $option);

        $this->alias[$rule] = $aliasRule;

        return $aliasRule;
    }

    
    public function getAlias($name = null)
    {
        if (is_null($name)) {
            return $this->alias;
        }

        return isset($this->alias[$name]) ? $this->alias[$name] : null;
    }

    
    public function setMethodPrefix($method, $prefix = '')
    {
        if (is_array($method)) {
            $this->methodPrefix = array_merge($this->methodPrefix, array_change_key_case($method));
        } else {
            $this->methodPrefix[strtolower($method)] = $prefix;
        }

        return $this;
    }

    
    public function getMethodPrefix($method)
    {
        $method = strtolower($method);

        return isset($this->methodPrefix[$method]) ? $this->methodPrefix[$method] : null;
    }

    
    public function rest($name, $resource = [])
    {
        if (is_array($name)) {
            $this->rest = $resource ? $name : array_merge($this->rest, $name);
        } else {
            $this->rest[$name] = $resource;
        }

        return $this;
    }

    
    public function getRest($name = null)
    {
        if (is_null($name)) {
            return $this->rest;
        }

        return isset($this->rest[$name]) ? $this->rest[$name] : null;
    }

    
    public function miss($route, $method = '*', array $option = [])
    {
        return $this->group->addMissRule($route, $method, $option);
    }

    
    public function auto($route)
    {
        return $this->group->addAutoRule($route);
    }

    
    public function check($url, $must = false)
    {
        
        $domain = $this->checkDomain();
        $url    = str_replace($this->config['pathinfo_depr'], '|', $url);

        $completeMatch = $this->config['route_complete_match'];

        $result = $domain->check($this->request, $url, $completeMatch);

        if (false === $result && !empty($this->cross)) {
            
            $result = $this->cross->check($this->request, $url, $completeMatch);
        }

        if (false !== $result) {
            
            return $result;
        } elseif ($must) {
            
            throw new RouteNotFoundException();
        }

        
        return new UrlDispatch($this->request, $this->group, $url, [
            'auto_search' => $this->autoSearchController,
        ]);
    }

    
    protected function checkDomain()
    {
        
        $subDomain = $this->request->subDomain();

        $item = false;

        if ($subDomain && count($this->domains) > 1) {
            $domain  = explode('.', $subDomain);
            $domain2 = array_pop($domain);

            if ($domain) {
                
                $domain3 = array_pop($domain);
            }

            if ($subDomain && isset($this->domains[$subDomain])) {
                
                $item = $this->domains[$subDomain];
            } elseif (isset($this->domains['*.' . $domain2]) && !empty($domain3)) {
                
                $item      = $this->domains['*.' . $domain2];
                $panDomain = $domain3;
            } elseif (isset($this->domains['*']) && !empty($domain2)) {
                
                if ('www' != $domain2) {
                    $item      = $this->domains['*'];
                    $panDomain = $domain2;
                }
            }

            if (isset($panDomain)) {
                
                $this->request->setPanDomain($panDomain);
            }
        }

        if (false === $item) {
            
            $item = $this->domains[$this->host];
        }

        if (is_string($item)) {
            $item = $this->domains[$item];
        }

        return $item;
    }

    
    public function clear()
    {
        $this->app['rule_name']->clear();
        $this->group->clear();
    }

    
    public function __call($method, $args)
    {
        return call_user_func_array([$this->group, $method], $args);
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['app'], $data['request']);

        return $data;
    }
}
