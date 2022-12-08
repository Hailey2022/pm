<?php









namespace cmf\behavior;

use think\facade\Env;
use think\facade\Lang;

class AdminLangBehavior
{
    protected static $run = false;

    
    public function run()
    {
        if (self::$run) {
            return;
        }
        self::$run = true;

        $langSet = request()->langset();

        
        $coreApps = ['admin', 'user'];
        foreach ($coreApps as $app) {
            Lang::load([
                Env::get('root_path') . "vendor/thinkcmf/cmf-app/src/{$app}/lang/{$langSet}/admin_menu.php",
                Env::get('root_path') . "vendor/thinkcmf/cmf-app/src/{$app}/lang/{$langSet}/admin.php"
            ]);
        }

        
        $apps = cmf_scan_dir(APP_PATH . '*', GLOB_ONLYDIR);
        foreach ($apps as $app) {
            Lang::load([
                APP_PATH . $app . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $langSet . DIRECTORY_SEPARATOR . 'admin_menu.php',
                APP_PATH . $app . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $langSet . DIRECTORY_SEPARATOR . 'admin.php',
            ]);
        }

        
        $defaultLangDir = config('DEFAULT_LANG');
        Lang::load([
            CMF_DATA . "lang/" . $defaultLangDir . "/admin_menu.php"
        ]);
    }
}