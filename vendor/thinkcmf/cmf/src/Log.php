<?php
namespace think;
class Log implements LoggerInterface
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    const SQL       = 'sql';
    protected $log = [];
    protected $config = [];
    protected $driver;
    protected $key;
    protected $allowWrite = true;
    protected $app;
    public function __construct(App $app)
    {
        $this->app = $app;
    }
    public static function __make(App $app, Config $config)
    {
        return (new static($app))->init($config->pull('log'));
    }
    public function init($config = [])
    {
        $type = isset($config['type']) ? $config['type'] : 'File';
        $this->config = $config;
        unset($config['type']);
        if (!empty($config['close'])) {
            $this->allowWrite = false;
        }
        $this->driver = Loader::factory($type, '\\think\\log\\driver\\', $config);
        return $this;
    }
    public function getLog($type = '')
    {
        return $type ? $this->log[$type] : $this->log;
    }
    public function record($msg, $type = 'info', array $context = [])
    {
        if (!$this->allowWrite) {
            return;
        }
        if (is_string($msg) && !empty($context)) {
            $replace = [];
            foreach ($context as $key => $val) {
                $replace['{' . $key . '}'] = $val;
            }
            $msg = strtr($msg, $replace);
        }
        if (PHP_SAPI == 'cli') {
            if (empty($this->config['level']) || in_array($type, $this->config['level'])) {
                $this->write($msg, $type, true);
            	$this->log[$type][] = $msg;
	    }
        } else {
            $this->log[$type][] = $msg;
        }
        return $this;
    }
    public function clear()
    {
        $this->log = [];
        return $this;
    }
    public function key($key)
    {
        $this->key = $key;
        return $this;
    }
    public function check($config)
    {
        if ($this->key && !empty($config['allow_key']) && !in_array($this->key, $config['allow_key'])) {
            return false;
        }
        return true;
    }
    public function close()
    {
        $this->allowWrite = false;
        $this->log        = [];
        return $this;
    }
    public function save()
    {
        if (empty($this->log) || !$this->allowWrite) {
            return true;
        }
        if (!$this->check($this->config)) {
            return false;
        }
        $log = [];
        foreach ($this->log as $level => $info) {
            if (!$this->app->isDebug() && 'debug' == $level) {
                continue;
            }
            if (empty($this->config['level']) || in_array($level, $this->config['level'])) {
                $log[$level] = $info;
                $this->app['hook']->listen('log_level', [$level, $info]);
            }
        }
        $result = $this->driver->save($log, true);
        if ($result) {
            $this->log = [];
        }
        return $result;
    }
    public function write($msg, $type = 'info', $force = false)
    {
        if (empty($this->config['level'])) {
            $force = true;
        }
        if (true === $force || in_array($type, $this->config['level'])) {
            $log[$type][] = $msg;
        } else {
            return false;
        }
        $this->app['hook']->listen('log_write', $log);
        return $this->driver->save($log, false);
    }
    public function log($level, $message, array $context = [])
    {
        $this->record($message, $level, $context);
    }
    public function emergency($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }
    public function alert($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }
    public function critical($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }
    public function error($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }
    public function warning($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }
    public function notice($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }
    public function info($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }
    public function debug($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }
    public function sql($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }
    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['app']);
        return $data;
    }
}
