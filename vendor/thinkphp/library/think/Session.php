<?php
namespace think;
use think\exception\ClassNotFoundException;
class Session
{
    protected $config = [];
    protected $prefix = '';
    protected $init = null;
    protected $lockDriver = null;
    protected $sessKey = 'PHPSESSID';
    protected $lockTimeout = 3;
    protected $lock = false;
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    public function prefix($prefix = '')
    {
        empty($this->init) && $this->boot();
        if (empty($prefix) && null !== $prefix) {
            return $this->prefix;
        } else {
            $this->prefix = $prefix;
        }
    }
    public static function __make(Config $config)
    {
        return new static($config->pull('session'));
    }
    public function setConfig(array $config = [])
    {
        $this->config = array_merge($this->config, array_change_key_case($config));
        if (isset($config['prefix'])) {
            $this->prefix = $config['prefix'];
        }
        if (isset($config['use_lock'])) {
            $this->lock = $config['use_lock'];
        }
    }
    public function inited()
    {
        $this->init = true;
    }
    public function init(array $config = [])
    {
        $config = $config ?: $this->config;
        $isDoStart = false;
        if (isset($config['use_trans_sid'])) {
            ini_set('session.use_trans_sid', $config['use_trans_sid'] ? 1 : 0);
        }
        if (!empty($config['auto_start']) && PHP_SESSION_ACTIVE != session_status()) {
            ini_set('session.auto_start', 0);
            $isDoStart = true;
        }
        if (isset($config['prefix'])) {
            $this->prefix = $config['prefix'];
        }
        if (isset($config['use_lock'])) {
            $this->lock = $config['use_lock'];
        }
        if (isset($config['var_session_id']) && isset($_REQUEST[$config['var_session_id']])) {
            session_id($_REQUEST[$config['var_session_id']]);
        } elseif (isset($config['id']) && !empty($config['id'])) {
            session_id($config['id']);
        }
        if (isset($config['name'])) {
            session_name($config['name']);
        }
        if (isset($config['path'])) {
            session_save_path($config['path']);
        }
        if (isset($config['domain'])) {
            ini_set('session.cookie_domain', $config['domain']);
        }
        if (isset($config['expire'])) {
            ini_set('session.gc_maxlifetime', $config['expire']);
            ini_set('session.cookie_lifetime', $config['expire']);
        }
        if (isset($config['secure'])) {
            ini_set('session.cookie_secure', $config['secure']);
        }
        if (isset($config['httponly'])) {
            ini_set('session.cookie_httponly', $config['httponly']);
        }
        if (isset($config['use_cookies'])) {
            ini_set('session.use_cookies', $config['use_cookies'] ? 1 : 0);
        }
        if (isset($config['cache_limiter'])) {
            session_cache_limiter($config['cache_limiter']);
        }
        if (isset($config['cache_expire'])) {
            session_cache_expire($config['cache_expire']);
        }
        if (!empty($config['type'])) {
            $class = false !== strpos($config['type'], '\\') ? $config['type'] : '\\think\\session\\driver\\' . ucwords($config['type']);
            if (!class_exists($class) || !session_set_save_handler(new $class($config))) {
                throw new ClassNotFoundException('error session handler:' . $class, $class);
            }
        }
        if ($isDoStart) {
            $this->start();
        } else {
            $this->init = false;
        }
        return $this;
    }
    public function boot()
    {
        if (is_null($this->init)) {
            $this->init();
        }
        if (false === $this->init) {
            if (PHP_SESSION_ACTIVE != session_status()) {
                $this->start();
            }
            $this->init = true;
        }
    }
    public function set($name, $value, $prefix = null)
    {
        $this->lock();
        empty($this->init) && $this->boot();
        $prefix = !is_null($prefix) ? $prefix : $this->prefix;
        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                $_SESSION[$prefix][$name1][$name2] = $value;
            } else {
                $_SESSION[$name1][$name2] = $value;
            }
        } elseif ($prefix) {
            $_SESSION[$prefix][$name] = $value;
        } else {
            $_SESSION[$name] = $value;
        }
        $this->unlock();
    }
    public function get($name = '', $prefix = null)
    {
        $this->lock();
        empty($this->init) && $this->boot();
        $prefix = !is_null($prefix) ? $prefix : $this->prefix;
        $value = $prefix ? (!empty($_SESSION[$prefix]) ? $_SESSION[$prefix] : []) : $_SESSION;
        if ('' != $name) {
            $name = explode('.', $name);
            foreach ($name as $val) {
                if (isset($value[$val])) {
                    $value = $value[$val];
                } else {
                    $value = null;
                    break;
                }
            }
        }
        $this->unlock();
        return $value;
    }
    protected function initDriver()
    {
        $config = $this->config;
        if (!empty($config['type']) && isset($config['use_lock']) && $config['use_lock']) {
            $class = false !== strpos($config['type'], '\\') ? $config['type'] : '\\think\\session\\driver\\' . ucwords($config['type']);
            if (class_exists($class) && method_exists($class, 'lock') && method_exists($class, 'unlock')) {
                $this->lockDriver = new $class($config);
            }
        }
        if (isset($config['name']) && $config['name']) {
            $this->sessKey = $config['name'];
        }
        if (isset($config['lock_timeout']) && $config['lock_timeout'] > 0) {
            $this->lockTimeout = $config['lock_timeout'];
        }
    }
    protected function lock()
    {
        if (empty($this->lock)) {
            return;
        }
        $this->initDriver();
        if (null !== $this->lockDriver && method_exists($this->lockDriver, 'lock')) {
            $t = time();
            $sessID = isset($_COOKIE[$this->sessKey]) ? $_COOKIE[$this->sessKey] : '';
            do {
                if (time() - $t > $this->lockTimeout) {
                    $this->unlock();
                }
            } while (!$this->lockDriver->lock($sessID, $this->lockTimeout));
        }
    }
    protected function unlock()
    {
        if (empty($this->lock)) {
            return;
        }
        $this->pause();
        if ($this->lockDriver && method_exists($this->lockDriver, 'unlock')) {
            $sessID = isset($_COOKIE[$this->sessKey]) ? $_COOKIE[$this->sessKey] : '';
            $this->lockDriver->unlock($sessID);
        }
    }
    public function pull($name, $prefix = null)
    {
        $result = $this->get($name, $prefix);
        if ($result) {
            $this->delete($name, $prefix);
            return $result;
        } else {
            return;
        }
    }
    public function flash($name, $value)
    {
        $this->set($name, $value);
        if (!$this->has('__flash__.__time__')) {
            $this->set('__flash__.__time__', $_SERVER['REQUEST_TIME_FLOAT']);
        }
        $this->push('__flash__', $name);
    }
    public function flush()
    {
        if (!$this->init) {
            return;
        }
        $item = $this->get('__flash__');
        if (!empty($item)) {
            $time = $item['__time__'];
            if ($_SERVER['REQUEST_TIME_FLOAT'] > $time) {
                unset($item['__time__']);
                $this->delete($item);
                $this->set('__flash__', []);
            }
        }
    }
    public function delete($name, $prefix = null)
    {
        empty($this->init) && $this->boot();
        $prefix = !is_null($prefix) ? $prefix : $this->prefix;
        if (is_array($name)) {
            foreach ($name as $key) {
                $this->delete($key, $prefix);
            }
        } elseif (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                unset($_SESSION[$prefix][$name1][$name2]);
            } else {
                unset($_SESSION[$name1][$name2]);
            }
        } else {
            if ($prefix) {
                unset($_SESSION[$prefix][$name]);
            } else {
                unset($_SESSION[$name]);
            }
        }
    }
    public function clear($prefix = null)
    {
        empty($this->init) && $this->boot();
        $prefix = !is_null($prefix) ? $prefix : $this->prefix;
        if ($prefix) {
            unset($_SESSION[$prefix]);
        } else {
            $_SESSION = [];
        }
    }
    public function has($name, $prefix = null)
    {
        empty($this->init) && $this->boot();
        $prefix = !is_null($prefix) ? $prefix : $this->prefix;
        $value  = $prefix ? (!empty($_SESSION[$prefix]) ? $_SESSION[$prefix] : []) : $_SESSION;
        $name = explode('.', $name);
        foreach ($name as $val) {
            if (!isset($value[$val])) {
                return false;
            } else {
                $value = $value[$val];
            }
        }
        return true;
    }
    public function push($key, $value)
    {
        $array = $this->get($key);
        if (is_null($array)) {
            $array = [];
        }
        $array[] = $value;
        $this->set($key, $array);
    }
    public function start()
    {
        session_start();
        $this->init = true;
    }
    public function destroy()
    {
        if (!empty($_SESSION)) {
            $_SESSION = [];
        }
        session_unset();
        session_destroy();
        $this->init       = null;
        $this->lockDriver = null;
    }
    public function regenerate($delete = false)
    {
        session_regenerate_id($delete);
    }
    public function pause()
    {
        session_write_close();
        $this->init = false;
    }
}
