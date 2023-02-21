<?php
namespace think\model;
use think\Collection as BaseCollection;
use think\Model;
class Collection extends BaseCollection
{
    public function load($relation)
    {
        if (!$this->isEmpty()) {
            $item = current($this->items);
            $item->eagerlyResultSet($this->items, $relation);
        }
        return $this;
    }
    public function bindAttr($relation, array $attrs = [])
    {
        $this->each(function (Model $model) use ($relation, $attrs) {
            $model->bindAttr($relation, $attrs);
        });
        return $this;
    }
    public function hidden($hidden = [], $override = false)
    {
        $this->each(function ($model) use ($hidden, $override) {
            $model->hidden($hidden, $override);
        });
        return $this;
    }
    public function visible($visible = [], $override = false)
    {
        $this->each(function ($model) use ($visible, $override) {
            $model->visible($visible, $override);
        });
        return $this;
    }
    public function append($append = [], $override = false)
    {
        $this->each(function ($model) use ($append, $override) {
            $model && $model->append($append, $override);
        });
        return $this;
    }
    public function withAttr($name, $callback = null)
    {
        $this->each(function ($model) use ($name, $callback) {
            $model && $model->withAttribute($name, $callback);
        });
        return $this;
    }
}
