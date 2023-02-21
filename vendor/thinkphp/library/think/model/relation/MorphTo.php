<?php
namespace think\model\relation;
use Closure;
use think\Exception;
use think\Loader;
use think\Model;
use think\model\Relation;
class MorphTo extends Relation
{
    protected $morphKey;
    protected $morphType;
    protected $alias;
    protected $relation;
    public function __construct(Model $parent, $morphType, $morphKey, $alias = [], $relation = null)
    {
        $this->parent    = $parent;
        $this->morphType = $morphType;
        $this->morphKey  = $morphKey;
        $this->alias     = $alias;
        $this->relation  = $relation;
    }
    public function getModel()
    {
        $morphType = $this->morphType;
        $model     = $this->parseModel($this->parent->$morphType);
        return (new $model);
    }
    public function getRelation($subRelation = '', $closure = null)
    {
        $morphKey  = $this->morphKey;
        $morphType = $this->morphType;
        $model = $this->parseModel($this->parent->$morphType);
        $pk = $this->parent->$morphKey;
        $relationModel = (new $model)->relation($subRelation)->find($pk);
        if ($relationModel) {
            $relationModel->setParent(clone $this->parent);
        }
        return $relationModel;
    }
    public function has($operator = '>=', $count = 1, $id = '*', $joinType = 'INNER')
    {
        return $this->parent;
    }
    public function hasWhere($where = [], $fields = null)
    {
        throw new Exception('relation not support: hasWhere');
    }
    protected function parseModel($model)
    {
        if (isset($this->alias[$model])) {
            $model = $this->alias[$model];
        }
        if (false === strpos($model, '\\')) {
            $path = explode('\\', get_class($this->parent));
            array_pop($path);
            array_push($path, Loader::parseName($model, 1));
            $model = implode('\\', $path);
        }
        return $model;
    }
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }
    public function removeOption()
    {
        return $this;
    }
    public function eagerlyResultSet(&$resultSet, $relation, $subRelation, $closure)
    {
        $morphKey  = $this->morphKey;
        $morphType = $this->morphType;
        $range     = [];
        foreach ($resultSet as $result) {
            if (!empty($result->$morphKey)) {
                $range[$result->$morphType][] = $result->$morphKey;
            }
        }
        if (!empty($range)) {
            $attr = Loader::parseName($relation);
            foreach ($range as $key => $val) {
                $model = $this->parseModel($key);
                $obj   = (new $model)->db();
                $pk    = $obj->getPk();
                if ($closure instanceof \Closure) {
                    $closure($obj);
                    if ($field = $obj->getOptions('with_field')) {
                        $obj->field($field)->removeOption('with_field');
                    }
                }
                $list  = $obj->all($val, $subRelation);
                $data  = [];
                foreach ($list as $k => $vo) {
                    $data[$vo->$pk] = $vo;
                }
                foreach ($resultSet as $result) {
                    if ($key == $result->$morphType) {
                        if (!isset($data[$result->$morphKey])) {
                            $relationModel = null;
                        } else {
                            $relationModel = $data[$result->$morphKey];
                            $relationModel->setParent(clone $result);
                            $relationModel->isUpdate(true);
                        }
                        $result->setRelation($attr, $relationModel);
                    }
                }
            }
        }
    }
    public function eagerlyResult(&$result, $relation, $subRelation, $closure)
    {
        $morphKey  = $this->morphKey;
        $morphType = $this->morphType;
        $model = $this->parseModel($result->{$this->morphType});
        $this->eagerlyMorphToOne($model, $relation, $result, $subRelation);
    }
    public function relationCount($result, $closure, $aggregate = 'count', $field = '*', &$name = '')
    {}
    protected function eagerlyMorphToOne($model, $relation, &$result, $subRelation = '')
    {
        $pk   = $this->parent->{$this->morphKey};
        $data = (new $model)->with($subRelation)->find($pk);
        if ($data) {
            $data->setParent(clone $result);
            $data->isUpdate(true);
        }
        $result->setRelation(Loader::parseName($relation), $data ?: null);
    }
    public function associate($model, $type = '')
    {
        $morphKey  = $this->morphKey;
        $morphType = $this->morphType;
        $pk        = $model->getPk();
        $this->parent->setAttr($morphKey, $model->$pk);
        $this->parent->setAttr($morphType, $type ?: get_class($model));
        $this->parent->save();
        return $this->parent->setRelation($this->relation, $model);
    }
    public function dissociate()
    {
        $morphKey  = $this->morphKey;
        $morphType = $this->morphType;
        $this->parent->setAttr($morphKey, null);
        $this->parent->setAttr($morphType, null);
        $this->parent->save();
        return $this->parent->setRelation($this->relation, null);
    }
}
