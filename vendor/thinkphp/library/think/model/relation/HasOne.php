<?php










namespace think\model\relation;

use Closure;
use think\db\Query;
use think\Loader;
use think\Model;

class HasOne extends OneToOne
{
    
    public function __construct(Model $parent, $model, $foreignKey, $localKey)
    {
        $this->parent     = $parent;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
        $this->joinType   = 'INNER';
        $this->query      = (new $model)->db();

        if (get_class($parent) == $model) {
            $this->selfRelation = true;
        }
    }

    
    public function getRelation($subRelation = '', $closure = null)
    {
        $localKey = $this->localKey;

        if ($closure instanceof Closure) {
            $closure($this->query);
        }

        
        $relationModel = $this->query
            ->removeWhereField($this->foreignKey)
            ->where($this->foreignKey, $this->parent->$localKey)
            ->relation($subRelation)
            ->find();

        if ($relationModel) {
            $relationModel->setParent(clone $this->parent);
        }

        return $relationModel;
    }

    
    public function getRelationCountQuery($closure, $aggregate = 'count', $field = '*', &$aggregateAlias = '')
    {
        if ($closure instanceof Closure) {
            $return = $closure($this->query);

            if ($return && is_string($return)) {
                $aggregateAlias = $return;
            }
        }

        return $this->query
            ->whereExp($this->foreignKey, '=' . $this->parent->getTable() . '.' . $this->localKey)
            ->fetchSql()
            ->$aggregate($field);
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

    
    public function has($operator = '>=', $count = 1, $id = '*', $joinType = 'INNER')
    {
        $table      = $this->query->getTable();
        $model      = basename(str_replace('\\', '/', get_class($this->parent)));
        $relation   = basename(str_replace('\\', '/', $this->model));
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;
        $softDelete = $this->query->getOptions('soft_delete');

        return $this->parent->db()
            ->alias($model)
            ->whereExists(function ($query) use ($table, $model, $relation, $localKey, $foreignKey, $softDelete) {
                $query->table([$table => $relation])
                    ->field($relation . '.' . $foreignKey)
                    ->whereExp($model . '.' . $localKey, '=' . $relation . '.' . $foreignKey)
                    ->when($softDelete, function ($query) use ($softDelete, $relation) {
                        $query->where($relation . strstr($softDelete[0], '.'), '=' == $softDelete[1][0] ? $softDelete[1][1] : null);
                    });
            });
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
            ->field($fields)
            ->join([$table => $relation], $model . '.' . $this->localKey . '=' . $relation . '.' . $this->foreignKey, $this->joinType)
            ->when($softDelete, function ($query) use ($softDelete, $relation) {
                $query->where($relation . strstr($softDelete[0], '.'), '=' == $softDelete[1][0] ? $softDelete[1][1] : null);
            })
            ->where($where);
    }

    
    protected function eagerlySet(&$resultSet, $relation, $subRelation, $closure)
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
                [$foreignKey, 'in', $range],
            ], $foreignKey, $relation, $subRelation, $closure);

            
            $attr = Loader::parseName($relation);

            
            foreach ($resultSet as $result) {
                
                if (!isset($data[$result->$localKey])) {
                    $relationModel = null;
                } else {
                    $relationModel = $data[$result->$localKey];
                    $relationModel->setParent(clone $result);
                    $relationModel->isUpdate(true);
                }

                if (!empty($this->bindAttr)) {
                    
                    $this->bindAttr($relationModel, $result, $this->bindAttr);
                } else {
                    
                    $result->setRelation($attr, $relationModel);
                }
            }
        }
    }

    
    protected function eagerlyOne(&$result, $relation, $subRelation, $closure)
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;

        $this->query->removeWhereField($foreignKey);

        $data = $this->eagerlyWhere([
            [$foreignKey, '=', $result->$localKey],
        ], $foreignKey, $relation, $subRelation, $closure);

        
        if (!isset($data[$result->$localKey])) {
            $relationModel = null;
        } else {
            $relationModel = $data[$result->$localKey];
            $relationModel->setParent(clone $result);
            $relationModel->isUpdate(true);
        }

        if (!empty($this->bindAttr)) {
            
            $this->bindAttr($relationModel, $result, $this->bindAttr);
        } else {
            $result->setRelation(Loader::parseName($relation), $relationModel);
        }
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
