<?php
namespace think;
use think\response\Redirect as RedirectResponse;
class Response
{
    protected $data;
    protected $app;
    protected $contentType = 'text/html';
    protected $charset = 'utf-8';
    protected $code = 200;
    protected $allowCache = true;
    protected $options = [];
    protected $header = [];
    protected $content = null;
    public function __construct($data = '', $code = 200, array $header = [], $options = [])
    {
        $this->data($data);
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $this->contentType($this->contentType, $this->charset);
        $this->code   = $code;
        $this->app    = Container::get('app');
        $this->header = array_merge($this->header, $header);
    }
    public static function create($data = '', $type = '', $code = 200, array $header = [], $options = [])
    {
        $class = false !== strpos($type, '\\') ? $type : '\\think\\response\\' . ucfirst(strtolower($type));
        if (class_exists($class)) {
            return new $class($data, $code, $header, $options);
        }
        return new static($data, $code, $header, $options);
    }
    public function send()
    {
        $this->app['hook']->listen('response_send', $this);
        $data = $this->getContent();
        if ('cli' != PHP_SAPI && $this->app['env']->get('app_trace', $this->app->config('app.app_trace'))) {
            $this->app['debug']->inject($this, $data);
        }
        if (200 == $this->code && $this->allowCache) {
            $cache = $this->app['request']->getCache();
            if ($cache) {
                $this->header['Cache-Control'] = 'max-age=' . $cache[1] . ',must-revalidate';
                $this->header['Last-Modified'] = gmdate('D, d M Y H:i:s') . ' GMT';
                $this->header['Expires']       = gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + $cache[1]) . ' GMT';
                $this->app['cache']->tag($cache[2])->set($cache[0], [$data, $this->header], $cache[1]);
            }
        }
        if (!headers_sent() && !empty($this->header)) {
            http_response_code($this->code);
            foreach ($this->header as $name => $val) {
                header($name . (!is_null($val) ? ':' . $val : ''));
            }
        }
        $this->sendData($data);
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $this->app['hook']->listen('response_end', $this);
        if (!($this instanceof RedirectResponse)) {
            $this->app['session']->flush();
        }
    }
    protected function output($data)
    {
        return $data;
    }
    protected function sendData($data)
    {
        echo $data;
    }
    public function options($options = [])
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }
    public function data($data)
    {
        $this->data = $data;
        return $this;
    }
    public function allowCache($cache)
    {
        $this->allowCache = $cache;
        return $this;
    }
    public function header($name, $value = null)
    {
        if (is_array($name)) {
            $this->header = array_merge($this->header, $name);
        } else {
            $this->header[$name] = $value;
        }
        return $this;
    }
    public function content($content)
    {
        if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable([
            $content,
            '__toString',
        ])
        ) {
            throw new \InvalidArgumentException(sprintf('variable type error： %s', gettype($content)));
        }
        $this->content = (string) $content;
        return $this;
    }
    public function code($code)
    {
        $this->code = $code;
        return $this;
    }
    public function lastModified($time)
    {
        $this->header['Last-Modified'] = $time;
        return $this;
    }
    public function expires($time)
    {
        $this->header['Expires'] = $time;
        return $this;
    }
    public function eTag($eTag)
    {
        $this->header['ETag'] = $eTag;
        return $this;
    }
    public function cacheControl($cache)
    {
        $this->header['Cache-control'] = $cache;
        return $this;
    }
    public function noCache()
    {
        $this->header['Cache-Control'] = 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0';
        $this->header['Pragma']        = 'no-cache';
        return $this;
    }
    public function contentType($contentType, $charset = 'utf-8')
    {
        $this->header['Content-Type'] = $contentType . '; charset=' . $charset;
        return $this;
    }
    public function getHeader($name = '')
    {
        if (!empty($name)) {
            return isset($this->header[$name]) ? $this->header[$name] : null;
        }
        return $this->header;
    }
    public function getData()
    {
        return $this->data;
    }
    public function getContent()
    {
        if (null == $this->content) {
            $content = $this->output($this->data);
            if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable([
                $content,
                '__toString',
            ])
            ) {
                throw new \InvalidArgumentException(sprintf('variable type error： %s', gettype($content)));
            }
            $this->content = (string) $content;
        }
        return $this->content;
    }
    public function getCode()
    {
        return $this->code;
    }
    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['app']);
        return $data;
    }
}
