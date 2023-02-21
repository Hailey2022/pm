<?php
namespace think\model\relation;
use Closure;
use think\db\Query;
use think\Exception;
use think\Loader;
use think\Model;
use think\model\Relation;
abstract class OneToOne extends Relation
{
    protected $eagerlyType = 1;
    protected $joinType;
    protected $bindAttr = [];
    protected $relation;
    public function joinType($type)
    {
        $this->joinType = $type;
        return $this;
    }
    public function eagerly(Query $query, $relation, $field, $joinType, $closure, $first)
    {
        $name = Loader::parseName(basename(str_replace('\\', '/', get_class($this->parent))));
        if ($first) {
            $table = $query->getTable();
            $query->table([$table => $name]);
            if ($query->getOptions('field')) {
                $masterField = $query->getOptions('field');
                $query->removeOption('field');
            } else {
                $masterField = true;
            }
            $query->field($masterField, false, $table, $name);
        }
        $joinTable = $this->query->getTable();
        $joinAlias = $relation;
        $joinType  = $joinType ?: $this->joinType;
        $query->via($joinAlias);
        if ($this instanceof BelongsTo) {
            $joinOn = $name . '.' . $this->foreignKey . '=' . $joinAlias . '.' . $this->localKey;
        } else {
            $joinOn = $name . '.' . $this->localKey . '=' . $joinAlias . '.' . $this->foreignKey;
        }
        if ($closure instanceof Closure) {
            $closure($query);
            if ($query->getOptions('with_field')) {
                $field = $query->getOptions('with_field');
                $query->removeOption('with_field');
            }
        }
        $query->join([$joinTable => $joinAlias], $joinOn, $joinType)
            ->field($field, false, $joinTable, $joinAlias, $relation . '__');
    }
    abstract protected function eagerlySet(&$resultSet, $relation, $subRelation, $closure);
    abstract protected function eagerlyOne(&$result, $relation, $subRelation, $closure);
    public function eagerlyResultSet(&$resultSet, $relation, $subRelation, $closure, $join = false)
    {
        if ($join || 0 == $this->eagerlyType) {
            foreach ($resultSet as $result) {
                $this->match($this->model, $relation, $result);
            }
        } else {
            $this->eagerlySet($resultSet, $relation, $subRelation, $closure);
        }
    }
    public function eagerlyResult(&$result, $relation, $subRelation, $closure, $join = false)
    {
        if (0 == $this->eagerlyType || $join) {
            $this->match($this->model, $relation, $result);
        } else {
            $this->eagerlyOne($result, $relation, $subRelation, $closure);
        }
    }
    public function save($data)
    {
        if ($data instanceof Model) {
            $data = $data->getData();
        }
        $model = new $this->model;
        $data[$this->foreignKey] = $this->parent->{$this->localKey};
        return $model->save($data) ? $model : false;
    }
    public function setEagerlyType($type)
    {
        $this->eagerlyType = $type;
        return $this;
    }
    public function getEagerlyType()
    {
        return $this->eagerlyType;
    }
    public function bind($attr)
    {
        if (is_string($attr)) {
            $attr = explode(',', $attr);
        }
        $this->bindAttr = $attr;
        return $this;
    }
    public function getBindAttr()
    {
        return $this->bindAttr;
    }
    protected function match($model, $relation, &$result)
    {
        foreach ($result->getData() as $key => $val) {
            if (strpos($key, '__')) {
                list($name, $attr) = explode('__', $key, 2);
                if ($name == $relation) {
                    $list[$name][$attr] = $val;
                    unset($result->$key);
                }
            }
        }
        if (isset($list[$relation])) {
            $array = array_unique($list[$relation]);
            if (count($array) == 1 && null === current($array)) {
                $relationModel = null;
            } else {
                $relationModel = new $model($list[$relation]);
                $relationModel->setParent(clone $result);
                $relationModel->isUpdate(true);
            }
            if (!empty($this->bindAttr)) {
                $this->bindAttr($relationModel, $result, $this->bindAttr);
            }
        } else {
            $relationModel = null;
        }
        $result->setRelation(Loader::parseName($relation), $relationModel);
    }
    protected function bindAttr($model, &$result)
    {
        foreach ($this->bindAttr as $key => $attr) {
            $key   = is_numeric($key) ? $attr : $key;
            $value = $result->getOrigin($key);
            if (!is_null($value)) {
                throw new Exception('bind attr has exists:' . $key);
            }
            $result->setAttr($key, $model ? $model->getAttr($attr) : null);
        }
    }
    protected function eagerlyWhere($where, $key, $relation, $subRelation = '', $closure = null)
    {
        if ($closure instanceof Closure) {
            $closure($this->query);
            if ($field = $this->query->getOptions('with_field')) {
                $this->query->field($field)->removeOption('with_field');
            }
        }
        $list = $this->query->where($where)->with($subRelation)->select();
        $data = [];
        foreach ($list as $set) {
            $data[$set->$key] = $set;
        }
        return $data;
    }
}
