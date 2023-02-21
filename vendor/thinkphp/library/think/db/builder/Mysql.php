<?php
namespace think\db\builder;
use think\db\Builder;
use think\db\Expression;
use think\db\Query;
use think\Exception;
class Mysql extends Builder
{
    protected $parser = [
        'parseCompare'     => ['=', '<>', '>', '>=', '<', '<='],
        'parseLike'        => ['LIKE', 'NOT LIKE'],
        'parseBetween'     => ['NOT BETWEEN', 'BETWEEN'],
        'parseIn'          => ['NOT IN', 'IN'],
        'parseExp'         => ['EXP'],
        'parseRegexp'      => ['REGEXP', 'NOT REGEXP'],
        'parseNull'        => ['NOT NULL', 'NULL'],
        'parseBetweenTime' => ['BETWEEN TIME', 'NOT BETWEEN TIME'],
        'parseTime'        => ['< TIME', '> TIME', '<= TIME', '>= TIME'],
        'parseExists'      => ['NOT EXISTS', 'EXISTS'],
        'parseColumn'      => ['COLUMN'],
    ];
    protected $insertAllSql = '%INSERT% INTO %TABLE% (%FIELD%) VALUES %DATA% %COMMENT%';
    protected $updateSql    = 'UPDATE %TABLE% %JOIN% SET %SET% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%';
    public function insertAll(Query $query, $dataSet, $replace = false)
    {
        $options = $query->getOptions();
        if ('*' == $options['field']) {
            $allowFields = $this->connection->getTableFields($options['table']);
        } else {
            $allowFields = $options['field'];
        }
        $bind = $this->connection->getFieldsBind($options['table']);
        foreach ($dataSet as $k => $data) {
            $data = $this->parseData($query, $data, $allowFields, $bind);
            $values[] = '( ' . implode(',', array_values($data)) . ' )';
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
                implode(' , ', $values),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertAllSql);
    }
    protected function parseRegexp(Query $query, $key, $exp, $value, $field)
    {
        if ($value instanceof Expression) {
            $value = $value->getValue();
        }
        return $key . ' ' . $exp . ' ' . $value;
    }
    public function parseKey(Query $query, $key, $strict = false)
    {
        if (is_numeric($key)) {
            return $key;
        } elseif ($key instanceof Expression) {
            return $key->getValue();
        }
        $key = trim($key);
        if(strpos($key, '->>') && false === strpos($key, '(')){
            list($field, $name) = explode('->>', $key, 2);
            return $this->parseKey($query, $field, true) . '->>\'$' . (strpos($name, '[') === 0 ? '' : '.') . str_replace('->>', '.', $name) . '\'';
        }
        elseif (strpos($key, '->') && false === strpos($key, '(')) {
            list($field, $name) = explode('->', $key, 2);
            return 'json_extract(' . $this->parseKey($query, $field, true) . ', \'$' . (strpos($name, '[') === 0 ? '' : '.') . str_replace('->', '.', $name) . '\')';
        } elseif (strpos($key, '.') && !preg_match('/[,\'\"\(\)`\s]/', $key)) {
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
        if ('*' != $key && !preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }
        if (isset($table)) {
            if (strpos($table, '.')) {
                $table = str_replace('.', '`.`', $table);
            }
            $key = '`' . $table . '`.' . $key;
        }
        return $key;
    }
    protected function parseRand(Query $query)
    {
        return 'rand()';
    }
}
