<?php
namespace think\db\builder;
use think\db\Builder;
use think\db\Query;
class Pgsql extends Builder
{
    protected $insertSql    = 'INSERT INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMENT%';
    protected $insertAllSql = 'INSERT INTO %TABLE% (%FIELD%) %DATA% %COMMENT%';
    public function parseLimit(Query $query, $limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr .= ' LIMIT ' . $limit[1] . ' OFFSET ' . $limit[0] . ' ';
            } else {
                $limitStr .= ' LIMIT ' . $limit[0] . ' ';
            }
        }
        return $limitStr;
    }
    public function parseKey(Query $query, $key, $strict = false)
    {
        if (is_numeric($key)) {
            return $key;
        } elseif ($key instanceof Expression) {
            return $key->getValue();
        }
        $key = trim($key);
        if (strpos($key, '->') && false === strpos($key, '(')) {
            list($field, $name) = explode('->', $key);
            $key                = $field . '->>\'' . $name . '\'';
        } elseif (strpos($key, '.')) {
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
        if (isset($table)) {
            $key = $table . '.' . $key;
        }
        return $key;
    }
    protected function parseRand(Query $query)
    {
        return 'RANDOM()';
    }
}
