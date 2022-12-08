<?php









namespace cmf\controller;

use think\App;
use think\exception\ValidateException;
use think\Request;
use think\Loader;

class PluginRestBaseController extends RestBaseController
{

    
    private $plugin;

    
    public function __construct(App $app = null)
    {
        parent::__construct($app);

        $this->getPlugin();
    }

    public function getPlugin()
    {

        if (is_null($this->plugin)) {
            $pluginName   = $this->request->param('_plugin');
            $pluginName   = cmf_parse_name($pluginName, 1);
            $class        = cmf_get_plugin_class($pluginName);
            $this->plugin = new $class;
        }

        return $this->plugin;

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