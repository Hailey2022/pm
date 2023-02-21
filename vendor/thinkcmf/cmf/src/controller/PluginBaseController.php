<?php
namespace cmf\controller;
use app\admin\model\PluginModel;
use think\Container;
use think\exception\ValidateException;
use think\facade\Config;
use think\Loader;
use think\exception\TemplateNotFoundException;
class PluginBaseController extends BaseController
{
    private $plugin;
    protected $beforeActionList = [];
    public function __construct()
    {
        $this->app     = Container::get('app');
        $this->request = $this->app['request'];
        $this->getPlugin();
        $this->view = $this->plugin->getView();
        $siteInfo = cmf_get_site_info();
        $this->assign('site_info', $siteInfo);
        $this->initialize();
    }
    public function getPlugin()
    {
        if (is_null($this->plugin)) {
            $pluginName = $this->request->param('_plugin');
            $pluginName = cmf_parse_name($pluginName, 1);
            $class      = cmf_get_plugin_class($pluginName);
            //检查是否启用。非启用则禁止访问。
            $pluginModel = new PluginModel();
            $findPlugin  = $pluginModel->where('name', '=', $pluginName)->find();
            if (empty($findPlugin)) {
                $this->error('插件未安装!');
            }
            if ($findPlugin['status'] != 1) {
                $this->error('插件未启用!');
            }
            $this->plugin = new $class;
        }
        return $this->plugin;
    }
    protected function initialize()
    {
    }
    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        $template = $this->parseTemplate($template);
        if (!is_file($template)) {
            throw new TemplateNotFoundException('template not exists:' . $template, $template);
        }
        return $this->view->fetch($template, $vars, $replace, $config);
    }
    private function parseTemplate($template)
    {
        $viewEngineConfig = Config::get('template.');
        $path = $this->plugin->getThemeRoot();
        $depr = $viewEngineConfig['view_depr'];
        $data       = $this->request->param();
        $controller = $data['_controller'];
        $action     = $data['_action'];
        if (0 !== strpos($template, '/')) {
            $template   = str_replace(['/', ':'], $depr, $template);
            $controller = Loader::parseName($controller);
            if ($controller) {
                if ('' == $template) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $action;
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }
        return $path . ltrim($template, '/') . '.' . ltrim($viewEngineConfig['view_suffix'], '.');
    }
    protected function display($content = '', $vars = [], $replace = [], $config = [])
    {
        return $this->view->display($content, $vars, $replace, $config);
    }
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
    }
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;
        return $this;
    }
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate)) {
            $v = $this->app->validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                list($validate, $scene) = explode('.', $validate);
            }
            $v = $this->app->validate('\\plugins\\' . cmf_parse_name($this->plugin->getName()) . '\\validate\\' . $validate . 'Validate');
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }
        if (is_array($message)) {
            $v->message($message);
        }
        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v, &$data]);
        }
        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            }
            return $v->getError();
        }
        return true;
    }
}
