<?php










namespace think\session\driver;

use SessionHandlerInterface;
use think\Exception;

class Memcache implements SessionHandlerInterface
{
    protected $handler = null;
    protected $config  = [
        'host'         => '127.0.0.1', 
        'port'         => 11211, 
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
        
        if (!extension_loaded('memcache')) {
            throw new Exception('not support:memcache');
        }

        $this->handler = new \Memcache;

        
        $hosts = explode(',', $this->config['host']);
        $ports = explode(',', $this->config['port']);

        if (empty($ports[0])) {
            $ports[0] = 11211;
        }

        
        foreach ((array) $hosts as $i => $host) {
            $port = isset($ports[$i]) ? $ports[$i] : $ports[0];
            $this->config['timeout'] > 0 ?
            $this->handler->addServer($host, $port, $this->config['persistent'], 1, $this->config['timeout']) :
            $this->handler->addServer($host, $port, $this->config['persistent'], 1);
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
        return $this->handler->set($this->config['session_name'] . $sessID, $sessData, 0, $this->config['expire']);
    }

    
    public function destroy($sessID)
    {
        return $this->handler->delete($this->config['session_name'] . $sessID);
    }

    
    public function gc($sessMaxLifeTime)
    {
        return true;
    }
}
