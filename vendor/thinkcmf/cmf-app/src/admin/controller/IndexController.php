<?php
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use app\admin\model\AdminMenuModel;
use think\Db;
class IndexController extends AdminBaseController
{
    public function initialize()
    {
        $adminSettings = cmf_get_option('admin_settings');
        if (empty($adminSettings['admin_password']) || $this->request->path() == $adminSettings['admin_password']) {
            $adminId = cmf_get_current_admin_id();
            if (empty($adminId)) {
                session("__LOGIN_BY_CMF_ADMIN_PW__", 1); //设置后台登录加密码
            }
        }
        parent::initialize();
    }
    public function index()
    {
        $content = hook_one('admin_index_index_view');
        if (!empty($content)) {
            return $content;
        }
        $adminMenuModel = new AdminMenuModel();
        $menus = cache('admin_menus_' . cmf_get_current_admin_id(), '', null, 'admin_menus');
        if (empty($menus)) {
            $menus = $adminMenuModel->menuTree();
            cache('admin_menus_' . cmf_get_current_admin_id(), $menus, null, 'admin_menus');
        }
        // var_dump($menus);
        if (cmf_auth_check(cmf_get_current_admin_id(),'admin/manager/view')){
            $projects = Db::name('project')->order('updateTime', 'desc')->select();
            foreach ($projects as $project){
                $menus[$project['projectId']] = [ //TODO: 改成auto
                    "icon" => "",
                    "id" => $project['projectId'],
                    "name" => $project['projectName'],
                    "parent" => 162, //TODO: 改成auto
                    "url" => url("manager/listProjectPayments", ['projectId'=>$project['projectId']]),
                    "lang" => "ADMIN_MANAGER_VIEW",
                    "items" => [
                        "工程信息" =>[
                            "icon" => "",
                            "id" => $project['projectId'] . "AAA",
                            "name" => "工程信息",
                            "parent" =>  $project['projectId'],
                            "url" => url("manager/updateProject", ['projectId'=>$project['projectId']]),
                            "lang" => "",
                        ],
                        "合同录入" =>[
                            "icon" => "",
                            "id" => $project['projectId'] . "BBB",
                            "name" => "合同录入",
                            "parent" =>  $project['projectId'],
                            "url" => url("manager/addContract", ['projectId'=>$project['projectId']]),
                            "lang" => "",
                        ],
                        "支付录入" =>[
                            "icon" => "",
                            "id" => $project['projectId'] . "CCC",
                            "name" => "支付录入",
                            "parent" =>  $project['projectId'],
                            "url" => url("manager/pay", ['projectId'=>$project['projectId']]),
                            "lang" => "",
                        ]
                    ]
                ];
            }
        }
        // var_dump($menus['162admin']['items']);
        $this->assign("menus", $menus);
        $result = AdminMenuModel::order(["app" => "ASC", "controller" => "ASC", "action" => "ASC"])->select();
        $menusTmp = array();
        foreach ($result as $item) {
            $indexTmp = $item['app'] . $item['controller'] . $item['action'];
            $indexTmp = preg_replace("/[\\/|_]/", "", $indexTmp);
            $indexTmp = strtolower($indexTmp);
            $menusTmp[$indexTmp] = $item;
        }
        $this->assign("menus_js_var", json_encode($menusTmp));
        return $this->fetch();
    }
}