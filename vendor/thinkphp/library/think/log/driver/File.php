<?php










namespace think\log\driver;

use think\App;


class File
{
    protected $config = [
        'time_format' => 'c',
        'single'      => false,
        'file_size'   => 2097152,
        'path'        => '',
        'apart_level' => [],
        'max_files'   => 0,
        'json'        => false,
    ];

    protected $app;

    
    public function __construct(App $app, $config = [])
    {
        $this->app = $app;

        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }

        if (empty($this->config['path'])) {
            $this->config['path'] = $this->app->getRuntimePath() . 'log' . DIRECTORY_SEPARATOR;
        } elseif (substr($this->config['path'], -1) != DIRECTORY_SEPARATOR) {
            $this->config['path'] .= DIRECTORY_SEPARATOR;
        }
    }

    
    public function save(array $log = [], $append = false)
    {
        $destination = $this->getMasterLogFile();

        $path = dirname($destination);
        !is_dir($path) && mkdir($path, 0755, true);

        $info = [];

        foreach ($log as $type => $val) {

            foreach ($val as $msg) {
                if (!is_string($msg)) {
                    $msg = var_export($msg, true);
                }

                $info[$type][] = $this->config['json'] ? $msg : '[ ' . $type . ' ] ' . $msg;
            }

            if (!$this->config['json'] && (true === $this->config['apart_level'] || in_array($type, $this->config['apart_level']))) {
                
                $filename = $this->getApartLevelFile($path, $type);

                $this->write($info[$type], $filename, true, $append);

                unset($info[$type]);
            }
        }

        if ($info) {
            return $this->write($info, $destination, false, $append);
        }

        return true;
    }

    
    protected function write($message, $destination, $apart = false, $append = false)
    {
        
        $this->checkLogSize($destination);

        
        $info['timestamp'] = date($this->config['time_format']);

        foreach ($message as $type => $msg) {
            $msg = is_array($msg) ? implode(PHP_EOL, $msg) : $msg;
            if (PHP_SAPI == 'cli') {
                $info['msg']  = $msg;
                $info['type'] = $type;
            } else {
                $info[$type] = $msg;
            }
        }

        if (PHP_SAPI == 'cli') {
            $message = $this->parseCliLog($info);
        } else {
            
            $this->getDebugLog($info, $append, $apart);

            $message = $this->parseLog($info);
        }

        return error_log($message, 3, $destination);
    }

    
    protected function getMasterLogFile()
    {
        if ($this->config['max_files']) {
            $files = glob($this->config['path'] . '*.log');

            try {
                if (count($files) > $this->config['max_files']) {
                    unlink($files[0]);
                }
            } catch (\Exception $e) {
            }
        }

        $cli = PHP_SAPI == 'cli' ? '_cli' : '';

        if ($this->config['single']) {
            $name = is_string($this->config['single']) ? $this->config['single'] : 'single';

            $destination = $this->config['path'] . $name . $cli . '.log';
        } else {
            if ($this->config['max_files']) {
                $filename = date('Ymd') . $cli . '.log';
            } else {
                $filename = date('Ym') . DIRECTORY_SEPARATOR . date('d') . $cli . '.log';
            }

            $destination = $this->config['path'] . $filename;
        }

        return $destination;
    }

    
    protected function getApartLevelFile($path, $type)
    {
        $cli = PHP_SAPI == 'cli' ? '_cli' : '';

        if ($this->config['single']) {
            $name = is_string($this->config['single']) ? $this->config['single'] : 'single';
        } elseif ($this->config['max_files']) {
            $name = date('Ymd');
        } else {
            $name = date('d');
        }

        return $path . DIRECTORY_SEPARATOR . $name . '_' . $type . $cli . '.log';
    }

    
    protected function checkLogSize($destination)
    {
        if (is_file($destination) && floor($this->config['file_size']) <= filesize($destination)) {
            try {
                rename($destination, dirname($destination) . DIRECTORY_SEPARATOR . time() . '-' . basename($destination));
            } catch (\Exception $e) {
            }
        }
    }

    
    protected function parseCliLog($info)
    {
        if ($this->config['json']) {
            $message = json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        } else {
            $now = $info['timestamp'];
            unset($info['timestamp']);

            $message = implode(PHP_EOL, $info);

            $message = "[{$now}]" . $message . PHP_EOL;
        }

        return $message;
    }

    
    protected function parseLog($info)
    {
        $requestInfo = [
            'ip'     => $this->app['request']->ip(),
            'method' => $this->app['request']->method(),
            'host'   => $this->app['request']->host(),
            'uri'    => $this->app['request']->url(),
        ];

        if ($this->config['json']) {
            $info = $requestInfo + $info;
            return json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        }

        array_unshift($info, "---------------------------------------------------------------" . PHP_EOL . "\r\n[{$info['timestamp']}] {$requestInfo['ip']} {$requestInfo['method']} {$requestInfo['host']}{$requestInfo['uri']}");
        unset($info['timestamp']);

        return implode(PHP_EOL, $info) . PHP_EOL;
    }

    protected function getDebugLog(&$info, $append, $apart)
    {
        if ($this->app->isDebug() && $append) {

            if ($this->config['json']) {
                
                $runtime = round(microtime(true) - $this->app->getBeginTime(), 10);
                $reqs    = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';

                $memory_use = number_format((memory_get_usage() - $this->app->getBeginMem()) / 1024, 2);

                $info = [
                    'runtime' => number_format($runtime, 6) . 's',
                    'reqs'    => $reqs . 'req/s',
                    'memory'  => $memory_use . 'kb',
                    'file'    => count(get_included_files()),
                ] + $info;

            } elseif (!$apart) {
                
                $runtime = round(microtime(true) - $this->app->getBeginTime(), 10);
                $reqs    = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';

                $memory_use = number_format((memory_get_usage() - $this->app->getBeginMem()) / 1024, 2);

                $time_str   = '[运行时间：' . number_format($runtime, 6) . 's] [吞吐率：' . $reqs . 'req/s]';
                $memory_str = ' [内存消耗：' . $memory_use . 'kb]';
                $file_load  = ' [文件加载：' . count(get_included_files()) . ']';

                array_unshift($info, $time_str . $memory_str . $file_load);
            }
        }
    }
}
