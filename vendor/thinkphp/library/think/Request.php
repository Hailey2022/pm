<?php
namespace think;
use think\facade\Cookie;
use think\facade\Session;
class Request
{
    protected $config = [
        'var_method'       => '_method',
        'var_ajax'         => '_ajax',
        'var_pjax'         => '_pjax',
        'var_pathinfo'     => 's',
        'pathinfo_fetch'   => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
        'default_filter'   => '',
        'url_domain_root'  => '',
        'https_agent_name' => '',
        'http_agent_ip'    => 'HTTP_X_REAL_IP',
        'url_html_suffix'  => 'html',
    ];
    protected $method;
    protected $host;
    protected $domain;
    protected $subDomain;
    protected $panDomain;
    protected $url;
    protected $baseUrl;
    protected $baseFile;
    protected $root;
    protected $pathinfo;
    protected $path;
    protected $routeInfo = [];
    protected $dispatch;
    protected $module;
    protected $controller;
    protected $action;
    protected $langset;
    protected $param = [];
    protected $get = [];
    protected $post = [];
    protected $request = [];
    protected $route = [];
    protected $put;
    protected $session = [];
    protected $file = [];
    protected $cookie = [];
    protected $server = [];
    protected $env = [];
    protected $header = [];
    protected $mimeType = [
        'xml'   => 'application/xml,text/xml,application/x-xml',
        'json'  => 'application/json,text/x-json,application/jsonrequest,text/json',
        'js'    => 'text/javascript,application/javascript,application/x-javascript',
        'css'   => 'text/css',
        'rss'   => 'application/rss+xml',
        'yaml'  => 'application/x-yaml,text/yaml',
        'atom'  => 'application/atom+xml',
        'pdf'   => 'application/pdf',
        'text'  => 'text/plain',
        'image' => 'image/png,image/jpg,image/jpeg,image/pjpeg,image/gif,image/webp,image*',
    ];
    protected $content;
    protected $filter;
    protected $hook = [];
    protected $input;
    protected $cache;
    protected $isCheckCache;
    protected $secureKey;
    protected $mergeParam = false;
    public function __construct(array $options = [])
    {
        $this->init($options);
        $this->input = file_get_contents('php://input');
    }
    public function init(array $options = [])
    {
        $this->config = array_merge($this->config, $options);
        if (is_null($this->filter) && !empty($this->config['default_filter'])) {
            $this->filter = $this->config['default_filter'];
        }
    }
    public function config($name = null)
    {
        if (is_null($name)) {
            return $this->config;
        }
        return isset($this->config[$name]) ? $this->config[$name] : null;
    }
    public static function __make(App $app, Config $config)
    {
        $request = new static($config->pull('app'));
        $request->server = $_SERVER;
        $request->env    = $app['env']->get();
        return $request;
    }
    public function __call($method, $args)
    {
        if (array_key_exists($method, $this->hook)) {
            array_unshift($args, $this);
            return call_user_func_array($this->hook[$method], $args);
        }
        throw new Exception('method not exists:' . static::class . '->' . $method);
    }
    public function hook($method, $callback = null)
    {
        if (is_array($method)) {
            $this->hook = array_merge($this->hook, $method);
        } else {
            $this->hook[$method] = $callback;
        }
    }
    public function create($uri, $method = 'GET', $params = [], $cookie = [], $files = [], $server = [], $content = null)
    {
        $server['PATH_INFO']      = '';
        $server['REQUEST_METHOD'] = strtoupper($method);
        $info                     = parse_url($uri);
        if (isset($info['host'])) {
            $server['SERVER_NAME'] = $info['host'];
            $server['HTTP_HOST']   = $info['host'];
        }
        if (isset($info['scheme'])) {
            if ('https' === $info['scheme']) {
                $server['HTTPS']       = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }
        if (isset($info['port'])) {
            $server['SERVER_PORT'] = $info['port'];
            $server['HTTP_HOST']   = $server['HTTP_HOST'] . ':' . $info['port'];
        }
        if (isset($info['user'])) {
            $server['PHP_AUTH_USER'] = $info['user'];
        }
        if (isset($info['pass'])) {
            $server['PHP_AUTH_PW'] = $info['pass'];
        }
        if (!isset($info['path'])) {
            $info['path'] = '/';
        }
        $options     = [];
        $queryString = '';
        $options[strtolower($method)] = $params;
        if (isset($info['query'])) {
            parse_str(html_entity_decode($info['query']), $query);
            if (!empty($params)) {
                $params      = array_replace($query, $params);
                $queryString = http_build_query($params, '', '&');
            } else {
                $params      = $query;
                $queryString = $info['query'];
            }
        } elseif (!empty($params)) {
            $queryString = http_build_query($params, '', '&');
        }
        if ($queryString) {
            parse_str($queryString, $get);
            $options['get'] = isset($options['get']) ? array_merge($get, $options['get']) : $get;
        }
        $server['REQUEST_URI']  = $info['path'] . ('' !== $queryString ? '?' . $queryString : '');
        $server['QUERY_STRING'] = $queryString;
        $options['cookie']      = $cookie;
        $options['param']       = $params;
        $options['file']        = $files;
        $options['server']      = $server;
        $options['url']         = $server['REQUEST_URI'];
        $options['baseUrl']     = $info['path'];
        $options['pathinfo']    = '/' == $info['path'] ? '/' : ltrim($info['path'], '/');
        $options['method']      = $server['REQUEST_METHOD'];
        $options['domain']      = isset($info['scheme']) ? $info['scheme'] . '://' . $server['HTTP_HOST'] : '';
        $options['content']     = $content;
        $request = new static();
        foreach ($options as $name => $item) {
            if (property_exists($request, $name)) {
                $request->$name = $item;
            }
        }
        return $request;
    }
    public function domain($port = false)
    {
        return $this->scheme() . '://' . $this->host($port);
    }
    public function rootDomain()
    {
        $root = $this->config['url_domain_root'];
        if (!$root) {
            $item  = explode('.', $this->host(true));
            $count = count($item);
            $root  = $count > 1 ? $item[$count - 2] . '.' . $item[$count - 1] : $item[0];
        }
        return $root;
    }
    public function subDomain()
    {
        if (is_null($this->subDomain)) {
            $rootDomain = $this->config['url_domain_root'];
            if ($rootDomain) {
                $domain = explode('.', rtrim(stristr($this->host(true), $rootDomain, true), '.'));
            } else {
                $domain = explode('.', $this->host(true), -2);
            }
            $this->subDomain = implode('.', $domain);
        }
        return $this->subDomain;
    }
    public function setPanDomain($domain)
    {
        $this->panDomain = $domain;
        return $this;
    }
    public function panDomain()
    {
        return $this->panDomain;
    }
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
    public function url($complete = false)
    {
        if (!$this->url) {
            if ($this->isCli()) {
                $this->url = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
            } elseif ($this->server('HTTP_X_REWRITE_URL')) {
                $this->url = $this->server('HTTP_X_REWRITE_URL');
            } elseif ($this->server('REQUEST_URI')) {
                $this->url = $this->server('REQUEST_URI');
            } elseif ($this->server('ORIG_PATH_INFO')) {
                $this->url = $this->server('ORIG_PATH_INFO') . (!empty($this->server('QUERY_STRING')) ? '?' . $this->server('QUERY_STRING') : '');
            } else {
                $this->url = '';
            }
        }
        return $complete ? $this->domain() . $this->url : $this->url;
    }
    public function setBaseUrl($url)
    {
        $this->baseUrl = $url;
        return $this;
    }
    public function baseUrl($domain = false)
    {
        if (!$this->baseUrl) {
            $str           = $this->url();
            $this->baseUrl = strpos($str, '?') ? strstr($str, '?', true) : $str;
        }
        return $domain ? $this->domain() . $this->baseUrl : $this->baseUrl;
    }
    public function baseFile($domain = false)
    {
        if (!$this->baseFile) {
            $url = '';
            if (!$this->isCli()) {
                $script_name = basename($this->server('SCRIPT_FILENAME'));
                if (basename($this->server('SCRIPT_NAME')) === $script_name) {
                    $url = $this->server('SCRIPT_NAME');
                } elseif (basename($this->server('PHP_SELF')) === $script_name) {
                    $url = $this->server('PHP_SELF');
                } elseif (basename($this->server('ORIG_SCRIPT_NAME')) === $script_name) {
                    $url = $this->server('ORIG_SCRIPT_NAME');
                } elseif (($pos = strpos($this->server('PHP_SELF'), '/' . $script_name)) !== false) {
                    $url = substr($this->server('SCRIPT_NAME'), 0, $pos) . '/' . $script_name;
                } elseif ($this->server('DOCUMENT_ROOT') && strpos($this->server('SCRIPT_FILENAME'), $this->server('DOCUMENT_ROOT')) === 0) {
                    $url = str_replace('\\', '/', str_replace($this->server('DOCUMENT_ROOT'), '', $this->server('SCRIPT_FILENAME')));
                }
            }
            $this->baseFile = $url;
        }
        return $domain ? $this->domain() . $this->baseFile : $this->baseFile;
    }
    public function setRoot($url = null)
    {
        $this->root = $url;
        return $this;
    }
    public function root($domain = false)
    {
        if (!$this->root) {
            $file = $this->baseFile();
            if ($file && 0 !== strpos($this->url(), $file)) {
                $file = str_replace('\\', '/', dirname($file));
            }
            $this->root = rtrim($file, '/');
        }
        return $domain ? $this->domain() . $this->root : $this->root;
    }
    public function rootUrl()
    {
        $base = $this->root();
        $root = strpos($base, '.') ? ltrim(dirname($base), DIRECTORY_SEPARATOR) : $base;
        if ('' != $root) {
            $root = '/' . ltrim($root, '/');
        }
        return $root;
    }
    public function setPathinfo($pathinfo)
    {
        $this->pathinfo = $pathinfo;
        return $this;
    }
    public function pathinfo()
    {
        if (is_null($this->pathinfo)) {
            if (isset($_GET[$this->config['var_pathinfo']])) {
                $pathinfo = $_GET[$this->config['var_pathinfo']];
                unset($_GET[$this->config['var_pathinfo']]);
                unset($this->get[$this->config['var_pathinfo']]);
            } elseif ($this->isCli()) {
                $pathinfo = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
            } elseif ('cli-server' == PHP_SAPI) {
                $pathinfo = strpos($this->server('REQUEST_URI'), '?') ? strstr($this->server('REQUEST_URI'), '?', true) : $this->server('REQUEST_URI');
            } elseif ($this->server('PATH_INFO')) {
                $pathinfo = $this->server('PATH_INFO');
            }
            if (!isset($pathinfo)) {
                foreach ($this->config['pathinfo_fetch'] as $type) {
                    if ($this->server($type)) {
                        $pathinfo = (0 === strpos($this->server($type), $this->server('SCRIPT_NAME'))) ?
                        substr($this->server($type), strlen($this->server('SCRIPT_NAME'))) : $this->server($type);
                        break;
                    }
                }
            }
            if (!empty($pathinfo)) {
                unset($this->get[$pathinfo], $this->request[$pathinfo]);
            }
            $this->pathinfo = empty($pathinfo) || '/' == $pathinfo ? '' : ltrim($pathinfo, '/');
        }
        return $this->pathinfo;
    }
    public function path()
    {
        if (is_null($this->path)) {
            $suffix   = $this->config['url_html_suffix'];
            $pathinfo = $this->pathinfo();
            if (false === $suffix) {
                $this->path = $pathinfo;
            } elseif ($suffix) {
                $this->path = preg_replace('/\.(' . ltrim($suffix, '.') . ')$/i', '', $pathinfo);
            } else {
                $this->path = preg_replace('/\.' . $this->ext() . '$/i', '', $pathinfo);
            }
        }
        return $this->path;
    }
    public function ext()
    {
        return pathinfo($this->pathinfo(), PATHINFO_EXTENSION);
    }
    public function time($float = false)
    {
        return $float ? $this->server('REQUEST_TIME_FLOAT') : $this->server('REQUEST_TIME');
    }
    public function type()
    {
        $accept = $this->server('HTTP_ACCEPT');
        if (empty($accept)) {
            return false;
        }
        foreach ($this->mimeType as $key => $val) {
            $array = explode(',', $val);
            foreach ($array as $k => $v) {
                if (stristr($accept, $v)) {
                    return $key;
                }
            }
        }
        return false;
    }
    public function mimeType($type, $val = '')
    {
        if (is_array($type)) {
            $this->mimeType = array_merge($this->mimeType, $type);
        } else {
            $this->mimeType[$type] = $val;
        }
    }
    public function method($origin = false)
    {
        if ($origin) {
            return $this->server('REQUEST_METHOD') ?: 'GET';
        } elseif (!$this->method) {
            if (isset($_POST[$this->config['var_method']])) {
                $method = strtolower($_POST[$this->config['var_method']]);
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    $this->method    = strtoupper($method);
                    $this->{$method} = $_POST;
                } else {
                    $this->method = 'POST';
                }
                unset($_POST[$this->config['var_method']]);
            } elseif ($this->server('HTTP_X_HTTP_METHOD_OVERRIDE')) {
                $this->method = strtoupper($this->server('HTTP_X_HTTP_METHOD_OVERRIDE'));
            } else {
                $this->method = $this->server('REQUEST_METHOD') ?: 'GET';
            }
        }
        return $this->method;
    }
    public function isGet()
    {
        return $this->method() == 'GET';
    }
    public function isPost()
    {
        return $this->method() == 'POST';
    }
    public function isPut()
    {
        return $this->method() == 'PUT';
    }
    public function isDelete()
    {
        return $this->method() == 'DELETE';
    }
    public function isHead()
    {
        return $this->method() == 'HEAD';
    }
    public function isPatch()
    {
        return $this->method() == 'PATCH';
    }
    public function isOptions()
    {
        return $this->method() == 'OPTIONS';
    }
    public function isCli()
    {
        return PHP_SAPI == 'cli';
    }
    public function isCgi()
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }
    public function param($name = '', $default = null, $filter = '')
    {
        if (!$this->mergeParam) {
            $method = $this->method(true);
            switch ($method) {
                case 'POST':
                    $vars = $this->post(false);
                    break;
                case 'PUT':
                case 'DELETE':
                case 'PATCH':
                    $vars = $this->put(false);
                    break;
                default:
                    $vars = [];
            }
            $this->param = array_merge($this->param, $this->get(false), $vars, $this->route(false));
            $this->mergeParam = true;
        }
        if (true === $name) {
            $file = $this->file();
            $data = is_array($file) ? array_merge($this->param, $file) : $this->param;
            return $this->input($data, '', $default, $filter);
        }
        return $this->input($this->param, $name, $default, $filter);
    }
    public function setRouteVars(array $route)
    {
        $this->route = array_merge($this->route, $route);
        return $this;
    }
    public function route($name = '', $default = null, $filter = '')
    {
        return $this->input($this->route, $name, $default, $filter);
    }
    public function get($name = '', $default = null, $filter = '')
    {
        if (empty($this->get)) {
            $this->get = $_GET;
        }
        return $this->input($this->get, $name, $default, $filter);
    }
    public function post($name = '', $default = null, $filter = '')
    {
        if (empty($this->post)) {
            $this->post = !empty($_POST) ? $_POST : $this->getInputData($this->input);
        }
        return $this->input($this->post, $name, $default, $filter);
    }
    public function put($name = '', $default = null, $filter = '')
    {
        if (is_null($this->put)) {
            $this->put = $this->getInputData($this->input);
        }
        return $this->input($this->put, $name, $default, $filter);
    }
    protected function getInputData($content)
    {
        if (false !== strpos($this->contentType(), 'json')) {
            return (array) json_decode($content, true);
        } elseif (strpos($content, '=')) {
            parse_str($content, $data);
            return $data;
        }
        return [];
    }
    public function delete($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }
    public function patch($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }
    public function request($name = '', $default = null, $filter = '')
    {
        if (empty($this->request)) {
            $this->request = $_REQUEST;
        }
        return $this->input($this->request, $name, $default, $filter);
    }
    public function session($name = '', $default = null)
    {
        if (empty($this->session)) {
            $this->session = Session::get();
        }
        if ('' === $name) {
            return $this->session;
        }
        $data = $this->getData($this->session, $name);
        return is_null($data) ? $default : $data;
    }
    public function cookie($name = '', $default = null, $filter = '')
    {
        if (empty($this->cookie)) {
            $this->cookie = Cookie::get();
        }
        if (!empty($name)) {
            $data = Cookie::has($name) ? Cookie::get($name) : $default;
        } else {
            $data = $this->cookie;
        }
        $filter = $this->getFilter($filter, $default);
        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
            reset($data);
        } else {
            $this->filterValue($data, $name, $filter);
        }
        return $data;
    }
    public function server($name = '', $default = null)
    {
        if (empty($name)) {
            return $this->server;
        } else {
            $name = strtoupper($name);
        }
        return isset($this->server[$name]) ? $this->server[$name] : $default;
    }
    public function file($name = '')
    {
        if (empty($this->file)) {
            $this->file = isset($_FILES) ? $_FILES : [];
        }
        $files = $this->file;
        if (!empty($files)) {
            if (strpos($name, '.')) {
                list($name, $sub) = explode('.', $name);
            }
            $array = $this->dealUploadFile($files, $name);
            if ('' === $name) {
                return $array;
            } elseif (isset($sub) && isset($array[$name][$sub])) {
                return $array[$name][$sub];
            } elseif (isset($array[$name])) {
                return $array[$name];
            }
        }
        return;
    }
    protected function dealUploadFile($files, $name)
    {
        $array = [];
        foreach ($files as $key => $file) {
            if ($file instanceof File) {
                $array[$key] = $file;
            } elseif (is_array($file['name'])) {
                $item  = [];
                $keys  = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($file['error'][$i] > 0) {
                        if ($name == $key) {
                            $this->throwUploadFileError($file['error'][$i]);
                        } else {
                            continue;
                        }
                    }
                    $temp['key'] = $key;
                    foreach ($keys as $_key) {
                        $temp[$_key] = $file[$_key][$i];
                    }
                    $item[] = (new File($temp['tmp_name']))->setUploadInfo($temp);
                }
                $array[$key] = $item;
            } else {
                if ($file['error'] > 0) {
                    if ($key == $name) {
                        $this->throwUploadFileError($file['error']);
                    } else {
                        continue;
                    }
                }
                $array[$key] = (new File($file['tmp_name']))->setUploadInfo($file);
            }
        }
        return $array;
    }
    protected function throwUploadFileError($error)
    {
        static $fileUploadErrors = [
            1 => 'upload File size exceeds the maximum value',
            2 => 'upload File size exceeds the maximum value',
            3 => 'only the portion of file is uploaded',
            4 => 'no file to uploaded',
            6 => 'upload temp dir not found',
            7 => 'file write error',
        ];
        $msg = $fileUploadErrors[$error];
        throw new Exception($msg);
    }
    public function env($name = '', $default = null)
    {
        if (empty($name)) {
            return $this->env;
        } else {
            $name = strtoupper($name);
        }
        return isset($this->env[$name]) ? $this->env[$name] : $default;
    }
    public function header($name = '', $default = null)
    {
        if (empty($this->header)) {
            $header = [];
            if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
                $header = $result;
            } else {
                $server = $this->server;
                foreach ($server as $key => $val) {
                    if (0 === strpos($key, 'HTTP_')) {
                        $key          = str_replace('_', '-', strtolower(substr($key, 5)));
                        $header[$key] = $val;
                    }
                }
                if (isset($server['CONTENT_TYPE'])) {
                    $header['content-type'] = $server['CONTENT_TYPE'];
                }
                if (isset($server['CONTENT_LENGTH'])) {
                    $header['content-length'] = $server['CONTENT_LENGTH'];
                }
            }
            $this->header = array_change_key_case($header);
        }
        if ('' === $name) {
            return $this->header;
        }
        $name = str_replace('_', '-', strtolower($name));
        return isset($this->header[$name]) ? $this->header[$name] : $default;
    }
    public function arrayReset(array &$data)
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->arrayReset($value);
            }
        }
        reset($data);
    }
    public function input($data = [], $name = '', $default = null, $filter = '')
    {
        if (false === $name) {
            return $data;
        }
        $name = (string) $name;
        if ('' != $name) {
            if (strpos($name, '/')) {
                list($name, $type) = explode('/', $name);
            }
            $data = $this->getData($data, $name);
            if (is_null($data)) {
                return $default;
            }
            if (is_object($data)) {
                return $data;
            }
        }
        $filter = $this->getFilter($filter, $default);
        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
            if (version_compare(PHP_VERSION, '7.1.0', '<')) {
                $this->arrayReset($data);
            }
        } else {
            $this->filterValue($data, $name, $filter);
        }
        if (isset($type) && $data !== $default) {
            $this->typeCast($data, $type);
        }
        return $data;
    }
    protected function getData(array $data, $name)
    {
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                return;
            }
        }
        return $data;
    }
    public function filter($filter = null)
    {
        if (is_null($filter)) {
            return $this->filter;
        }
        $this->filter = $filter;
    }
    protected function getFilter($filter, $default)
    {
        if (is_null($filter)) {
            $filter = [];
        } else {
            $filter = $filter ?: $this->filter;
            if (is_string($filter) && false === strpos($filter, '/')) {
                $filter = explode(',', $filter);
            } else {
                $filter = (array) $filter;
            }
        }
        $filter[] = $default;
        return $filter;
    }
    private function filterValue(&$value, $key, $filters)
    {
        $default = array_pop($filters);
        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                $value = call_user_func($filter, $value);
            } elseif (is_scalar($value)) {
                if (false !== strpos($filter, '/')) {
                    if (!preg_match($filter, $value)) {
                        $value = $default;
                        break;
                    }
                } elseif (!empty($filter)) {
                    $value = filter_var($value, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $value) {
                        $value = $default;
                        break;
                    }
                }
            }
        }
        return $value;
    }
    private function typeCast(&$data, $type)
    {
        switch (strtolower($type)) {
            case 'a':
                $data = (array) $data;
                break;
            case 'd':
                $data = (int) $data;
                break;
            case 'f':
                $data = (float) $data;
                break;
            case 'b':
                $data = (boolean) $data;
                break;
            case 's':
                if (is_scalar($data)) {
                    $data = (string) $data;
                } else {
                    throw new \InvalidArgumentException('variable type error：' . gettype($data));
                }
                break;
        }
    }
    public function has($name, $type = 'param', $checkEmpty = false)
    {
        if (!in_array($type, ['param', 'get', 'post', 'request', 'put', 'patch', 'file', 'session', 'cookie', 'env', 'header', 'route'])) {
            return false;
        }
        if (empty($this->$type)) {
            $param = $this->$type();
        } else {
            $param = $this->$type;
        }
        foreach (explode('.', $name) as $val) {
            if (isset($param[$val])) {
                $param = $param[$val];
            } else {
                return false;
            }
        }
        return ($checkEmpty && '' === $param) ? false : true;
    }
    public function only($name, $type = 'param')
    {
        $param = $this->$type();
        if (is_string($name)) {
            $name = explode(',', $name);
        }
        $item = [];
        foreach ($name as $key => $val) {
            if (is_int($key)) {
                $default = null;
                $key     = $val;
            } else {
                $default = $val;
            }
            if (isset($param[$key])) {
                $item[$key] = $param[$key];
            } elseif (isset($default)) {
                $item[$key] = $default;
            }
        }
        return $item;
    }
    public function except($name, $type = 'param')
    {
        $param = $this->$type();
        if (is_string($name)) {
            $name = explode(',', $name);
        }
        foreach ($name as $key) {
            if (isset($param[$key])) {
                unset($param[$key]);
            }
        }
        return $param;
    }
    public function isSsl()
    {
        if ($this->server('HTTPS') && ('1' == $this->server('HTTPS') || 'on' == strtolower($this->server('HTTPS')))) {
            return true;
        } elseif ('https' == $this->server('REQUEST_SCHEME')) {
            return true;
        } elseif ('443' == $this->server('SERVER_PORT')) {
            return true;
        } elseif ('https' == $this->server('HTTP_X_FORWARDED_PROTO')) {
            return true;
        } elseif ($this->config['https_agent_name'] && $this->server($this->config['https_agent_name'])) {
            return true;
        }
        return false;
    }
    public function isJson()
    {
        return false !== strpos($this->type(), 'json');
    }
    public function isAjax($ajax = false)
    {
        $value  = $this->server('HTTP_X_REQUESTED_WITH');
        $result = 'xmlhttprequest' == strtolower($value) ? true : false;
        if (true === $ajax) {
            return $result;
        }
        $result           = $this->param($this->config['var_ajax']) ? true : $result;
        $this->mergeParam = false;
        return $result;
    }
    public function isPjax($pjax = false)
    {
        $result = !is_null($this->server('HTTP_X_PJAX')) ? true : false;
        if (true === $pjax) {
            return $result;
        }
        $result           = $this->param($this->config['var_pjax']) ? true : $result;
        $this->mergeParam = false;
        return $result;
    }
    public function ip($type = 0, $adv = true)
    {
        $type      = $type ? 1 : 0;
        static $ip = null;
        if (null !== $ip) {
            return $ip[$type];
        }
        $httpAgentIp = $this->config['http_agent_ip'];
        if ($httpAgentIp && $this->server($httpAgentIp)) {
            $ip = $this->server($httpAgentIp);
        } elseif ($adv) {
            if ($this->server('HTTP_X_FORWARDED_FOR')) {
                $arr = explode(',', $this->server('HTTP_X_FORWARDED_FOR'));
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim(current($arr));
            } elseif ($this->server('HTTP_CLIENT_IP')) {
                $ip = $this->server('HTTP_CLIENT_IP');
            } elseif ($this->server('REMOTE_ADDR')) {
                $ip = $this->server('REMOTE_ADDR');
            }
        } elseif ($this->server('REMOTE_ADDR')) {
            $ip = $this->server('REMOTE_ADDR');
        }
        $ip_mode = (strpos($ip, ':') === false) ? 'ipv4' : 'ipv6';
        if (filter_var($ip, FILTER_VALIDATE_IP) !== $ip) {
            $ip = ('ipv4' === $ip_mode) ? '0.0.0.0' : '::';
        }
        $long_ip = ('ipv4' === $ip_mode) ? sprintf("%u", ip2long($ip)) : 0;
        $ip = [$ip, $long_ip];
        return $ip[$type];
    }
    public function isMobile()
    {
        if ($this->server('HTTP_VIA') && stristr($this->server('HTTP_VIA'), "wap")) {
            return true;
        } elseif ($this->server('HTTP_ACCEPT') && strpos(strtoupper($this->server('HTTP_ACCEPT')), "VND.WAP.WML")) {
            return true;
        } elseif ($this->server('HTTP_X_WAP_PROFILE') || $this->server('HTTP_PROFILE')) {
            return true;
        } elseif ($this->server('HTTP_USER_AGENT') && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $this->server('HTTP_USER_AGENT'))) {
            return true;
        }
        return false;
    }
    public function scheme()
    {
        return $this->isSsl() ? 'https' : 'http';
    }
    public function query()
    {
        return $this->server('QUERY_STRING');
    }
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }
    public function host($strict = false)
    {
        if (!$this->host) {
            $this->host = $this->server('HTTP_X_REAL_HOST') ?: $this->server('HTTP_X_FORWARDED_HOST') ?: $this->server('HTTP_HOST');
        }
        return true === $strict && strpos($this->host, ':') ? strstr($this->host, ':', true) : $this->host;
    }
    public function port()
    {
        return $this->server('SERVER_PORT');
    }
    public function protocol()
    {
        return $this->server('SERVER_PROTOCOL');
    }
    public function remotePort()
    {
        return $this->server('REMOTE_PORT');
    }
    public function contentType()
    {
        $contentType = $this->server('CONTENT_TYPE');
        if ($contentType) {
            if (strpos($contentType, ';')) {
                list($type) = explode(';', $contentType);
            } else {
                $type = $contentType;
            }
            return trim($type);
        }
        return '';
    }
    public function routeInfo(array $route = [])
    {
        if (!empty($route)) {
            $this->routeInfo = $route;
        }
        return $this->routeInfo;
    }
    public function dispatch($dispatch = null)
    {
        if (!is_null($dispatch)) {
            $this->dispatch = $dispatch;
        }
        return $this->dispatch;
    }
    public function secureKey()
    {
        if (is_null($this->secureKey)) {
            $this->secureKey = uniqid('', true);
        }
        return $this->secureKey;
    }
    public function setModule($module)
    {
        $this->module = $module;
        return $this;
    }
    public function setController($controller)
    {
        $this->controller = $controller;
        return $this;
    }
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }
    public function module()
    {
        return $this->module ?: '';
    }
    public function controller($convert = false)
    {
        $name = $this->controller ?: '';
        return $convert ? strtolower($name) : $name;
    }
    public function action($convert = false)
    {
        $name = $this->action ?: '';
        return $convert ? $name : strtolower($name);
    }
    public function setLangset($lang)
    {
        $this->langset = $lang;
        return $this;
    }
    public function langset()
    {
        return $this->langset ?: '';
    }
    public function getContent()
    {
        if (is_null($this->content)) {
            $this->content = $this->input;
        }
        return $this->content;
    }
    public function getInput()
    {
        return $this->input;
    }
    public function token($name = '__token__', $type = null)
    {
        $type  = is_callable($type) ? $type : 'md5';
        $token = call_user_func($type, $this->server('REQUEST_TIME_FLOAT'));
        if ($this->isAjax()) {
            header($name . ': ' . $token);
        }
        facade\Session::set($name, $token);
        return $token;
    }
    public function cache($key, $expire = null, $except = [], $tag = null)
    {
        if (!is_array($except)) {
            $tag    = $except;
            $except = [];
        }
        if (false === $key || !$this->isGet() || $this->isCheckCache || false === $expire) {
            return;
        }
        $this->isCheckCache = true;
        foreach ($except as $rule) {
            if (0 === stripos($this->url(), $rule)) {
                return;
            }
        }
        if ($key instanceof \Closure) {
            $key = call_user_func_array($key, [$this]);
        } elseif (true === $key) {
            $key = '__URL__';
        } elseif (strpos($key, '|')) {
            list($key, $fun) = explode('|', $key);
        }
        if (false !== strpos($key, '__')) {
            $key = str_replace(['__MODULE__', '__CONTROLLER__', '__ACTION__', '__URL__'], [$this->module, $this->controller, $this->action, md5($this->url(true))], $key);
        }
        if (false !== strpos($key, ':')) {
            $param = $this->param();
            foreach ($param as $item => $val) {
                if (is_string($val) && false !== strpos($key, ':' . $item)) {
                    $key = str_replace(':' . $item, $val, $key);
                }
            }
        } elseif (strpos($key, ']')) {
            if ('[' . $this->ext() . ']' == $key) {
                $key = md5($this->url());
            } else {
                return;
            }
        }
        if (isset($fun)) {
            $key = $fun($key);
        }
        $this->cache = [$key, $expire, $tag];
        return $this->cache;
    }
    public function getCache()
    {
        return $this->cache;
    }
    public function withGet(array $get)
    {
        $this->get = $get;
        return $this;
    }
    public function withPost(array $post)
    {
        $this->post = $post;
        return $this;
    }
    public function withInput($input)
    {
        $this->input = $input;
        return $this;
    }
    public function withFiles(array $files)
    {
        $this->file = $files;
        return $this;
    }
    public function withCookie(array $cookie)
    {
        $this->cookie = $cookie;
        return $this;
    }
    public function withServer(array $server)
    {
        $this->server = array_change_key_case($server, CASE_UPPER);
        return $this;
    }
    public function withHeader(array $header)
    {
        $this->header = array_change_key_case($header);
        return $this;
    }
    public function withEnv(array $env)
    {
        $this->env = $env;
        return $this;
    }
    public function withRoute(array $route)
    {
        $this->route = $route;
        return $this;
    }
    public function __set($name, $value)
    {
        return $this->param[$name] = $value;
    }
    public function __get($name)
    {
        return $this->param($name);
    }
    public function __isset($name)
    {
        return isset($this->param[$name]);
    }
    public function __debugInfo()
    {
        $data = get_object_vars($this);
        unset($data['dispatch'], $data['config']);
        return $data;
    }
}
