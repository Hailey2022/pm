<?php










namespace think\db;

use PDO;
use think\Exception;

abstract class Builder
{
    
    protected $connection;

    
    protected $exp = ['EQ' => '=', 'NEQ' => '<>', 'GT' => '>', 'EGT' => '>=', 'LT' => '<', 'ELT' => '<=', 'NOTLIKE' => 'NOT LIKE', 'NOTIN' => 'NOT IN', 'NOTBETWEEN' => 'NOT BETWEEN', 'NOTEXISTS' => 'NOT EXISTS', 'NOTNULL' => 'NOT NULL', 'NOTBETWEEN TIME' => 'NOT BETWEEN TIME'];

    
    protected $parser = [
        'parseCompare'     => ['=', '<>', '>', '>=', '<', '<='],
        'parseLike'        => ['LIKE', 'NOT LIKE'],
        'parseBetween'     => ['NOT BETWEEN', 'BETWEEN'],
        'parseIn'          => ['NOT IN', 'IN'],
        'parseExp'         => ['EXP'],
        'parseNull'        => ['NOT NULL', 'NULL'],
        'parseBetweenTime' => ['BETWEEN TIME', 'NOT BETWEEN TIME'],
        'parseTime'        => ['< TIME', '> TIME', '<= TIME', '>= TIME'],
        'parseExists'      => ['NOT EXISTS', 'EXISTS'],
        'parseColumn'      => ['COLUMN'],
    ];

    
    protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    protected $insertSql = '%INSERT% INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMENT%';

    protected $insertAllSql = '%INSERT% INTO %TABLE% (%FIELD%) %DATA% %COMMENT%';

    protected $updateSql = 'UPDATE %TABLE% SET %SET%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    protected $deleteSql = 'DELETE FROM %TABLE%%USING%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    
    public function getConnection()
    {
        return $this->connection;
    }

    
    public function bindParser($name, $parser)
    {
        $this->parser[$name] = $parser;
        return $this;
    }

    
    protected function parseData(Query $query, $data = [], $fields = [], $bind = [])
    {
        if (empty($data)) {
            return [];
        }

        $options = $query->getOptions();

        
        if (empty($bind)) {
            $bind = $this->connection->getFieldsBind($options['table']);
        }

        if (empty($fields)) {
            if ('*' == $options['field']) {
                $fields = array_keys($bind);
            } else {
                $fields = $options['field'];
            }
        }

        $result = [];

        foreach ($data as $key => $val) {
            if ('*' != $options['field'] && !in_array($key, $fields, true)) {
                continue;
            }

            $item = $this->parseKey($query, $key, true);

            if ($val instanceof Expression) {
                $result[$item] = $val->getValue();
                continue;
            } elseif (!is_scalar($val) && (in_array($key, (array) $query->getOptions('json')) || 'json' == $this->connection->getFieldsType($options['table'], $key))) {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE);
            } elseif (is_object($val) && method_exists($val, '__toString')) {
                
                $val = $val->__toString();
            }

            if (false !== strpos($key, '->')) {
                list($key, $name) = explode('->', $key);
                $item             = $this->parseKey($query, $key);
                $result[$item]    = 'json_set(' . $item . ', \'$.' . $name . '\', ' . $this->parseDataBind($query, $key, $val, $bind) . ')';
            } elseif ('*' == $options['field'] && false === strpos($key, '.') && !in_array($key, $fields, true)) {
                if ($options['strict']) {
                    throw new Exception('fields not exists:[' . $key . ']');
                }
            } elseif (is_null($val)) {
                $result[$item] = 'NULL';
            } elseif (is_array($val) && !empty($val)) {
                switch (strtoupper($val[0])) {
                    case 'INC':
                        $result[$item] = $item . ' + ' . floatval($val[1]);
                        break;
                    case 'DEC':
                        $result[$item] = $item . ' - ' . floatval($val[1]);
                        break;
                    case 'EXP':
                        throw new Exception('not support data:[' . $val[0] . ']');
                }
            } elseif (is_scalar($val)) {
                
                $result[$item] = $this->parseDataBind($query, $key, $val, $bind);
            }
        }

        return $result;
    }

    
    protected function parseDataBind(Query $query, $key, $data, $bind = [])
    {
        if ($data instanceof Expression) {
            return $data->getValue();
        }

        $name = $query->bind($data, isset($bind[$key]) ? $bind[$key] : PDO::PARAM_STR);

        return ':' . $name;
    }

    
    public function parseKey(Query $query, $key, $strict = false)
    {
        return $key instanceof Expression ? $key->getValue() : $key;
    }

    
    protected function parseField(Query $query, $fields)
    {
        if ('*' == $fields || empty($fields)) {
            $fieldsStr = '*';
        } elseif (is_array($fields)) {
            
            $array = [];

            foreach ($fields as $key => $field) {
                if (!is_numeric($key)) {
                    $array[] = $this->parseKey($query, $key) . ' AS ' . $this->parseKey($query, $field, true);
                } else {
                    $array[] = $this->parseKey($query, $field);
                }
            }

            $fieldsStr = implode(',', $array);
        }

        return $fieldsStr;
    }

    
    protected function parseTable(Query $query, $tables)
    {
        $item    = [];
        $options = $query->getOptions();

        foreach ((array) $tables as $key => $table) {
            if (!is_numeric($key)) {
                $key    = $this->connection->parseSqlTable($key);
                $item[] = $this->parseKey($query, $key) . ' ' . $this->parseKey($query, $table);
            } else {
                $table = $this->connection->parseSqlTable($table);

                if (isset($options['alias'][$table])) {
                    $item[] = $this->parseKey($query, $table) . ' ' . $this->parseKey($query, $options['alias'][$table]);
                } else {
                    $item[] = $this->parseKey($query, $table);
                }
            }
        }

        return implode(',', $item);
    }

    
    protected function parseWhere(Query $query, $where)
    {
        $options  = $query->getOptions();
        $whereStr = $this->buildWhere($query, $where);

        if (!empty($options['soft_delete'])) {
            
            list($field, $condition) = $options['soft_delete'];

            $binds    = $this->connection->getFieldsBind($options['table']);
            $whereStr = $whereStr ? '( ' . $whereStr . ' ) AND ' : '';
            $whereStr = $whereStr . $this->parseWhereItem($query, $field, $condition, '', $binds);
        }

        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }

    
    public function buildWhere(Query $query, $where)
    {
        if (empty($where)) {
            $where = [];
        }

        $whereStr = '';
        $binds    = $this->connection->getFieldsBind($query->getOptions('table'));

        foreach ($where as $logic => $val) {
            $str = [];

            foreach ($val as $value) {
                if ($value instanceof Expression) {
                    $str[] = ' ' . $logic . ' ( ' . $value->getValue() . ' )';
                    continue;
                }

                if (is_array($value)) {
                    if (key($value) !== 0) {
                        throw new Exception('where express error:' . var_export($value, true));
                    }
                    $field = array_shift($value);
                } elseif (!($value instanceof \Closure)) {
                    throw new Exception('where express error:' . var_export($value, true));
                }

                if ($value instanceof \Closure) {
                    
                    $newQuery = $query->newQuery()->setConnection($this->connection);
                    $value($newQuery);
                    $whereClause = $this->buildWhere($newQuery, $newQuery->getOptions('where'));

                    if (!empty($whereClause)) {
                        $query->bind($newQuery->getBind(false));
                        $str[] = ' ' . $logic . ' ( ' . $whereClause . ' )';
                    }
                } elseif (is_array($field)) {
                    array_unshift($value, $field);
                    $str2 = [];
                    foreach ($value as $item) {
                        $str2[] = $this->parseWhereItem($query, array_shift($item), $item, $logic, $binds);
                    }

                    $str[] = ' ' . $logic . ' ( ' . implode(' AND ', $str2) . ' )';
                } elseif (strpos($field, '|')) {
                    
                    $array = explode('|', $field);
                    $item  = [];

                    foreach ($array as $k) {
                        $item[] = $this->parseWhereItem($query, $k, $value, '', $binds);
                    }

                    $str[] = ' ' . $logic . ' ( ' . implode(' OR ', $item) . ' )';
                } elseif (strpos($field, '&')) {
                    
                    $array = explode('&', $field);
                    $item  = [];

                    foreach ($array as $k) {
                        $item[] = $this->parseWhereItem($query, $k, $value, '', $binds);
                    }

                    $str[] = ' ' . $logic . ' ( ' . implode(' AND ', $item) . ' )';
                } else {
                    
                    $field = is_string($field) ? $field : '';
                    $str[] = ' ' . $logic . ' ' . $this->parseWhereItem($query, $field, $value, $logic, $binds);
                }
            }

            $whereStr .= empty($whereStr) ? substr(implode(' ', $str), strlen($logic) + 1) : implode(' ', $str);
        }

        return $whereStr;
    }

    
    protected function parseWhereItem(Query $query, $field, $val, $rule = '', $binds = [])
    {
        
        $key = $field ? $this->parseKey($query, $field, true) : '';

        
        if (!is_array($val)) {
            $val = is_null($val) ? ['NULL', ''] : ['=', $val];
        }

        list($exp, $value) = $val;

        
        if (is_array($exp)) {
            $item = array_pop($val);

            
            if (is_string($item) && in_array($item, ['AND', 'and', 'OR', 'or'])) {
                $rule = $item;
            } else {
                array_push($val, $item);
            }

            foreach ($val as $k => $item) {
                $str[] = $this->parseWhereItem($query, $field, $item, $rule, $binds);
            }

            return '( ' . implode(' ' . $rule . ' ', $str) . ' )';
        }

        
        $exp = strtoupper($exp);
        if (isset($this->exp[$exp])) {
            $exp = $this->exp[$exp];
        }

        if ($value instanceof Expression) {

        } elseif (is_object($value) && method_exists($value, '__toString')) {
            
            $value = $value->__toString();
        }

        if (strpos($field, '->')) {
            $jsonType = $query->getJsonFieldType($field);
            $bindType = $this->connection->getFieldBindType($jsonType);
        } else {
            $bindType = isset($binds[$field]) && 'LIKE' != $exp ? $binds[$field] : PDO::PARAM_STR;
        }

        if (is_scalar($value) && !in_array($exp, ['EXP', 'NOT NULL', 'NULL', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN']) && strpos($exp, 'TIME') === false) {
            if (0 === strpos($value, ':') && $query->isBind(substr($value, 1))) {
            } else {
                $name  = $query->bind($value, $bindType);
                $value = ':' . $name;
            }
        }

        
        foreach ($this->parser as $fun => $parse) {
            if (in_array($exp, $parse)) {
                $whereStr = $this->$fun($query, $key, $exp, $value, $field, $bindType, isset($val[2]) ? $val[2] : 'AND');
                break;
            }
        }

        if (!isset($whereStr)) {
            throw new Exception('where express error:' . $exp);
        }

        return $whereStr;
    }

    
    protected function parseLike(Query $query, $key, $exp, $value, $field, $bindType, $logic)
    {
        
        if (is_array($value)) {
            foreach ($value as $item) {
                $name    = $query->bind($item, PDO::PARAM_STR);
                $array[] = $key . ' ' . $exp . ' :' . $name;
            }

            $whereStr = '(' . implode(' ' . strtoupper($logic) . ' ', $array) . ')';
        } else {
            $whereStr = $key . ' ' . $exp . ' ' . $value;
        }

        return $whereStr;
    }

    
    protected function parseColumn(Query $query, $key, $exp, array $value, $field, $bindType)
    {
        
        list($op, $field2) = $value;

        if (!in_array($op, ['=', '<>', '>', '>=', '<', '<='])) {
            throw new Exception('where express error:' . var_export($value, true));
        }

        return '( ' . $key . ' ' . $op . ' ' . $this->parseKey($query, $field2, true) . ' )';
    }

    
    protected function parseExp(Query $query, $key, $exp, Expression $value, $field, $bindType)
    {
        
        return '( ' . $key . ' ' . $value->getValue() . ' )';
    }

    
    protected function parseNull(Query $query, $key, $exp, $value, $field, $bindType)
    {
        
        return $key . ' IS ' . $exp;
    }

    
    protected function parseBetween(Query $query, $key, $exp, $value, $field, $bindType)
    {
        
        $data = is_array($value) ? $value : explode(',', $value);

        $min = $query->bind($data[0], $bindType);
        $max = $query->bind($data[1], $bindType);

        return $key . ' ' . $exp . ' :' . $min . ' AND :' . $max . ' ';
    }

    
    protected function parseExists(Query $query, $key, $exp, $value, $field, $bindType)
    {
        
        if ($value instanceof \Closure) {
            $value = $this->parseClosure($query, $value, false);
        } elseif ($value instanceof Expression) {
            $value = $value->getValue();
        } else {
            throw new Exception('where express error:' . $value);
        }

        return $exp . ' (' . $value . ')';
    }

    
    protected function parseTime(Query $query, $key, $exp, $value, $field, $bindType)
    {
        return $key . ' ' . substr($exp, 0, 2) . ' ' . $this->parseDateTime($query, $value, $field, $bindType);
    }

    
    protected function parseCompare(Query $query, $key, $exp, $value, $field, $bindType)
    {
        if (is_array($value)) {
            throw new Exception('where express error:' . $exp . var_export($value, true));
        }

        
        if ($value instanceof \Closure) {
            $value = $this->parseClosure($query, $value);
        }

        if ('=' == $exp && is_null($value)) {
            return $key . ' IS NULL';
        }

        return $key . ' ' . $exp . ' ' . $value;
    }

    
    protected function parseBetweenTime(Query $query, $key, $exp, $value, $field, $bindType)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return $key . ' ' . substr($exp, 0, -4)
        . $this->parseDateTime($query, $value[0], $field, $bindType)
        . ' AND '
        . $this->parseDateTime($query, $value[1], $field, $bindType);

    }

    
    protected function parseIn(Query $query, $key, $exp, $value, $field, $bindType)
    {
        
        if ($value instanceof \Closure) {
            $value = $this->parseClosure($query, $value, false);
        } elseif ($value instanceof Expression) {
            $value = $value->getValue();
        } else {
            $value = array_unique(is_array($value) ? $value : explode(',', $value));
            $array = [];

            foreach ($value as $k => $v) {
                $name    = $query->bind($v, $bindType);
                $array[] = ':' . $name;
            }

            if (count($array) == 1) {
                return $key . ('IN' == $exp ? ' = ' : ' <> ') . $array[0];
            } else {
                $zone  = implode(',', $array);
                $value = empty($zone) ? "''" : $zone;
            }
        }

        return $key . ' ' . $exp . ' (' . $value . ')';
    }

    
    protected function parseClosure(Query $query, $call, $show = true)
    {
        $newQuery = $query->newQuery()->removeOption();
        $call($newQuery);

        return $newQuery->buildSql($show);
    }

    
    protected function parseDateTime(Query $query, $value, $key, $bindType = null)
    {
        $options = $query->getOptions();

        
        if (strpos($key, '.')) {
            list($table, $key) = explode('.', $key);

            if (isset($options['alias']) && $pos = array_search($table, $options['alias'])) {
                $table = $pos;
            }
        } else {
            $table = $options['table'];
        }

        $type = $this->connection->getTableInfo($table, 'type');

        if (isset($type[$key])) {
            $info = $type[$key];
        }

        if (isset($info)) {
            if (is_string($value)) {
                $value = strtotime($value) ?: $value;
            }

            if (preg_match('/(datetime|timestamp)/is', $info)) {
                
                $value = date('Y-m-d H:i:s', $value);
            } elseif (preg_match('/(date)/is', $info)) {
                
                $value = date('Y-m-d', $value);
            }
        }

        $name = $query->bind($value, $bindType);

        return ':' . $name;
    }

    
    protected function parseLimit(Query $query, $limit)
    {
        return (!empty($limit) && false === strpos($limit, '(')) ? ' LIMIT ' . $limit . ' ' : '';
    }

    
    protected function parseJoin(Query $query, $join)
    {
        $joinStr = '';

        if (!empty($join)) {
            foreach ($join as $item) {
                list($table, $type, $on) = $item;

                $condition = [];

                foreach ((array) $on as $val) {
                    if ($val instanceof Expression) {
                        $condition[] = $val->getValue();
                    } elseif (strpos($val, '=')) {
                        list($val1, $val2) = explode('=', $val, 2);

                        $condition[] = $this->parseKey($query, $val1) . '=' . $this->parseKey($query, $val2);
                    } else {
                        $condition[] = $val;
                    }
                }

                $table = $this->parseTable($query, $table);

                $joinStr .= ' ' . $type . ' JOIN ' . $table . ' ON ' . implode(' AND ', $condition);
            }
        }

        return $joinStr;
    }

    
    protected function parseOrder(Query $query, $order)
    {
        foreach ($order as $key => $val) {
            if ($val instanceof Expression) {
                $array[] = $val->getValue();
            } elseif (is_array($val) && preg_match('/^[\w\.]+$/', $key)) {
                $array[] = $this->parseOrderField($query, $key, $val);
            } elseif ('[rand]' == $val) {
                $array[] = $this->parseRand($query);
            } elseif (is_string($val)) {
                if (is_numeric($key)) {
                    list($key, $sort) = explode(' ', strpos($val, ' ') ? $val : $val . ' ');
                } else {
                    $sort = $val;
                }

                if (preg_match('/^[\w\.]+$/', $key)) {
                    $sort    = strtoupper($sort);
                    $sort    = in_array($sort, ['ASC', 'DESC'], true) ? ' ' . $sort : '';
                    $array[] = $this->parseKey($query, $key, true) . $sort;
                } else {
                    throw new Exception('order express error:' . $key);
                }
            }
        }

        return empty($array) ? '' : ' ORDER BY ' . implode(',', $array);
    }

    
    protected function parseOrderField($query, $key, $val)
    {
        if (isset($val['sort'])) {
            $sort = $val['sort'];
            unset($val['sort']);
        } else {
            $sort = '';
        }

        $sort = strtoupper($sort);
        $sort = in_array($sort, ['ASC', 'DESC'], true) ? ' ' . $sort : '';

        $options = $query->getOptions();
        $bind    = $this->connection->getFieldsBind($options['table']);

        foreach ($val as $k => $item) {
            $val[$k] = $this->parseDataBind($query, $key, $item, $bind);
        }

        return 'field(' . $this->parseKey($query, $key, true) . ',' . implode(',', $val) . ')' . $sort;
    }

    
    protected function parseGroup(Query $query, $group)
    {
        if (empty($group)) {
            return '';
        }

        if (is_string($group)) {
            $group = explode(',', $group);
        }

        foreach ($group as $key) {
            $val[] = $this->parseKey($query, $key);
        }

        return ' GROUP BY ' . implode(',', $val);
    }

    
    protected function parseHaving(Query $query, $having)
    {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    
    protected function parseComment(Query $query, $comment)
    {
        if (false !== strpos($comment, '*/')) {
            $comment = strstr($comment, '*/', true);
        }

        return !empty($comment) ? ' ' : '';
    }

    
    protected function parseDistinct(Query $query, $distinct)
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    
    protected function parseUnion(Query $query, $union)
    {
        if (empty($union)) {
            return '';
        }

        $type = $union['type'];
        unset($union['type']);

        foreach ($union as $u) {
            if ($u instanceof \Closure) {
                $sql[] = $type . ' ' . $this->parseClosure($query, $u);
            } elseif (is_string($u)) {
                $sql[] = $type . ' ( ' . $this->connection->parseSqlTable($u) . ' )';
            }
        }

        return ' ' . implode(' ', $sql);
    }

    
    protected function parseForce(Query $query, $index)
    {
        if (empty($index)) {
            return '';
        }

        return sprintf(" FORCE INDEX ( %s ) ", is_array($index) ? implode(',', $index) : $index);
    }

    
    protected function parseLock(Query $query, $lock = false)
    {
        if (is_bool($lock)) {
            return $lock ? ' FOR UPDATE ' : '';
        } elseif (is_string($lock) && !empty($lock)) {
            return ' ' . trim($lock) . ' ';
        }
    }

    
    public function select(Query $query)
    {
        $options = $query->getOptions();

        return str_replace(
            ['%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'],
            [
                $this->parseTable($query, $options['table']),
                $this->parseDistinct($query, $options['distinct']),
                $this->parseField($query, $options['field']),
                $this->parseJoin($query, $options['join']),
                $this->parseWhere($query, $options['where']),
                $this->parseGroup($query, $options['group']),
                $this->parseHaving($query, $options['having']),
                $this->parseOrder($query, $options['order']),
                $this->parseLimit($query, $options['limit']),
                $this->parseUnion($query, $options['union']),
                $this->parseLock($query, $options['lock']),
                $this->parseComment($query, $options['comment']),
                $this->parseForce($query, $options['force']),
            ],
            $this->selectSql);
    }

    
    public function insert(Query $query, $replace = false)
    {
        $options = $query->getOptions();

        
        $data = $this->parseData($query, $options['data']);
        if (empty($data)) {
            return '';
        }

        $fields = array_keys($data);
        $values = array_values($data);

        return str_replace(
            ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseTable($query, $options['table']),
                implode(' , ', $fields),
                implode(' , ', $values),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertSql);
    }

    
    public function insertAll(Query $query, $dataSet, $replace = false)
    {
        $options = $query->getOptions();

        
        if ('*' == $options['field']) {
            $allowFields = $this->connection->getTableFields($options['table']);
        } else {
            $allowFields = $options['field'];
        }

        
        $bind = $this->connection->getFieldsBind($options['table']);

        foreach ($dataSet as $data) {
            $data = $this->parseData($query, $data, $allowFields, $bind);

            $values[] = 'SELECT ' . implode(',', array_values($data));

            if (!isset($insertFields)) {
                $insertFields = array_keys($data);
            }
        }

        $fields = [];

        foreach ($insertFields as $field) {
            $fields[] = $this->parseKey($query, $field);
        }

        return str_replace(
            ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseTable($query, $options['table']),
                implode(' , ', $fields),
                implode(' UNION ALL ', $values),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertAllSql);
    }

    
    public function selectInsert(Query $query, $fields, $table)
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }

        foreach ($fields as &$field) {
            $field = $this->parseKey($query, $field, true);
        }

        return 'INSERT INTO ' . $this->parseTable($query, $table) . ' (' . implode(',', $fields) . ') ' . $this->select($query);
    }

    
    public function update(Query $query)
    {
        $options = $query->getOptions();

        $data = $this->parseData($query, $options['data']);

        if (empty($data)) {
            return '';
        }

        foreach ($data as $key => $val) {
            $set[] = $key . ' = ' . $val;
        }

        return str_replace(
            ['%TABLE%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($query, $options['table']),
                implode(' , ', $set),
                $this->parseJoin($query, $options['join']),
                $this->parseWhere($query, $options['where']),
                $this->parseOrder($query, $options['order']),
                $this->parseLimit($query, $options['limit']),
                $this->parseLock($query, $options['lock']),
                $this->parseComment($query, $options['comment']),
            ],
            $this->updateSql);
    }

    
    public function delete(Query $query)
    {
        $options = $query->getOptions();

        return str_replace(
            ['%TABLE%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($query, $options['table']),
                !empty($options['using']) ? ' USING ' . $this->parseTable($query, $options['using']) . ' ' : '',
                $this->parseJoin($query, $options['join']),
                $this->parseWhere($query, $options['where']),
                $this->parseOrder($query, $options['order']),
                $this->parseLimit($query, $options['limit']),
                $this->parseLock($query, $options['lock']),
                $this->parseComment($query, $options['comment']),
            ],
            $this->deleteSql);
    }
}
