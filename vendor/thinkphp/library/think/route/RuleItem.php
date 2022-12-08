<?php










namespace think\route;

use think\Container;
use think\Exception;
use think\Route;

class RuleItem extends Rule
{
    protected $hasSetRule;

    
    public function __construct(Route $router, RuleGroup $parent, $name, $rule, $route, $method = '*', $option = [], $pattern = [])
    {
        $this->router  = $router;
        $this->parent  = $parent;
        $this->name    = $name;
        $this->route   = $route;
        $this->method  = $method;
        $this->option  = $option;
        $this->pattern = $pattern;

        $this->setRule($rule);

        if (!empty($option['cross_domain'])) {
            $this->router->setCrossDomainRule($this, $method);
        }
    }

    
    public function setRule($rule)
    {
        if ('$' == substr($rule, -1, 1)) {
            
            $rule = substr($rule, 0, -1);

            $this->option['complete_match'] = true;
        }

        $rule = '/' != $rule ? ltrim($rule, '/') : '';

        if ($this->parent && $prefix = $this->parent->getFullName()) {
            $rule = $prefix . ($rule ? '/' . ltrim($rule, '/') : '');
        }

        if (false !== strpos($rule, ':')) {
            $this->rule = preg_replace(['/\[\:(\w+)\]/', '/\:(\w+)/'], ['<\1?>', '<\1>'], $rule);
        } else {
            $this->rule = $rule;
        }

        
        $this->setRuleName();
    }

    
    public function ext($ext = '')
    {
        $this->option('ext', $ext);
        $this->setRuleName(true);

        return $this;
    }

    
    public function name($name)
    {
        $this->name = $name;
        $this->setRuleName(true);

        return $this;
    }

    
    protected function setRuleName($first = false)
    {
        if ($this->name) {
            $vars = $this->parseVar($this->rule);
            $name = strtolower($this->name);

            if (isset($this->option['ext'])) {
                $suffix = $this->option['ext'];
            } elseif ($this->parent->getOption('ext')) {
                $suffix = $this->parent->getOption('ext');
            } else {
                $suffix = null;
            }

            $value = [$this->rule, $vars, $this->parent->getDomain(), $suffix, $this->method];

            Container::get('rule_name')->set($name, $value, $first);
        }

        if (!$this->hasSetRule) {
            Container::get('rule_name')->setRule($this->rule, $this);
            $this->hasSetRule = true;
        }
    }

    
    public function checkRule($request, $url, $match = null, $completeMatch = false)
    {
        
        if (!$this->checkOption($this->option, $request)) {
            return false;
        }

        
        $option = $this->mergeGroupOptions();

        $url = $this->urlSuffixCheck($request, $url, $option);

        if (is_null($match)) {
            $match = $this->match($url, $option, $completeMatch);
        }

        if (false !== $match) {
            if (!empty($option['cross_domain'])) {
                if ($dispatch = $this->checkCrossDomain($request)) {
                    
                    return $dispatch;
                }

                $option['header'] = $this->option['header'];
            }

            
            if (isset($option['before']) && false === $this->checkBefore($option['before'])) {
                return false;
            }

            return $this->parseRule($request, $this->rule, $this->route, $url, $option, $match);
        }

        return false;
    }

    
    public function check($request, $url, $completeMatch = false)
    {
        return $this->checkRule($request, $url, null, $completeMatch);
    }

    
    protected function urlSuffixCheck($request, $url, $option = [])
    {
        
        if (!empty($option['remove_slash']) && '/' != $this->rule) {
            $this->rule = rtrim($this->rule, '/');
            $url        = rtrim($url, '|');
        }

        if (isset($option['ext'])) {
            
            $url = preg_replace('/\.(' . $request->ext() . ')$/i', '', $url);
        }

        return $url;
    }

    
    private function match($url, $option, $completeMatch)
    {
        if (isset($option['complete_match'])) {
            $completeMatch = $option['complete_match'];
        }

        $pattern = array_merge($this->parent->getPattern(), $this->pattern);
        $depr    = $this->router->config('pathinfo_depr');

        
        if (isset($pattern['__url__']) && !preg_match(0 === strpos($pattern['__url__'], '/') ? $pattern['__url__'] : '/^' . $pattern['__url__'] . '/', str_replace('|', $depr, $url))) {
            return false;
        }

        $var  = [];
        $url  = $depr . str_replace('|', $depr, $url);
        $rule = $depr . str_replace('/', $depr, $this->rule);

        if ($depr == $rule && $depr != $url) {
            return false;
        }

        if (false === strpos($rule, '<')) {
            if (0 === strcasecmp($rule, $url) || (!$completeMatch && 0 === strncasecmp($rule . $depr, $url . $depr, strlen($rule . $depr)))) {
                return $var;
            }
            return false;
        }

        $slash = preg_quote('/-' . $depr, '/');

        if ($matchRule = preg_split('/[' . $slash . ']?<\w+\??>/', $rule, 2)) {
            if ($matchRule[0] && 0 !== strncasecmp($rule, $url, strlen($matchRule[0]))) {
                return false;
            }
        }

        if (preg_match_all('/[' . $slash . ']?<?\w+\??>?/', $rule, $matches)) {
            $regex = $this->buildRuleRegex($rule, $matches[0], $pattern, $option, $completeMatch);

            try {
                if (!preg_match('/^' . $regex . ($completeMatch ? '$' : '') . '/u', $url, $match)) {
                    return false;
                }
            } catch (\Exception $e) {
                throw new Exception('route pattern error');
            }

            foreach ($match as $key => $val) {
                if (is_string($key)) {
                    $var[$key] = $val;
                }
            }
        }

        
        return $var;
    }

}
