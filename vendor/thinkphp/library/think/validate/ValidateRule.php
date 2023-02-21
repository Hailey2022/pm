<?php
namespace think\validate;
class ValidateRule
{
    protected $title;
    protected $rule = [];
    protected $message = [];
    protected function addItem($name, $rule = null, $msg = '')
    {
        if ($rule || 0 === $rule) {
            $this->rule[$name] = $rule;
        } else {
            $this->rule[] = $name;
        }
        $this->message[] = $msg;
        return $this;
    }
    public function getRule()
    {
        return $this->rule;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function getMsg()
    {
        return $this->message;
    }
    public function title($title)
    {
        $this->title = $title;
        return $this;
    }
    public function __call($method, $args)
    {
        if ('is' == strtolower(substr($method, 0, 2))) {
            $method = substr($method, 2);
        }
        array_unshift($args, lcfirst($method));
        return call_user_func_array([$this, 'addItem'], $args);
    }
    public static function __callStatic($method, $args)
    {
        $rule = new static();
        if ('is' == strtolower(substr($method, 0, 2))) {
            $method = substr($method, 2);
        }
        array_unshift($args, lcfirst($method));
        return call_user_func_array([$rule, 'addItem'], $args);
    }
}
