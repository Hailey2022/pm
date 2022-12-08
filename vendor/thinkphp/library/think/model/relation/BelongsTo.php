<?php










namespace think\model\relation;

use Closure;
use think\Loader;
use think\Model;

class BelongsTo extends OneToOne
{
    
    public function __construct(Model $parent, $model, $foreignKey, $localKey, $relation = null)
    {
        $this->parent     = $parent;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
        $this->joinType   = 'INNER';
        $this->query      = (new $model)->db();
        $this->relation   = $relation;

        if (get_class($parent) == $model) {
            $this->selfRelation = true;
        }
    }

    
    public function getRelation($subRelation = '', $closure = null)
    {
        if ($closure instanceof Closure) {
            $closure($this->query);
        }

        $foreignKey = $this->foreignKey;

        $relationModel = $this->query
            ->removeWhereField($this->localKey)
            ->where($this->localKey, $this->parent->$foreignKey)
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
            ->whereExp($this->localKey, '=' . $this->parent->getTable() . '.' . $this->foreignKey)
            ->fetchSql()
            ->$aggregate($field);
    }

    
    public function relationCount($result, $closure, $aggregate = 'count', $field = '*', &$name = '')
    {
        $foreignKey = $this->foreignKey;

        if (!isset($result->$foreignKey)) {
            return 0;
        }

        if ($closure instanceof Closure) {
            $return = $closure($this->query);

            if ($return && is_string($return)) {
                $name = $return;
            }
        }

        return $this->query
            ->where($this->localKey, '=', $result->$foreignKey)
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
            ->whereExists(function ($query) use ($table, $model, $relation, $localKey, $foreignKey) {
                $query->table([$table => $relation])
                    ->field($relation . '.' . $localKey)
                    ->whereExp($model . '.' . $foreignKey, '=' . $relation . '.' . $localKey)
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
            ->join([$table => $relation], $model . '.' . $this->foreignKey . '=' . $relation . '.' . $this->localKey, $this->joinType)
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
            
            if (isset($result->$foreignKey)) {
                $range[] = $result->$foreignKey;
            }
        }

        if (!empty($range)) {
            $this->query->removeWhereField($localKey);

            $data = $this->eagerlyWhere([
                [$localKey, 'in', $range],
            ], $localKey, $relation, $subRelation, $closure);

            
            $attr = Loader::parseName($relation);

            
            foreach ($resultSet as $result) {
                
                if (!isset($data[$result->$foreignKey])) {
                    $relationModel = null;
                } else {
                    $relationModel = $data[$result->$foreignKey];
                    $relationModel->setParent(clone $result);
                    $relationModel->isUpdate(true);
                }

                if (!empty($this->bindAttr)) {
                    
                    $this->bindAttr($relationModel, $result);
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

        $this->query->removeWhereField($localKey);

        $data = $this->eagerlyWhere([
            [$localKey, '=', $result->$foreignKey],
        ], $localKey, $relation, $subRelation, $closure);

        
        if (!isset($data[$result->$foreignKey])) {
            $relationModel = null;
        } else {
            $relationModel = $data[$result->$foreignKey];
            $relationModel->setParent(clone $result);
            $relationModel->isUpdate(true);
        }

        if (!empty($this->bindAttr)) {
            
            $this->bindAttr($relationModel, $result);
        } else {
            
            $result->setRelation(Loader::parseName($relation), $relationModel);
        }
    }

    
    public function associate($model)
    {
        $this->parent->setAttr($this->foreignKey, $model->getKey());
        $this->parent->save();

        return $this->parent->setRelation($this->relation, $model);
    }

    
    public function dissociate()
    {
        $this->parent->setAttr($this->foreignKey, null);
        $this->parent->save();

        return $this->parent->setRelation($this->relation, null);
    }

    
    protected function baseQuery()
    {
        if (empty($this->baseQuery)) {
            if (isset($this->parent->{$this->foreignKey})) {
                
                $this->query->where($this->localKey, '=', $this->parent->{$this->foreignKey});
            }

            $this->baseQuery = true;
        }
    }
}
