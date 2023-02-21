<?php
namespace think;
use think\db\Connection;
class Db
{
    protected static $connection;
    protected static $config = [];
    public static $queryTimes = 0;
    public static $executeTimes = 0;
    public static function init($config = [])
    {
        self::$config = $config;
        if (empty($config['query'])) {
            self::$config['query'] = '\\think\\db\\Query';
        }
    }
    public static function getConfig($name = '')
    {
        if ('' === $name) {
            return self::$config;
        }
        return isset(self::$config[$name]) ? self::$config[$name] : null;
    }
    public static function connect($config = [], $name = false, $query = '')
    {
        $options = self::parseConfig($config ?: self::$config);
        $query = $query ?: $options['query'];
        self::$connection = Connection::instance($options, $name);
        return new $query(self::$connection);
    }
    private static function parseConfig($config)
    {
        if (is_string($config) && false === strpos($config, '/')) {
            $config = isset(self::$config[$config]) ? self::$config[$config] : self::$config;
        }
        $result = is_string($config) ? self::parseDsnConfig($config) : $config;
        if (empty($result['query'])) {
            $result['query'] = self::$config['query'];
        }
        return $result;
    }
    private static function parseDsnConfig($dsnStr)
    {
        $info = parse_url($dsnStr);
        if (!$info) {
            return [];
        }
        $dsn = [
            'type'     => $info['scheme'],
            'username' => isset($info['user']) ? $info['user'] : '',
            'password' => isset($info['pass']) ? $info['pass'] : '',
            'hostname' => isset($info['host']) ? $info['host'] : '',
            'hostport' => isset($info['port']) ? $info['port'] : '',
            'database' => !empty($info['path']) ? ltrim($info['path'], '/') : '',
            'charset'  => isset($info['fragment']) ? $info['fragment'] : 'utf8',
        ];
        if (isset($info['query'])) {
            parse_str($info['query'], $dsn['params']);
        } else {
            $dsn['params'] = [];
        }
        return $dsn;
    }
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([static::connect(), $method], $args);
    }
}
