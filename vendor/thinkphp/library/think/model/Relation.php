<?php
namespace think\model;
use think\db\Query;
use think\Exception;
use think\Model;
abstract class Relation
{
    protected $parent;
    protected $model;
    protected $query;
    protected $foreignKey;
    protected $localKey;
    protected $baseQuery;
    protected $selfRelation;
    public function getParent()
    {
        return $this->parent;
    }
    public function getModel()
    {
        return $this->query->getModel();
    }
    public function getQuery()
    {
        return $this->query;
    }
    public function selfRelation($self = true)
    {
        $this->selfRelation = $self;
        return $this;
    }
    public function isSelfRelation()
    {
        return $this->selfRelation;
    }
    protected function resultSetBuild($resultSet)
    {
        return (new $this->model)->toCollection($resultSet);
    }
    protected function getQueryFields($model)
    {
        $fields = $this->query->getOptions('field');
        return $this->getRelationQueryFields($fields, $model);
    }
    protected function getRelationQueryFields($fields, $model)
    {
        if ($fields) {
            if (is_string($fields)) {
                $fields = explode(',', $fields);
            }
            foreach ($fields as &$field) {
                if (false === strpos($field, '.')) {
                    $field = $model . '.' . $field;
                }
            }
        } else {
            $fields = $model . '.*';
        }
        return $fields;
    }
    protected function getQueryWhere(&$where, $relation)
    {
        foreach ($where as $key => &$val) {
            if (is_string($key)) {
                $where[] = [false === strpos($key, '.') ? $relation . '.' . $key : $key, '=', $val];
                unset($where[$key]);
            } elseif (isset($val[0]) && false === strpos($val[0], '.')) {
                $val[0] = $relation . '.' . $val[0];
            }
        }
    }
    public function update(array $data = [])
    {
        return $this->query->update($data);
    }
    public function delete($data = null)
    {
        return $this->query->delete($data);
    }
    protected function baseQuery()
    {}
    public function __call($method, $args)
    {
        if ($this->query) {
            $this->baseQuery();
            $result = call_user_func_array([$this->query->getModel(), $method], $args);
            return $result === $this->query && !in_array(strtolower($method), ['fetchsql', 'fetchpdo']) ? $this : $result;
        } else {
            throw new Exception('method not exists:' . __CLASS__ . '->' . $method);
        }
    }
}
