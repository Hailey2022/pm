<?php
namespace plugins\portal\controller;
use cmf\controller\PluginAdminBaseController;
use plugins\portal\model\PortalCategoryModel;
use think\Db;
use app\admin\model\AdminMenuModel;
class AdminRbacController extends PluginAdminBaseController
{
    public function authorize()
    {
        $content = hook_one('admin_rbac_authorize_view');
        if (!empty($content)) {
            return $content;
        }
        $AuthAccess     = Db::name("AuthAccess");
        $adminMenuModel = new AdminMenuModel();
        //角色ID
        $roleId = $this->request->param("id", 0, 'intval');
        if (empty($roleId)) {
            $this->error("参数错误！");
        }
        $findOnlySelfArticlesAuthAccess = Db::name("auth_access")->where("role_id", $roleId)
            ->where('type', 'portal_only_self_articles')->find();
        $portalCategoryModel = new PortalCategoryModel();
        $keyword             = $this->request->param('keyword');
        $categoryTree = $portalCategoryModel->adminAuthorizeCategoryTableTree(0, '', $roleId);
        $this->assign('category_tree', $categoryTree);
        $this->assign('only_self_articles', $findOnlySelfArticlesAuthAccess);
        $this->assign('keyword', $keyword);
        $this->assign("role_id", $roleId);
        return $this->fetch();
    }
    public function authorizePost()
    {
        if ($this->request->isPost()) {
            $roleId = $this->request->param("role_id", 0, 'intval');
            if (!$roleId) {
                $this->error("需要授权的角色不存在！");
            }
            $categoryModel = new PortalCategoryModel();
            $categories = $categoryModel->select();
            foreach ($categories as $category) {
                $ruleName = "portal/Category/index?id={$category['id']}";
                $findRule = Db::name("auth_rule")->where('name', $ruleName)->find();
                if (empty($findRule)) {
                    Db::name("auth_rule")->insert([
                        'status' => 1,
                        'app'    => 'portal',
                        'type'   => 'portal_category',
                        'name'   => $ruleName,
                        'title'  => $category['name']
                    ]);
                } else {
                    Db::name("auth_rule")->where('id', $findRule['id'])->update([
                        'status' => 1,
                        'app'    => 'portal',
                        'type'   => 'portal_category',
                        'name'   => $ruleName,
                        'title'  => $category['name']
                    ]);
                }
            }
            $onlySelfArticles = $this->request->param('only_self_articles', 0);
            if (empty($onlySelfArticles)) {
                Db::name("auth_access")->where("role_id", $roleId)
                    ->where('type', 'portal_only_self_articles')->delete();
            } else {
                $findOnlySelfArticlesAuthAccess = Db::name("auth_access")->where("role_id", $roleId)
                    ->where('type', 'portal_only_self_articles')->find();
                if (empty($findOnlySelfArticlesAuthAccess)) {
                    Db::name("auth_access")->insert(["role_id" => $roleId, "rule_name" => "portal/Article/only_self_articles", 'type' => 'portal_only_self_articles']);
                }
                $findOnlySelfArticlesAuthRule = Db::name("auth_rule")
                    ->where('name', 'portal/Article/only_self_articles')->find();
                if (empty($findOnlySelfArticlesAuthRule)) {
                    Db::name("auth_rule")->insert([
                        'status'=>1,
                        'app'=>'portal',
                        'type'=>'portal_only_self_articles',
                        'name'=>'portal/Article/only_self_articles',
                        'title'=>'只能看到自己的文章'
                    ]);
                }
            }
            $ids = $this->request->param('ids/a');
            if (!empty($ids)) {
                Db::name("auth_access")->where(["role_id" => $roleId, 'type' => 'portal_category'])->delete();
                foreach ($ids as $id) {
                    $ruleName = "portal/Category/index?id={$id}";
                    Db::name("auth_access")->insert(["role_id" => $roleId, "rule_name" => $ruleName, 'type' => 'portal_category']);
                }
                $this->success("授权成功！");
            } else {
                //当没有数据时，清除当前角色授权
                Db::name("auth_access")->where("role_id", $roleId)->where('type', 'portal_category')->delete();
                $this->success("授权成功！");
            }
        }
    }
    private function _isChecked($menu, $privData)
    {
        $app    = $menu['app'];
        $model  = $menu['controller'];
        $action = $menu['action'];
        $name   = strtolower("$app/$model/$action");
        if ($privData) {
            if (in_array($name, $privData)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    protected function _getLevel($id, $array = [], $i = 0)
    {
        if ($array[$id]['parent_id'] == 0 || empty($array[$array[$id]['parent_id']]) || $array[$id]['parent_id'] == $id) {
            return $i;
        } else {
            $i++;
            return $this->_getLevel($array[$id]['parent_id'], $array, $i);
        }
    }
    //角色成员管理
    public function member()
    {
        //TODO 添加角色成员管理
    }
}
