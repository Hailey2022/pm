<?php
namespace think\db\builder;
use think\db\Builder;
use think\db\Expression;
use think\db\Query;
use think\Exception;
class Sqlsrv extends Builder
{
    protected $selectSql       = 'SELECT T1.* FROM (SELECT thinkphp.*, ROW_NUMBER() OVER (%ORDER%) AS ROW_NUMBER FROM (SELECT %DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%) AS thinkphp) AS T1 %LIMIT%%COMMENT%';
    protected $selectInsertSql = 'SELECT %DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%';
    protected $updateSql       = 'UPDATE %TABLE% SET %SET% FROM %TABLE% %JOIN% %WHERE% %LIMIT% %LOCK%%COMMENT%';
    protected $deleteSql       = 'DELETE FROM %TABLE% %USING% FROM %TABLE% %JOIN% %WHERE% %LIMIT% %LOCK%%COMMENT%';
    protected $insertSql       = 'INSERT INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMENT%';
    protected $insertAllSql    = 'INSERT INTO %TABLE% (%FIELD%) %DATA% %COMMENT%';
    protected function parseOrder(Query $query, $order)
    {
        if (empty($order)) {
            return ' ORDER BY rand()';
        }
        foreach ($order as $key => $val) {
            if ($val instanceof Expression) {
                $array[] = $val->getValue();
            } elseif ('[rand]' == $val) {
                $array[] = $this->parseRand($query);
            } else {
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
    protected function parseRand(Query $query)
    {
        return 'rand()';
    }
    public function parseKey(Query $query, $key, $strict = false)
    {
        if (is_numeric($key)) {
            return $key;
        } elseif ($key instanceof Expression) {
            return $key->getValue();
        }
        $key = trim($key);
        if (strpos($key, '.') && !preg_match('/[,\'\"\(\)\[\s]/', $key)) {
            list($table, $key) = explode('.', $key, 2);
            $alias = $query->getOptions('alias');
            if ('__TABLE__' == $table) {
                $table = $query->getOptions('table');
                $table = is_array($table) ? array_shift($table) : $table;
            }
            if (isset($alias[$table])) {
                $table = $alias[$table];
            }
        }
        if ($strict && !preg_match('/^[\w\.\*]+$/', $key)) {
            throw new Exception('not support data:' . $key);
        }
        if ('*' != $key && !preg_match('/[,\'\"\*\(\)\[.\s]/', $key)) {
            $key = '[' . $key . ']';
        }
        if (isset($table)) {
            $key = '[' . $table . '].' . $key;
        }
        return $key;
    }
    protected function parseLimit(Query $query, $limit)
    {
        if (empty($limit)) {
            return '';
        }
        $limit = explode(',', $limit);
        if (count($limit) > 1) {
            $limitStr = '(T1.ROW_NUMBER BETWEEN ' . $limit[0] . ' + 1 AND ' . $limit[0] . ' + ' . $limit[1] . ')';
        } else {
            $limitStr = '(T1.ROW_NUMBER BETWEEN 1 AND ' . $limit[0] . ")";
        }
        return 'WHERE ' . $limitStr;
    }
    public function selectInsert(Query $query, $fields, $table)
    {
        $this->selectSql = $this->selectInsertSql;
        return parent::selectInsert($query, $fields, $table);
    }
}
