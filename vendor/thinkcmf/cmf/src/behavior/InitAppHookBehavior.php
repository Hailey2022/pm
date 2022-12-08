<?php









namespace cmf\behavior;

use cmf\model\HookModel;
use cmf\model\HookPluginModel;
use think\Db;
use think\facade\Hook;

class InitAppHookBehavior
{

    public static $appLoaded = [];

    
    public function run($param)
    {
        $app = request()->module();
        if (!empty(self::$appLoaded[$app])) {
            return;
        }

        self::$appLoaded[$app] = true;

        
        $appAutoLoadFile = APP_PATH . $app . '/vendor/autoload.php';
        if (file_exists($appAutoLoadFile)) {
            require_once $appAutoLoadFile;
        }

        if (!cmf_is_installed()) {
            return;
        }

        
        $appHookPluginsCacheKey = "init_hook_plugins_app_{$app}_hook_plugins";
        $appHookPlugins         = cache($appHookPluginsCacheKey);

        if (empty($appHookPlugins)) {
            $appHooks = HookModel::where('app', $app)->column('hook');

            $appHookPlugins = HookPluginModel::field('hook,plugin')->where('status', 1)
                ->where('hook', 'in', $appHooks)
                ->order('list_order ASC')
                ->select();
            cache($appHookPluginsCacheKey, $appHookPlugins, null, 'init_hook_plugins');
        }

        if (!empty($appHookPlugins)) {
            foreach ($appHookPlugins as $hookPlugin) {
                Hook::add($hookPlugin['hook'], cmf_get_plugin_class($hookPlugin['plugin']));
            }
        }
    }
}