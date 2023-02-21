<?php
namespace think\model\relation;
use Closure;
use think\Collection;
use think\db\Query;
use think\Exception;
use think\Loader;
use think\Model;
use think\model\Pivot;
use think\model\Relation;
use think\Paginator;
class BelongsToMany extends Relation
{
    protected $middle;
    protected $pivotName;
    protected $pivotDataName = 'pivot';
    protected $pivot;
    public function __construct(Model $parent, $model, $table, $foreignKey, $localKey)
    {
        $this->parent     = $parent;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
        if (false !== strpos($table, '\\')) {
            $this->pivotName = $table;
            $this->middle    = basename(str_replace('\\', '/', $table));
        } else {
            $this->middle = $table;
        }
        $this->query = (new $model)->db();
        $this->pivot = $this->newPivot();
    }
    public function pivot($pivot)
    {
        $this->pivotName = $pivot;
        return $this;
    }
    public function pivotDataName($name)
    {
        $this->pivotDataName = $name;
        return $this;
    }
    protected function getUpdateWhere($data)
    {
        return [
            $this->localKey   => $data[$this->localKey],
            $this->foreignKey => $data[$this->foreignKey],
        ];
    }
    protected function newPivot($data = [], $isUpdate = false)
    {
        $class = $this->pivotName ?: '\\think\\model\\Pivot';
        $pivot = new $class($data, $this->parent, $this->middle);
        if ($pivot instanceof Pivot) {
            return $isUpdate ? $pivot->isUpdate(true, $this->getUpdateWhere($data)) : $pivot;
        }
        throw new Exception('pivot model must extends: \think\model\Pivot');
    }
    protected function hydratePivot($models)
    {
        foreach ($models as $model) {
            $pivot = [];
            foreach ($model->getData() as $key => $val) {
                if (strpos($key, '__')) {
                    list($name, $attr) = explode('__', $key, 2);
                    if ('pivot' == $name) {
                        $pivot[$attr] = $val;
                        unset($model->$key);
                    }
                }
            }
            $model->setRelation($this->pivotDataName, $this->newPivot($pivot, true));
        }
    }
    protected function buildQuery()
    {
        $foreignKey = $this->foreignKey;
        $localKey   = $this->localKey;
        $pk = $this->parent->getPk();
        $condition[] = ['pivot.' . $localKey, '=', $this->parent->$pk];
        return $this->belongsToManyQuery($foreignKey, $localKey, $condition);
    }
    public function getRelation($subRelation = '', $closure = null)
    {
        if ($closure instanceof Closure) {
            $closure($this->query);
        }
        $result = $this->buildQuery()->relation($subRelation)->select();
        $this->hydratePivot($result);
        return $result;
    }
    public function select($data = null)
    {
        $result = $this->buildQuery()->select($data);
        $this->hydratePivot($result);
        return $result;
    }
    public function paginate($listRows = null, $simple = false, $config = [])
    {
        $result = $this->buildQuery()->paginate($listRows, $simple, $config);
        $this->hydratePivot($result);
        return $result;
    }
    public function find($data = null)
    {
        $result = $this->buildQuery()->find($data);
        if ($result) {
            $this->hydratePivot([$result]);
        }
        return $result;
    }
    public function selectOrFail($data = null)
    {
        return $this->failException(true)->select($data);
    }
    public function findOrFail($data = null)
    {
        return $this->failException(true)->find($data);
    }
    public function has($operator = '>=', $count = 1, $id = '*', $joinType = 'INNER')
    {
        return $this->parent;
    }
    public function hasWhere($where = [], $fields = null)
    {
        throw new Exception('relation not support: hasWhere');
    }
    public function wherePivot($field, $op = null, $condition = null)
    {
        $this->query->where('pivot.' . $field, $op, $condition);
        return $this;
    }
    public function eagerlyResultSet(&$resultSet, $relation, $subRelation, $closure)
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;
        $pk    = $resultSet[0]->getPk();
        $range = [];
        foreach ($resultSet as $result) {
            if (isset($result->$pk)) {
                $range[] = $result->$pk;
            }
        }
        if (!empty($range)) {
            $data = $this->eagerlyManyToMany([
                ['pivot.' . $localKey, 'in', $range],
            ], $relation, $subRelation, $closure);
            $attr = Loader::parseName($relation);
            foreach ($resultSet as $result) {
                if (!isset($data[$result->$pk])) {
                    $data[$result->$pk] = [];
                }
                $result->setRelation($attr, $this->resultSetBuild($data[$result->$pk]));
            }
        }
    }
    public function eagerlyResult(&$result, $relation, $subRelation, $closure)
    {
        $pk = $result->getPk();
        if (isset($result->$pk)) {
            $pk = $result->$pk;
            $data = $this->eagerlyManyToMany([
                ['pivot.' . $this->localKey, '=', $pk],
            ], $relation, $subRelation, $closure);
            if (!isset($data[$pk])) {
                $data[$pk] = [];
            }
            $result->setRelation(Loader::parseName($relation), $this->resultSetBuild($data[$pk]));
        }
    }
    public function relationCount($result, $closure, $aggregate = 'count', $field = '*', &$name = '')
    {
        $pk = $result->getPk();
        if (!isset($result->$pk)) {
            return 0;
        }
        $pk = $result->$pk;
        if ($closure instanceof Closure) {
            $return = $closure($this->query);
            if ($return && is_string($return)) {
                $name = $return;
            }
        }
        return $this->belongsToManyQuery($this->foreignKey, $this->localKey, [
            ['pivot.' . $this->localKey, '=', $pk],
        ])->$aggregate($field);
    }
    public function getRelationCountQuery($closure, $aggregate = 'count', $field = '*', &$aggregateAlias = '')
    {
        if ($closure instanceof Closure) {
            $return = $closure($this->query);
            if ($return && is_string($return)) {
                $aggregateAlias = $return;
            }
        }
        return $this->belongsToManyQuery($this->foreignKey, $this->localKey, [
            [
                'pivot.' . $this->localKey, 'exp', $this->query->raw('=' . $this->parent->getTable() . '.' . $this->parent->getPk()),
            ],
        ])->fetchSql()->$aggregate($field);
    }
    protected function eagerlyManyToMany($where, $relation, $subRelation = '', $closure = null)
    {
        if ($closure instanceof Closure) {
            $closure($this->query);
        }
        $list = $this->belongsToManyQuery($this->foreignKey, $this->localKey, $where)
            ->with($subRelation)
            ->select();
        $data = [];
        foreach ($list as $set) {
            $pivot = [];
            foreach ($set->getData() as $key => $val) {
                if (strpos($key, '__')) {
                    list($name, $attr) = explode('__', $key, 2);
                    if ('pivot' == $name) {
                        $pivot[$attr] = $val;
                        unset($set->$key);
                    }
                }
            }
            $set->setRelation($this->pivotDataName, $this->newPivot($pivot, true));
            $data[$pivot[$this->localKey]][] = $set;
        }
        return $data;
    }
    protected function belongsToManyQuery($foreignKey, $localKey, $condition = [])
    {
        $tableName = $this->query->getTable();
        $table     = $this->pivot->getTable();
        $fields    = $this->getQueryFields($tableName);
        $query = $this->query
            ->field($fields)
            ->field(true, false, $table, 'pivot', 'pivot__');
        if (empty($this->baseQuery)) {
            $relationFk = $this->query->getPk();
            $query->join([$table => 'pivot'], 'pivot.' . $foreignKey . '=' . $tableName . '.' . $relationFk)
                ->where($condition);
        }
        return $query;
    }
    public function save($data, array $pivot = [])
    {
        return $this->attach($data, $pivot);
    }
    public function saveAll(array $dataSet, array $pivot = [], $samePivot = false)
    {
        $result = [];
        foreach ($dataSet as $key => $data) {
            if (!$samePivot) {
                $pivotData = isset($pivot[$key]) ? $pivot[$key] : [];
            } else {
                $pivotData = $pivot;
            }
            $result[] = $this->attach($data, $pivotData);
        }
        return empty($result) ? false : $result;
    }
    public function attach($data, $pivot = [])
    {
        if (is_array($data)) {
            if (key($data) === 0) {
                $id = $data;
            } else {
                $model = new $this->model;
                $id    = $model->insertGetId($data);
            }
        } elseif (is_numeric($data) || is_string($data)) {
            $id = $data;
        } elseif ($data instanceof Model) {
            $relationFk = $data->getPk();
            $id         = $data->$relationFk;
        }
        if ($id) {
            $pk                     = $this->parent->getPk();
            $pivot[$this->localKey] = $this->parent->$pk;
            $ids                    = (array) $id;
            foreach ($ids as $id) {
                $pivot[$this->foreignKey] = $id;
                $this->pivot->replace()
                    ->exists(false)
                    ->data([])
                    ->save($pivot);
                $result[] = $this->newPivot($pivot, true);
            }
            if (count($result) == 1) {
                $result = $result[0];
            }
            return $result;
        } else {
            throw new Exception('miss relation data');
        }
    }
    public function attached($data)
    {
        if ($data instanceof Model) {
            $id = $data->getKey();
        } else {
            $id = $data;
        }
        $pivot = $this->pivot
            ->where($this->localKey, $this->parent->getKey())
            ->where($this->foreignKey, $id)
            ->find();
        return $pivot ?: false;
    }
    public function detach($data = null, $relationDel = false)
    {
        if (is_array($data)) {
            $id = $data;
        } elseif (is_numeric($data) || is_string($data)) {
            $id = $data;
        } elseif ($data instanceof Model) {
            $relationFk = $data->getPk();
            $id         = $data->$relationFk;
        }
        $pk      = $this->parent->getPk();
        $pivot[] = [$this->localKey, '=', $this->parent->$pk];
        if (isset($id)) {
            $pivot[] = [$this->foreignKey, is_array($id) ? 'in' : '=', $id];
        }
        $result = $this->pivot->where($pivot)->delete();
        if (isset($id) && $relationDel) {
            $model = $this->model;
            $model::destroy($id);
        }
        return $result;
    }
    public function sync($ids, $detaching = true)
    {
        $changes = [
            'attached' => [],
            'detached' => [],
            'updated'  => [],
        ];
        $pk = $this->parent->getPk();
        $current = $this->pivot
            ->where($this->localKey, $this->parent->$pk)
            ->column($this->foreignKey);
        $records = [];
        foreach ($ids as $key => $value) {
            if (!is_array($value)) {
                $records[$value] = [];
            } else {
                $records[$key] = $value;
            }
        }
        $detach = array_diff($current, array_keys($records));
        if ($detaching && count($detach) > 0) {
            $this->detach($detach);
            $changes['detached'] = $detach;
        }
        foreach ($records as $id => $attributes) {
            if (!in_array($id, $current)) {
                $this->attach($id, $attributes);
                $changes['attached'][] = $id;
            } elseif (count($attributes) > 0 && $this->attach($id, $attributes)) {
                $changes['updated'][] = $id;
            }
        }
        return $changes;
    }
    protected function baseQuery()
    {
        if (empty($this->baseQuery) && $this->parent->getData()) {
            $pk    = $this->parent->getPk();
            $table = $this->pivot->getTable();
            $this->query
                ->join([$table => 'pivot'], 'pivot.' . $this->foreignKey . '=' . $this->query->getTable() . '.' . $this->query->getPk())
                ->where('pivot.' . $this->localKey, $this->parent->$pk);
            $this->baseQuery = true;
        }
    }
}
