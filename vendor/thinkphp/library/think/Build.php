<?php










namespace think;

class Build
{
    
    protected $app;

    
    protected $basePath;

    public function __construct(App $app)
    {
        $this->app      = $app;
        $this->basePath = $this->app->getAppPath();
    }

    
    public function run(array $build = [], $namespace = 'app', $suffix = false)
    {
        
        $lockfile = $this->basePath . 'build.lock';

        if (is_writable($lockfile)) {
            return;
        } elseif (!touch($lockfile)) {
            throw new Exception('应用目录[' . $this->basePath . ']不可写，目录无法自动生成！<BR>请手动生成项目目录~', 10006);
        }

        foreach ($build as $module => $list) {
            if ('__dir__' == $module) {
                
                $this->buildDir($list);
            } elseif ('__file__' == $module) {
                
                $this->buildFile($list);
            } else {
                
                $this->module($module, $list, $namespace, $suffix);
            }
        }

        
        unlink($lockfile);
    }

    
    protected function buildDir($list)
    {
        foreach ($list as $dir) {
            $this->checkDirBuild($this->basePath . $dir);
        }
    }

    
    protected function buildFile($list)
    {
        foreach ($list as $file) {
            if (!is_dir($this->basePath . dirname($file))) {
                
                mkdir($this->basePath . dirname($file), 0755, true);
            }

            if (!is_file($this->basePath . $file)) {
                file_put_contents($this->basePath . $file, 'php' == pathinfo($file, PATHINFO_EXTENSION) ? "<?php\n" : '');
            }
        }
    }

    
    public function module($module = '', $list = [], $namespace = 'app', $suffix = false)
    {
        $module = $module ? $module : '';

        if (!is_dir($this->basePath . $module)) {
            
            mkdir($this->basePath . $module);
        }

        if (basename($this->app->getRuntimePath()) != $module) {
            
            $this->buildCommon($module);
            
            $this->buildHello($module, $namespace, $suffix);
        }

        if (empty($list)) {
            
            $list = [
                '__file__' => ['common.php'],
                '__dir__'  => ['controller', 'model', 'view', 'config'],
            ];
        }

        
        foreach ($list as $path => $file) {
            $modulePath = $this->basePath . $module . DIRECTORY_SEPARATOR;
            if ('__dir__' == $path) {
                
                foreach ($file as $dir) {
                    $this->checkDirBuild($modulePath . $dir);
                }
            } elseif ('__file__' == $path) {
                
                foreach ($file as $name) {
                    if (!is_file($modulePath . $name)) {
                        file_put_contents($modulePath . $name, 'php' == pathinfo($name, PATHINFO_EXTENSION) ? "<?php\n" : '');
                    }
                }
            } else {
                
                foreach ($file as $val) {
                    $val      = trim($val);
                    $filename = $modulePath . $path . DIRECTORY_SEPARATOR . $val . ($suffix ? ucfirst($path) : '') . '.php';
                    $space    = $namespace . '\\' . ($module ? $module . '\\' : '') . $path;
                    $class    = $val . ($suffix ? ucfirst($path) : '');
                    switch ($path) {
                        case 'controller': 
                            $content = "<?php\nnamespace {$space};\n\nclass {$class}\n{\n\n}";
                            break;
                        case 'model': 
                            $content = "<?php\nnamespace {$space};\n\nuse think\Model;\n\nclass {$class} extends Model\n{\n\n}";
                            break;
                        case 'view': 
                            $filename = $modulePath . $path . DIRECTORY_SEPARATOR . $val . '.html';
                            $this->checkDirBuild(dirname($filename));
                            $content = '';
                            break;
                        default:
                            
                            $content = "<?php\nnamespace {$space};\n\nclass {$class}\n{\n\n}";
                    }

                    if (!is_file($filename)) {
                        file_put_contents($filename, $content);
                    }
                }
            }
        }
    }

    
    public function buildRoute($suffix = false, $layer = '')
    {
        $namespace = $this->app->getNameSpace();
        $content   = '<?php ' . PHP_EOL . '//根据 Annotation 自动生成的路由规则';

        if (!$layer) {
            $layer = $this->app->config('app.url_controller_layer');
        }

        if ($this->app->config('app.app_multi_module')) {
            $modules = glob($this->basePath . '*', GLOB_ONLYDIR);

            foreach ($modules as $module) {
                $module = basename($module);

                if (in_array($module, $this->app->config('app.deny_module_list'))) {
                    continue;
                }

                $path = $this->basePath . $module . DIRECTORY_SEPARATOR . $layer . DIRECTORY_SEPARATOR;
                $content .= $this->buildDirRoute($path, $namespace, $module, $suffix, $layer);
            }
        } else {
            $path = $this->basePath . $layer . DIRECTORY_SEPARATOR;
            $content .= $this->buildDirRoute($path, $namespace, '', $suffix, $layer);
        }

        $filename = $this->app->getRuntimePath() . 'build_route.php';
        file_put_contents($filename, $content);

        return $filename;
    }

    
    protected function buildDirRoute($path, $namespace, $module, $suffix, $layer)
    {
        $content     = '';
        $controllers = glob($path . '*.php');

        foreach ($controllers as $controller) {
            $controller = basename($controller, '.php');

            $class = new \ReflectionClass($namespace . '\\' . ($module ? $module . '\\' : '') . $layer . '\\' . $controller);

            if (strpos($layer, '\\')) {
                
                $level      = str_replace(DIRECTORY_SEPARATOR, '.', substr($layer, 11));
                $controller = $level . '.' . $controller;
                $length     = strlen(strstr($layer, '\\', true));
            } else {
                $length = strlen($layer);
            }

            if ($suffix) {
                $controller = substr($controller, 0, -$length);
            }

            $content .= $this->getControllerRoute($class, $module, $controller);
        }

        $subDir = glob($path . '*', GLOB_ONLYDIR);

        foreach ($subDir as $dir) {
            $content .= $this->buildDirRoute($dir . DIRECTORY_SEPARATOR, $namespace, $module, $suffix, $layer . '\\' . basename($dir));
        }

        return $content;
    }

    
    protected function getControllerRoute($class, $module, $controller)
    {
        $content = '';
        $comment = $class->getDocComment();

        if (false !== strpos($comment, '@route(')) {
            $comment = $this->parseRouteComment($comment);
            $route   = ($module ? $module . '/' : '') . $controller;
            $comment = preg_replace('/route\(\s?([\'\"][\-\_\/\:\<\>\?\$\[\]\w]+[\'\"])\s?\)/is', 'Route::resource(\1,\'' . $route . '\')', $comment);
            $content .= PHP_EOL . $comment;
        } elseif (false !== strpos($comment, '@alias(')) {
            $comment = $this->parseRouteComment($comment, '@alias(');
            $route   = ($module ? $module . '/' : '') . $controller;
            $comment = preg_replace('/alias\(\s?([\'\"][\-\_\/\w]+[\'\"])\s?\)/is', 'Route::alias(\1,\'' . $route . '\')', $comment);
            $content .= PHP_EOL . $comment;
        }

        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $comment = $this->getMethodRouteComment($module, $controller, $method);
            if ($comment) {
                $content .= PHP_EOL . $comment;
            }
        }

        return $content;
    }

    
    protected function parseRouteComment($comment, $tag = '@route(')
    {
        $comment = substr($comment, 3, -2);
        $comment = explode(PHP_EOL, substr(strstr(trim($comment), $tag), 1));
        $comment = array_map(function ($item) {return trim(trim($item), ' \t*');}, $comment);

        if (count($comment) > 1) {
            $key     = array_search('', $comment);
            $comment = array_slice($comment, 0, false === $key ? 1 : $key);
        }

        $comment = implode(PHP_EOL . "\t", $comment) . ';';

        if (strpos($comment, '{')) {
            $comment = preg_replace_callback('/\{\s?.*?\s?\}/s', function ($matches) {
                return false !== strpos($matches[0], '"') ? '[' . substr(var_export(json_decode($matches[0], true), true), 7, -1) . ']' : $matches[0];
            }, $comment);
        }
        return $comment;
    }

    
    protected function getMethodRouteComment($module, $controller, $reflectMethod)
    {
        $comment = $reflectMethod->getDocComment();

        if (false !== strpos($comment, '@route(')) {
            $comment = $this->parseRouteComment($comment);
            $action  = $reflectMethod->getName();

            if ($suffix = $this->app->config('app.action_suffix')) {
                $action = substr($action, 0, -strlen($suffix));
            }

            $route   = ($module ? $module . '/' : '') . $controller . '/' . $action;
            $comment = preg_replace('/route\s?\(\s?([\'\"][\-\_\/\:\<\>\?\$\[\]\w]+[\'\"])\s?\,?\s?[\'\"]?(\w+?)[\'\"]?\s?\)/is', 'Route::\2(\1,\'' . $route . '\')', $comment);
            $comment = preg_replace('/route\s?\(\s?([\'\"][\-\_\/\:\<\>\?\$\[\]\w]+[\'\"])\s?\)/is', 'Route::rule(\1,\'' . $route . '\')', $comment);

            return $comment;
        }
    }

    
    protected function buildHello($module, $namespace, $suffix = false)
    {
        $filename = $this->basePath . ($module ? $module . DIRECTORY_SEPARATOR : '') . 'controller' . DIRECTORY_SEPARATOR . 'Index' . ($suffix ? 'Controller' : '') . '.php';
        if (!is_file($filename)) {
            $content = file_get_contents($this->app->getThinkPath() . 'tpl' . DIRECTORY_SEPARATOR . 'default_index.tpl');
            $content = str_replace(['{$app}', '{$module}', '{layer}', '{$suffix}'], [$namespace, $module ? $module . '\\' : '', 'controller', $suffix ? 'Controller' : ''], $content);
            $this->checkDirBuild(dirname($filename));

            file_put_contents($filename, $content);
        }
    }

    
    protected function buildCommon($module)
    {
        $filename = $this->app->getConfigPath() . ($module ? $module . DIRECTORY_SEPARATOR : '') . 'app.php';
        $this->checkDirBuild(dirname($filename));

        if (!is_file($filename)) {
            file_put_contents($filename, "<?php\n//配置文件\nreturn [\n\n];");
        }

        $filename = $this->basePath . ($module ? $module . DIRECTORY_SEPARATOR : '') . 'common.php';

        if (!is_file($filename)) {
            file_put_contents($filename, "<?php\n");
        }
    }

    
    protected function checkDirBuild($dirname)
    {
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }
    }
}
