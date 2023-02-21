<?php
namespace think\model\relation;
use Closure;
use think\db\Query;
use think\Loader;
use think\Model;
use think\model\Relation;
class HasManyThrough extends Relation
{
    protected $throughKey;
    protected $through;
    protected $throughPk;
    public function __construct(Model $parent, $model, $through, $foreignKey, $throughKey, $localKey)
    {
        $this->parent     = $parent;
        $this->model      = $model;
        $this->through    = (new $through)->db();
        $this->foreignKey = $foreignKey;
        $this->throughKey = $throughKey;
        $this->throughPk  = $this->through->getPk();
        $this->localKey   = $localKey;
        $this->query      = (new $model)->db();
    }
    public function getRelation($subRelation = '', $closure = null)
    {
        if ($closure instanceof Closure) {
            $closure($this->query);
        }
        $this->baseQuery();
        return $this->query->relation($subRelation)->select();
    }
    public function has($operator = '>=', $count = 1, $id = '*', $joinType = 'INNER')
    {
        $model         = Loader::parseName(basename(str_replace('\\', '/', get_class($this->parent))));
        $throughTable  = $this->through->getTable();
        $pk            = $this->throughPk;
        $throughKey    = $this->throughKey;
        $relation      = (new $this->model)->db();
        $relationTable = $relation->getTable();
        $softDelete    = $this->query->getOptions('soft_delete');
        if ('*' != $id) {
            $id = $relationTable . '.' . $relation->getPk();
        }
        return $this->parent->db()
            ->alias($model)
            ->field($model . '.*')
            ->join($throughTable, $throughTable . '.' . $this->foreignKey . '=' . $model . '.' . $this->localKey)
            ->join($relationTable, $relationTable . '.' . $throughKey . '=' . $throughTable . '.' . $this->throughPk)
            ->when($softDelete, function ($query) use ($softDelete, $relationTable) {
                $query->where($relationTable . strstr($softDelete[0], '.'), '=' == $softDelete[1][0] ? $softDelete[1][1] : null);
            })
            ->group($relationTable . '.' . $this->throughKey)
            ->having('count(' . $id . ')' . $operator . $count);
    }
    public function hasWhere($where = [], $fields = null)
    {
        $model        = Loader::parseName(basename(str_replace('\\', '/', get_class($this->parent))));
        $throughTable = $this->through->getTable();
        $pk           = $this->throughPk;
        $throughKey   = $this->throughKey;
        $modelTable   = (new $this->model)->db()->getTable();
        if (is_array($where)) {
            $this->getQueryWhere($where, $modelTable);
        }
        $fields     = $this->getRelationQueryFields($fields, $model);
        $softDelete = $this->query->getOptions('soft_delete');
        return $this->parent->db()
            ->alias($model)
            ->join($throughTable, $throughTable . '.' . $this->foreignKey . '=' . $model . '.' . $this->localKey)
            ->join($modelTable, $modelTable . '.' . $throughKey . '=' . $throughTable . '.' . $this->throughPk)
            ->when($softDelete, function ($query) use ($softDelete, $modelTable) {
                $query->where($modelTable . strstr($softDelete[0], '.'), '=' == $softDelete[1][0] ? $softDelete[1][1] : null);
            })
            ->group($modelTable . '.' . $this->throughKey)
            ->where($where)
            ->field($fields);
    }
    public function eagerlyResultSet(array &$resultSet, $relation, $subRelation = '', $closure = null)
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;
        $range = [];
        foreach ($resultSet as $result) {
            if (isset($result->$localKey)) {
                $range[] = $result->$localKey;
            }
        }
        if (!empty($range)) {
            $this->query->removeWhereField($foreignKey);
            $data = $this->eagerlyWhere([
                [$this->foreignKey, 'in', $range],
            ], $foreignKey, $relation, $subRelation, $closure);
            $attr = Loader::parseName($relation);
            foreach ($resultSet as $result) {
                $pk = $result->$localKey;
                if (!isset($data[$pk])) {
                    $data[$pk] = [];
                }
                foreach ($data[$pk] as &$relationModel) {
                    $relationModel->setParent(clone $result);
                }
                $result->setRelation($attr, $this->resultSetBuild($data[$pk]));
            }
        }
    }
    public function eagerlyResult($result, $relation, $subRelation = '', $closure = null)
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;
        $pk         = $result->$localKey;
        $this->query->removeWhereField($foreignKey);
        $data = $this->eagerlyWhere([
            [$foreignKey, '=', $pk],
        ], $foreignKey, $relation, $subRelation, $closure);
        if (!isset($data[$pk])) {
            $data[$pk] = [];
        }
        foreach ($data[$pk] as &$relationModel) {
            $relationModel->setParent(clone $result);
        }
        $result->setRelation(Loader::parseName($relation), $this->resultSetBuild($data[$pk]));
    }
    protected function eagerlyWhere(array $where, $key, $relation, $subRelation = '', $closure = null)
    {
        $throughList = $this->through->where($where)->select();
        $keys        = $throughList->column($this->throughPk, $this->throughPk);
        if ($closure instanceof Closure) {
            $closure($this->query);
        }
        $list = $this->query->where($this->throughKey, 'in', $keys)->select();
        $data = [];
        $keys = $throughList->column($this->foreignKey, $this->throughPk);
        foreach ($list as $set) {
            $data[$keys[$set->{$this->throughKey}]][] = $set;
        }
        return $data;
    }
    public function relationCount($result, $closure, $aggregate = 'count', $field = '*', &$name = null)
    {
        $localKey = $this->localKey;
        if (!isset($result->$localKey)) {
            return 0;
        }
        if ($closure instanceof Closure) {
            $return = $closure($this->query);
            if ($return && is_string($return)) {
                $name = $return;
            }
        }
        $alias        = Loader::parseName(basename(str_replace('\\', '/', $this->model)));
        $throughTable = $this->through->getTable();
        $pk           = $this->throughPk;
        $throughKey   = $this->throughKey;
        $modelTable   = $this->parent->getTable();
        if (false === strpos($field, '.')) {
            $field = $alias . '.' . $field;
        }
        return $this->query
            ->alias($alias)
            ->join($throughTable, $throughTable . '.' . $pk . '=' . $alias . '.' . $throughKey)
            ->join($modelTable, $modelTable . '.' . $this->localKey . '=' . $throughTable . '.' . $this->foreignKey)
            ->where($throughTable . '.' . $this->foreignKey, $result->$localKey)
            ->$aggregate($field);
    }
    public function getRelationCountQuery($closure = null, $aggregate = 'count', $field = '*', &$name = null)
    {
        if ($closure instanceof Closure) {
            $return = $closure($this->query);
            if ($return && is_string($return)) {
                $name = $return;
            }
        }
        $alias        = Loader::parseName(basename(str_replace('\\', '/', $this->model)));
        $throughTable = $this->through->getTable();
        $pk           = $this->throughPk;
        $throughKey   = $this->throughKey;
        $modelTable   = $this->parent->getTable();
        if (false === strpos($field, '.')) {
            $field = $alias . '.' . $field;
        }
        return $this->query
            ->alias($alias)
            ->join($throughTable, $throughTable . '.' . $pk . '=' . $alias . '.' . $throughKey)
            ->join($modelTable, $modelTable . '.' . $this->localKey . '=' . $throughTable . '.' . $this->foreignKey)
            ->whereExp($throughTable . '.' . $this->foreignKey, '=' . $this->parent->getTable() . '.' . $this->localKey)
            ->fetchSql()
            ->$aggregate($field);
    }
    protected function baseQuery()
    {
        if (empty($this->baseQuery) && $this->parent->getData()) {
            $alias        = Loader::parseName(basename(str_replace('\\', '/', $this->model)));
            $throughTable = $this->through->getTable();
            $pk           = $this->throughPk;
            $throughKey   = $this->throughKey;
            $modelTable   = $this->parent->getTable();
            $fields       = $this->getQueryFields($alias);
            $this->query
                ->field($fields)
                ->alias($alias)
                ->join($throughTable, $throughTable . '.' . $pk . '=' . $alias . '.' . $throughKey)
                ->join($modelTable, $modelTable . '.' . $this->localKey . '=' . $throughTable . '.' . $this->foreignKey)
                ->where($throughTable . '.' . $this->foreignKey, $this->parent->{$this->localKey});
            $this->baseQuery = true;
        }
    }
}
