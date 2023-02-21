<?php
namespace think\model\concern;
use think\Collection;
use think\Exception;
use think\Loader;
use think\Model;
use think\model\Collection as ModelCollection;
trait Conversion
{
    protected $visible = [];
    protected $hidden = [];
    protected $append = [];
    protected $resultSetType;
    public function append(array $append = [], $override = false)
    {
        $this->append = $override ? $append : array_merge($this->append, $append);
        return $this;
    }
    public function appendRelationAttr($attr, $append)
    {
        if (is_string($append)) {
            $append = explode(',', $append);
        }
        $relation = Loader::parseName($attr, 1, false);
        if (isset($this->relation[$relation])) {
            $model = $this->relation[$relation];
        } else {
            $model = $this->getRelationData($this->$relation());
        }
        if ($model instanceof Model) {
            foreach ($append as $key => $attr) {
                $key = is_numeric($key) ? $attr : $key;
                if (isset($this->data[$key])) {
                    throw new Exception('bind attr has exists:' . $key);
                } else {
                    $this->data[$key] = $model->getAttr($attr);
                }
            }
        }
        return $this;
    }
    public function hidden(array $hidden = [], $override = false)
    {
        $this->hidden = $override ? $hidden : array_merge($this->hidden, $hidden);
        return $this;
    }
    public function visible(array $visible = [], $override = false)
    {
        $this->visible = $override ? $visible : array_merge($this->visible, $visible);
        return $this;
    }
    public function toArray()
    {
        $item       = [];
        $hasVisible = false;
        foreach ($this->visible as $key => $val) {
            if (is_string($val)) {
                if (strpos($val, '.')) {
                    list($relation, $name)      = explode('.', $val);
                    $this->visible[$relation][] = $name;
                } else {
                    $this->visible[$val] = true;
                    $hasVisible          = true;
                }
                unset($this->visible[$key]);
            }
        }
        foreach ($this->hidden as $key => $val) {
            if (is_string($val)) {
                if (strpos($val, '.')) {
                    list($relation, $name)     = explode('.', $val);
                    $this->hidden[$relation][] = $name;
                } else {
                    $this->hidden[$val] = true;
                }
                unset($this->hidden[$key]);
            }
        }
        $data = array_merge($this->data, $this->relation);
        foreach ($data as $key => $val) {
            if ($val instanceof Model || $val instanceof ModelCollection) {
                if (isset($this->visible[$key]) && is_array($this->visible[$key])) {
                    $val->visible($this->visible[$key]);
                } elseif (isset($this->hidden[$key]) && is_array($this->hidden[$key])) {
                    $val->hidden($this->hidden[$key]);
                }
                if (!isset($this->hidden[$key]) || true !== $this->hidden[$key]) {
                    $item[$key] = $val->toArray();
                }
            } elseif (isset($this->visible[$key])) {
                $item[$key] = $this->getAttr($key);
            } elseif (!isset($this->hidden[$key]) && !$hasVisible) {
                $item[$key] = $this->getAttr($key);
            }
        }
        if (!empty($this->append)) {
            foreach ($this->append as $key => $name) {
                if (is_array($name)) {
                    $relation = $this->getRelation($key);
                    if (!$relation) {
                        $relation = $this->getAttr($key);
                        if ($relation) {
                            $relation->visible($name);
                        }
                    }
                    $item[$key] = $relation ? $relation->append($name)->toArray() : [];
                } elseif (strpos($name, '.')) {
                    list($key, $attr) = explode('.', $name);
                    $relation = $this->getRelation($key);
                    if (!$relation) {
                        $relation = $this->getAttr($key);
                        if ($relation) {
                            $relation->visible([$attr]);
                        }
                    }
                    $item[$key] = $relation ? $relation->append([$attr])->toArray() : [];
                } else {
                    $item[$name] = $this->getAttr($name, $item);
                }
            }
        }
        return $item;
    }
    public function toJson($options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->toArray(), $options);
    }
    public function removeRelation()
    {
        $this->relation = [];
        return $this;
    }
    public function __toString()
    {
        return $this->toJson();
    }
    public function jsonSerialize()
    {
        return $this->toArray();
    }
    public function toCollection($collection, $resultSetType = null)
    {
        $resultSetType = $resultSetType ?: $this->resultSetType;
        if ($resultSetType && false !== strpos($resultSetType, '\\')) {
            $collection = new $resultSetType($collection);
        } else {
            $collection = new ModelCollection($collection);
        }
        return $collection;
    }
}
