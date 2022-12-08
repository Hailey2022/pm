<?php









namespace app\admin\controller;

use app\admin\logic\HookLogic;
use cmf\controller\AdminBaseController;
use app\admin\model\HookModel;
use app\admin\model\PluginModel;
use app\admin\model\HookPluginModel;


class HookController extends AdminBaseController
{
    
    public function index()
    {
        $hookModel = new HookModel();
        $hooks     = $hookModel->select();
        $this->assign('hooks', $hooks);
        return $this->fetch();
    }

    
    public function plugins()
    {
        $hook        = $this->request->param('hook');
        $pluginModel = new PluginModel();
        $plugins     = $pluginModel
            ->field('a.*,b.hook,b.plugin,b.list_order,b.status as hook_plugin_status,b.id as hook_plugin_id')
            ->alias('a')
            ->join('hook_plugin b', 'a.name = b.plugin')
            ->where('b.hook', $hook)
            ->order('b.list_order asc')
            ->select();
        $this->assign('plugins', $plugins);
        return $this->fetch();
    }

    
    public function pluginListOrder()
    {
        $hookPluginModel = new HookPluginModel();
        parent::listOrders($hookPluginModel);

        $this->success("排序更新成功！");
    }

    
    public function sync()
    {

        $apps = cmf_scan_dir(APP_PATH . '*', GLOB_ONLYDIR);

        array_push($apps, 'cmf', 'admin', 'user', 'swoole');

        foreach ($apps as $app) {
            HookLogic::importHooks($app);
        }

        return $this->fetch();
    }


}