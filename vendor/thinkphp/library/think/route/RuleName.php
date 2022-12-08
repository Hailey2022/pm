<?php










namespace think\route;

class RuleName
{
    protected $item = [];
    protected $rule = [];

    
    public function set($name, $value, $first = false)
    {
        if ($first && isset($this->item[$name])) {
            array_unshift($this->item[$name], $value);
        } else {
            $this->item[$name][] = $value;
        }
    }

    
    public function setRule($rule, $route)
    {
        $this->rule[$route->getDomain()][$rule][$route->getMethod()] = $route;
    }

    
    public function getRule($rule, $domain = null)
    {
        return isset($this->rule[$domain][$rule]) ? $this->rule[$domain][$rule] : [];
    }

    
    public function getRuleList($domain = null)
    {
        $list = [];

        foreach ($this->rule as $ruleDomain => $rules) {
            foreach ($rules as $rule => $items) {
                foreach ($items as $item) {
                    $val['domain'] = $ruleDomain;

                    foreach (['method', 'rule', 'name', 'route', 'pattern', 'option'] as $param) {
                        $call        = 'get' . $param;
                        $val[$param] = $item->$call();
                    }

                    $list[$ruleDomain][] = $val;
                }
            }
        }

        if ($domain) {
            return isset($list[$domain]) ? $list[$domain] : [];
        }

        return $list;
    }

    
    public function import($item)
    {
        $this->item = $item;
    }

    
    public function get($name = null, $domain = null, $method = '*')
    {
        if (is_null($name)) {
            return $this->item;
        }

        $name = strtolower($name);
        $method = strtolower($method);

        if (isset($this->item[$name])) {
            if (is_null($domain)) {
                $result = $this->item[$name];
            } else {
                $result = [];
                foreach ($this->item[$name] as $item) {
                    if ($item[2] == $domain && ('*' == $item[4] || $method == $item[4])) {
                        $result[] = $item;
                    }
                }
            }
        } else {
            $result = null;
        }

        return $result;
    }

    
    public function clear()
    {
        $this->item = [];
        $this->rule = [];
    }
}
