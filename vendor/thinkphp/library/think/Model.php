<?php
namespace think;
use InvalidArgumentException;
use think\db\Query;
abstract class Model implements \JsonSerializable, \ArrayAccess
{
    use model\concern\Attribute;
    use model\concern\RelationShip;
    use model\concern\ModelEvent;
    use model\concern\TimeStamp;
    use model\concern\Conversion;
    private $exists = false;
    private $replace = false;
    private $force = false;
    private $updateWhere;
    protected $connection = [];
    protected $query;
    protected $name;
    protected $table;
    protected $auto = [];
    protected $insert = [];
    protected $update = [];
    protected static $initialized = [];
    protected static $readMaster;
    protected $queryInstance;
    protected $error;
    protected $defaultSoftDelete;
    protected $globalScope = [];
    public function __construct($data = [])
    {
        if (is_object($data)) {
            $this->data = get_object_vars($data);
        } else {
            $this->data = $data;
        }
        if ($this->disuse) {
            foreach ((array) $this->disuse as $key) {
                if (array_key_exists($key, $this->data)) {
                    unset($this->data[$key]);
                }
            }
        }
        $this->origin = $this->data;
        $config = Db::getConfig();
        if (empty($this->name)) {
            $name       = str_replace('\\', '/', static::class);
            $this->name = basename($name);
            if (Container::get('config')->get('class_suffix')) {
                $suffix     = basename(dirname($name));
                $this->name = substr($this->name, 0, -strlen($suffix));
            }
        }
        if (is_null($this->autoWriteTimestamp)) {
            $this->autoWriteTimestamp = $config['auto_timestamp'];
        }
        if (is_null($this->dateFormat)) {
            $this->dateFormat = $config['datetime_format'];
        }
        if (is_null($this->resultSetType)) {
            $this->resultSetType = $config['resultset_type'];
        }
        if (!empty($this->connection) && is_array($this->connection)) {
            $this->connection = array_merge($config, $this->connection);
        }
        if ($this->observerClass) {
            static::observe($this->observerClass);
        }
        $this->initialize();
    }
    public function getName()
    {
        return $this->name;
    }
    public function readMaster($all = false)
    {
        $model = $all ? '*' : static::class;
        static::$readMaster[$model] = true;
        return $this;
    }
    public function newInstance($data = [], $isUpdate = false, $where = null)
    {
        return (new static($data))->isUpdate($isUpdate, $where);
    }
    protected function buildQuery()
    {
        $query = Db::connect($this->connection, false, $this->query);
        $query->model($this)
            ->name($this->name)
            ->json($this->json, $this->jsonAssoc)
            ->setJsonFieldType($this->jsonType);
        if (isset(static::$readMaster['*']) || isset(static::$readMaster[static::class])) {
            $query->master(true);
        }
        if (!empty($this->table)) {
            $query->table($this->table);
        }
        if (!empty($this->pk)) {
            $query->pk($this->pk);
        }
        return $query;
    }
    public function setQuery($query)
    {
        $this->queryInstance = $query;
        return $this;
    }
    public function db($useBaseQuery = true)
    {
        if ($this->queryInstance) {
            return $this->queryInstance;
        }
        $query = $this->buildQuery();
        if (property_exists($this, 'withTrashed') && !$this->withTrashed) {
            $this->withNoTrashed($query);
        }
        if (true === $useBaseQuery && method_exists($this, 'base')) {
            call_user_func_array([$this, 'base'], [ & $query]);
        }
        $globalScope = is_array($useBaseQuery) && $useBaseQuery ? $useBaseQuery : $this->globalScope;
        if ($globalScope && false !== $useBaseQuery) {
            $query->scope($globalScope);
        }
        return $query;
    }
    protected function initialize()
    {
        if (!isset(static::$initialized[static::class])) {
            static::$initialized[static::class] = true;
            static::init();
        }
    }
    protected static function init()
    {}
    protected function autoCompleteData($auto = [])
    {
        foreach ($auto as $field => $value) {
            if (is_integer($field)) {
                $field = $value;
                $value = null;
            }
            if (!isset($this->data[$field])) {
                $default = null;
            } else {
                $default = $this->data[$field];
            }
            $this->setAttr($field, !is_null($value) ? $value : $default);
        }
    }
    public function force($force = true)
    {
        $this->force = $force;
        return $this;
    }
    public function isForce()
    {
        return $this->force;
    }
    public function replace($replace = true)
    {
        $this->replace = $replace;
        return $this;
    }
    public function exists($exists)
    {
        $this->exists = $exists;
        return $this;
    }
    public function isExists()
    {
        return $this->exists;
    }
    public function isEmpty()
    {
        return empty($this->data);
    }
    public function save($data = [], $where = [], $sequence = null)
    {
        if (is_string($data)) {
            $sequence = $data;
            $data     = [];
        }
        if (!$this->checkBeforeSave($data, $where)) {
            return false;
        }
        $result = $this->exists ? $this->updateData($where) : $this->insertData($sequence);
        if (false === $result) {
            return false;
        }
        $this->trigger('after_write');
        $this->origin = $this->data;
        $this->set    = [];
        return true;
    }
    protected function checkBeforeSave($data, $where)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $this->setAttr($key, $value, $data);
            }
            if (!empty($where)) {
                $this->exists      = true;
                $this->updateWhere = $where;
            }
        }
        $this->autoCompleteData($this->auto);
        if (false === $this->trigger('before_write')) {
            return false;
        }
        return true;
    }
    protected function checkAllowFields(array $append = [])
    {
        if (empty($this->field) || true === $this->field) {
            $query = $this->db(false);
            $table = $this->table ?: $query->getTable();
            $this->field = $query->getConnection()->getTableFields($table);
            $field = $this->field;
        } else {
            $field = array_merge($this->field, $append);
            if ($this->autoWriteTimestamp) {
                array_push($field, $this->createTime, $this->updateTime);
            }
        }
        if ($this->disuse) {
            $field = array_diff($field, (array) $this->disuse);
        }
        return $field;
    }
    protected function updateData($where)
    {
        $this->autoCompleteData($this->update);
        if (false === $this->trigger('before_update')) {
            return false;
        }
        $data = $this->getChangedData();
        if (empty($data)) {
            if (!empty($this->relationWrite)) {
                $this->autoRelationUpdate();
            }
            return true;
        } elseif ($this->autoWriteTimestamp && $this->updateTime && !isset($data[$this->updateTime])) {
            $data[$this->updateTime] = $this->autoWriteTimestamp($this->updateTime);
            $this->data[$this->updateTime] = $data[$this->updateTime];
        }
        if (empty($where) && !empty($this->updateWhere)) {
            $where = $this->updateWhere;
        }
        $allowFields = $this->checkAllowFields(array_merge($this->auto, $this->update));
        foreach ($this->data as $key => $val) {
            if ($this->isPk($key)) {
                $data[$key] = $val;
            }
        }
        $pk    = $this->getPk();
        $array = [];
        foreach ((array) $pk as $key) {
            if (isset($data[$key])) {
                $array[] = [$key, '=', $data[$key]];
                unset($data[$key]);
            }
        }
        if (!empty($array)) {
            $where = $array;
        }
        foreach ((array) $this->relationWrite as $name => $val) {
            if (is_array($val)) {
                foreach ($val as $key) {
                    if (isset($data[$key])) {
                        unset($data[$key]);
                    }
                }
            }
        }
        $db = $this->db(false);
        $db->startTrans();
        try {
            $db->where($where)
                ->strict(false)
                ->field($allowFields)
                ->update($data);
            if (!empty($this->relationWrite)) {
                $this->autoRelationUpdate();
            }
            $db->commit();
            $this->trigger('after_update');
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    protected function insertData($sequence)
    {
        $this->autoCompleteData($this->insert);
        $this->checkTimeStampWrite();
        if (false === $this->trigger('before_insert')) {
            return false;
        }
        $allowFields = $this->checkAllowFields(array_merge($this->auto, $this->insert));
        $db = $this->db(false);
        $db->startTrans();
        try {
            $result = $db->strict(false)
                ->field($allowFields)
                ->insert($this->data, $this->replace, false, $sequence);
            if ($result && $insertId = $db->getLastInsID($sequence)) {
                $pk = $this->getPk();
                foreach ((array) $pk as $key) {
                    if (!isset($this->data[$key]) || '' == $this->data[$key]) {
                        $this->data[$key] = $insertId;
                    }
                }
            }
            if (!empty($this->relationWrite)) {
                $this->autoRelationInsert();
            }
            $db->commit();
            $this->exists = true;
            $this->trigger('after_insert');
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    public function setInc($field, $step = 1, $lazyTime = 0)
    {
        $where = $this->getWhere();
        if (false === $this->trigger('before_update')) {
            return false;
        }
        $result = $this->db(false)
            ->where($where)
            ->setInc($field, $step, $lazyTime);
        if (true !== $result) {
            $this->data[$field] += $step;
        }
        $this->trigger('after_update');
        return true;
    }
    public function setDec($field, $step = 1, $lazyTime = 0)
    {
        $where = $this->getWhere();
        if (false === $this->trigger('before_update')) {
            return false;
        }
        $result = $this->db(false)
            ->where($where)
            ->setDec($field, $step, $lazyTime);
        if (true !== $result) {
            $this->data[$field] -= $step;
        }
        $this->trigger('after_update');
        return true;
    }
    protected function getWhere()
    {
        $pk = $this->getPk();
        $where = [];
        if (is_string($pk) && isset($this->data[$pk])) {
            $where[] = [$pk, '=', $this->data[$pk]];
        } elseif (is_array($pk)) {
            foreach ($pk as $field) {
                if (isset($this->data[$field])) {
                    $where[] = [$field, '=', $this->data[$field]];
                }
            }
        }
        if (empty($where)) {
            $where = empty($this->updateWhere) ? null : $this->updateWhere;
        }
        return $where;
    }
    public function saveAll($dataSet, $replace = true)
    {
        $db = $this->db(false);
        $db->startTrans();
        try {
            $pk = $this->getPk();
            if (is_string($pk) && $replace) {
                $auto = true;
            }
            $result = [];
            foreach ($dataSet as $key => $data) {
                if ($this->exists || (!empty($auto) && isset($data[$pk]))) {
                    $result[$key] = self::update($data, [], $this->field);
                } else {
                    $result[$key] = self::create($data, $this->field, $this->replace);
                }
            }
            $db->commit();
            return $this->toCollection($result);
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    public function isUpdate($update = true, $where = null)
    {
        if (is_bool($update)) {
            $this->exists = $update;
            if (!empty($where)) {
                $this->updateWhere = $where;
            }
        } else {
            $this->exists      = true;
            $this->updateWhere = $update;
        }
        return $this;
    }
    public function delete()
    {
        if (!$this->exists || false === $this->trigger('before_delete')) {
            return false;
        }
        $where = $this->getWhere();
        $db = $this->db(false);
        $db->startTrans();
        try {
            $db->where($where)->delete();
            if (!empty($this->relationWrite)) {
                $this->autoRelationDelete();
            }
            $db->commit();
            $this->trigger('after_delete');
            $this->exists = false;
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    public function auto($fields)
    {
        $this->auto = $fields;
        return $this;
    }
    public static function create($data = [], $field = null, $replace = false)
    {
        $model = new static();
        if (!empty($field)) {
            $model->allowField($field);
        }
        $model->isUpdate(false)->replace($replace)->save($data, []);
        return $model;
    }
    public static function update($data = [], $where = [], $field = null)
    {
        $model = new static();
        if (!empty($field)) {
            $model->allowField($field);
        }
        $model->isUpdate(true)->save($data, $where);
        return $model;
    }
    public static function destroy($data)
    {
        if (empty($data) && 0 !== $data) {
            return false;
        }
        $model = new static();
        $query = $model->db();
        if (is_array($data) && key($data) !== 0) {
            $query->where($data);
            $data = null;
        } elseif ($data instanceof \Closure) {
            $data($query);
            $data = null;
        }
        $resultSet = $query->select($data);
        if ($resultSet) {
            foreach ($resultSet as $data) {
                $data->delete();
            }
        }
        return true;
    }
    public function getError()
    {
        return $this->error;
    }
    public function __wakeup()
    {
        $this->initialize();
    }
    public function __debugInfo()
    {
        return [
            'data'     => $this->data,
            'relation' => $this->relation,
        ];
    }
    public function __set($name, $value)
    {
        $this->setAttr($name, $value);
    }
    public function __get($name)
    {
        return $this->getAttr($name);
    }
    public function __isset($name)
    {
        try {
            return !is_null($this->getAttr($name));
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
    public function __unset($name)
    {
        unset($this->data[$name], $this->relation[$name]);
    }
    public function offsetSet($name, $value)
    {
        $this->setAttr($name, $value);
    }
    public function offsetExists($name)
    {
        return $this->__isset($name);
    }
    public function offsetUnset($name)
    {
        $this->__unset($name);
    }
    public function offsetGet($name)
    {
        return $this->getAttr($name);
    }
    public static function useGlobalScope($use)
    {
        $model = new static();
        return $model->db($use);
    }
    public function __call($method, $args)
    {
        if ('withattr' == strtolower($method)) {
            return call_user_func_array([$this, 'withAttribute'], $args);
        }
        return call_user_func_array([$this->db(), $method], $args);
    }
    public static function __callStatic($method, $args)
    {
        $model = new static();
        return call_user_func_array([$model->db(), $method], $args);
    }
}
