<?php










namespace think;

use think\model\Collection as ModelCollection;
use think\response\Redirect;

class Debug
{
    
    protected $config = [];

    
    protected $info = [];

    
    protected $mem = [];

    
    protected $app;

    public function __construct(App $app, array $config = [])
    {
        $this->app    = $app;
        $this->config = $config;
    }

    public static function __make(App $app, Config $config)
    {
        return new static($app, $config->pull('trace'));
    }

    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    
    public function remark($name, $value = '')
    {
        
        $this->info[$name] = is_float($value) ? $value : microtime(true);

        if ('time' != $value) {
            $this->mem['mem'][$name]  = is_float($value) ? $value : memory_get_usage();
            $this->mem['peak'][$name] = memory_get_peak_usage();
        }
    }

    
    public function getRangeTime($start, $end, $dec = 6)
    {
        if (!isset($this->info[$end])) {
            $this->info[$end] = microtime(true);
        }

        return number_format(($this->info[$end] - $this->info[$start]), $dec);
    }

    
    public function getUseTime($dec = 6)
    {
        return number_format((microtime(true) - $this->app->getBeginTime()), $dec);
    }

    
    public function getThroughputRate()
    {
        return number_format(1 / $this->getUseTime(), 2) . 'req/s';
    }

    
    public function getRangeMem($start, $end, $dec = 2)
    {
        if (!isset($this->mem['mem'][$end])) {
            $this->mem['mem'][$end] = memory_get_usage();
        }

        $size = $this->mem['mem'][$end] - $this->mem['mem'][$start];
        $a    = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pos  = 0;

        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }

        return round($size, $dec) . " " . $a[$pos];
    }

    
    public function getUseMem($dec = 2)
    {
        $size = memory_get_usage() - $this->app->getBeginMem();
        $a    = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pos  = 0;

        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }

        return round($size, $dec) . " " . $a[$pos];
    }

    
    public function getMemPeak($start, $end, $dec = 2)
    {
        if (!isset($this->mem['peak'][$end])) {
            $this->mem['peak'][$end] = memory_get_peak_usage();
        }

        $size = $this->mem['peak'][$end] - $this->mem['peak'][$start];
        $a    = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pos  = 0;

        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }

        return round($size, $dec) . " " . $a[$pos];
    }

    
    public function getFile($detail = false)
    {
        if ($detail) {
            $files = get_included_files();
            $info  = [];

            foreach ($files as $key => $file) {
                $info[] = $file . ' ( ' . number_format(filesize($file) / 1024, 2) . ' KB )';
            }

            return $info;
        }

        return count(get_included_files());
    }

    
    public function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
    {
        $label = (null === $label) ? '' : rtrim($label) . ':';
        if ($var instanceof Model || $var instanceof ModelCollection) {
            $var = $var->toArray();
        }

        ob_start();
        var_dump($var);

        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);

        if (PHP_SAPI == 'cli') {
            $output = PHP_EOL . $label . $output . PHP_EOL;
        } else {
            if (!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, $flags);
            }
            $output = '<pre>' . $label . $output . '</pre>';
        }
        if ($echo) {
            echo($output);
            return;
        }
        return $output;
    }

    public function inject(Response $response, &$content)
    {
        $config = $this->config;
        $type   = isset($config['type']) ? $config['type'] : 'Html';

        unset($config['type']);

        $trace = Loader::factory($type, '\\think\\debug\\', $config);

        if ($response instanceof Redirect) {
            //TODO 记录
        } else {
            $output = $trace->output($response, $this->app['log']->getLog());
            if (is_string($output)) {
                
                $pos = strripos($content, '</body>');
                if (false !== $pos) {
                    $content = substr($content, 0, $pos) . $output . substr($content, $pos);
                } else {
                    $content = $content . $output;
                }
            }
        }
    }

    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['app']);

        return $data;
    }
}
