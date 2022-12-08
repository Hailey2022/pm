<?php










namespace think\model\relation;

use Closure;
use think\db\Query;
use think\Exception;
use think\Loader;
use think\Model;
use think\model\Relation;

class MorphMany extends Relation
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

        $list   = $this->query->relation($subRelation)->select();
        $parent = clone $this->parent;

        foreach ($list as &$model) {
            $model->setParent($parent);
        }

        return $list;
    }

    
    public function has($operator = '>=', $count = 1, $id = '*', $joinType = 'INNER')
    {
        throw new Exception('relation not support: has');
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
            $where = [
                [$morphKey, 'in', $range],
                [$morphType, '=', $type],
            ];
            $data = $this->eagerlyMorphToMany($where, $relation, $subRelation, $closure);

            
            $attr = Loader::parseName($relation);

            
            foreach ($resultSet as $result) {
                if (!isset($data[$result->$pk])) {
                    $data[$result->$pk] = [];
                }

                foreach ($data[$result->$pk] as &$relationModel) {
                    $relationModel->setParent(clone $result);
                    $relationModel->isUpdate(true);
                }

                $result->setRelation($attr, $this->resultSetBuild($data[$result->$pk]));
            }
        }
    }

    
    public function eagerlyResult(&$result, $relation, $subRelation, $closure)
    {
        $pk = $result->getPk();

        if (isset($result->$pk)) {
            $key   = $result->$pk;
            $where = [
                [$this->morphKey, '=', $key],
                [$this->morphType, '=', $this->type],
            ];
            $data = $this->eagerlyMorphToMany($where, $relation, $subRelation, $closure);

            if (!isset($data[$key])) {
                $data[$key] = [];
            }

            foreach ($data[$key] as &$relationModel) {
                $relationModel->setParent(clone $result);
                $relationModel->isUpdate(true);
            }

            $result->setRelation(Loader::parseName($relation), $this->resultSetBuild($data[$key]));
        }
    }

    
    public function relationCount($result, $closure, $aggregate = 'count', $field = '*', &$name = '')
    {
        $pk = $result->getPk();

        if (!isset($result->$pk)) {
            return 0;
        }

        if ($closure instanceof Closure) {
            $return = $closure($this->query);

            if ($return && is_string($return)) {
                $name = $return;
            }
        }

        return $this->query
            ->where([
                [$this->morphKey, '=', $result->$pk],
                [$this->morphType, '=', $this->type],
            ])
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

        return $this->query
            ->whereExp($this->morphKey, '=' . $this->parent->getTable() . '.' . $this->parent->getPk())
            ->where($this->morphType, '=', $this->type)
            ->fetchSql()
            ->$aggregate($field);
    }

    
    protected function eagerlyMorphToMany($where, $relation, $subRelation = '', $closure = null)
    {
        
        $this->query->removeOption('where');

        if ($closure instanceof Closure) {
            $closure($this->query);
        }

        $list     = $this->query->where($where)->with($subRelation)->select();
        $morphKey = $this->morphKey;

        
        $data = [];
        foreach ($list as $set) {
            $data[$set->$morphKey][] = $set;
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

    
    public function saveAll(array $dataSet)
    {
        $result = [];

        foreach ($dataSet as $key => $data) {
            $result[] = $this->save($data);
        }

        return empty($result) ? false : $result;
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
