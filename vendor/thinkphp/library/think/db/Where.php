<?php
namespace think\db;
use ArrayAccess;
class Where implements ArrayAccess
{
    protected $where = [];
    protected $enclose = false;
    public function __construct(array $where = [], $enclose = false)
    {
        $this->where   = $where;
        $this->enclose = $enclose;
    }
    public function enclose($enclose = true)
    {
        $this->enclose = $enclose;
        return $this;
    }
    public function parse()
    {
        $where = [];
        foreach ($this->where as $key => $val) {
            if ($val instanceof Expression) {
                $where[] = [$key, 'exp', $val];
            } elseif (is_null($val)) {
                $where[] = [$key, 'NULL', ''];
            } elseif (is_array($val)) {
                $where[] = $this->parseItem($key, $val);
            } else {
                $where[] = [$key, '=', $val];
            }
        }
        return $this->enclose ? [$where] : $where;
    }
    protected function parseItem($field, $where = [])
    {
        $op        = $where[0];
        $condition = isset($where[1]) ? $where[1] : null;
        if (is_array($op)) {
            array_unshift($where, $field);
        } elseif (is_null($condition)) {
            if (in_array(strtoupper($op), ['NULL', 'NOTNULL', 'NOT NULL'], true)) {
                $where = [$field, $op, ''];
            } elseif (in_array($op, ['=', 'eq', 'EQ', null], true)) {
                $where = [$field, 'NULL', ''];
            } elseif (in_array($op, ['<>', 'neq', 'NEQ'], true)) {
                $where = [$field, 'NOTNULL', ''];
            } else {
                $where = [$field, '=', $op];
            }
        } else {
            $where = [$field, $op, $condition];
        }
        return $where;
    }
    public function __set($name, $value)
    {
        $this->where[$name] = $value;
    }
    public function __get($name)
    {
        return isset($this->where[$name]) ? $this->where[$name] : null;
    }
    public function __isset($name)
    {
        return isset($this->where[$name]);
    }
    public function __unset($name)
    {
        unset($this->where[$name]);
    }
    public function offsetSet($name, $value)
    {
        $this->__set($name, $value);
    }
    public function offsetExists($name)
    {
        return $this->__isset($name);
    }
    public function offsetUnset($name)
    {
        $this->__unset($name);
    }
    public function offsetGet($name)
    {
        return $this->__get($name);
    }
}
