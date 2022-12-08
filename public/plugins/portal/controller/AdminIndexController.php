<?php







namespace plugins\portal\controller; 

use cmf\controller\PluginAdminBaseController;
use think\Db;

class AdminIndexController extends PluginAdminBaseController
{

    
    public function setting()
    {
        $data = Db::name('role')->order(["list_order" => "ASC", "id" => "DESC"])->select();
        $this->assign("roles", $data);
        return $this->fetch();

        return $this->fetch();
    }

}
