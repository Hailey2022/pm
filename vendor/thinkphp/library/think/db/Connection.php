<?php
namespace think\db;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use think\Container;
use think\Db;
use think\db\exception\BindParamException;
use think\Debug;
use think\Exception;
use think\exception\PDOException;
use think\Loader;
abstract class Connection
{
    const PARAM_FLOAT          = 21;
    protected static $instance = [];
    protected $PDOStatement;
    protected $queryStr = '';
    protected $numRows = 0;
    protected $transTimes = 0;
    protected $error = '';
    protected $links = [];
    protected $linkID;
    protected $linkRead;
    protected $linkWrite;
    protected $fetchType = PDO::FETCH_ASSOC;
    protected $attrCase = PDO::CASE_LOWER;
    protected static $event = [];
    protected static $info = [];
    protected $builderClassName;
    protected $builder;
    protected $config = [
        'type'            => '',
        'hostname'        => '',
        'database'        => '',
        'username'        => '',
        'password'        => '',
        'hostport'        => '',
        'dsn'             => '',
        'params'          => [],
        'charset'         => 'utf8',
        'prefix'          => '',
        'debug'           => false,
        'deploy'          => 0,
        'rw_separate'     => false,
        'master_num'      => 1,
        'slave_no'        => '',
        'read_master'     => false,
        'fields_strict'   => true,
        'resultset_type'  => '',
        'auto_timestamp'  => false,
        'datetime_format' => 'Y-m-d H:i:s',
        'sql_explain'     => false,
        'builder'         => '',
        'query'           => '\\think\\db\\Query',
        'break_reconnect' => false,
        'break_match_str' => [],
    ];
    protected $params = [
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES  => false,
    ];
    protected $breakMatchStr = [
        'server has gone away',
        'no connection to the server',
        'Lost connection',
        'is dead or not enabled',
        'Error while sending',
        'decryption failed or bad record mac',
        'server closed the connection unexpectedly',
        'SSL connection has been closed unexpectedly',
        'Error writing data to the connection',
        'Resource deadlock avoided',
        'failed with errno',
    ];
    protected $bind = [];
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $class = $this->getBuilderClass();
        $this->builder = new $class($this);
        $this->initialize();
    }
    protected function initialize()
    {}
    public static function instance($config = [], $name = false)
    {
        if (false === $name) {
            $name = md5(serialize($config));
        }
        if (true === $name || !isset(self::$instance[$name])) {
            if (empty($config['type'])) {
                throw new InvalidArgumentException('Undefined db type');
            }
            Container::get('app')->log('[ DB ] INIT ' . $config['type']);
            if (true === $name) {
                $name = md5(serialize($config));
            }
            self::$instance[$name] = Loader::factory($config['type'], '\\think\\db\\connector\\', $config);
        }
        return self::$instance[$name];
    }
    public function getBuilderClass()
    {
        if (!empty($this->builderClassName)) {
            return $this->builderClassName;
        }
        return $this->getConfig('builder') ?: '\\think\\db\\builder\\' . ucfirst($this->getConfig('type'));
    }
    protected function setBuilder(Builder $builder)
    {
        $this->builder = $builder;
        return $this;
    }
    public function getBuilder()
    {
        return $this->builder;
    }
    abstract protected function parseDsn($config);
    abstract public function getFields($tableName);
    abstract public function getTables($dbName);
    abstract protected function getExplain($sql);
    public function fieldCase($info)
    {
        switch ($this->attrCase) {
            case PDO::CASE_LOWER:
                $info = array_change_key_case($info);
                break;
            case PDO::CASE_UPPER:
                $info = array_change_key_case($info, CASE_UPPER);
                break;
            case PDO::CASE_NATURAL:
            default:
        }
        return $info;
    }
    public function getFieldBindType($type)
    {
        if (0 === strpos($type, 'set') || 0 === strpos($type, 'enum')) {
            $bind = PDO::PARAM_STR;
        } elseif (preg_match('/(double|float|decimal|real|numeric)/is', $type)) {
            $bind = self::PARAM_FLOAT;
        } elseif (preg_match('/(int|serial|bit)/is', $type)) {
            $bind = PDO::PARAM_INT;
        } elseif (preg_match('/bool/is', $type)) {
            $bind = PDO::PARAM_BOOL;
        } else {
            $bind = PDO::PARAM_STR;
        }
        return $bind;
    }
    public function parseSqlTable($sql)
    {
        if (false !== strpos($sql, '__')) {
            $sql = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) {
                return $this->getConfig('prefix') . strtolower($match[1]);
            }, $sql);
        }
        return $sql;
    }
    public function getTableInfo($tableName, $fetch = '')
    {
        if (is_array($tableName)) {
            $tableName = key($tableName) ?: current($tableName);
        }
        if (strpos($tableName, ',')) {
            return false;
        } else {
            $tableName = $this->parseSqlTable($tableName);
        }
        if (strpos($tableName, ')')) {
            return [];
        }
        list($tableName) = explode(' ', $tableName);
        if (false === strpos($tableName, '.')) {
            $schema = $this->getConfig('database') . '.' . $tableName;
        } else {
            $schema = $tableName;
        }
        if (!isset(self::$info[$schema])) {
            $cacheFile = Container::get('app')->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR . $schema . '.php';
            if (!$this->config['debug'] && is_file($cacheFile)) {
                $info = include $cacheFile;
            } else {
                $info = $this->getFields($tableName);
            }
            $fields = array_keys($info);
            $bind   = $type   = [];
            foreach ($info as $key => $val) {
                $type[$key] = $val['type'];
                $bind[$key] = $this->getFieldBindType($val['type']);
                if (!empty($val['primary'])) {
                    $pk[] = $key;
                }
            }
            if (isset($pk)) {
                $pk = count($pk) > 1 ? $pk : $pk[0];
            } else {
                $pk = null;
            }
            self::$info[$schema] = ['fields' => $fields, 'type' => $type, 'bind' => $bind, 'pk' => $pk];
        }
        return $fetch ? self::$info[$schema][$fetch] : self::$info[$schema];
    }
    public function getPk($tableName)
    {
        return $this->getTableInfo($tableName, 'pk');
    }
    public function getTableFields($tableName)
    {
        return $this->getTableInfo($tableName, 'fields');
    }
    public function getFieldsType($tableName, $field = null)
    {
        $result = $this->getTableInfo($tableName, 'type');
        if ($field && isset($result[$field])) {
            return $result[$field];
        }
        return $result;
    }
    public function getFieldsBind($tableName)
    {
        return $this->getTableInfo($tableName, 'bind');
    }
    public function getConfig($config = '')
    {
        return $config ? $this->config[$config] : $this->config;
    }
    public function setConfig($config, $value = '')
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        } else {
            $this->config[$config] = $value;
        }
    }
    public function connect(array $config = [], $linkNum = 0, $autoConnection = false)
    {
        if (isset($this->links[$linkNum])) {
            return $this->links[$linkNum];
        }
        if (!$config) {
            $config = $this->config;
        } else {
            $config = array_merge($this->config, $config);
        }
        if (isset($config['params']) && is_array($config['params'])) {
            $params = $config['params'] + $this->params;
        } else {
            $params = $this->params;
        }
        $this->attrCase = $params[PDO::ATTR_CASE];
        if (!empty($config['break_match_str'])) {
            $this->breakMatchStr = array_merge($this->breakMatchStr, (array) $config['break_match_str']);
        }
        try {
            if (empty($config['dsn'])) {
                $config['dsn'] = $this->parseDsn($config);
            }
            if ($config['debug']) {
                $startTime = microtime(true);
            }
            $this->links[$linkNum] = new PDO($config['dsn'], $config['username'], $config['password'], $params);
            if ($config['debug']) {
                $this->log('[ DB ] CONNECT:[ UseTime:' . number_format(microtime(true) - $startTime, 6) . 's ] ' . $config['dsn']);
            }
            return $this->links[$linkNum];
        } catch (\PDOException $e) {
            if ($autoConnection) {
                $this->log($e->getMessage(), 'error');
                return $this->connect($autoConnection, $linkNum);
            } else {
                throw $e;
            }
        }
    }
    public function free()
    {
        $this->PDOStatement = null;
    }
    public function getPdo()
    {
        if (!$this->linkID) {
            return false;
        }
        return $this->linkID;
    }
    public function getCursor($sql, $bind = [], $master = false, $model = null, $condition = null, $relation = null)
    {
        $this->initConnect($master);
        $this->queryStr = $sql;
        $this->bind = $bind;
        Db::$queryTimes++;
        $this->debug(true);
        $this->PDOStatement = $this->linkID->prepare($sql);
        $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);
        if ($procedure) {
            $this->bindParam($bind);
        } else {
            $this->bindValue($bind);
        }
        $this->PDOStatement->execute();
        $this->debug(false, '', $master);
        while ($result = $this->PDOStatement->fetch($this->fetchType)) {
            if ($model) {
                $instance = $model->newInstance($result, $condition);
                if ($relation) {
                    $instance->relationQuery($relation);
                }
                yield $instance;
            } else {
                yield $result;
            }
        }
    }
    public function query($sql, $bind = [], $master = false, $pdo = false)
    {
        $this->initConnect($master);
        if (!$this->linkID) {
            return false;
        }
        $this->queryStr = $sql;
        $this->bind = $bind;
        Db::$queryTimes++;
        try {
            $this->debug(true);
            $this->PDOStatement = $this->linkID->prepare($sql);
            $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);
            if ($procedure) {
                $this->bindParam($bind);
            } else {
                $this->bindValue($bind);
            }
            $this->PDOStatement->execute();
            $this->debug(false, '', $master);
            return $this->getResult($pdo, $procedure);
        } catch (\PDOException $e) {
            if ($this->isBreak($e)) {
                return $this->close()->query($sql, $bind, $master, $pdo);
            }
            throw new PDOException($e, $this->config, $this->getLastsql());
        } catch (\Throwable $e) {
            if ($this->isBreak($e)) {
                return $this->close()->query($sql, $bind, $master, $pdo);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($this->isBreak($e)) {
                return $this->close()->query($sql, $bind, $master, $pdo);
            }
            throw $e;
        }
    }
    public function execute($sql, $bind = [], Query $query = null)
    {
        $this->initConnect(true);
        if (!$this->linkID) {
            return false;
        }
        $this->queryStr = $sql;
        $this->bind = $bind;
        Db::$executeTimes++;
        try {
            $this->debug(true);
            $this->PDOStatement = $this->linkID->prepare($sql);
            $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);
            if ($procedure) {
                $this->bindParam($bind);
            } else {
                $this->bindValue($bind);
            }
            $this->PDOStatement->execute();
            $this->debug(false, '', true);
            if ($query && !empty($this->config['deploy']) && !empty($this->config['read_master'])) {
                $query->readMaster();
            }
            $this->numRows = $this->PDOStatement->rowCount();
            return $this->numRows;
        } catch (\PDOException $e) {
            if ($this->isBreak($e)) {
                return $this->close()->execute($sql, $bind, $query);
            }
            throw new PDOException($e, $this->config, $this->getLastsql());
        } catch (\Throwable $e) {
            if ($this->isBreak($e)) {
                return $this->close()->execute($sql, $bind, $query);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($this->isBreak($e)) {
                return $this->close()->execute($sql, $bind, $query);
            }
            throw $e;
        }
    }
    public function find(Query $query)
    {
        $options = $query->getOptions();
        $pk      = $query->getPk($options);
        $data = $options['data'];
        $query->setOption('limit', 1);
        if (empty($options['fetch_sql']) && !empty($options['cache'])) {
            $cache = $options['cache'];
            if (is_string($cache['key'])) {
                $key = $cache['key'];
            } else {
                $key = $this->getCacheKey($query, $data);
            }
            $result = Container::get('cache')->get($key);
            if (false !== $result) {
                return $result;
            }
        }
        if (is_string($pk) && !is_array($data)) {
            if (isset($key) && strpos($key, '|')) {
                list($a, $val) = explode('|', $key);
                $item[$pk]     = $val;
            } else {
                $item[$pk] = $data;
            }
            $data = $item;
        }
        $query->setOption('data', $data);
        $sql = $this->builder->select($query);
        $query->removeOption('limit');
        $bind = $query->getBind();
        if (!empty($options['fetch_sql'])) {
            return $this->getRealSql($sql, $bind);
        }
        $result = $query->trigger('before_find');
        if (!$result) {
            $resultSet = $this->query($sql, $bind, $options['master'], $options['fetch_pdo']);
            if ($resultSet instanceof \PDOStatement) {
                return $resultSet;
            }
            $result = isset($resultSet[0]) ? $resultSet[0] : null;
        }
        if (isset($cache) && $result) {
            $this->cacheData($key, $result, $cache);
        }
        return $result;
    }
    public function cursor(Query $query)
    {
        $options = $query->getOptions();
        $sql = $this->builder->select($query);
        $bind = $query->getBind();
        $condition = isset($options['where']['AND']) ? $options['where']['AND'] : null;
        $relation  = isset($options['relaltion']) ? $options['relation'] : null;
        return $this->getCursor($sql, $bind, $options['master'], $query->getModel(), $condition, $relation);
    }
    public function select(Query $query)
    {
        $options = $query->getOptions();
        if (empty($options['fetch_sql']) && !empty($options['cache'])) {
            $resultSet = $this->getCacheData($query, $options['cache'], null, $key);
            if (false !== $resultSet) {
                return $resultSet;
            }
        }
        $sql = $this->builder->select($query);
        $query->removeOption('limit');
        $bind = $query->getBind();
        if (!empty($options['fetch_sql'])) {
            return $this->getRealSql($sql, $bind);
        }
        $resultSet = $query->trigger('before_select');
        if (!$resultSet) {
            $resultSet = $this->query($sql, $bind, $options['master'], $options['fetch_pdo']);
            if ($resultSet instanceof \PDOStatement) {
                return $resultSet;
            }
        }
        if (!empty($options['cache']) && false !== $resultSet) {
            $this->cacheData($key, $resultSet, $options['cache']);
        }
        return $resultSet;
    }
    public function insert(Query $query, $replace = false, $getLastInsID = false, $sequence = null)
    {
        $options = $query->getOptions();
        $sql = $this->builder->insert($query, $replace);
        $bind = $query->getBind();
        if (!empty($options['fetch_sql'])) {
            return $this->getRealSql($sql, $bind);
        }
        $result = '' == $sql ? 0 : $this->execute($sql, $bind, $query);
        if ($result) {
            $sequence  = $sequence ?: (isset($options['sequence']) ? $options['sequence'] : null);
            $lastInsId = $this->getLastInsID($sequence);
            $data = $options['data'];
            if ($lastInsId) {
                $pk = $query->getPk($options);
                if (is_string($pk)) {
                    $data[$pk] = $lastInsId;
                }
            }
            $query->setOption('data', $data);
            $query->trigger('after_insert');
            if ($getLastInsID) {
                return $lastInsId;
            }
        }
        return $result;
    }
    public function insertAll(Query $query, $dataSet = [], $replace = false, $limit = null)
    {
        if (!is_array(reset($dataSet))) {
            return false;
        }
        $options = $query->getOptions();
        if ($limit) {
            $this->startTrans();
            try {
                $array = array_chunk($dataSet, $limit, true);
                $count = 0;
                foreach ($array as $item) {
                    $sql  = $this->builder->insertAll($query, $item, $replace);
                    $bind = $query->getBind();
                    if (!empty($options['fetch_sql'])) {
                        $fetchSql[] = $this->getRealSql($sql, $bind);
                    } else {
                        $count += $this->execute($sql, $bind, $query);
                    }
                }
                $this->commit();
            } catch (\Exception $e) {
                $this->rollback();
                throw $e;
            } catch (\Throwable $e) {
                $this->rollback();
                throw $e;
            }
            return isset($fetchSql) ? implode(';', $fetchSql) : $count;
        }
        $sql  = $this->builder->insertAll($query, $dataSet, $replace);
        $bind = $query->getBind();
        if (!empty($options['fetch_sql'])) {
            return $this->getRealSql($sql, $bind);
        }
        return $this->execute($sql, $bind, $query);
    }
    public function selectInsert(Query $query, $fields, $table)
    {
        $options = $query->getOptions();
        $table = $this->parseSqlTable($table);
        $sql = $this->builder->selectInsert($query, $fields, $table);
        $bind = $query->getBind();
        if (!empty($options['fetch_sql'])) {
            return $this->getRealSql($sql, $bind);
        }
        return $this->execute($sql, $bind, $query);
    }
    public function update(Query $query)
    {
        $options = $query->getOptions();
        if (isset($options['cache']) && is_string($options['cache']['key'])) {
            $key = $options['cache']['key'];
        }
        $pk   = $query->getPk($options);
        $data = $options['data'];
        if (empty($options['where'])) {
            if (is_string($pk) && isset($data[$pk])) {
                $where[$pk] = [$pk, '=', $data[$pk]];
                if (!isset($key)) {
                    $key = $this->getCacheKey($query, $data[$pk]);
                }
                unset($data[$pk]);
            } elseif (is_array($pk)) {
                foreach ($pk as $field) {
                    if (isset($data[$field])) {
                        $where[$field] = [$field, '=', $data[$field]];
                    } else {
                        throw new Exception('miss complex primary data');
                    }
                    unset($data[$field]);
                }
            }
            if (!isset($where)) {
                throw new Exception('miss update condition');
            } else {
                $options['where']['AND'] = $where;
                $query->setOption('where', ['AND' => $where]);
            }
        } elseif (!isset($key) && is_string($pk) && isset($options['where']['AND'])) {
            foreach ($options['where']['AND'] as $val) {
                if (is_array($val) && $val[0] == $pk) {
                    $key = $this->getCacheKey($query, $val);
                }
            }
        }
        $query->setOption('data', $data);
        $sql  = $this->builder->update($query);
        $bind = $query->getBind();
        if (!empty($options['fetch_sql'])) {
            return $this->getRealSql($sql, $bind);
        }
        $cache = Container::get('cache');
        if (isset($key) && $cache->get($key)) {
            $cache->rm($key);
        } elseif (!empty($options['cache']['tag'])) {
            $cache->clear($options['cache']['tag']);
        }
        $result = '' == $sql ? 0 : $this->execute($sql, $bind, $query);
        if ($result) {
            if (is_string($pk) && isset($where[$pk])) {
                $data[$pk] = $where[$pk];
            } elseif (is_string($pk) && isset($key) && strpos($key, '|')) {
                list($a, $val) = explode('|', $key);
                $data[$pk]     = $val;
            }
            $query->setOption('data', $data);
            $query->trigger('after_update');
        }
        return $result;
    }
    public function delete(Query $query)
    {
        $options = $query->getOptions();
        $pk      = $query->getPk($options);
        $data    = $options['data'];
        if (isset($options['cache']) && is_string($options['cache']['key'])) {
            $key = $options['cache']['key'];
        } elseif (!is_null($data) && true !== $data && !is_array($data)) {
            $key = $this->getCacheKey($query, $data);
        } elseif (is_string($pk) && isset($options['where']['AND'])) {
            foreach ($options['where']['AND'] as $val) {
                if (is_array($val) && $val[0] == $pk) {
                    $key = $this->getCacheKey($query, $val);
                }
            }
        }
        if (true !== $data && empty($options['where'])) {
            throw new Exception('delete without condition');
        }
        $sql = $this->builder->delete($query);
        $bind = $query->getBind();
        if (!empty($options['fetch_sql'])) {
            return $this->getRealSql($sql, $bind);
        }
        $cache = Container::get('cache');
        if (isset($key) && $cache->get($key)) {
            $cache->rm($key);
        } elseif (!empty($options['cache']['tag'])) {
            $cache->clear($options['cache']['tag']);
        }
        $result = $this->execute($sql, $bind, $query);
        if ($result) {
            if (!is_array($data) && is_string($pk) && isset($key) && strpos($key, '|')) {
                list($a, $val) = explode('|', $key);
                $item[$pk]     = $val;
                $data          = $item;
            }
            $options['data'] = $data;
            $query->trigger('after_delete');
        }
        return $result;
    }
    public function value(Query $query, $field, $default = null, $one = true)
    {
        $options = $query->getOptions();
        if (isset($options['field'])) {
            $query->removeOption('field');
        }
        if (is_string($field)) {
            $field = array_map('trim', explode(',', $field));
        }
        $query->setOption('field', $field);
        if (empty($options['fetch_sql']) && !empty($options['cache'])) {
            $cache  = $options['cache'];
            $result = $this->getCacheData($query, $cache, null, $key);
            if (false !== $result) {
                return $result;
            }
        }
        if ($one) {
            $query->setOption('limit', 1);
        }
        $sql = $this->builder->select($query);
        if (isset($options['field'])) {
            $query->setOption('field', $options['field']);
        } else {
            $query->removeOption('field');
        }
        $query->removeOption('limit');
        $bind = $query->getBind();
        if (!empty($options['fetch_sql'])) {
            return $this->getRealSql($sql, $bind);
        }
        $pdo = $this->query($sql, $bind, $options['master'], true);
        $result = $pdo->fetchColumn();
        if (isset($cache) && false !== $result) {
            $this->cacheData($key, $result, $cache);
        }
        return false !== $result ? $result : $default;
    }
    public function aggregate(Query $query, $aggregate, $field)
    {
        if (is_string($field) && 0 === stripos($field, 'DISTINCT ')) {
            list($distinct, $field) = explode(' ', $field);
        }
        $field = $aggregate . '(' . (!empty($distinct) ? 'DISTINCT ' : '') . $this->builder->parseKey($query, $field, true) . ') AS tp_' . strtolower($aggregate);
        return $this->value($query, $field, 0, false);
    }
    public function column(Query $query, $field, $key = '')
    {
        $options = $query->getOptions();
        if (isset($options['field'])) {
            $query->removeOption('field');
        }
        if (is_null($field)) {
            $field = ['*'];
        } elseif (is_string($field)) {
            $field = array_map('trim', explode(',', $field));
        }
        if ($key && ['*'] != $field) {
            array_unshift($field, $key);
            $field = array_unique($field);
        }
        $query->setOption('field', $field);
        if (empty($options['fetch_sql']) && !empty($options['cache'])) {
            $cache  = $options['cache'];
            $result = $this->getCacheData($query, $cache, null, $guid);
            if (false !== $result) {
                return $result;
            }
        }
        $sql = $this->builder->select($query);
        if (isset($options['field'])) {
            $query->setOption('field', $options['field']);
        } else {
            $query->removeOption('field');
        }
        $bind = $query->getBind();
        if (!empty($options['fetch_sql'])) {
            return $this->getRealSql($sql, $bind);
        }
        $pdo = $this->query($sql, $bind, $options['master'], true);
        if (1 == $pdo->columnCount()) {
            $result = $pdo->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $resultSet = $pdo->fetchAll(PDO::FETCH_ASSOC);
            if (['*'] == $field && $key) {
                $result = array_column($resultSet, null, $key);
            } elseif ($resultSet) {
                $fields = array_keys($resultSet[0]);
                $count  = count($fields);
                $key1   = array_shift($fields);
                $key2   = $fields ? array_shift($fields) : '';
                $key    = $key ?: $key1;
                if (strpos($key, '.')) {
                    list($alias, $key) = explode('.', $key);
                }
                if (2 == $count) {
                    $column = $key2;
                } elseif (1 == $count) {
                    $column = $key1;
                } else {
                    $column = null;
                }
                $result = array_column($resultSet, $column, $key);
            } else {
                $result = [];
            }
        }
        if (isset($cache) && isset($guid)) {
            $this->cacheData($guid, $result, $cache);
        }
        return $result;
    }
    public function pdo(Query $query)
    {
        $options = $query->getOptions();
        $sql = $this->builder->select($query);
        $bind = $query->getBind();
        if (!empty($options['fetch_sql'])) {
            return $this->getRealSql($sql, $bind);
        }
        return $this->query($sql, $bind, $options['master'], true);
    }
    public function getRealSql($sql, array $bind = [])
    {
        if (is_array($sql)) {
            $sql = implode(';', $sql);
        }
        foreach ($bind as $key => $val) {
            $value = is_array($val) ? $val[0] : $val;
            $type  = is_array($val) ? $val[1] : PDO::PARAM_STR;
            if ((self::PARAM_FLOAT == $type || PDO::PARAM_STR == $type) && is_string($value)) {
                $value = '\'' . addslashes($value) . '\'';
            } elseif (PDO::PARAM_INT == $type && '' === $value) {
                $value = 0;
            }
            $sql = is_numeric($key) ?
            substr_replace($sql, $value, strpos($sql, '?'), 1) :
            substr_replace($sql, $value, strpos($sql, ':' . $key), strlen(':' . $key));
        }
        return rtrim($sql);
    }
    protected function bindValue(array $bind = [])
    {
        foreach ($bind as $key => $val) {
            $param = is_int($key) ? $key + 1 : ':' . $key;
            if (is_array($val)) {
                if (PDO::PARAM_INT == $val[1] && '' === $val[0]) {
                    $val[0] = 0;
                } elseif (self::PARAM_FLOAT == $val[1]) {
                    $val[0] = is_string($val[0]) ? (float) $val[0] : $val[0];
                    $val[1] = PDO::PARAM_STR;
                }
                $result = $this->PDOStatement->bindValue($param, $val[0], $val[1]);
            } else {
                $result = $this->PDOStatement->bindValue($param, $val);
            }
            if (!$result) {
                throw new BindParamException(
                    "Error occurred  when binding parameters '{$param}'",
                    $this->config,
                    $this->getLastsql(),
                    $bind
                );
            }
        }
    }
    protected function bindParam($bind)
    {
        foreach ($bind as $key => $val) {
            $param = is_int($key) ? $key + 1 : ':' . $key;
            if (is_array($val)) {
                array_unshift($val, $param);
                $result = call_user_func_array([$this->PDOStatement, 'bindParam'], $val);
            } else {
                $result = $this->PDOStatement->bindValue($param, $val);
            }
            if (!$result) {
                $param = array_shift($val);
                throw new BindParamException(
                    "Error occurred  when binding parameters '{$param}'",
                    $this->config,
                    $this->getLastsql(),
                    $bind
                );
            }
        }
    }
    protected function getResult($pdo = false, $procedure = false)
    {
        if ($pdo) {
            return $this->PDOStatement;
        }
        if ($procedure) {
            return $this->procedure();
        }
        $result = $this->PDOStatement->fetchAll($this->fetchType);
        $this->numRows = count($result);
        return $result;
    }
    protected function procedure()
    {
        $item = [];
        do {
            $result = $this->getResult();
            if ($result) {
                $item[] = $result;
            }
        } while ($this->PDOStatement->nextRowset());
        $this->numRows = count($item);
        return $item;
    }
    public function transaction($callback)
    {
        $this->startTrans();
        try {
            $result = null;
            if (is_callable($callback)) {
                $result = call_user_func_array($callback, [$this]);
            }
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }
    public function startTransXa($xid)
    {}
    public function prepareXa($xid)
    {}
    public function commitXa($xid)
    {}
    public function rollbackXa($xid)
    {}
    public function startTrans()
    {
        $this->initConnect(true);
        if (!$this->linkID) {
            return false;
        }
        ++$this->transTimes;
        try {
            if (1 == $this->transTimes) {
                $this->linkID->beginTransaction();
            } elseif ($this->transTimes > 1 && $this->supportSavepoint()) {
                $this->linkID->exec(
                    $this->parseSavepoint('trans' . $this->transTimes)
                );
            }
        } catch (\Exception $e) {
            if ($this->isBreak($e)) {
                --$this->transTimes;
                return $this->close()->startTrans();
            }
            throw $e;
        }
    }
    public function commit()
    {
        $this->initConnect(true);
        if (1 == $this->transTimes) {
            $this->linkID->commit();
        }
        --$this->transTimes;
    }
    public function rollback()
    {
        $this->initConnect(true);
        if (1 == $this->transTimes) {
            $this->linkID->rollBack();
        } elseif ($this->transTimes > 1 && $this->supportSavepoint()) {
            $this->linkID->exec(
                $this->parseSavepointRollBack('trans' . $this->transTimes)
            );
        }
        $this->transTimes = max(0, $this->transTimes - 1);
    }
    protected function supportSavepoint()
    {
        return false;
    }
    protected function parseSavepoint($name)
    {
        return 'SAVEPOINT ' . $name;
    }
    protected function parseSavepointRollBack($name)
    {
        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }
    public function batchQuery($sqlArray = [], $bind = [])
    {
        if (!is_array($sqlArray)) {
            return false;
        }
        $this->startTrans();
        try {
            foreach ($sqlArray as $sql) {
                $this->execute($sql, $bind);
            }
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
        return true;
    }
    public function getQueryTimes($execute = false)
    {
        return $execute ? Db::$queryTimes + Db::$executeTimes : Db::$queryTimes;
    }
    public function getExecuteTimes()
    {
        return Db::$executeTimes;
    }
    public function close()
    {
        $this->linkID    = null;
        $this->linkWrite = null;
        $this->linkRead  = null;
        $this->links     = [];
        $this->free();
        return $this;
    }
    protected function isBreak($e)
    {
        if (!$this->config['break_reconnect']) {
            return false;
        }
        $error = $e->getMessage();
        foreach ($this->breakMatchStr as $msg) {
            if (false !== stripos($error, $msg)) {
                return true;
            }
        }
        return false;
    }
    public function getLastSql()
    {
        return $this->getRealSql($this->queryStr, $this->bind);
    }
    public function getLastInsID($sequence = null)
    {
        return $this->linkID->lastInsertId($sequence);
    }
    public function getNumRows()
    {
        return $this->numRows;
    }
    public function getError()
    {
        if ($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            $error = $error[1] . ':' . $error[2];
        } else {
            $error = '';
        }
        if ('' != $this->queryStr) {
            $error .= "\n [ SQL语句 ] : " . $this->getLastsql();
        }
        return $error;
    }
    protected function debug($start, $sql = '', $master = false)
    {
        if (!empty($this->config['debug'])) {
            $debug = Container::get('debug');
            if ($start) {
                $debug->remark('queryStartTime', 'time');
            } else {
                $debug->remark('queryEndTime', 'time');
                $runtime = $debug->getRangeTime('queryStartTime', 'queryEndTime');
                $sql     = $sql ?: $this->getLastsql();
                $result  = [];
                if ($this->config['sql_explain'] && 0 === stripos(trim($sql), 'select')) {
                    $result = $this->getExplain($sql);
                }
                $this->triggerSql($sql, $runtime, $result, $master);
            }
        }
    }
    public function listen($callback)
    {
        self::$event[] = $callback;
    }
    protected function triggerSql($sql, $runtime, $explain = [], $master = false)
    {
        if (!empty(self::$event)) {
            foreach (self::$event as $callback) {
                if (is_callable($callback)) {
                    call_user_func_array($callback, [$sql, $runtime, $explain, $master]);
                }
            }
        } else {
            if ($this->config['deploy']) {
                $master = $master ? 'master|' : 'slave|';
            } else {
                $master = '';
            }
            $this->log('[ SQL ] ' . $sql . ' [ ' . $master . 'RunTime:' . $runtime . 's ]');
            if (!empty($explain)) {
                $this->log('[ EXPLAIN : ' . var_export($explain, true) . ' ]');
            }
        }
    }
    public function log($log, $type = 'sql')
    {
        $this->config['debug'] && Container::get('log')->record($log, $type);
    }
    protected function initConnect($master = true)
    {
        if (!empty($this->config['deploy'])) {
            if ($master || $this->transTimes) {
                if (!$this->linkWrite) {
                    $this->linkWrite = $this->multiConnect(true);
                }
                $this->linkID = $this->linkWrite;
            } else {
                if (!$this->linkRead) {
                    $this->linkRead = $this->multiConnect(false);
                }
                $this->linkID = $this->linkRead;
            }
        } elseif (!$this->linkID) {
            $this->linkID = $this->connect();
        }
    }
    protected function multiConnect($master = false)
    {
        $_config = [];
        foreach (['username', 'password', 'hostname', 'hostport', 'database', 'dsn', 'charset'] as $name) {
            $_config[$name] = is_string($this->config[$name]) ? explode(',', $this->config[$name]) : $this->config[$name];
        }
        $m = floor(mt_rand(0, $this->config['master_num'] - 1));
        if ($this->config['rw_separate']) {
            if ($master) 
            {
                $r = $m;
            } elseif (is_numeric($this->config['slave_no'])) {
                $r = $this->config['slave_no'];
            } else {
                $r = floor(mt_rand($this->config['master_num'], count($_config['hostname']) - 1));
            }
        } else {
            $r = floor(mt_rand(0, count($_config['hostname']) - 1));
        }
        $dbMaster = false;
        if ($m != $r) {
            $dbMaster = [];
            foreach (['username', 'password', 'hostname', 'hostport', 'database', 'dsn', 'charset'] as $name) {
                $dbMaster[$name] = isset($_config[$name][$m]) ? $_config[$name][$m] : $_config[$name][0];
            }
        }
        $dbConfig = [];
        foreach (['username', 'password', 'hostname', 'hostport', 'database', 'dsn', 'charset'] as $name) {
            $dbConfig[$name] = isset($_config[$name][$r]) ? $_config[$name][$r] : $_config[$name][0];
        }
        return $this->connect($dbConfig, $r, $r == $m ? false : $dbMaster);
    }
    public function __destruct()
    {
        $this->close();
    }
    protected function cacheData($key, $data, $config = [])
    {
        $cache = Container::get('cache');
        if (isset($config['tag'])) {
            $cache->tag($config['tag'])->set($key, $data, $config['expire']);
        } else {
            $cache->set($key, $data, $config['expire']);
        }
    }
    protected function getCacheData(Query $query, $cache, $data, &$key = null)
    {
        $key = is_string($cache['key']) ? $cache['key'] : $this->getCacheKey($query, $data);
        return Container::get('cache')->get($key);
    }
    protected function getCacheKey(Query $query, $value)
    {
        if (is_scalar($value)) {
            $data = $value;
        } elseif (is_array($value) && isset($value[1], $value[2]) && in_array($value[1], ['=', 'eq'], true) && is_scalar($value[2])) {
            $data = $value[2];
        }
        $prefix = 'think:' . $this->getConfig('database') . '.';
        if (isset($data)) {
            return $prefix . $query->getTable() . '|' . $data;
        }
        try {
            return md5($prefix . serialize($query->getOptions()) . serialize($query->getBind(false)));
        } catch (\Exception $e) {
            throw new Exception('closure not support cache(true)');
        }
    }
}
