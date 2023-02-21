<?php
namespace think\model\relation;
use Closure;
use think\db\Query;
use think\Exception;
use think\Loader;
use think\Model;
use think\model\Relation;
class MorphOne extends Relation
{
    protected $morphKey;
    protected $morphType;
    protected $type;
    public function __construct(Model $parent, $model, $morphKey, $morphType, $type)
    {
        $this->parent    = $parent;
        $this->model     = $model;
        $this->type      = $type;
        $this->morphKey  = $morphKey;
        $this->morphType = $morphType;
        $this->query     = (new $model)->db();
    }
    public function getRelation($subRelation = '', $closure = null)
    {
        if ($closure instanceof Closure) {
            $closure($this->query);
        }
        $this->baseQuery();
        $relationModel = $this->query->relation($subRelation)->find();
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
    public function eagerlyResultSet(&$resultSet, $relation, $subRelation, $closure)
    {
        $morphType = $this->morphType;
        $morphKey  = $this->morphKey;
        $type      = $this->type;
        $range     = [];
        foreach ($resultSet as $result) {
            $pk = $result->getPk();
            if (isset($result->$pk)) {
                $range[] = $result->$pk;
            }
        }
        if (!empty($range)) {
            $data = $this->eagerlyMorphToOne([
                [$morphKey, 'in', $range],
                [$morphType, '=', $type],
            ], $relation, $subRelation, $closure);
            $attr = Loader::parseName($relation);
            foreach ($resultSet as $result) {
                if (!isset($data[$result->$pk])) {
                    $relationModel = null;
                } else {
                    $relationModel = $data[$result->$pk];
                    $relationModel->setParent(clone $result);
                    $relationModel->isUpdate(true);
                }
                $result->setRelation($attr, $relationModel);
            }
        }
    }
    public function eagerlyResult(&$result, $relation, $subRelation, $closure)
    {
        $pk = $result->getPk();
        if (isset($result->$pk)) {
            $pk   = $result->$pk;
            $data = $this->eagerlyMorphToOne([
                [$this->morphKey, '=', $pk],
                [$this->morphType, '=', $this->type],
            ], $relation, $subRelation, $closure);
            if (isset($data[$pk])) {
                $relationModel = $data[$pk];
                $relationModel->setParent(clone $result);
                $relationModel->isUpdate(true);
            } else {
                $relationModel = null;
            }
            $result->setRelation(Loader::parseName($relation), $relationModel);
        }
    }
    protected function eagerlyMorphToOne($where, $relation, $subRelation = '', $closure = null)
    {
        if ($closure instanceof Closure) {
            $closure($this->query);
        }
        $list     = $this->query->where($where)->with($subRelation)->select();
        $morphKey = $this->morphKey;
        $data = [];
        foreach ($list as $set) {
            $data[$set->$morphKey] = $set;
        }
        return $data;
    }
    public function save($data)
    {
        $model = $this->make();
        return $model->save($data) ? $model : false;
    }
    public function make($data = [])
    {
        if ($data instanceof Model) {
            $data = $data->getData();
        }
        $pk = $this->parent->getPk();
        $data[$this->morphKey]  = $this->parent->$pk;
        $data[$this->morphType] = $this->type;
        return new $this->model($data);
    }
    protected function baseQuery()
    {
        if (empty($this->baseQuery) && $this->parent->getData()) {
            $pk = $this->parent->getPk();
            $this->query->where([
                [$this->morphKey, '=', $this->parent->$pk],
                [$this->morphType, '=', $this->type],
            ]);
            $this->baseQuery = true;
        }
    }
}
