<?php
namespace think\route;
use think\Route;
class Resource extends RuleGroup
{
    protected $resource;
    protected $rest = [];
    public function __construct(Route $router, RuleGroup $parent = null, $name = '', $route = '', $option = [], $pattern = [], $rest = [])
    {
        $this->router   = $router;
        $this->parent   = $parent;
        $this->resource = $name;
        $this->route    = $route;
        $this->name     = strpos($name, '.') ? strstr($name, '.', true) : $name;
        $this->setFullName();
        $option['complete_match'] = true;
        $this->pattern = $pattern;
        $this->option  = $option;
        $this->rest    = $rest;
        if ($this->parent) {
            $this->domain = $this->parent->getDomain();
            $this->parent->addRuleItem($this);
        }
        if ($router->isTest()) {
            $this->buildResourceRule();
        }
    }
    protected function buildResourceRule()
    {
        $origin = $this->router->getGroup();
        $this->router->setGroup($this);
        $rule   = $this->resource;
        $option = $this->option;
        if (strpos($rule, '.')) {
            $array = explode('.', $rule);
            $last  = array_pop($array);
            $item  = [];
            foreach ($array as $val) {
                $item[] = $val . '/<' . (isset($option['var'][$val]) ? $option['var'][$val] : $val . '_id') . '>';
            }
            $rule = implode('/', $item) . '/' . $last;
        }
        $prefix = substr($rule, strlen($this->name) + 1);
        foreach ($this->rest as $key => $val) {
            if ((isset($option['only']) && !in_array($key, $option['only']))
                || (isset($option['except']) && in_array($key, $option['except']))) {
                continue;
            }
            if (isset($last) && strpos($val[1], '<id>') && isset($option['var'][$last])) {
                $val[1] = str_replace('<id>', '<' . $option['var'][$last] . '>', $val[1]);
            } elseif (strpos($val[1], '<id>') && isset($option['var'][$rule])) {
                $val[1] = str_replace('<id>', '<' . $option['var'][$rule] . '>', $val[1]);
            }
            $this->addRule(trim($prefix . $val[1], '/'), $this->route . '/' . $val[2], $val[0]);
        }
        $this->router->setGroup($origin);
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
}
