<?php










namespace think\session\driver;

use SessionHandlerInterface;
use think\Exception;

class Redis implements SessionHandlerInterface
{
    
    protected $handler = null;
    protected $config  = [
        'host'         => '127.0.0.1', 
        'port'         => 6379, 
        'password'     => '', 
        'select'       => 0, 
        'expire'       => 3600, 
        'timeout'      => 0, 
        'persistent'   => true, 
        'session_name' => '', 
    ];

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    
    public function open($savePath, $sessName)
    {
        if (extension_loaded('redis')) {
            $this->handler = new \Redis;

            
            $func = $this->config['persistent'] ? 'pconnect' : 'connect';
            $this->handler->$func($this->config['host'], $this->config['port'], $this->config['timeout']);

            if ('' != $this->config['password']) {
                $this->handler->auth($this->config['password']);
            }

            if (0 != $this->config['select']) {
                $this->handler->select($this->config['select']);
            }
        } elseif (class_exists('\Predis\Client')) {
            $params = [];
            foreach ($this->config as $key => $val) {
                if (in_array($key, ['aggregate', 'cluster', 'connections', 'exceptions', 'prefix', 'profile', 'replication'])) {
                    $params[$key] = $val;
                    unset($this->config[$key]);
                }
            }
            $this->handler = new \Predis\Client($this->config, $params);
        } else {
            throw new \BadFunctionCallException('not support: redis');
        }

        return true;
    }

    
    public function close()
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
        $this->handler->close();
        $this->handler = null;

        return true;
    }

    
    public function read($sessID)
    {
        return (string) $this->handler->get($this->config['session_name'] . $sessID);
    }

    
    public function write($sessID, $sessData)
    {
        if ($this->config['expire'] > 0) {
            $result = $this->handler->setex($this->config['session_name'] . $sessID, $this->config['expire'], $sessData);
        } else {
            $result = $this->handler->set($this->config['session_name'] . $sessID, $sessData);
        }

        return $result ? true : false;
    }

    
    public function destroy($sessID)
    {
        return $this->handler->del($this->config['session_name'] . $sessID) > 0;
    }

    
    public function gc($sessMaxLifeTime)
    {
        return true;
    }

    
    public function lock($sessID, $timeout = 10)
    {
        if (null == $this->handler) {
            $this->open('', '');
        }

        $lockKey = 'LOCK_PREFIX_' . $sessID;
        
        $isLock = $this->handler->setnx($lockKey, 1);
        if ($isLock) {
            
            $this->handler->expire($lockKey, $timeout);
            return true;
        }

        return false;
    }

    
    public function unlock($sessID)
    {
        if (null == $this->handler) {
            $this->open('', '');
        }

        $this->handler->del('LOCK_PREFIX_' . $sessID);
    }
}
