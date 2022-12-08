<?php









namespace cmf\controller;

class PluginAdminBaseController extends PluginBaseController
{

    
    protected function initialize()
    {
        
        $param = ['is_plugin' => true];
        hook('admin_init', $param);
        $adminId = cmf_get_current_admin_id();
        if (!empty($adminId)) {
            if (!$this->checkAccess($adminId)) {
                $this->error("您没有访问权限！");
            }
        } else {
            if ($this->request->isAjax()) {
                $this->error("您还没有登录！", url("admin/Public/login"));
            } else {
                header("Location:" . url("admin/Public/login"));
                exit();
            }
        }
    }

    
    private function checkAccess($userId)
    {
        
        if ($userId == 1) {
            return true;
        }

        $pluginName = $this->request->param('_plugin');
        $pluginName = cmf_parse_name($pluginName, 1);
        $controller = $this->request->param('_controller');
        $controller = cmf_parse_name($controller, 1);
        $action     = $this->request->param('_action');

        return cmf_auth_check($userId, "plugin/{$pluginName}/$controller/$action");
    }


}