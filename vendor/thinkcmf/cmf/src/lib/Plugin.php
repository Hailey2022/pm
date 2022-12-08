<?php









namespace cmf\lib;

use think\exception\TemplateNotFoundException;
use think\facade\Lang;
use think\Loader;
use think\Db;
use think\View;
use think\facade\Config;



abstract class Plugin
{
    
    private $view = null;

    public static $vendorLoaded = [];

    
    public $info = [];
    private $pluginPath = '';
    private $name = '';
    private $configFilePath = '';
    private $themeRoot = "";

    
    public function __construct()
    {
        $request = request();

        $engineConfig = Config::pull('template');

        $this->name = $this->getName();

        $nameCStyle = cmf_parse_name($this->name);

        $this->pluginPath     = WEB_ROOT . 'plugins/' . $nameCStyle . '/';
        $this->configFilePath = $this->pluginPath . 'config.php';

        if (empty(self::$vendorLoaded[$this->name])) {
            $pluginVendorAutoLoadFile = $this->pluginPath . 'vendor/autoload.php';
            if (file_exists($pluginVendorAutoLoadFile)) {
                require_once $pluginVendorAutoLoadFile;
            }

            self::$vendorLoaded[$this->name] = true;
        }

        $config = $this->getConfig();

        $theme = isset($config['theme']) ? $config['theme'] : '';

        //$depr = "/";

        $root = cmf_get_root();

        $themeDir = empty($theme) ? "" : '/' . $theme;

        $themePath = 'view' . $themeDir;

        $this->themeRoot = $this->pluginPath . $themePath . '/';

        $engineConfig['view_base'] = $this->themeRoot;

        $pluginRoot = "plugins/{$nameCStyle}";

        $cmfAdminThemePath    = config('template.cmf_admin_theme_path');
        $cmfAdminDefaultTheme = config('template.cmf_admin_default_theme');

        $adminThemePath = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}";

        //使cdn设置生效
        $cdnSettings = cmf_get_option('cdn_settings');
        if (empty($cdnSettings['cdn_static_root'])) {
            $replaceConfig = [
                '__ROOT__'        => $root,
                '__PLUGIN_TMPL__' => $root . '/' . $pluginRoot . '/' . $themePath,
                '__PLUGIN_ROOT__' => $root . '/' . $pluginRoot,
                '__ADMIN_TMPL__'  => "{$root}/{$adminThemePath}",
                '__STATIC__'      => "{$root}/static",
                '__WEB_ROOT__'    => $root
            ];
        } else {
            $cdnStaticRoot = rtrim($cdnSettings['cdn_static_root'], '/');
            $replaceConfig = [
                '__ROOT__'        => $root,
                '__PLUGIN_TMPL__' => $cdnStaticRoot . '/' . $pluginRoot . '/' . $themePath,
                '__PLUGIN_ROOT__' => $cdnStaticRoot . '/' . $pluginRoot,
                '__ADMIN_TMPL__'  => "{$cdnStaticRoot}/{$adminThemePath}",
                '__STATIC__'      => "{$cdnStaticRoot}/static",
                '__WEB_ROOT__'    => $cdnStaticRoot
            ];
        }
        $view = new View();

        $this->view = $view->init($engineConfig);
        $this->view->config('tpl_replace_string', $replaceConfig);

        //加载多语言
        $langSet   = $request->langset();
        $lang_file = $this->pluginPath . "lang/" . $langSet . ".php";
        Lang::load($lang_file);

    }

    
    final protected function fetch($template)
    {
        if (!is_file($template)) {
            $engineConfig = Config::pull('template');
            $template     = $this->themeRoot . $template . '.' . $engineConfig['view_suffix'];
        }

        
        if (!is_file($template)) {
            throw new TemplateNotFoundException('template not exists:' . $template, $template);
        }

        return $this->view->fetch($template);
    }

    
    final protected function display($content = '')
    {
        return $this->view->display($content);
    }

    
    final protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
    }

    
    final public function getName()
    {
        if (empty($this->name)) {
            $class = get_class($this);

            $this->name = substr($class, strrpos($class, '\\') + 1, -6);
        }

        return $this->name;

    }

    
    final public function checkInfo()
    {
        $infoCheckKeys = ['name', 'title', 'description', 'status', 'author', 'version'];
        foreach ($infoCheckKeys as $value) {
            if (!array_key_exists($value, $this->info))
                return false;
        }
        return true;
    }

    
    final public function getPluginPath()
    {

        return $this->pluginPath;
    }

    
    final public function getConfigFilePath()
    {
        return $this->configFilePath;
    }

    
    final public function getThemeRoot()
    {
        return $this->themeRoot;
    }

    
    public function getView()
    {
        return $this->view;
    }

    
    final public function getConfig()
    {
        $name = $this->getName();

        if (PHP_SAPI != 'cli') {
            static $_config = [];
            if (isset($_config[$name])) {
                return $_config[$name];
            }
        }

        $config = Db::name('plugin')->where('name', $name)->value('config');

        if (!empty($config) && $config != "null") {
            $config = json_decode($config, true);
        } else {
            $config = $this->getDefaultConfig();

        }
        $_config[$name] = $config;
        return $config;
    }

    
    final public function getDefaultConfig()
    {
        $config = [];
        if (file_exists($this->configFilePath)) {
            $tempArr = include $this->configFilePath;
            if (!empty($tempArr) && is_array($tempArr)) {
                foreach ($tempArr as $key => $value) {
                    if ($value['type'] == 'group') {
                        foreach ($value['options'] as $gkey => $gvalue) {
                            foreach ($gvalue['options'] as $ikey => $ivalue) {
                                $config[$ikey] = $ivalue['value'];
                            }
                        }
                    } else {
                        $config[$key] = $tempArr[$key]['value'];
                    }
                }
            }
        }

        return $config;
    }

    //必须实现安装
    abstract public function install();

    //必须卸载插件方法
    abstract public function uninstall();
}
