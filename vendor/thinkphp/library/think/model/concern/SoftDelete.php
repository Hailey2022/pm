<?php
namespace think\model\concern;
use think\db\Query;
trait SoftDelete
{
    protected $withTrashed = false;
    public function trashed()
    {
        $field = $this->getDeleteTimeField();
        if ($field && !empty($this->getOrigin($field))) {
            return true;
        }
        return false;
    }
    public static function withTrashed()
    {
        $model = new static();
        return $model->withTrashedData(true)->db(false);
    }
    protected function withTrashedData($withTrashed)
    {
        $this->withTrashed = $withTrashed;
        return $this;
    }
    public static function onlyTrashed()
    {
        $model = new static();
        $field = $model->getDeleteTimeField(true);
        if ($field) {
            return $model
                ->db(false)
                ->useSoftDelete($field, $model->getWithTrashedExp());
        }
        return $model->db(false);
    }
    protected function getWithTrashedExp()
    {
        return is_null($this->defaultSoftDelete) ?
        ['notnull', ''] : ['<>', $this->defaultSoftDelete];
    }
    public function delete($force = false)
    {
        if (!$this->isExists() || false === $this->trigger('before_delete', $this)) {
            return false;
        }
        $force = $force ?: $this->isForce();
        $name  = $this->getDeleteTimeField();
        if ($name && !$force) {
            $this->data($name, $this->autoWriteTimestamp($name));
            $result = $this->isUpdate()->withEvent(false)->save();
            $this->withEvent(true);
        } else {
            $where = $this->getWhere();
            $result = $this->db(false)
                ->where($where)
                ->removeOption('soft_delete')
                ->delete();
        }
        if (!empty($this->relationWrite)) {
            $this->autoRelationDelete();
        }
        $this->trigger('after_delete', $this);
        $this->exists(false);
        return true;
    }
    public static function destroy($data, $force = false)
    {
        if (empty($data) && 0 !== $data) {
            return false;
        }
        $query = (new static())->db(false);
        if (is_array($data) && key($data) !== 0) {
            $query->where($data);
            $data = null;
        } elseif ($data instanceof \Closure) {
            call_user_func_array($data, [ & $query]);
            $data = null;
        } elseif (is_null($data)) {
            return false;
        }
        $resultSet = $query->select($data);
        if ($resultSet) {
            foreach ($resultSet as $data) {
                $data->force($force)->delete();
            }
        }
        return true;
    }
    public function restore($where = [])
    {
        $name = $this->getDeleteTimeField();
        if ($name) {
            if (false === $this->trigger('before_restore')) {
                return false;
            }
            if (empty($where)) {
                $pk = $this->getPk();
                $where[] = [$pk, '=', $this->getData($pk)];
            }
            $this->db(false)
                ->where($where)
                ->useSoftDelete($name, $this->getWithTrashedExp())
                ->update([$name => $this->defaultSoftDelete]);
            $this->trigger('after_restore');
            return true;
        }
        return false;
    }
    protected function getDeleteTimeField($read = false)
    {
        $field = property_exists($this, 'deleteTime') && isset($this->deleteTime) ? $this->deleteTime : 'delete_time';
        if (false === $field) {
            return false;
        }
        if (false === strpos($field, '.')) {
            $field = '__TABLE__.' . $field;
        }
        if (!$read && strpos($field, '.')) {
            $array = explode('.', $field);
            $field = array_pop($array);
        }
        return $field;
    }
    protected function withNoTrashed($query)
    {
        $field = $this->getDeleteTimeField(true);
        if ($field) {
            $condition = is_null($this->defaultSoftDelete) ? ['null', ''] : ['=', $this->defaultSoftDelete];
            $query->useSoftDelete($field, $condition);
        }
    }
}
