<?php










namespace think\db;

use PDO;
use think\Collection;
use think\Container;
use think\Db;
use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\Loader;
use think\Model;
use think\model\Collection as ModelCollection;
use think\model\Relation;
use think\model\relation\OneToOne;
use think\Paginator;

class Query
{
    
    protected $connection;

    
    protected $model;

    
    protected $name = '';

    
    protected $pk;

    
    protected $prefix = '';

    
    protected $options = [];

    
    protected $bind = [];

    
    private static $event = [];

    
    private static $extend = [];

    
    protected static $readMaster = [];

    
    protected $timeRule = [
        'today'      => ['today', 'tomorrow -1second'],
        'yesterday'  => ['yesterday', 'today -1second'],
        'week'       => ['this week 00:00:00', 'next week 00:00:00 -1second'],
        'last week'  => ['last week 00:00:00', 'this week 00:00:00 -1second'],
        'month'      => ['first Day of this month 00:00:00', 'first Day of next month 00:00:00 -1second'],
        'last month' => ['first Day of last month 00:00:00', 'first Day of this month 00:00:00 -1second'],
        'year'       => ['this year 1/1', 'next year 1/1 -1second'],
        'last year'  => ['last year 1/1', 'this year 1/1 -1second'],
    ];

    
    protected $timeExp = ['d' => 'today', 'w' => 'week', 'm' => 'month', 'y' => 'year'];

    
    public function __construct(Connection $connection = null)
    {
        if (is_null($connection)) {
            $this->connection = Db::connect();
        } else {
            $this->connection = $connection;
        }

        $this->prefix = $this->connection->getConfig('prefix');
    }

    
    public function newQuery()
    {
        $query = new static($this->connection);

        if ($this->model) {
            $query->model($this->model);
        }

        if (isset($this->options['table'])) {
            $query->table($this->options['table']);
        } else {
            $query->name($this->name);
        }

        if (isset($this->options['json'])) {
            $query->json($this->options['json'], $this->options['json_assoc']);
        }

        if (isset($this->options['field_type'])) {
            $query->setJsonFieldType($this->options['field_type']);
        }

        return $query;
    }

    
    public function __call($method, $args)
    {
        if (isset(self::$extend[strtolower($method)])) {
            
            array_unshift($args, $this);

            return Container::getInstance()
                ->invoke(self::$extend[strtolower($method)], $args);
        } elseif (strtolower(substr($method, 0, 5)) == 'getby') {
            
            $field = Loader::parseName(substr($method, 5));
            return $this->where($field, '=', $args[0])->find();
        } elseif (strtolower(substr($method, 0, 10)) == 'getfieldby') {
            
            $name = Loader::parseName(substr($method, 10));
            return $this->where($name, '=', $args[0])->value($args[1]);
        } elseif (strtolower(substr($method, 0, 7)) == 'whereor') {
            $name = Loader::parseName(substr($method, 7));
            array_unshift($args, $name);
            return call_user_func_array([$this, 'whereOr'], $args);
        } elseif (strtolower(substr($method, 0, 5)) == 'where') {
            $name = Loader::parseName(substr($method, 5));
            array_unshift($args, $name);
            return call_user_func_array([$this, 'where'], $args);
        } elseif ($this->model && method_exists($this->model, 'scope' . $method)) {
            
            $method = 'scope' . $method;
            array_unshift($args, $this);

            call_user_func_array([$this->model, $method], $args);
            return $this;
        } else {
            throw new Exception('method not exist:' . ($this->model ? get_class($this->model) : static::class) . '->' . $method);
        }
    }

    
    public static function extend($method, $callback = null)
    {
        if (is_array($method)) {
            foreach ($method as $key => $val) {
                self::$extend[strtolower($key)] = $val;
            }
        } else {
            self::$extend[strtolower($method)] = $callback;
        }
    }

    
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        $this->prefix     = $this->connection->getConfig('prefix');

        return $this;
    }

    
    public function getConnection()
    {
        return $this->connection;
    }

    
    public function model(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    
    public function getModel()
    {
        return $this->model ? $this->model->setQuery($this) : null;
    }

    
    public function readMaster($all = false)
    {
        $table = $all ? '*' : $this->getTable();

        static::$readMaster[$table] = true;

        return $this;
    }

    
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    
    public function getName()
    {
        return $this->name ?: $this->model->getName();
    }

    
    public function getTable($name = '')
    {
        if (empty($name) && isset($this->options['table'])) {
            return $this->options['table'];
        }

        $name = $name ?: $this->name;

        return $this->prefix . Loader::parseName($name);
    }

    
    public function query($sql, $bind = [], $master = false, $pdo = false)
    {
        return $this->connection->query($sql, $bind, $master, $pdo);
    }

    
    public function execute($sql, $bind = [])
    {
        return $this->connection->execute($sql, $bind, $this);
    }

    
    public function listen($callback)
    {
        $this->connection->listen($callback);
    }

    
    public function getLastInsID($sequence = null)
    {
        return $this->connection->getLastInsID($sequence);
    }

    
    public function getNumRows()
    {
        return $this->connection->getNumRows();
    }

    
    public function getLastSql()
    {
        return $this->connection->getLastSql();
    }

    
    public function transactionXa($callback, array $dbs = [])
    {
        $xid = uniqid('xa');

        if (empty($dbs)) {
            $dbs[] = $this->getConnection();
        }

        foreach ($dbs as $key => $db) {
            if ($db instanceof Query) {
                $db = $db->getConnection();

                $dbs[$key] = $db;
            }

            $db->startTransXa($xid);
        }

        try {
            $result = null;
            if (is_callable($callback)) {
                $result = call_user_func_array($callback, [$this]);
            }

            foreach ($dbs as $db) {
                $db->prepareXa($xid);
            }

            foreach ($dbs as $db) {
                $db->commitXa($xid);
            }

            return $result;
        } catch (\Exception $e) {
            foreach ($dbs as $db) {
                $db->rollbackXa($xid);
            }
            throw $e;
        } catch (\Throwable $e) {
            foreach ($dbs as $db) {
                $db->rollbackXa($xid);
            }
            throw $e;
        }
    }

    
    public function transaction($callback)
    {
        return $this->connection->transaction($callback);
    }

    
    public function startTrans()
    {
        $this->connection->startTrans();
    }

    
    public function commit()
    {
        $this->connection->commit();
    }

    
    public function rollback()
    {
        $this->connection->rollback();
    }

    
    public function batchQuery($sql = [])
    {
        return $this->connection->batchQuery($sql);
    }

    
    public function getConfig($name = '')
    {
        return $this->connection->getConfig($name);
    }

    
    public function getTableFields($tableName = '')
    {
        if ('' == $tableName) {
            $tableName = isset($this->options['table']) ? $this->options['table'] : $this->getTable();
        }

        return $this->connection->getTableFields($tableName);
    }

    
    public function getFieldsType($tableName = '', $field = null)
    {
        if ('' == $tableName) {
            $tableName = isset($this->options['table']) ? $this->options['table'] : $this->getTable();
        }

        return $this->connection->getFieldsType($tableName, $field);
    }

    
    public function getPartitionTableName($data, $field, $rule = [])
    {
        
        if ($field && isset($data[$field])) {
            $value = $data[$field];
            $type  = $rule['type'];
            switch ($type) {
                case 'id':
                    
                    $step = $rule['expr'];
                    $seq  = floor($value / $step) + 1;
                    break;
                case 'year':
                    
                    if (!is_numeric($value)) {
                        $value = strtotime($value);
                    }
                    $seq = date('Y', $value) - $rule['expr'] + 1;
                    break;
                case 'mod':
                    
                    $seq = ($value % $rule['num']) + 1;
                    break;
                case 'md5':
                    
                    $seq = (ord(substr(md5($value), 0, 1)) % $rule['num']) + 1;
                    break;
                default:
                    if (function_exists($type)) {
                        
                        $value = $type($value);
                    }

                    $seq = (ord(substr($value, 0, 1)) % $rule['num']) + 1;
            }

            return $this->getTable() . '_' . $seq;
        }
        
        
        $tableName = [];
        for ($i = 0; $i < $rule['num']; $i++) {
            $tableName[] = 'SELECT * FROM ' . $this->getTable() . '_' . ($i + 1);
        }

        return ['( ' . implode(" UNION ", $tableName) . ' )' => $this->name];
    }

    
    public function value($field, $default = null)
    {
        $this->parseOptions();

        return $this->connection->value($this, $field, $default);
    }

    
    public function column($field, $key = '')
    {
        $this->parseOptions();

        return $this->connection->column($this, $field, $key);
    }

    
    public function aggregate($aggregate, $field, $force = false)
    {
        $this->parseOptions();

        $result = $this->connection->aggregate($this, $aggregate, $field);

        if (!empty($this->options['fetch_sql'])) {
            return $result;
        } elseif ($force) {
            $result = (float) $result;
        }

        return $result;
    }

    
    public function count($field = '*')
    {
        if (!empty($this->options['group'])) {
            
            $options = $this->getOptions();
            $subSql  = $this->options($options)
                ->field('count(' . $field . ') AS think_count')
                ->bind($this->bind)
                ->buildSql();

            $query = $this->newQuery()->table([$subSql => '_group_count_']);

            if (!empty($options['fetch_sql'])) {
                $query->fetchSql(true);
            }

            $count = $query->aggregate('COUNT', '*', true);
        } else {
            $count = $this->aggregate('COUNT', $field, true);
        }

        return is_string($count) ? $count : (int) $count;
    }

    
    public function sum($field)
    {
        return $this->aggregate('SUM', $field, true);
    }

    
    public function min($field, $force = true)
    {
        return $this->aggregate('MIN', $field, $force);
    }

    
    public function max($field, $force = true)
    {
        return $this->aggregate('MAX', $field, $force);
    }

    
    public function avg($field)
    {
        return $this->aggregate('AVG', $field, true);
    }

    
    public function setField($field, $value = '')
    {
        if (is_array($field)) {
            $data = $field;
        } else {
            $data[$field] = $value;
        }

        return $this->update($data);
    }

    
    public function setInc($field, $step = 1, $lazyTime = 0)
    {
        $condition = !empty($this->options['where']) ? $this->options['where'] : [];

        if (empty($condition)) {
            
            throw new Exception('no data to update');
        }

        if ($lazyTime > 0) {
            
            $guid = md5($this->getTable() . '_' . $field . '_' . serialize($condition));
            $step = $this->lazyWrite('inc', $guid, $step, $lazyTime);

            if (false === $step) {
                
                $this->options = [];
                return true;
            }
        }

        return $this->setField($field, ['INC', $step]);
    }

    
    public function setDec($field, $step = 1, $lazyTime = 0)
    {
        $condition = !empty($this->options['where']) ? $this->options['where'] : [];

        if (empty($condition)) {
            
            throw new Exception('no data to update');
        }

        if ($lazyTime > 0) {
            
            $guid = md5($this->getTable() . '_' . $field . '_' . serialize($condition));
            $step = $this->lazyWrite('dec', $guid, $step, $lazyTime);

            if (false === $step) {
                
                $this->options = [];
                return true;
            }

            $value = ['INC', $step];
        } else {
            $value = ['DEC', $step];
        }

        return $this->setField($field, $value);
    }

    
    protected function lazyWrite($type, $guid, $step, $lazyTime)
    {
        $cache = Container::get('cache');

        if (!$cache->has($guid . '_time')) {
            
            $cache->set($guid . '_time', time(), 0);
            $cache->$type($guid, $step);
        } elseif (time() > $cache->get($guid . '_time') + $lazyTime) {
            
            $value = $cache->$type($guid, $step);
            $cache->rm($guid);
            $cache->rm($guid . '_time');
            return 0 === $value ? false : $value;
        } else {
            
            $cache->$type($guid, $step);
        }

        return false;
    }

    
    public function join($join, $condition = null, $type = 'INNER', $bind = [])
    {
        if (empty($condition)) {
            
            foreach ($join as $key => $value) {
                if (is_array($value) && 2 <= count($value)) {
                    $this->join($value[0], $value[1], isset($value[2]) ? $value[2] : $type);
                }
            }
        } else {
            $table = $this->getJoinTable($join);
            if ($bind) {
                $this->bindParams($condition, $bind);
            }
            $this->options['join'][] = [$table, strtoupper($type), $condition];
        }

        return $this;
    }

    
    public function leftJoin($join, $condition = null, $bind = [])
    {
        return $this->join($join, $condition, 'LEFT');
    }

    
    public function rightJoin($join, $condition = null, $bind = [])
    {
        return $this->join($join, $condition, 'RIGHT');
    }

    
    public function fullJoin($join, $condition = null, $bind = [])
    {
        return $this->join($join, $condition, 'FULL');
    }

    
    protected function getJoinTable($join, &$alias = null)
    {
        if (is_array($join)) {
            $table = $join;
            $alias = array_shift($join);
        } else {
            $join = trim($join);

            if (false !== strpos($join, '(')) {
                
                $table = $join;
            } else {
                $prefix = $this->prefix;
                if (strpos($join, ' ')) {
                    
                    list($table, $alias) = explode(' ', $join);
                } else {
                    $table = $join;
                    if (false === strpos($join, '.') && 0 !== strpos($join, '__')) {
                        $alias = $join;
                    }
                }

                if ($prefix && false === strpos($table, '.') && 0 !== strpos($table, $prefix) && 0 !== strpos($table, '__')) {
                    $table = $this->getTable($table);
                }
            }

            if (isset($alias) && $table != $alias) {
                $table = [$table => $alias];
            }
        }

        return $table;
    }

    
    public function union($union, $all = false)
    {
        if (empty($union)) {
            return $this;
        }

        $this->options['union']['type'] = $all ? 'UNION ALL' : 'UNION';

        if (is_array($union)) {
            $this->options['union'] = array_merge($this->options['union'], $union);
        } else {
            $this->options['union'][] = $union;
        }

        return $this;
    }

    
    public function unionAll($union)
    {
        return $this->union($union, true);
    }

    
    public function field($field, $except = false, $tableName = '', $prefix = '', $alias = '')
    {
        if (empty($field)) {
            return $this;
        } elseif ($field instanceof Expression) {
            $this->options['field'][] = $field;
            return $this;
        }

        if (is_string($field)) {
            if (preg_match('/[\<\'\"\(]/', $field)) {
                return $this->fieldRaw($field);
            }

            $field = array_map('trim', explode(',', $field));
        }

        if (true === $field) {
            
            $fields = $this->getTableFields($tableName);
            $field  = $fields ?: ['*'];
        } elseif ($except) {
            
            $fields = $this->getTableFields($tableName);
            $field  = $fields ? array_diff($fields, $field) : $field;
        }

        if ($tableName) {
            
            $prefix = $prefix ?: $tableName;
            foreach ($field as $key => &$val) {
                if (is_numeric($key) && $alias) {
                    $field[$prefix . '.' . $val] = $alias . $val;
                    unset($field[$key]);
                } elseif (is_numeric($key)) {
                    $val = $prefix . '.' . $val;
                }
            }
        }

        if (isset($this->options['field'])) {
            $field = array_merge((array) $this->options['field'], $field);
        }

        $this->options['field'] = array_unique($field);

        return $this;
    }

    
    public function fieldRaw($field)
    {
        $this->options['field'][] = $this->raw($field);

        return $this;
    }

    
    public function data($field, $value = null)
    {
        if (is_array($field)) {
            $this->options['data'] = isset($this->options['data']) ? array_merge($this->options['data'], $field) : $field;
        } else {
            $this->options['data'][$field] = $value;
        }

        return $this;
    }

    
    public function inc($field, $step = 1, $op = 'INC')
    {
        $fields = is_string($field) ? explode(',', $field) : $field;

        foreach ($fields as $field => $val) {
            if (is_numeric($field)) {
                $field = $val;
            } else {
                $step = $val;
            }

            $this->data($field, [$op, $step]);
        }

        return $this;
    }

    
    public function dec($field, $step = 1)
    {
        return $this->inc($field, $step, 'DEC');
    }

    
    public function exp($field, $value)
    {
        $this->data($field, $this->raw($value));
        return $this;
    }

    
    public function raw($value)
    {
        return new Expression($value);
    }

    
    public function view($join, $field = true, $on = null, $type = 'INNER')
    {
        $this->options['view'] = true;

        if (is_array($join) && key($join) === 0) {
            foreach ($join as $key => $val) {
                $this->view($val[0], $val[1], isset($val[2]) ? $val[2] : null, isset($val[3]) ? $val[3] : 'INNER');
            }
        } else {
            $fields = [];
            $table  = $this->getJoinTable($join, $alias);

            if (true === $field) {
                $fields = $alias . '.*';
            } else {
                if (is_string($field)) {
                    $field = explode(',', $field);
                }

                foreach ($field as $key => $val) {
                    if (is_numeric($key)) {
                        $fields[] = $alias . '.' . $val;

                        $this->options['map'][$val] = $alias . '.' . $val;
                    } else {
                        if (preg_match('/[,=\.\'\"\(\s]/', $key)) {
                            $name = $key;
                        } else {
                            $name = $alias . '.' . $key;
                        }

                        $fields[] = $name . ' AS ' . $val;

                        $this->options['map'][$val] = $name;
                    }
                }
            }

            $this->field($fields);

            if ($on) {
                $this->join($table, $on, $type);
            } else {
                $this->table($table);
            }
        }

        return $this;
    }

    
    public function partition($data, $field, $rule = [])
    {
        $this->options['table'] = $this->getPartitionTableName($data, $field, $rule);

        return $this;
    }

    
    public function where($field, $op = null, $condition = null)
    {
        $param = func_get_args();
        array_shift($param);
        return $this->parseWhereExp('AND', $field, $op, $condition, $param);
    }

    
    public function whereOr($field, $op = null, $condition = null)
    {
        $param = func_get_args();
        array_shift($param);
        return $this->parseWhereExp('OR', $field, $op, $condition, $param);
    }

    
    public function whereXor($field, $op = null, $condition = null)
    {
        $param = func_get_args();
        array_shift($param);
        return $this->parseWhereExp('XOR', $field, $op, $condition, $param);
    }

    
    public function whereNull($field, $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'NULL', null, [], true);
    }

    
    public function whereNotNull($field, $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'NOTNULL', null, [], true);
    }

    
    public function whereExists($condition, $logic = 'AND')
    {
        if (is_string($condition)) {
            $condition = $this->raw($condition);
        }

        $this->options['where'][strtoupper($logic)][] = ['', 'EXISTS', $condition];
        return $this;
    }

    
    public function whereNotExists($condition, $logic = 'AND')
    {
        if (is_string($condition)) {
            $condition = $this->raw($condition);
        }

        $this->options['where'][strtoupper($logic)][] = ['', 'NOT EXISTS', $condition];
        return $this;
    }

    
    public function whereIn($field, $condition, $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'IN', $condition, [], true);
    }

    
    public function whereNotIn($field, $condition, $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'NOT IN', $condition, [], true);
    }

    
    public function whereLike($field, $condition, $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'LIKE', $condition, [], true);
    }

    
    public function whereNotLike($field, $condition, $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'NOT LIKE', $condition, [], true);
    }

    
    public function whereBetween($field, $condition, $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'BETWEEN', $condition, [], true);
    }

    
    public function whereNotBetween($field, $condition, $logic = 'AND')
    {
        return $this->parseWhereExp($logic, $field, 'NOT BETWEEN', $condition, [], true);
    }

    
    public function whereColumn($field1, $operator = null, $field2 = null, $logic = 'AND')
    {
        if (is_array($field1)) {
            foreach ($field1 as $item) {
                $this->whereColumn($item[0], $item[1], isset($item[2]) ? $item[2] : null);
            }
            return $this;
        }

        if (is_null($field2)) {
            $field2   = $operator;
            $operator = '=';
        }

        return $this->parseWhereExp($logic, $field1, 'COLUMN', [$operator, $field2], [], true);
    }

    
    public function useSoftDelete($field, $condition = null)
    {
        if ($field) {
            $this->options['soft_delete'] = [$field, $condition];
        }

        return $this;
    }

    
    public function whereExp($field, $where, $bind = [], $logic = 'AND')
    {
        if ($bind) {
            $this->bindParams($where, $bind);
        }

        $this->options['where'][$logic][] = [$field, 'EXP', $this->raw($where)];

        return $this;
    }

    
    public function whereRaw($where, $bind = [], $logic = 'AND')
    {
        if ($bind) {
            $this->bindParams($where, $bind);
        }

        $this->options['where'][$logic][] = $this->raw($where);

        return $this;
    }

    
    protected function bindParams(&$sql, array $bind = [])
    {
        foreach ($bind as $key => $value) {
            if (is_array($value)) {
                $name = $this->bind($value[0], $value[1], isset($value[2]) ? $value[2] : null);
            } else {
                $name = $this->bind($value);
            }

            if (is_numeric($key)) {
                $sql = substr_replace($sql, ':' . $name, strpos($sql, '?'), 1);
            } else {
                $sql = str_replace(':' . $key, ':' . $name, $sql);
            }
        }
    }

    
    public function whereOrRaw($where, $bind = [])
    {
        return $this->whereRaw($where, $bind, 'OR');
    }

    
    protected function parseWhereExp($logic, $field, $op, $condition, array $param = [], $strict = false)
    {
        if ($field instanceof $this) {
            $this->options['where'] = $field->getOptions('where');
            $this->bind($field->getBind(false));
            return $this;
        }

        $logic = strtoupper($logic);

        if ($field instanceof Where) {
            $this->options['where'][$logic] = $field->parse();
            return $this;
        }

        if (is_string($field) && !empty($this->options['via']) && false === strpos($field, '.')) {
            $field = $this->options['via'] . '.' . $field;
        }

        if ($field instanceof Expression) {
            return $this->whereRaw($field, is_array($op) ? $op : [], $logic);
        } elseif ($strict) {
            
            $where = [$field, $op, $condition, $logic];
        } elseif (is_array($field)) {
            
            return $this->parseArrayWhereItems($field, $logic);
        } elseif ($field instanceof \Closure) {
            $where = $field;
        } elseif (is_string($field)) {
            if (preg_match('/[,=\<\'\"\(\s]/', $field)) {
                return $this->whereRaw($field, $op, $logic);
            } elseif (is_string($op) && strtolower($op) == 'exp') {
                $bind = isset($param[2]) && is_array($param[2]) ? $param[2] : null;
                return $this->whereExp($field, $condition, $bind, $logic);
            }

            $where = $this->parseWhereItem($logic, $field, $op, $condition, $param);
        }

        if (!empty($where)) {
            $this->options['where'][$logic][] = $where;
        }

        return $this;
    }

    
    protected function parseWhereItem($logic, $field, $op, $condition, $param = [])
    {
        if (is_array($op)) {
            
            array_unshift($param, $field);
            $where = $param;
        } elseif ($field && is_null($condition)) {
            if (in_array(strtoupper($op), ['NULL', 'NOTNULL', 'NOT NULL'], true)) {
                
                $where = [$field, $op, ''];
            } elseif (in_array($op, ['=', 'eq', 'EQ', null], true)) {
                $where = [$field, 'NULL', ''];
            } elseif (in_array($op, ['<>', 'neq', 'NEQ'], true)) {
                $where = [$field, 'NOTNULL', ''];
            } else {
                
                $where = [$field, '=', $op];
            }
        } elseif (in_array(strtoupper($op), ['EXISTS', 'NOT EXISTS', 'NOTEXISTS'], true)) {
            $where = [$field, $op, is_string($condition) ? $this->raw($condition) : $condition];
        } else {
            $where = $field ? [$field, $op, $condition, isset($param[2]) ? $param[2] : null] : null;
        }

        return $where;
    }

    
    protected function parseArrayWhereItems($field, $logic)
    {
        if (key($field) !== 0) {
            $where = [];
            foreach ($field as $key => $val) {
                if ($val instanceof Expression) {
                    $where[] = [$key, 'exp', $val];
                } elseif (is_null($val)) {
                    $where[] = [$key, 'NULL', ''];
                } else {
                    $where[] = [$key, is_array($val) ? 'IN' : '=', $val];
                }
            }
        } else {
            
            $where = $field;
        }

        if (!empty($where)) {
            $this->options['where'][$logic] = isset($this->options['where'][$logic]) ? array_merge($this->options['where'][$logic], $where) : $where;
        }

        return $this;
    }

    
    public function removeWhereField($field, $logic = 'AND')
    {
        $logic = strtoupper($logic);

        if (isset($this->options['where'][$logic])) {
            foreach ($this->options['where'][$logic] as $key => $val) {
                if (is_array($val) && $val[0] == $field) {
                    unset($this->options['where'][$logic][$key]);
                }
            }
        }

        return $this;
    }

    
    public function removeOption($option = true)
    {
        if (true === $option) {
            $this->options = [];
            $this->bind    = [];
        } elseif (is_string($option) && isset($this->options[$option])) {
            unset($this->options[$option]);
        }

        return $this;
    }

    
    public function when($condition, $query, $otherwise = null)
    {
        if ($condition instanceof \Closure) {
            $condition = $condition($this);
        }

        if ($condition) {
            if ($query instanceof \Closure) {
                $query($this, $condition);
            } elseif (is_array($query)) {
                $this->where($query);
            }
        } elseif ($otherwise) {
            if ($otherwise instanceof \Closure) {
                $otherwise($this, $condition);
            } elseif (is_array($otherwise)) {
                $this->where($otherwise);
            }
        }

        return $this;
    }

    
    public function limit($offset, $length = null)
    {
        if (is_null($length) && strpos($offset, ',')) {
            list($offset, $length) = explode(',', $offset);
        }

        $this->options['limit'] = intval($offset) . ($length ? ',' . intval($length) : '');

        return $this;
    }

    
    public function page($page, $listRows = null)
    {
        if (is_null($listRows) && strpos($page, ',')) {
            list($page, $listRows) = explode(',', $page);
        }

        $this->options['page'] = [intval($page), intval($listRows)];

        return $this;
    }

    
    public function paginate($listRows = null, $simple = false, $config = [])
    {
        if (is_int($simple)) {
            $total  = $simple;
            $simple = false;
        }

        $paginate = Container::get('config')->pull('paginate');

        if (is_array($listRows)) {
            $config   = array_merge($paginate, $listRows);
            $listRows = $config['list_rows'];
        } else {
            $config   = array_merge($paginate, $config);
            $listRows = $listRows ?: $config['list_rows'];
        }

        
        $class = false !== strpos($config['type'], '\\') ? $config['type'] : '\\think\\paginator\\driver\\' . ucwords($config['type']);
        $page  = isset($config['page']) ? (int) $config['page'] : call_user_func([
            $class,
            'getCurrentPage',
        ], $config['var_page']);

        $page = $page < 1 ? 1 : $page;

        $config['path'] = isset($config['path']) ? $config['path'] : call_user_func([$class, 'getCurrentPath']);

        if (!isset($total) && !$simple) {
            $options = $this->getOptions();

            unset($this->options['order'], $this->options['limit'], $this->options['page'], $this->options['field']);

            $bind    = $this->bind;
            $total   = $this->count();
            $results = $this->options($options)->bind($bind)->page($page, $listRows)->select();
        } elseif ($simple) {
            $results = $this->limit(($page - 1) * $listRows, $listRows + 1)->select();
            $total   = null;
        } else {
            $results = $this->page($page, $listRows)->select();
        }

        $this->removeOption('limit');
        $this->removeOption('page');

        return $class::make($results, $listRows, $page, $total, $simple, $config);
    }

    
    public function table($table)
    {
        if (is_string($table)) {
            if (strpos($table, ')')) {
                
            } elseif (strpos($table, ',')) {
                $tables = explode(',', $table);
                $table  = [];

                foreach ($tables as $item) {
                    list($item, $alias) = explode(' ', trim($item));
                    if ($alias) {
                        $this->alias([$item => $alias]);
                        $table[$item] = $alias;
                    } else {
                        $table[] = $item;
                    }
                }
            } elseif (strpos($table, ' ')) {
                list($table, $alias) = explode(' ', $table);

                $table = [$table => $alias];
                $this->alias($table);
            }
        } else {
            $tables = $table;
            $table  = [];

            foreach ($tables as $key => $val) {
                if (is_numeric($key)) {
                    $table[] = $val;
                } else {
                    $this->alias([$key => $val]);
                    $table[$key] = $val;
                }
            }
        }

        $this->options['table'] = $table;

        return $this;
    }

    
    public function using($using)
    {
        $this->options['using'] = $using;
        return $this;
    }

    
    public function order($field, $order = null)
    {
        if (empty($field)) {
            return $this;
        } elseif ($field instanceof Expression) {
            $this->options['order'][] = $field;
            return $this;
        }

        if (is_string($field)) {
            if (!empty($this->options['via'])) {
                $field = $this->options['via'] . '.' . $field;
            }

            if (strpos($field, ',')) {
                $field = array_map('trim', explode(',', $field));
            } else {
                $field = empty($order) ? $field : [$field => $order];
            }
        } elseif (!empty($this->options['via'])) {
            foreach ($field as $key => $val) {
                if (is_numeric($key)) {
                    $field[$key] = $this->options['via'] . '.' . $val;
                } else {
                    $field[$this->options['via'] . '.' . $key] = $val;
                    unset($field[$key]);
                }
            }
        }

        if (!isset($this->options['order'])) {
            $this->options['order'] = [];
        }

        if (is_array($field)) {
            $this->options['order'] = array_merge($this->options['order'], $field);
        } else {
            $this->options['order'][] = $field;
        }

        return $this;
    }

    
    public function orderRaw($field, $bind = [])
    {
        if ($bind) {
            $this->bindParams($field, $bind);
        }

        $this->options['order'][] = $this->raw($field);

        return $this;
    }

    
    public function orderField($field, array $values, $order = '')
    {
        if (!empty($values)) {
            $values['sort'] = $order;

            $this->options['order'][$field] = $values;
        }

        return $this;
    }

    
    public function orderRand()
    {
        $this->options['order'][] = '[rand]';
        return $this;
    }

    
    public function cache($key = true, $expire = null, $tag = null)
    {
        
        if ($key instanceof \DateTime || (is_numeric($key) && is_null($expire))) {
            $expire = $key;
            $key    = true;
        }

        if (false !== $key) {
            $this->options['cache'] = ['key' => $key, 'expire' => $expire, 'tag' => $tag];
        }

        return $this;
    }

    
    public function group($group)
    {
        $this->options['group'] = $group;
        return $this;
    }

    
    public function having($having)
    {
        $this->options['having'] = $having;
        return $this;
    }

    
    public function lock($lock = false)
    {
        $this->options['lock']   = $lock;
        $this->options['master'] = true;

        return $this;
    }

    
    public function distinct($distinct)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }

    
    public function alias($alias)
    {
        if (is_array($alias)) {
            foreach ($alias as $key => $val) {
                if (false !== strpos($key, '__')) {
                    $table = $this->connection->parseSqlTable($key);
                } else {
                    $table = $key;
                }
                $this->options['alias'][$table] = $val;
            }
        } else {
            if (isset($this->options['table'])) {
                $table = is_array($this->options['table']) ? key($this->options['table']) : $this->options['table'];
                if (false !== strpos($table, '__')) {
                    $table = $this->connection->parseSqlTable($table);
                }
            } else {
                $table = $this->getTable();
            }

            $this->options['alias'][$table] = $alias;
        }

        return $this;
    }

    
    public function force($force)
    {
        $this->options['force'] = $force;
        return $this;
    }

    
    public function comment($comment)
    {
        $this->options['comment'] = $comment;
        return $this;
    }

    
    public function fetchSql($fetch = true)
    {
        $this->options['fetch_sql'] = $fetch;
        return $this;
    }

    
    public function fetchPdo($pdo = true)
    {
        $this->options['fetch_pdo'] = $pdo;
        return $this;
    }

    
    public function fetchCollection($collection = true)
    {
        $this->options['collection'] = $collection;

        return $this;
    }

    
    public function master()
    {
        $this->options['master'] = true;
        return $this;
    }

    
    public function strict($strict = true)
    {
        $this->options['strict'] = $strict;
        return $this;
    }

    
    public function failException($fail = true)
    {
        $this->options['fail'] = $fail;
        return $this;
    }

    
    public function sequence($sequence = null)
    {
        $this->options['sequence'] = $sequence;
        return $this;
    }

    
    public function hidden($hidden)
    {
        if ($this->model) {
            $this->options['hidden'] = $hidden;
            return $this;
        }

        return $this->field($hidden, true);
    }

    
    public function visible(array $visible)
    {
        $this->options['visible'] = $visible;
        return $this;
    }

    
    public function append(array $append = [])
    {
        $this->options['append'] = $append;
        return $this;
    }

    
    public function withAttr($name, $callback = null)
    {
        if (is_array($name)) {
            $this->options['with_attr'] = $name;
        } else {
            $this->options['with_attr'][$name] = $callback;
        }

        return $this;
    }

    
    public function json(array $json = [], $assoc = false)
    {
        $this->options['json']       = $json;
        $this->options['json_assoc'] = $assoc;
        return $this;
    }

    
    public function setJsonFieldType(array $type)
    {
        $this->options['field_type'] = $type;
        return $this;
    }

    
    public function getJsonFieldType($field)
    {
        return isset($this->options['field_type'][$field]) ? $this->options['field_type'][$field] : null;
    }

    
    public function allowEmpty($allowEmpty = true)
    {
        $this->options['allow_empty'] = $allowEmpty;
        return $this;
    }

    
    public function scope($scope, ...$args)
    {
        
        array_unshift($args, $this);

        if ($scope instanceof \Closure) {
            call_user_func_array($scope, $args);
            return $this;
        }

        if (is_string($scope)) {
            $scope = explode(',', $scope);
        }

        if ($this->model) {
            
            foreach ($scope as $name) {
                $method = 'scope' . trim($name);

                if (method_exists($this->model, $method)) {
                    call_user_func_array([$this->model, $method], $args);
                }
            }
        }

        return $this;
    }

    
    public function withSearch(array $fields, array $data = [], $prefix = '')
    {
        foreach ($fields as $key => $field) {
            if ($field instanceof \Closure) {
                $field($this, isset($data[$key]) ? $data[$key] : null, $data, $prefix);
            } elseif ($this->model) {
                
                $fieldName = is_numeric($key) ? $field : $key;
                $method    = 'search' . Loader::parseName($fieldName, 1) . 'Attr';

                if (method_exists($this->model, $method)) {
                    $this->model->$method($this, isset($data[$field]) ? $data[$field] : null, $data, $prefix);
                }
            }
        }

        return $this;
    }

    
    public function pk($pk)
    {
        $this->pk = $pk;
        return $this;
    }

    
    public function timeRule($name, $rule)
    {
        $this->timeRule[$name] = $rule;
        return $this;
    }

    
    public function whereTime($field, $op, $range = null, $logic = 'AND')
    {
        if (is_null($range)) {
            if (is_array($op)) {
                $range = $op;
            } else {
                if (isset($this->timeExp[strtolower($op)])) {
                    $op = $this->timeExp[strtolower($op)];
                }

                if (isset($this->timeRule[strtolower($op)])) {
                    $range = $this->timeRule[strtolower($op)];
                } else {
                    $range = $op;
                }
            }

            $op = is_array($range) ? 'between' : '>=';
        }

        return $this->parseWhereExp($logic, $field, strtolower($op) . ' time', $range, [], true);
    }

    
    public function whereBetweenTimeField($startField, $endField)
    {
        return $this->whereTime($startField, '<=', time())
            ->whereTime($endField, '>=', time());
    }

    
    public function whereNotBetweenTimeField($startField, $endField)
    {
        return $this->whereTime($startField, '>', time())
            ->whereTime($endField, '<', time(), 'OR');
    }

    
    public function whereBetweenTime($field, $startTime, $endTime = null, $logic = 'AND')
    {
        if (is_null($endTime)) {
            $time    = is_string($startTime) ? strtotime($startTime) : $startTime;
            $endTime = strtotime('+1 day', $time);
        }

        return $this->parseWhereExp($logic, $field, 'between time', [$startTime, $endTime], [], true);
    }

    
    public function getPk($options = '')
    {
        if (!empty($this->pk)) {
            $pk = $this->pk;
        } else {
            $pk = $this->connection->getPk(is_array($options) && isset($options['table']) ? $options['table'] : $this->getTable());
        }

        return $pk;
    }

    
    public function bind($value, $type = PDO::PARAM_STR, $name = null)
    {
        if (is_array($value)) {
            $this->bind = array_merge($this->bind, $value);
        } else {
            $name = $name ?: 'ThinkBind_' . (count($this->bind) + 1) . '_' . mt_rand() . '_';

            $this->bind[$name] = [$value, $type];
            return $name;
        }

        return $this;
    }

    
    public function isBind($key)
    {
        return isset($this->bind[$key]);
    }

    
    public function option($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    
    protected function options(array $options)
    {
        $this->options = $options;
        return $this;
    }

    
    public function getOptions($name = '')
    {
        if ('' === $name) {
            return $this->options;
        }
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    
    public function with($with)
    {
        if (empty($with)) {
            return $this;
        }

        if (is_string($with)) {
            $with = explode(',', $with);
        }

        $first = true;

        
        $class = $this->model;
        foreach ($with as $key => $relation) {
            $closure = null;

            if ($relation instanceof \Closure) {
                
                $closure  = $relation;
                $relation = $key;
            } elseif (is_array($relation)) {
                $relation = $key;
            } elseif (is_string($relation) && strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);
            }

            
            $relation = Loader::parseName($relation, 1, false);
            $model    = $class->$relation();

            if ($model instanceof OneToOne && 0 == $model->getEagerlyType()) {
                $table = $model->getTable();
                $model->removeOption()
                    ->table($table)
                    ->eagerly($this, $relation, true, '', $closure, $first);
                $first = false;
            }
        }

        $this->via();

        $this->options['with'] = $with;

        return $this;
    }

    
    public function withJoin($with, $joinType = '')
    {
        if (empty($with)) {
            return $this;
        }

        if (is_string($with)) {
            $with = explode(',', $with);
        }

        $first = true;

        
        $class = $this->model;
        foreach ($with as $key => $relation) {
            $closure = null;
            $field   = true;

            if ($relation instanceof \Closure) {
                
                $closure  = $relation;
                $relation = $key;
            } elseif (is_array($relation)) {
                $field    = $relation;
                $relation = $key;
            } elseif (is_string($relation) && strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);
            }

            
            $relation = Loader::parseName($relation, 1, false);
            $model    = $class->$relation();

            if ($model instanceof OneToOne) {
                $model->eagerly($this, $relation, $field, $joinType, $closure, $first);
                $first = false;
            } else {
                
                unset($with[$key]);
            }
        }

        $this->via();

        $this->options['with_join'] = $with;

        return $this;
    }

    
    protected function withAggregate($relation, $aggregate = 'count', $field = '*', $subQuery = true)
    {
        $relations = is_string($relation) ? explode(',', $relation) : $relation;

        if (!$subQuery) {
            $this->options['with_count'][] = [$relations, $aggregate, $field];
        } else {
            if (!isset($this->options['field'])) {
                $this->field('*');
            }

            foreach ($relations as $key => $relation) {
                $closure = $aggregateField = null;

                if ($relation instanceof \Closure) {
                    $closure  = $relation;
                    $relation = $key;
                } elseif (!is_int($key)) {
                    $aggregateField = $relation;
                    $relation       = $key;
                }

                $relation = Loader::parseName($relation, 1, false);

                $count = $this->model->$relation()->getRelationCountQuery($closure, $aggregate, $field, $aggregateField);

                if (empty($aggregateField)) {
                    $aggregateField = Loader::parseName($relation) . '_' . $aggregate;
                }

                $this->field(['(' . $count . ')' => $aggregateField]);
            }
        }

        return $this;
    }

    
    public function withCount($relation, $subQuery = true)
    {
        return $this->withAggregate($relation, 'count', '*', $subQuery);
    }

    
    public function withSum($relation, $field, $subQuery = true)
    {
        return $this->withAggregate($relation, 'sum', $field, $subQuery);
    }

    
    public function withMax($relation, $field, $subQuery = true)
    {
        return $this->withAggregate($relation, 'max', $field, $subQuery);
    }

    
    public function withMin($relation, $field, $subQuery = true)
    {
        return $this->withAggregate($relation, 'min', $field, $subQuery);
    }

    
    public function withAvg($relation, $field, $subQuery = true)
    {
        return $this->withAggregate($relation, 'avg', $field, $subQuery);
    }

    
    public function withField($field)
    {
        $this->options['with_field'] = $field;

        return $this;
    }

    
    public function via($via = '')
    {
        $this->options['via'] = $via;

        return $this;
    }

    
    public function relation($relation)
    {
        if (empty($relation)) {
            return $this;
        }

        if (is_string($relation)) {
            $relation = explode(',', $relation);
        }

        if (isset($this->options['relation'])) {
            $this->options['relation'] = array_merge($this->options['relation'], $relation);
        } else {
            $this->options['relation'] = $relation;
        }

        return $this;
    }

    
    public function insert(array $data = [], $replace = false, $getLastInsID = false, $sequence = null)
    {
        $this->parseOptions();

        $this->options['data'] = array_merge($this->options['data'], $data);

        return $this->connection->insert($this, $replace, $getLastInsID, $sequence);
    }

    
    public function insertGetId(array $data, $replace = false, $sequence = null)
    {
        return $this->insert($data, $replace, true, $sequence);
    }

    
    public function insertAll(array $dataSet = [], $replace = false, $limit = null)
    {
        $this->parseOptions();

        if (empty($dataSet)) {
            $dataSet = $this->options['data'];
        }

        if (empty($limit) && !empty($this->options['limit'])) {
            $limit = $this->options['limit'];
        }

        return $this->connection->insertAll($this, $dataSet, $replace, $limit);
    }

    
    public function selectInsert($fields, $table)
    {
        $this->parseOptions();

        return $this->connection->selectInsert($this, $fields, $table);
    }

    
    public function update(array $data = [])
    {
        $this->parseOptions();

        $this->options['data'] = array_merge($this->options['data'], $data);

        return $this->connection->update($this);
    }

    
    public function delete($data = null)
    {
        $this->parseOptions();

        if (!is_null($data) && true !== $data) {
            
            $this->parsePkWhere($data);
        }

        if (!empty($this->options['soft_delete'])) {
            
            list($field, $condition) = $this->options['soft_delete'];
            if ($condition) {
                unset($this->options['soft_delete']);
                $this->options['data'] = [$field => $condition];

                return $this->connection->update($this);
            }
        }

        $this->options['data'] = $data;

        return $this->connection->delete($this);
    }

    
    public function getPdo()
    {
        $this->parseOptions();

        return $this->connection->pdo($this);
    }

    
    public function cursor($data = null)
    {
        if ($data instanceof \Closure) {
            $data($this);
            $data = null;
        }

        $this->parseOptions();

        if (!is_null($data)) {
            
            $this->parsePkWhere($data);
        }

        $this->options['data'] = $data;

        $connection = clone $this->connection;

        return $connection->cursor($this);
    }

    
    public function select($data = null)
    {
        if ($data instanceof Query) {
            return $data->select();
        } elseif ($data instanceof \Closure) {
            $data($this);
            $data = null;
        }

        $this->parseOptions();

        if (false === $data) {
            
            $this->options['fetch_sql'] = true;
        } elseif (!is_null($data)) {
            
            $this->parsePkWhere($data);
        }

        $this->options['data'] = $data;

        $resultSet = $this->connection->select($this);

        if ($this->options['fetch_sql']) {
            return $resultSet;
        }

        
        if (!empty($this->options['fail']) && count($resultSet) == 0) {
            $this->throwNotFound($this->options);
        }

        
        if (!empty($this->model)) {
            
            $resultSet = $this->resultSetToModelCollection($resultSet);
        } else {
            $this->resultSet($resultSet);
        }

        return $resultSet;
    }

    
    protected function resultSetToModelCollection(array $resultSet)
    {
        if (!empty($this->options['collection']) && is_string($this->options['collection'])) {
            $collection = $this->options['collection'];
        }

        if (empty($resultSet)) {
            return $this->model->toCollection([], isset($collection) ? $collection : null);
        }

        
        if (!empty($this->options['with_attr'])) {
            foreach ($this->options['with_attr'] as $name => $val) {
                if (strpos($name, '.')) {
                    list($relation, $field) = explode('.', $name);

                    $withRelationAttr[$relation][$field] = $val;
                    unset($this->options['with_attr'][$name]);
                }
            }
        }

        $withRelationAttr = isset($withRelationAttr) ? $withRelationAttr : [];

        foreach ($resultSet as $key => &$result) {
            
            $this->resultToModel($result, $this->options, true, $withRelationAttr);
        }

        if (!empty($this->options['with'])) {
            
            $result->eagerlyResultSet($resultSet, $this->options['with'], $withRelationAttr);
        }

        if (!empty($this->options['with_join'])) {
            
            $result->eagerlyResultSet($resultSet, $this->options['with_join'], $withRelationAttr, true);
        }

        
        return $result->toCollection($resultSet, isset($collection) ? $collection : null);
    }

    
    protected function resultSet(&$resultSet)
    {
        if (!empty($this->options['json'])) {
            foreach ($resultSet as &$result) {
                $this->jsonResult($result, $this->options['json'], true);
            }
        }

        if (!empty($this->options['with_attr'])) {
            foreach ($resultSet as &$result) {
                $this->getResultAttr($result, $this->options['with_attr']);
            }
        }

        if (!empty($this->options['collection']) || 'collection' == $this->connection->getConfig('resultset_type')) {
            
            $resultSet = new Collection($resultSet);
        }
    }

    
    public function find($data = null)
    {
        if ($data instanceof Query) {
            return $data->find();
        } elseif ($data instanceof \Closure) {
            $data($this);
            $data = null;
        }

        $this->parseOptions();

        if (!is_null($data)) {
            
            $this->parsePkWhere($data);
        }

        $this->options['data'] = $data;

        $result = $this->connection->find($this);

        if ($this->options['fetch_sql']) {
            return $result;
        }

        
        if (empty($result)) {
            return $this->resultToEmpty();
        }

        if (!empty($this->model)) {
            
            $this->resultToModel($result, $this->options);
        } else {
            $this->result($result);
        }

        return $result;
    }

    
    protected function resultToEmpty()
    {
        if (!empty($this->options['allow_empty'])) {
            return !empty($this->model) ? $this->model->newInstance([], $this->getModelUpdateCondition($this->options)) : [];
        } elseif (!empty($this->options['fail'])) {
            $this->throwNotFound($this->options);
        }
    }

    
    public function get($data, $with = [], $cache = false, $failException = false)
    {
        if (is_null($data)) {
            return;
        }

        if (true === $with || is_int($with)) {
            $cache = $with;
            $with  = [];
        }

        return $this->parseQuery($data, $with, $cache)
            ->failException($failException)
            ->find($data);
    }

    
    public function getOrFail($data, $with = [], $cache = false)
    {
        return $this->get($data, $with, $cache, true);
    }

    
    public function all($data = null, $with = [], $cache = false)
    {
        if (true === $with || is_int($with)) {
            $cache = $with;
            $with  = [];
        }

        return $this->parseQuery($data, $with, $cache)->select($data);
    }

    
    protected function parseQuery(&$data, $with, $cache)
    {
        $result = $this->with($with)->cache($cache);

        if ((is_array($data) && key($data) !== 0) || $data instanceof Where) {
            $result = $result->where($data);
            $data   = null;
        } elseif ($data instanceof \Closure) {
            $data($result);
            $data = null;
        } elseif ($data instanceof Query) {
            $result = $data->with($with)->cache($cache);
            $data   = null;
        }

        return $result;
    }

    
    protected function result(&$result)
    {
        if (!empty($this->options['json'])) {
            $this->jsonResult($result, $this->options['json'], true);
        }

        if (!empty($this->options['with_attr'])) {
            $this->getResultAttr($result, $this->options['with_attr']);
        }
    }

    
    protected function getResultAttr(&$result, $withAttr = [])
    {
        foreach ($withAttr as $name => $closure) {
            $name = Loader::parseName($name);

            if (strpos($name, '.')) {
                
                list($key, $field) = explode('.', $name);

                if (isset($result[$key])) {
                    $result[$key][$field] = $closure(isset($result[$key][$field]) ? $result[$key][$field] : null, $result[$key]);
                }
            } else {
                $result[$name] = $closure(isset($result[$name]) ? $result[$name] : null, $result);
            }
        }
    }

    
    protected function jsonResult(&$result, $json = [], $assoc = false, $withRelationAttr = [])
    {
        foreach ($json as $name) {
            if (isset($result[$name])) {
                $result[$name] = json_decode($result[$name], $assoc);

                if (isset($withRelationAttr[$name])) {
                    foreach ($withRelationAttr[$name] as $key => $closure) {
                        $data                = get_object_vars($result[$name]);
                        $result[$name]->$key = $closure(isset($result[$name]->$key) ? $result[$name]->$key : null, $data);
                    }
                }
            }
        }
    }

    
    protected function resultToModel(&$result, $options = [], $resultSet = false, $withRelationAttr = [])
    {
        
        if (!empty($options['with_attr']) && empty($withRelationAttr)) {
            foreach ($options['with_attr'] as $name => $val) {
                if (strpos($name, '.')) {
                    list($relation, $field) = explode('.', $name);

                    $withRelationAttr[$relation][$field] = $val;
                    unset($options['with_attr'][$name]);
                }
            }
        }

        
        if (!empty($options['json'])) {
            $this->jsonResult($result, $options['json'], $options['json_assoc'], $withRelationAttr);
        }

        $result = $this->model->newInstance($result, $resultSet ? null : $this->getModelUpdateCondition($options));

        
        if (!empty($options['with_attr'])) {
            $result->withAttribute($options['with_attr']);
        }

        
        if (!empty($options['visible'])) {
            $result->visible($options['visible'], true);
        } elseif (!empty($options['hidden'])) {
            $result->hidden($options['hidden'], true);
        }

        if (!empty($options['append'])) {
            $result->append($options['append'], true);
        }

        
        if (!empty($options['relation'])) {
            $result->relationQuery($options['relation'], $withRelationAttr);
        }

        
        if (!$resultSet && !empty($options['with'])) {
            $result->eagerlyResult($result, $options['with'], $withRelationAttr);
        }

        
        if (!$resultSet && !empty($options['with_join'])) {
            $result->eagerlyResult($result, $options['with_join'], $withRelationAttr, true);
        }

        
        if (!empty($options['with_count'])) {
            foreach ($options['with_count'] as $val) {
                $result->relationCount($result, $val[0], $val[1], $val[2]);
            }
        }
    }

    
    protected function getModelUpdateCondition(array $options)
    {
        return isset($options['where']['AND']) ? $options['where']['AND'] : null;
    }

    
    protected function throwNotFound($options = [])
    {
        if (!empty($this->model)) {
            $class = get_class($this->model);
            throw new ModelNotFoundException('model data Not Found:' . $class, $class, $options);
        }
        $table = is_array($options['table']) ? key($options['table']) : $options['table'];
        throw new DataNotFoundException('table data not Found:' . $table, $table, $options);
    }

    
    public function selectOrFail($data = null)
    {
        return $this->failException(true)->select($data);
    }

    
    public function findOrFail($data = null)
    {
        return $this->failException(true)->find($data);
    }

    
    public function findOrEmpty($data = null)
    {
        return $this->allowEmpty(true)->find($data);
    }

    
    public function chunk($count, $callback, $column = null, $order = 'asc')
    {
        $options = $this->getOptions();
        $column  = $column ?: $this->getPk($options);

        if (isset($options['order'])) {
            if (Container::get('app')->isDebug()) {
                throw new DbException('chunk not support call order');
            }
            unset($options['order']);
        }

        $bind = $this->bind;

        if (is_array($column)) {
            $times = 1;
            $query = $this->options($options)->page($times, $count);
        } else {
            $query = $this->options($options)->limit($count);

            if (strpos($column, '.')) {
                list($alias, $key) = explode('.', $column);
            } else {
                $key = $column;
            }
        }

        $resultSet = $query->order($column, $order)->select();

        while (count($resultSet) > 0) {
            if ($resultSet instanceof Collection) {
                $resultSet = $resultSet->all();
            }

            if (false === call_user_func($callback, $resultSet)) {
                return false;
            }

            if (isset($times)) {
                $times++;
                $query = $this->options($options)->page($times, $count);
            } else {
                $end    = end($resultSet);
                $lastId = is_array($end) ? $end[$key] : $end->getData($key);

                $query = $this->options($options)
                    ->limit($count)
                    ->where($column, 'asc' == strtolower($order) ? '>' : '<', $lastId);
            }

            $resultSet = $query->bind($bind)->order($column, $order)->select();
        }

        return true;
    }

    
    public function getBind($clear = true)
    {
        $bind = $this->bind;
        if ($clear) {
            $this->bind = [];
        }

        return $bind;
    }

    
    public function buildSql($sub = true)
    {
        return $sub ? '( ' . $this->select(false) . ' )' : $this->select(false);
    }

    
    protected function parseView(&$options)
    {
        if (!isset($options['map'])) {
            return;
        }

        foreach (['AND', 'OR'] as $logic) {
            if (isset($options['where'][$logic])) {
                foreach ($options['where'][$logic] as $key => $val) {
                    if (array_key_exists($key, $options['map'])) {
                        array_shift($val);
                        array_unshift($val, $options['map'][$key]);
                        $options['where'][$logic][$options['map'][$key]] = $val;
                        unset($options['where'][$logic][$key]);
                    }
                }
            }
        }

        if (isset($options['order'])) {
            
            if (is_string($options['order'])) {
                $options['order'] = explode(',', $options['order']);
            }
            foreach ($options['order'] as $key => $val) {
                if (is_numeric($key) && is_string($val)) {
                    if (strpos($val, ' ')) {
                        list($field, $sort) = explode(' ', $val);
                        if (array_key_exists($field, $options['map'])) {
                            $options['order'][$options['map'][$field]] = $sort;
                            unset($options['order'][$key]);
                        }
                    } elseif (array_key_exists($val, $options['map'])) {
                        $options['order'][$options['map'][$val]] = 'asc';
                        unset($options['order'][$key]);
                    }
                } elseif (array_key_exists($key, $options['map'])) {
                    $options['order'][$options['map'][$key]] = $val;
                    unset($options['order'][$key]);
                }
            }
        }
    }

    
    public function parsePkWhere($data)
    {
        $pk = $this->getPk($this->options);
        
        $table = is_array($this->options['table']) ? key($this->options['table']) : $this->options['table'];

        if (!empty($this->options['alias'][$table])) {
            $alias = $this->options['alias'][$table];
        }

        if (is_string($pk)) {
            $key = isset($alias) ? $alias . '.' . $pk : $pk;
            
            if (is_array($data)) {
                $where[$pk] = isset($data[$pk]) ? [$key, '=', $data[$pk]] : [$key, 'in', $data];
            } else {
                $where[$pk] = strpos($data, ',') ? [$key, 'IN', $data] : [$key, '=', $data];
            }
        } elseif (is_array($pk) && is_array($data) && !empty($data)) {
            
            foreach ($pk as $key) {
                if (isset($data[$key])) {
                    $attr        = isset($alias) ? $alias . '.' . $key : $key;
                    $where[$key] = [$attr, '=', $data[$key]];
                } else {
                    throw new Exception('miss complex primary data');
                }
            }
        }

        if (!empty($where)) {
            if (isset($this->options['where']['AND'])) {
                $this->options['where']['AND'] = array_merge($this->options['where']['AND'], $where);
            } else {
                $this->options['where']['AND'] = $where;
            }
        }

        return;
    }

    
    protected function parseOptions()
    {
        $options = $this->getOptions();

        
        if (empty($options['table'])) {
            $options['table'] = $this->getTable();
        }

        if (!isset($options['where'])) {
            $options['where'] = [];
        } elseif (isset($options['view'])) {
            
            $this->parseView($options);
        }

        if (!isset($options['field'])) {
            $options['field'] = '*';
        }

        foreach (['data', 'order', 'join', 'union'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = [];
            }
        }

        if (!isset($options['strict'])) {
            $options['strict'] = $this->getConfig('fields_strict');
        }

        foreach (['master', 'lock', 'fetch_pdo', 'fetch_sql', 'distinct'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = false;
            }
        }

        if (isset(static::$readMaster['*']) || (is_string($options['table']) && isset(static::$readMaster[$options['table']]))) {
            $options['master'] = true;
        }

        foreach (['group', 'having', 'limit', 'force', 'comment'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = '';
            }
        }

        if (isset($options['page'])) {
            
            list($page, $listRows) = $options['page'];
            $page                  = $page > 0 ? $page : 1;
            $listRows              = $listRows > 0 ? $listRows : (is_numeric($options['limit']) ? $options['limit'] : 20);
            $offset                = $listRows * ($page - 1);
            $options['limit']      = $offset . ',' . $listRows;
        }

        $this->options = $options;

        return $options;
    }

    
    public static function event($event, $callback)
    {
        self::$event[$event] = $callback;
    }

    
    public function trigger($event)
    {
        $result = false;

        if (isset(self::$event[$event])) {
            $result = Container::getInstance()->invoke(self::$event[$event], [$this]);
        }

        return $result;
    }

}
