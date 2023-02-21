<?php
namespace think\route;
use think\Container;
use think\Exception;
use think\Request;
use think\Response;
use think\Route;
use think\route\dispatch\Response as ResponseDispatch;
use think\route\dispatch\Url as UrlDispatch;
class RuleGroup extends Rule
{
    protected $rules = [
        '*'       => [],
        'get'     => [],
        'post'    => [],
        'put'     => [],
        'patch'   => [],
        'delete'  => [],
        'head'    => [],
        'options' => [],
    ];
    protected $miss;
    protected $auto;
    protected $fullName;
    protected $domain;
    public function __construct(Route $router, RuleGroup $parent = null, $name = '', $rule = [], $option = [], $pattern = [])
    {
        $this->router  = $router;
        $this->parent  = $parent;
        $this->rule    = $rule;
        $this->name    = trim($name, '/');
        $this->option  = $option;
        $this->pattern = $pattern;
        $this->setFullName();
        if ($this->parent) {
            $this->domain = $this->parent->getDomain();
            $this->parent->addRuleItem($this);
        }
        if (!empty($option['cross_domain'])) {
            $this->router->setCrossDomainRule($this);
        }
        if ($router->isTest()) {
            $this->lazy(false);
        }
    }
    protected function setFullName()
    {
        if (false !== strpos($this->name, ':')) {
            $this->name = preg_replace(['/\[\:(\w+)\]/', '/\:(\w+)/'], ['<\1?>', '<\1>'], $this->name);
        }
        if ($this->parent && $this->parent->getFullName()) {
            $this->fullName = $this->parent->getFullName() . ($this->name ? '/' . $this->name : '');
        } else {
            $this->fullName = $this->name;
        }
    }
    public function getDomain()
    {
        return $this->domain;
    }
    public function check($request, $url, $completeMatch = false)
    {
        if ($dispatch = $this->checkCrossDomain($request)) {
            return $dispatch;
        }
        if (!$this->checkOption($this->option, $request) || !$this->checkUrl($url)) {
            return false;
        }
        if (isset($this->option['before'])) {
            if (false === $this->checkBefore($this->option['before'])) {
                return false;
            }
            unset($this->option['before']);
        }
        if ($this instanceof Resource) {
            $this->buildResourceRule();
        } elseif ($this->rule) {
            if ($this->rule instanceof Response) {
                return new ResponseDispatch($request, $this, $this->rule);
            }
            $this->parseGroupRule($this->rule);
        }
        $method = strtolower($request->method());
        $rules  = $this->getMethodRules($method);
        if ($this->parent) {
            $this->mergeGroupOptions();
            $this->pattern = array_merge($this->parent->getPattern(), $this->pattern);
        }
        if (isset($this->option['complete_match'])) {
            $completeMatch = $this->option['complete_match'];
        }
        if (!empty($this->option['merge_rule_regex'])) {
            $result = $this->checkMergeRuleRegex($request, $rules, $url, $completeMatch);
            if (false !== $result) {
                return $result;
            }
        }
        foreach ($rules as $key => $item) {
            $result = $item->check($request, $url, $completeMatch);
            if (false !== $result) {
                return $result;
            }
        }
        if ($this->auto) {
            $result = new UrlDispatch($request, $this, $this->auto . '/' . $url, ['auto_search' => false]);
        } elseif ($this->miss && in_array($this->miss->getMethod(), ['*', $method])) {
            $result = $this->miss->parseRule($request, '', $this->miss->getRoute(), $url, $this->miss->mergeGroupOptions());
        } else {
            $result = false;
        }
        return $result;
    }
    protected function getMethodRules($method)
    {
        return array_merge($this->rules[$method], $this->rules['*']);
    }
    protected function checkUrl($url)
    {
        if ($this->fullName) {
            $pos = strpos($this->fullName, '<');
            if (false !== $pos) {
                $str = substr($this->fullName, 0, $pos);
            } else {
                $str = $this->fullName;
            }
            if ($str && 0 !== stripos(str_replace('|', '/', $url), $str)) {
                return false;
            }
        }
        return true;
    }
    public function lazy($lazy = true)
    {
        if (!$lazy) {
            $this->parseGroupRule($this->rule);
            $this->rule = null;
        }
        return $this;
    }
    public function parseGroupRule($rule)
    {
        $origin = $this->router->getGroup();
        $this->router->setGroup($this);
        if ($rule instanceof \Closure) {
            Container::getInstance()->invokeFunction($rule);
        } elseif (is_array($rule)) {
            $this->addRules($rule);
        } elseif (is_string($rule) && $rule) {
            $this->router->bind($rule, $this->domain);
        }
        $this->router->setGroup($origin);
    }
    protected function checkMergeRuleRegex($request, &$rules, $url, $completeMatch)
    {
        $depr = $this->router->config('pathinfo_depr');
        $url  = $depr . str_replace('|', $depr, $url);
        foreach ($rules as $key => $item) {
            if ($item instanceof RuleItem) {
                $rule = $depr . str_replace('/', $depr, $item->getRule());
                if ($depr == $rule && $depr != $url) {
                    unset($rules[$key]);
                    continue;
                }
                $complete = null !== $item->getOption('complete_match') ? $item->getOption('complete_match') : $completeMatch;
                if (false === strpos($rule, '<')) {
                    if (0 === strcasecmp($rule, $url) || (!$complete && 0 === strncasecmp($rule, $url, strlen($rule)))) {
                        return $item->checkRule($request, $url, []);
                    }
                    unset($rules[$key]);
                    continue;
                }
                $slash = preg_quote('/-' . $depr, '/');
                if ($matchRule = preg_split('/[' . $slash . ']<\w+\??>/', $rule, 2)) {
                    if ($matchRule[0] && 0 !== strncasecmp($rule, $url, strlen($matchRule[0]))) {
                        unset($rules[$key]);
                        continue;
                    }
                }
                if (preg_match_all('/[' . $slash . ']?<?\w+\??>?/', $rule, $matches)) {
                    unset($rules[$key]);
                    $pattern = array_merge($this->getPattern(), $item->getPattern());
                    $option  = array_merge($this->getOption(), $item->getOption());
                    $regex[$key] = $this->buildRuleRegex($rule, $matches[0], $pattern, $option, $complete, '_THINK_' . $key);
                    $items[$key] = $item;
                }
            }
        }
        if (empty($regex)) {
            return false;
        }
        try {
            $result = preg_match('/^(?:' . implode('|', $regex) . ')/u', $url, $match);
        } catch (\Exception $e) {
            throw new Exception('route pattern error');
        }
        if ($result) {
            $var = [];
            foreach ($match as $key => $val) {
                if (is_string($key) && '' !== $val) {
                    list($name, $pos) = explode('_THINK_', $key);
                    $var[$name] = $val;
                }
            }
            if (!isset($pos)) {
                foreach ($regex as $key => $item) {
                    if (0 === strpos(str_replace(['\/', '\-', '\\' . $depr], ['/', '-', $depr], $item), $match[0])) {
                        $pos = $key;
                        break;
                    }
                }
            }
            $rule  = $items[$pos]->getRule();
            $array = $this->router->getRule($rule);
            foreach ($array as $item) {
                if (in_array($item->getMethod(), ['*', strtolower($request->method())])) {
                    $result = $item->checkRule($request, $url, $var);
                    if (false !== $result) {
                        return $result;
                    }
                }
            }
        }
        return false;
    }
    public function getMissRule()
    {
        return $this->miss;
    }
    public function getAutoRule()
    {
        return $this->auto;
    }
    public function addAutoRule($route)
    {
        $this->auto = $route;
    }
    public function addMissRule($route, $method = '*', $option = [])
    {
        $ruleItem = new RuleItem($this->router, $this, null, '', $route, strtolower($method), $option);
        $this->miss = $ruleItem;
        return $ruleItem;
    }
    public function addRule($rule, $route, $method = '*', $option = [], $pattern = [])
    {
        if (is_array($rule)) {
            $name = $rule[0];
            $rule = $rule[1];
        } elseif (is_string($route)) {
            $name = $route;
        } else {
            $name = null;
        }
        $method = strtolower($method);
        if ('/' === $rule || '' === $rule) {
            $rule .= '$';
        }
        $ruleItem = new RuleItem($this->router, $this, $name, $rule, $route, $method, $option, $pattern);
        if (!empty($option['cross_domain'])) {
            $this->router->setCrossDomainRule($ruleItem, $method);
        }
        $this->addRuleItem($ruleItem, $method);
        return $ruleItem;
    }
    public function addRules($rules, $method = '*', $option = [], $pattern = [])
    {
        foreach ($rules as $key => $val) {
            if (is_numeric($key)) {
                $key = array_shift($val);
            }
            if (is_array($val)) {
                $route   = array_shift($val);
                $option  = $val ? array_shift($val) : [];
                $pattern = $val ? array_shift($val) : [];
            } else {
                $route = $val;
            }
            $this->addRule($key, $route, $method, $option, $pattern);
        }
    }
    public function addRuleItem($rule, $method = '*')
    {
        if (strpos($method, '|')) {
            $rule->method($method);
            $method = '*';
        }
        $this->rules[$method][] = $rule;
        return $this;
    }
    public function prefix($prefix)
    {
        if ($this->parent && $this->parent->getOption('prefix')) {
            $prefix = $this->parent->getOption('prefix') . $prefix;
        }
        return $this->option('prefix', $prefix);
    }
    public function only($only)
    {
        return $this->option('only', $only);
    }
    public function except($except)
    {
        return $this->option('except', $except);
    }
    public function vars($vars)
    {
        return $this->option('var', $vars);
    }
    public function mergeRuleRegex($merge = true)
    {
        return $this->option('merge_rule_regex', $merge);
    }
    public function getFullName()
    {
        return $this->fullName;
    }
    public function getRules($method = '')
    {
        if ('' === $method) {
            return $this->rules;
        }
        return isset($this->rules[strtolower($method)]) ? $this->rules[strtolower($method)] : [];
    }
    public function clear()
    {
        $this->rules = [
            '*'       => [],
            'get'     => [],
            'post'    => [],
            'put'     => [],
            'patch'   => [],
            'delete'  => [],
            'head'    => [],
            'options' => [],
        ];
    }
}
