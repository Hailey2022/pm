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
                session("__LOGIN_BY_CMF_ADMIN_PW__", 1);
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
        if (cmf_auth_check(cmf_get_current_admin_id(), 'admin/manager/view')) {
            $years = Db::name('project')->order('constructionYear', 'desc')->Distinct(true)->column('constructionYear');
            foreach ($years as $year) {

                $projects = Db::name('project')->order('updateTime', 'desc')->where('constructionYear', $year)->select();
                if ($year == "") {
                    $year = "未知";
                }
                $menus["year" . $year] = [
                    "icon" => "year",
                    "id" => "year" . $year,
                    "name" => $year . "年份工程",
                    "url" => "o",
                    "lang" => "ADMIN_MANAGER_VIEW"
                ];
                foreach ($projects as $project) {
                    $menus["year" . $year]["items"][$project['projectId']] = [
                        "icon" => "projectName",
                        "id" => $project['projectId'],
                        "name" => $project['projectName'],
                        "parent" => $project['projectId'],
                        "url" => url("manager/{action}", ['projectId' => $project['projectId']]),
                        "lang" => "ADMIN_MANAGER_VIEW"
                    ];
                }
            }
        }
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