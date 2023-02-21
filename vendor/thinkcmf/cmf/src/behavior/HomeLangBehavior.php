<?php
namespace cmf\behavior;
use think\facade\Env;
use think\facade\Lang;
class HomeLangBehavior
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
                Env::get('root_path') . "vendor/thinkcmf/cmf-app/src/{$app}/lang/{$langSet}/home.php"
            ]);
        }
        $apps = cmf_scan_dir(APP_PATH . '*', GLOB_ONLYDIR);
        foreach ($apps as $app) {
            Lang::load([
                APP_PATH . $app . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $langSet . DIRECTORY_SEPARATOR . 'home.php',
            ]);
        }
    }
}