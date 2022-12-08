<?php










namespace think\model\concern;

use think\Collection;
use think\db\Query;
use think\Exception;
use think\Loader;
use think\Model;
use think\model\Relation;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;
use think\model\relation\HasManyThrough;
use think\model\relation\HasOne;
use think\model\relation\MorphMany;
use think\model\relation\MorphOne;
use think\model\relation\MorphTo;


trait RelationShip
{
    
    private $parent;

    
    private $relation = [];

    
    private $together;

    
    protected $relationWrite;

    
    public function setParent($model)
    {
        $this->parent = $model;

        return $this;
    }

    
    public function getParent()
    {
        return $this->parent;
    }

    
    public function getRelation($name = null)
    {
        if (is_null($name)) {
            return $this->relation;
        } elseif (array_key_exists($name, $this->relation)) {
            return $this->relation[$name];
        }
        return;
    }

    
    public function setRelation($name, $value, $data = [])
    {
        
        $method = 'set' . Loader::parseName($name, 1) . 'Attr';

        if (method_exists($this, $method)) {
            $value = $this->$method($value, array_merge($this->data, $data));
        }

        $this->relation[$name] = $value;

        return $this;
    }

    
    public function bindAttr($relation, array $attrs = [])
    {
        $relation = $this->getRelation($relation);

        foreach ($attrs as $key => $attr) {
            $key   = is_numeric($key) ? $attr : $key;
            $value = $this->getOrigin($key);

            if (!is_null($value)) {
                throw new Exception('bind attr has exists:' . $key);
            }

            $this->setAttr($key, $relation ? $relation->getAttr($attr) : null);
        }

        return $this;
    }

    
    public function together($relation)
    {
        if (is_string($relation)) {
            $relation = explode(',', $relation);
        }

        $this->together = $relation;

        $this->checkAutoRelationWrite();

        return $this;
    }

    
    public static function has($relation, $operator = '>=', $count = 1, $id = '*', $joinType = 'INNER')
    {
        $relation = (new static())->$relation();

        if (is_array($operator) || $operator instanceof \Closure) {
            return $relation->hasWhere($operator);
        }

        return $relation->has($operator, $count, $id, $joinType);
    }

    
    public static function hasWhere($relation, $where = [], $fields = '*')
    {
        return (new static())->$relation()->hasWhere($where, $fields);
    }

    
    public function relationQuery($relations, $withRelationAttr = [])
    {
        if (is_string($relations)) {
            $relations = explode(',', $relations);
        }

        foreach ($relations as $key => $relation) {
            $subRelation = '';
            $closure     = null;

            if ($relation instanceof \Closure) {
                
                $closure  = $relation;
                $relation = $key;
            }

            if (is_array($relation)) {
                $subRelation = $relation;
                $relation    = $key;
            } elseif (strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);
            }

            $method       = Loader::parseName($relation, 1, false);
            $relationName = Loader::parseName($relation);

            $relationResult = $this->$method();

            if (isset($withRelationAttr[$relationName])) {
                $relationResult->getQuery()->withAttr($withRelationAttr[$relationName]);
            }

            $this->relation[$relation] = $relationResult->getRelation($subRelation, $closure);
        }

        return $this;
    }

    
    public function eagerlyResultSet(&$resultSet, $relation, $withRelationAttr = [], $join = false)
    {
        $relations = is_string($relation) ? explode(',', $relation) : $relation;

        foreach ($relations as $key => $relation) {
            $subRelation = '';
            $closure     = null;

            if ($relation instanceof \Closure) {
                $closure  = $relation;
                $relation = $key;
            }

            if (is_array($relation)) {
                $subRelation = $relation;
                $relation    = $key;
            } elseif (strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);
            }

            $relation     = Loader::parseName($relation, 1, false);
            $relationName = Loader::parseName($relation);

            $relationResult = $this->$relation();

            if (isset($withRelationAttr[$relationName])) {
                $relationResult->getQuery()->withAttr($withRelationAttr[$relationName]);
            }

            $relationResult->eagerlyResultSet($resultSet, $relation, $subRelation, $closure, $join);
        }
    }

    
    public function eagerlyResult(&$result, $relation, $withRelationAttr = [], $join = false)
    {
        $relations = is_string($relation) ? explode(',', $relation) : $relation;

        foreach ($relations as $key => $relation) {
            $subRelation = '';
            $closure     = null;

            if ($relation instanceof \Closure) {
                $closure  = $relation;
                $relation = $key;
            }

            if (is_array($relation)) {
                $subRelation = $relation;
                $relation    = $key;
            } elseif (strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);
            }

            $relation     = Loader::parseName($relation, 1, false);
            $relationName = Loader::parseName($relation);

            $relationResult = $this->$relation();

            if (isset($withRelationAttr[$relationName])) {
                $relationResult->getQuery()->withAttr($withRelationAttr[$relationName]);
            }

            $relationResult->eagerlyResult($result, $relation, $subRelation, $closure, $join);
        }
    }

    
    public function relationCount(&$result, $relations, $aggregate = 'sum', $field = '*')
    {
        foreach ($relations as $key => $relation) {
            $closure = $name = null;

            if ($relation instanceof \Closure) {
                $closure  = $relation;
                $relation = $key;
            } elseif (is_string($key)) {
                $name     = $relation;
                $relation = $key;
            }

            $relation = Loader::parseName($relation, 1, false);

            $count = $this->$relation()->relationCount($result, $closure, $aggregate, $field, $name);

            if (empty($name)) {
                $name = Loader::parseName($relation) . '_' . $aggregate;
            }

            $result->setAttr($name, $count);
        }
    }

    
    public function hasOne($model, $foreignKey = '', $localKey = '')
    {
        
        $model      = $this->parseModel($model);
        $localKey   = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);

        return new HasOne($this, $model, $foreignKey, $localKey);
    }

    
    public function belongsTo($model, $foreignKey = '', $localKey = '')
    {
        
        $model      = $this->parseModel($model);
        $foreignKey = $foreignKey ?: $this->getForeignKey((new $model)->getName());
        $localKey   = $localKey ?: (new $model)->getPk();
        $trace      = debug_backtrace(false, 2);
        $relation   = Loader::parseName($trace[1]['function']);

        return new BelongsTo($this, $model, $foreignKey, $localKey, $relation);
    }

    
    public function hasMany($model, $foreignKey = '', $localKey = '')
    {
        
        $model      = $this->parseModel($model);
        $localKey   = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);

        return new HasMany($this, $model, $foreignKey, $localKey);
    }

    
    public function hasManyThrough($model, $through, $foreignKey = '', $throughKey = '', $localKey = '')
    {
        
        $model      = $this->parseModel($model);
        $through    = $this->parseModel($through);
        $localKey   = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);
        $throughKey = $throughKey ?: $this->getForeignKey((new $through)->getName());

        return new HasManyThrough($this, $model, $through, $foreignKey, $throughKey, $localKey);
    }

    
    public function belongsToMany($model, $table = '', $foreignKey = '', $localKey = '')
    {
        
        $model      = $this->parseModel($model);
        $name       = Loader::parseName(basename(str_replace('\\', '/', $model)));
        $table      = $table ?: Loader::parseName($this->name) . '_' . $name;
        $foreignKey = $foreignKey ?: $name . '_id';
        $localKey   = $localKey ?: $this->getForeignKey($this->name);

        return new BelongsToMany($this, $model, $table, $foreignKey, $localKey);
    }

    
    public function morphOne($model, $morph = null, $type = '')
    {
        
        $model = $this->parseModel($model);

        if (is_null($morph)) {
            $trace = debug_backtrace(false, 2);
            $morph = Loader::parseName($trace[1]['function']);
        }

        if (is_array($morph)) {
            list($morphType, $foreignKey) = $morph;
        } else {
            $morphType  = $morph . '_type';
            $foreignKey = $morph . '_id';
        }

        $type = $type ?: get_class($this);

        return new MorphOne($this, $model, $foreignKey, $morphType, $type);
    }

    
    public function morphMany($model, $morph = null, $type = '')
    {
        
        $model = $this->parseModel($model);

        if (is_null($morph)) {
            $trace = debug_backtrace(false, 2);
            $morph = Loader::parseName($trace[1]['function']);
        }

        $type = $type ?: get_class($this);

        if (is_array($morph)) {
            list($morphType, $foreignKey) = $morph;
        } else {
            $morphType  = $morph . '_type';
            $foreignKey = $morph . '_id';
        }

        return new MorphMany($this, $model, $foreignKey, $morphType, $type);
    }

    
    public function morphTo($morph = null, $alias = [])
    {
        $trace    = debug_backtrace(false, 2);
        $relation = Loader::parseName($trace[1]['function']);

        if (is_null($morph)) {
            $morph = $relation;
        }

        
        if (is_array($morph)) {
            list($morphType, $foreignKey) = $morph;
        } else {
            $morphType  = $morph . '_type';
            $foreignKey = $morph . '_id';
        }

        return new MorphTo($this, $morphType, $foreignKey, $alias, $relation);
    }

    
    protected function parseModel($model)
    {
        if (false === strpos($model, '\\')) {
            $path = explode('\\', static::class);
            array_pop($path);
            array_push($path, Loader::parseName($model, 1));
            $model = implode('\\', $path);
        }

        return $model;
    }

    
    protected function getForeignKey($name)
    {
        if (strpos($name, '\\')) {
            $name = basename(str_replace('\\', '/', $name));
        }

        return Loader::parseName($name) . '_id';
    }

    
    protected function isRelationAttr($attr)
    {
        $relation = Loader::parseName($attr, 1, false);

        if (method_exists($this, $relation) && !method_exists('think\Model', $relation)) {
            return $relation;
        }

        return false;
    }

    
    protected function getRelationData(Relation $modelRelation)
    {
        if ($this->parent && !$modelRelation->isSelfRelation() && get_class($this->parent) == get_class($modelRelation->getModel())) {
            $value = $this->parent;
        } else {
            
            $value = $modelRelation->getRelation();
        }

        return $value;
    }

    
    protected function checkAutoRelationWrite()
    {
        foreach ($this->together as $key => $name) {
            if (is_array($name)) {
                if (key($name) === 0) {
                    $this->relationWrite[$key] = [];
                    
                    foreach ((array) $name as $val) {
                        if (isset($this->data[$val])) {
                            $this->relationWrite[$key][$val] = $this->data[$val];
                        }
                    }
                } else {
                    
                    $this->relationWrite[$key] = $name;
                }
            } elseif (isset($this->relation[$name])) {
                $this->relationWrite[$name] = $this->relation[$name];
            } elseif (isset($this->data[$name])) {
                $this->relationWrite[$name] = $this->data[$name];
                unset($this->data[$name]);
            }
        }
    }

    
    protected function autoRelationUpdate()
    {
        foreach ($this->relationWrite as $name => $val) {
            if ($val instanceof Model) {
                $val->isUpdate()->save();
            } else {
                $model = $this->getRelation($name);
                if ($model instanceof Model) {
                    $model->isUpdate()->save($val);
                }
            }
        }
    }

    
    protected function autoRelationInsert()
    {
        foreach ($this->relationWrite as $name => $val) {
            $method = Loader::parseName($name, 1, false);
            $this->$method()->save($val);
        }
    }

    
    protected function autoRelationDelete()
    {
        foreach ($this->relationWrite as $key => $name) {
            $name   = is_numeric($key) ? $name : $key;
            $result = $this->getRelation($name);

            if ($result instanceof Model) {
                $result->delete();
            } elseif ($result instanceof Collection) {
                foreach ($result as $model) {
                    $model->delete();
                }
            }
        }
    }
}
