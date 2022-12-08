<?php










namespace think;

use think\exception\ClassNotFoundException;
use think\exception\HttpResponseException;
use think\route\Dispatch;


class App extends Container
{
    const VERSION = '5.1.41 LTS';

    
    protected $modulePath;

    
    protected $appDebug = true;

    
    protected $beginTime;

    
    protected $beginMem;

    
    protected $namespace = 'app';

    
    protected $suffix = false;

    
    protected $routeMust;

    
    protected $appPath;

    
    protected $thinkPath;

    
    protected $rootPath;

    
    protected $runtimePath;

    
    protected $configPath;

    
    protected $routePath;

    
    protected $configExt;

    
    protected $dispatch;

    
    protected $bindModule;

    
    protected $initialized = false;

    public function __construct($appPath = '')
    {
        $this->thinkPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR;
        $this->path($appPath);
    }

    
    public function bind($bind)
    {
        $this->bindModule = $bind;
        return $this;
    }

    
    public function path($path)
    {
        $this->appPath = $path ? realpath($path) . DIRECTORY_SEPARATOR : $this->getAppPath();

        return $this;
    }

    
    public function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;
        $this->beginTime   = microtime(true);
        $this->beginMem    = memory_get_usage();

        $this->rootPath    = dirname($this->appPath) . DIRECTORY_SEPARATOR;
        $this->runtimePath = $this->rootPath . 'runtime' . DIRECTORY_SEPARATOR;
        $this->routePath   = $this->rootPath . 'route' . DIRECTORY_SEPARATOR;
        $this->configPath  = $this->rootPath . 'config' . DIRECTORY_SEPARATOR;

        static::setInstance($this);

        $this->instance('app', $this);

        
        if (is_file($this->rootPath . '.env')) {
            $this->env->load($this->rootPath . '.env');
        }

        $this->configExt = $this->env->get('config_ext', '.php');

        
        $this->config->set(include $this->thinkPath . 'convention.php');

        
        $this->env->set([
            'think_path'   => $this->thinkPath,
            'root_path'    => $this->rootPath,
            'app_path'     => $this->appPath,
            'config_path'  => $this->configPath,
            'route_path'   => $this->routePath,
            'runtime_path' => $this->runtimePath,
            'extend_path'  => $this->rootPath . 'extend' . DIRECTORY_SEPARATOR,
            'vendor_path'  => $this->rootPath . 'vendor' . DIRECTORY_SEPARATOR,
        ]);

        $this->namespace = $this->env->get('app_namespace', $this->namespace);
        $this->env->set('app_namespace', $this->namespace);

        
        Loader::addNamespace($this->namespace, $this->appPath);

        
        $this->init();

        
        $this->suffix = $this->config('app.class_suffix');

        
        $this->appDebug = $this->env->get('app_debug', $this->config('app.app_debug'));
        $this->env->set('app_debug', $this->appDebug);

        if (!$this->appDebug) {
            ini_set('display_errors', 'Off');
        } elseif (PHP_SAPI != 'cli') {
            //重新申请一块比较大的buffer
            if (ob_get_level() > 0) {
                $output = ob_get_clean();
            }
            ob_start();
            if (!empty($output)) {
                echo $output;
            }
        }

        
        if ($this->config('app.exception_handle')) {
            Error::setExceptionHandler($this->config('app.exception_handle'));
        }

        
        if (!empty($this->config('app.root_namespace'))) {
            Loader::addNamespace($this->config('app.root_namespace'));
        }

        
        Loader::loadComposerAutoloadFiles();

        
        Loader::addClassAlias($this->config->pull('alias'));

        
        Db::init($this->config->pull('database'));

        
        date_default_timezone_set($this->config('app.default_timezone'));

        
        $this->loadLangPack();

        
        $this->routeInit();
    }

    
    public function init($module = '')
    {
        
        $module = $module ? $module . DIRECTORY_SEPARATOR : '';
        $path   = $this->appPath . $module;

        
        if (is_file($path . 'init.php')) {
            include $path . 'init.php';
        } elseif (is_file($this->runtimePath . $module . 'init.php')) {
            include $this->runtimePath . $module . 'init.php';
        } else {
            
            if (is_file($path . 'tags.php')) {
                $tags = include $path . 'tags.php';
                if (is_array($tags)) {
                    $this->hook->import($tags);
                }
            }

            
            if (is_file($path . 'common.php')) {
                include_once $path . 'common.php';
            }

            if ('' == $module) {
                
                include $this->thinkPath . 'helper.php';
            }

            
            if (is_file($path . 'middleware.php')) {
                $middleware = include $path . 'middleware.php';
                if (is_array($middleware)) {
                    $this->middleware->import($middleware);
                }
            }

            
            if (is_file($path . 'provider.php')) {
                $provider = include $path . 'provider.php';
                if (is_array($provider)) {
                    $this->bindTo($provider);
                }
            }

            
            if (is_dir($path . 'config')) {
                $dir = $path . 'config' . DIRECTORY_SEPARATOR;
            } elseif (is_dir($this->configPath . $module)) {
                $dir = $this->configPath . $module;
            }

            $files = isset($dir) ? scandir($dir) : [];

            foreach ($files as $file) {
                if ('.' . pathinfo($file, PATHINFO_EXTENSION) === $this->configExt) {
                    $this->config->load($dir . $file, pathinfo($file, PATHINFO_FILENAME));
                }
            }
        }

        $this->setModulePath($path);

        if ($module) {
            
            $this->containerConfigUpdate($module);
        }
    }

    protected function containerConfigUpdate($module)
    {
        $config = $this->config->get();

        
        if ($config['app']['exception_handle']) {
            Error::setExceptionHandler($config['app']['exception_handle']);
        }

        Db::init($config['database']);
        $this->middleware->setConfig($config['middleware']);
        $this->route->setConfig($config['app']);
        $this->request->init($config['app']);
        $this->cookie->init($config['cookie']);
        $this->view->init($config['template']);
        $this->log->init($config['log']);
        $this->session->setConfig($config['session']);
        $this->debug->setConfig($config['trace']);
        $this->cache->init($config['cache'], true);

        
        $this->lang->load($this->appPath . $module . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $this->request->langset() . '.php');

        
        $this->checkRequestCache(
            $config['app']['request_cache'],
            $config['app']['request_cache_expire'],
            $config['app']['request_cache_except']
        );
    }

    
    public function run()
    {
        try {
            
            $this->initialize();

            
            $this->hook->listen('app_init');

            if ($this->bindModule) {
                
                $this->route->bind($this->bindModule);
            } elseif ($this->config('app.auto_bind_module')) {
                
                $name = pathinfo($this->request->baseFile(), PATHINFO_FILENAME);
                if ($name && 'index' != $name && is_dir($this->appPath . $name)) {
                    $this->route->bind($name);
                }
            }

            
            $this->hook->listen('app_dispatch');

            $dispatch = $this->dispatch;

            if (empty($dispatch)) {
                
                $dispatch = $this->routeCheck()->init();
            }

            
            $this->request->dispatch($dispatch);

            
            if ($this->appDebug) {
                $this->log('[ ROUTE ] ' . var_export($this->request->routeInfo(), true));
                $this->log('[ HEADER ] ' . var_export($this->request->header(), true));
                $this->log('[ PARAM ] ' . var_export($this->request->param(), true));
            }

            
            $this->hook->listen('app_begin');

            
            $this->checkRequestCache(
                $this->config('request_cache'),
                $this->config('request_cache_expire'),
                $this->config('request_cache_except')
            );

            $data = null;
        } catch (HttpResponseException $exception) {
            $dispatch = null;
            $data     = $exception->getResponse();
        }

        $this->middleware->add(function (Request $request, $next) use ($dispatch, $data) {
            return is_null($data) ? $dispatch->run() : $data;
        });

        $response = $this->middleware->dispatch($this->request);

        
        $this->hook->listen('app_end', $response);

        return $response;
    }

    protected function getRouteCacheKey()
    {
        if ($this->config->get('route_check_cache_key')) {
            $closure  = $this->config->get('route_check_cache_key');
            $routeKey = $closure($this->request);
        } else {
            $routeKey = md5($this->request->baseUrl(true) . ':' . $this->request->method());
        }

        return $routeKey;
    }

    protected function loadLangPack()
    {
        
        $this->lang->range($this->config('app.default_lang'));

        if ($this->config('app.lang_switch_on')) {
            
            $this->lang->detect();
        }

        $this->request->setLangset($this->lang->range());

        
        $this->lang->load([
            $this->thinkPath . 'lang' . DIRECTORY_SEPARATOR . $this->request->langset() . '.php',
            $this->appPath . 'lang' . DIRECTORY_SEPARATOR . $this->request->langset() . '.php',
        ]);
    }

    
    public function checkRequestCache($key, $expire = null, $except = [], $tag = null)
    {
        $cache = $this->request->cache($key, $expire, $except, $tag);

        if ($cache) {
            $this->setResponseCache($cache);
        }
    }

    public function setResponseCache($cache)
    {
        list($key, $expire, $tag) = $cache;

        if (strtotime($this->request->server('HTTP_IF_MODIFIED_SINCE')) + $expire > $this->request->server('REQUEST_TIME')) {
            
            $response = Response::create()->code(304);
            throw new HttpResponseException($response);
        } elseif ($this->cache->has($key)) {
            list($content, $header) = $this->cache->get($key);

            $response = Response::create($content)->header($header);
            throw new HttpResponseException($response);
        }
    }

    
    public function dispatch(Dispatch $dispatch)
    {
        $this->dispatch = $dispatch;
        return $this;
    }

    
    public function log($msg, $type = 'info')
    {
        $this->appDebug && $this->log->record($msg, $type);
    }

    
    public function config($name = '')
    {
        return $this->config->get($name);
    }

    
    public function routeInit()
    {
        
        if (is_dir($this->routePath)) {
            $files = glob($this->routePath . '*.php');
            foreach ($files as $file) {
                $rules = include $file;
                if (is_array($rules)) {
                    $this->route->import($rules);
                }
            }
        }

        if ($this->route->config('route_annotation')) {
            
            if ($this->appDebug) {
                $suffix = $this->route->config('controller_suffix') || $this->route->config('class_suffix');
                $this->build->buildRoute($suffix);
            }

            $filename = $this->runtimePath . 'build_route.php';

            if (is_file($filename)) {
                include $filename;
            }
        }
    }

    
    public function routeCheck()
    {
        
        if (!$this->appDebug && $this->config->get('route_check_cache')) {
            $routeKey = $this->getRouteCacheKey();
            $option   = $this->config->get('route_cache_option');

            if ($option && $this->cache->connect($option)->has($routeKey)) {
                return $this->cache->connect($option)->get($routeKey);
            } elseif ($this->cache->has($routeKey)) {
                return $this->cache->get($routeKey);
            }
        }

        
        $path = $this->request->path();

        
        $must = !is_null($this->routeMust) ? $this->routeMust : $this->route->config('url_route_must');

        
        $dispatch = $this->route->check($path, $must);

        if (!empty($routeKey)) {
            try {
                if ($option) {
                    $this->cache->connect($option)->tag('route_cache')->set($routeKey, $dispatch);
                } else {
                    $this->cache->tag('route_cache')->set($routeKey, $dispatch);
                }
            } catch (\Exception $e) {
                
            }
        }

        return $dispatch;
    }

    
    public function routeMust($must = false)
    {
        $this->routeMust = $must;
        return $this;
    }

    
    protected function parseModuleAndClass($name, $layer, $appendSuffix)
    {
        if (false !== strpos($name, '\\')) {
            $class  = $name;
            $module = $this->request->module();
        } else {
            if (strpos($name, '/')) {
                list($module, $name) = explode('/', $name, 2);
            } else {
                $module = $this->request->module();
            }

            $class = $this->parseClass($module, $layer, $name, $appendSuffix);
        }

        return [$module, $class];
    }

    
    public function create($name, $layer, $appendSuffix = false, $common = 'common')
    {
        $guid = $name . $layer;

        if ($this->__isset($guid)) {
            return $this->__get($guid);
        }

        list($module, $class) = $this->parseModuleAndClass($name, $layer, $appendSuffix);

        if (class_exists($class)) {
            $object = $this->__get($class);
        } else {
            $class = str_replace('\\' . $module . '\\', '\\' . $common . '\\', $class);
            if (class_exists($class)) {
                $object = $this->__get($class);
            } else {
                throw new ClassNotFoundException('class not exists:' . $class, $class);
            }
        }

        $this->__set($guid, $class);

        return $object;
    }

    
    public function model($name = '', $layer = 'model', $appendSuffix = false, $common = 'common')
    {
        return $this->create($name, $layer, $appendSuffix, $common);
    }

    
    public function controller($name, $layer = 'controller', $appendSuffix = false, $empty = '')
    {
        list($module, $class) = $this->parseModuleAndClass($name, $layer, $appendSuffix);

        if (class_exists($class)) {
            return $this->make($class, true);
        } elseif ($empty && class_exists($emptyClass = $this->parseClass($module, $layer, $empty, $appendSuffix))) {
            return $this->make($emptyClass, true);
        }

        throw new ClassNotFoundException('class not exists:' . $class, $class);
    }

    
    public function validate($name = '', $layer = 'validate', $appendSuffix = false, $common = 'common')
    {
        $name = $name ?: $this->config('default_validate');

        if (empty($name)) {
            return new Validate;
        }

        return $this->create($name, $layer, $appendSuffix, $common);
    }

    
    public function db($config = [], $name = false)
    {
        return Db::connect($config, $name);
    }

    
    public function action($url, $vars = [], $layer = 'controller', $appendSuffix = false)
    {
        $info   = pathinfo($url);
        $action = $info['basename'];
        $module = '.' != $info['dirname'] ? $info['dirname'] : $this->request->controller();
        $class  = $this->controller($module, $layer, $appendSuffix);

        if (is_scalar($vars)) {
            if (strpos($vars, '=')) {
                parse_str($vars, $vars);
            } else {
                $vars = [$vars];
            }
        }

        return $this->invokeMethod([$class, $action . $this->config('action_suffix')], $vars);
    }

    
    public function parseClass($module, $layer, $name, $appendSuffix = false)
    {
        $name  = str_replace(['/', '.'], '\\', $name);
        $array = explode('\\', $name);
        $class = Loader::parseName(array_pop($array), 1) . ($this->suffix || $appendSuffix ? ucfirst($layer) : '');
        $path  = $array ? implode('\\', $array) . '\\' : '';

        return $this->namespace . '\\' . ($module ? $module . '\\' : '') . $layer . '\\' . $path . $class;
    }

    
    public function version()
    {
        return static::VERSION;
    }

    
    public function isDebug()
    {
        return $this->appDebug;
    }

    
    public function getModulePath()
    {
        return $this->modulePath;
    }

    
    public function setModulePath($path)
    {
        $this->modulePath = $path;
        $this->env->set('module_path', $path);
    }

    
    public function getRootPath()
    {
        return $this->rootPath;
    }

    
    public function getAppPath()
    {
        if (is_null($this->appPath)) {
            $this->appPath = Loader::getRootPath() . 'application' . DIRECTORY_SEPARATOR;
        }

        return $this->appPath;
    }

    
    public function getRuntimePath()
    {
        return $this->runtimePath;
    }

    
    public function getThinkPath()
    {
        return $this->thinkPath;
    }

    
    public function getRoutePath()
    {
        return $this->routePath;
    }

    
    public function getConfigPath()
    {
        return $this->configPath;
    }

    
    public function getConfigExt()
    {
        return $this->configExt;
    }

    
    public function getNamespace()
    {
        return $this->namespace;
    }

    
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    
    public function getSuffix()
    {
        return $this->suffix;
    }

    
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    
    public function getBeginMem()
    {
        return $this->beginMem;
    }

}
