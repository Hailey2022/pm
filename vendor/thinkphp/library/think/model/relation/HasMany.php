<?php










namespace think\model\relation;

use Closure;
use think\db\Query;
use think\Loader;
use think\Model;
use think\model\Relation;

class HasMany extends Relation
{
    
    public function __construct(Model $parent, $model, $foreignKey, $localKey)
    {
        $this->parent     = $parent;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
        $this->query      = (new $model)->db();

        if (get_class($parent) == $model) {
            $this->selfRelation = true;
        }
    }

    
    public function getRelation($subRelation = '', $closure = null)
    {
        if ($closure instanceof Closure) {
            $closure($this->query);
        }

        $list = $this->query
            ->where($this->foreignKey, $this->parent->{$this->localKey})
            ->relation($subRelation)
            ->select();

        $parent = clone $this->parent;

        foreach ($list as &$model) {
            $model->setParent($parent);
        }

        return $list;
    }

    
    public function eagerlyResultSet(&$resultSet, $relation, $subRelation, $closure)
    {
        $localKey = $this->localKey;
        $range    = [];

        foreach ($resultSet as $result) {
            
            if (isset($result->$localKey)) {
                $range[] = $result->$localKey;
            }
        }

        if (!empty($range)) {
            $where = [
                [$this->foreignKey, 'in', $range],
            ];
            $data = $this->eagerlyOneToMany($where, $relation, $subRelation, $closure);

            
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

    
    public function eagerlyResult(&$result, $relation, $subRelation, $closure)
    {
        $localKey = $this->localKey;

        if (isset($result->$localKey)) {
            $pk    = $result->$localKey;
            $where = [
                [$this->foreignKey, '=', $pk],
            ];
            $data = $this->eagerlyOneToMany($where, $relation, $subRelation, $closure);

            
            if (!isset($data[$pk])) {
                $data[$pk] = [];
            }

            foreach ($data[$pk] as &$relationModel) {
                $relationModel->setParent(clone $result);
            }

            $result->setRelation(Loader::parseName($relation), $this->resultSetBuild($data[$pk]));
        }
    }

    
    public function relationCount($result, $closure, $aggregate = 'count', $field = '*', &$name = '')
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

        return $this->query
            ->where($this->foreignKey, '=', $result->$localKey)
            ->$aggregate($field);
    }

    
    public function getRelationCountQuery($closure, $aggregate = 'count', $field = '*', &$aggregateAlias = '')
    {
        if ($closure instanceof Closure) {
            $return = $closure($this->query);

            if ($return && is_string($return)) {
                $aggregateAlias = $return;
            }
        }

        return $this->query->alias($aggregate . '_table')
            ->whereExp($aggregate . '_table.' . $this->foreignKey, '=' . $this->parent->getTable() . '.' . $this->localKey)
            ->fetchSql()
            ->$aggregate($field);
    }

    
    protected function eagerlyOneToMany($where, $relation, $subRelation = '', $closure = null)
    {
        $foreignKey = $this->foreignKey;

        $this->query->removeWhereField($this->foreignKey);

        
        if ($closure instanceof Closure) {
            $closure($this->query);
        }

        $list = $this->query->where($where)->with($subRelation)->select();

        
        $data = [];

        foreach ($list as $set) {
            $data[$set->$foreignKey][] = $set;
        }

        return $data;
    }

    
    public function save($data, $replace = true)
    {
        $model = $this->make();

        return $model->replace($replace)->save($data) ? $model : false;
    }

    
    public function make($data = [])
    {
        if ($data instanceof Model) {
            $data = $data->getData();
        }

        
        $data[$this->foreignKey] = $this->parent->{$this->localKey};

        return new $this->model($data);
    }

    
    public function saveAll($dataSet, $replace = true)
    {
        $result = [];

        foreach ($dataSet as $key => $data) {
            $result[] = $this->save($data, $replace);
        }

        return empty($result) ? false : $result;
    }

    
    public function has($operator = '>=', $count = 1, $id = '*', $joinType = 'INNER')
    {
        $table      = $this->query->getTable();
        $model      = basename(str_replace('\\', '/', get_class($this->parent)));
        $relation   = basename(str_replace('\\', '/', $this->model));
        $softDelete = $this->query->getOptions('soft_delete');

        return $this->parent->db()
            ->alias($model)
            ->field($model . '.*')
            ->join([$table => $relation], $model . '.' . $this->localKey . '=' . $relation . '.' . $this->foreignKey, $joinType)
            ->when($softDelete, function ($query) use ($softDelete, $relation) {
                $query->where($relation . strstr($softDelete[0], '.'), '=' == $softDelete[1][0] ? $softDelete[1][1] : null);
            })
            ->group($relation . '.' . $this->foreignKey)
            ->having('count(' . $id . ')' . $operator . $count);
    }

    
    public function hasWhere($where = [], $fields = null)
    {
        $table    = $this->query->getTable();
        $model    = basename(str_replace('\\', '/', get_class($this->parent)));
        $relation = basename(str_replace('\\', '/', $this->model));

        if (is_array($where)) {
            $this->getQueryWhere($where, $relation);
        }

        $fields     = $this->getRelationQueryFields($fields, $model);
        $softDelete = $this->query->getOptions('soft_delete');

        return $this->parent->db()
            ->alias($model)
            ->group($model . '.' . $this->localKey)
            ->field($fields)
            ->join([$table => $relation], $model . '.' . $this->localKey . '=' . $relation . '.' . $this->foreignKey)
            ->when($softDelete, function ($query) use ($softDelete, $relation) {
                $query->where($relation . strstr($softDelete[0], '.'), '=' == $softDelete[1][0] ? $softDelete[1][1] : null);
            })
            ->where($where);
    }

    
    protected function baseQuery()
    {
        if (empty($this->baseQuery)) {
            if (isset($this->parent->{$this->localKey})) {
                
                $this->query->where($this->foreignKey, '=', $this->parent->{$this->localKey});
            }

            $this->baseQuery = true;
        }
    }

}
