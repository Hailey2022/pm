<?php
namespace think\db\connector;
use PDO;
use think\db\Connection;
use think\db\Query;
class Mysql extends Connection
{
    protected $builder = '\\think\\db\\builder\\Mysql';
    protected function initialize()
    {
        Query::extend('point', function ($query, $field, $value = null, $fun = 'GeomFromText', $type = 'POINT') {
            if (!is_null($value)) {
                $query->data($field, ['point', $value, $fun, $type]);
            } else {
                if (is_string($field)) {
                    $field = explode(',', $field);
                }
                $query->setOption('point', $field);
            }
            return $query;
        });
    }
    protected function parseDsn($config)
    {
        if (!empty($config['socket'])) {
            $dsn = 'mysql:unix_socket=' . $config['socket'];
        } elseif (!empty($config['hostport'])) {
            $dsn = 'mysql:host=' . $config['hostname'] . ';port=' . $config['hostport'];
        } else {
            $dsn = 'mysql:host=' . $config['hostname'];
        }
        $dsn .= ';dbname=' . $config['database'];
        if (!empty($config['charset'])) {
            $dsn .= ';charset=' . $config['charset'];
        }
        return $dsn;
    }
    public function getFields($tableName)
    {
        list($tableName) = explode(' ', $tableName);
        if (false === strpos($tableName, '`')) {
            if (strpos($tableName, '.')) {
                $tableName = str_replace('.', '`.`', $tableName);
            }
            $tableName = '`' . $tableName . '`';
        }
        $sql    = 'SHOW COLUMNS FROM ' . $tableName;
        $pdo    = $this->query($sql, [], false, true);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];
        if ($result) {
            foreach ($result as $key => $val) {
                $val                 = array_change_key_case($val);
                $info[$val['field']] = [
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => 'NO' == $val['null'],
                    'default' => $val['default'],
                    'primary' => strtolower($val['key']) == 'pri',
                    'autoinc' => strtolower($val['extra']) == 'auto_increment',
                ];
            }
        }
        return $this->fieldCase($info);
    }
    public function getTables($dbName = '')
    {
        $sql    = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES ';
        $pdo    = $this->query($sql, [], false, true);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }
    protected function getExplain($sql)
    {
        $pdo = $this->linkID->prepare("EXPLAIN " . $this->queryStr);
        foreach ($this->bind as $key => $val) {
            $param = is_int($key) ? $key + 1 : ':' . $key;
            if (is_array($val)) {
                if (PDO::PARAM_INT == $val[1] && '' === $val[0]) {
                    $val[0] = 0;
                } elseif (self::PARAM_FLOAT == $val[1]) {
                    $val[0] = is_string($val[0]) ? (float) $val[0] : $val[0];
                    $val[1] = PDO::PARAM_STR;
                }
                $result = $pdo->bindValue($param, $val[0], $val[1]);
            } else {
                $result = $pdo->bindValue($param, $val);
            }
        }
        $pdo->execute();
        $result = $pdo->fetch(PDO::FETCH_ASSOC);
        $result = array_change_key_case($result);
        if (isset($result['extra'])) {
            if (strpos($result['extra'], 'filesort') || strpos($result['extra'], 'temporary')) {
                $this->log('SQL:' . $this->queryStr . '[' . $result['extra'] . ']', 'warn');
            }
        }
        return $result;
    }
    protected function supportSavepoint()
    {
        return true;
    }
    public function startTransXa($xid)
    {
        $this->initConnect(true);
        if (!$this->linkID) {
            return false;
        }
        $this->linkID->exec("XA START '$xid'");
    }
    public function prepareXa($xid)
    {
        $this->initConnect(true);
        $this->linkID->exec("XA END '$xid'");
        $this->linkID->exec("XA PREPARE '$xid'");
    }
    public function commitXa($xid)
    {
        $this->initConnect(true);
        $this->linkID->exec("XA COMMIT '$xid'");
    }
    public function rollbackXa($xid)
    {
        $this->initConnect(true);
        $this->linkID->exec("XA ROLLBACK '$xid'");
    }
}
