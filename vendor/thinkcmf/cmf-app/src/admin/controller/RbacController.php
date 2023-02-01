<?php

namespace app\admin\controller;

use app\admin\model\AuthAccessModel;
use app\admin\model\RoleModel;
use app\admin\model\RoleUserModel;
use cmf\controller\AdminBaseController;
use think\facade\Cache;
use tree\Tree;
use app\admin\model\AdminMenuModel;

class RbacController extends AdminBaseController
{
    public function index()
    {
        $content = hook_one('admin_rbac_index_view');

        if (!empty($content)) {
            return $content;
        }

        $data = RoleModel::order(["list_order" => "ASC", "id" => "DESC"])->select();
        $this->assign("roles", $data);
        return $this->fetch();
    }


    public function roleAdd()
    {
        $content = hook_one('admin_rbac_role_add_view');

        if (!empty($content)) {
            return $content;
        }

        return $this->fetch();
    }


    public function roleAddPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $result = $this->validate($data, 'role');
            if ($result !== true) {

                $this->error($result);
            } else {
                $result = RoleModel::insert($data);
                if ($result) {
                    $this->success("添加角色成功", url("rbac/index"));
                } else {
                    $this->error("添加角色失败");
                }

            }
        }
    }


    public function roleEdit()
    {
        $content = hook_one('admin_rbac_role_edit_view');

        if (!empty($content)) {
            return $content;
        }

        $id = $this->request->param("id", 0, 'intval');
        if ($id == 1) {
            $this->error("超级管理员角色不能被修改！");
        }
        $data = RoleModel::where("id", $id)->find();
        if (!$data) {
            $this->error("该角色不存在！");
        }
        $this->assign("data", $data);
        return $this->fetch();
    }


    public function roleEditPost()
    {
        $id = $this->request->param("id", 0, 'intval');
        if ($id == 1) {
            $this->error("超级管理员角色不能被修改！");
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $result = $this->validate($data, 'role');
            if ($result !== true) {

                $this->error($result);

            } else {
                if (RoleModel::update($data) !== false) {
                    $this->success("保存成功！", url('rbac/index'));
                } else {
                    $this->error("保存失败！");
                }
            }
        }
    }


    public function roleDelete()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param("id", 0, 'intval');
            if ($id == 1) {
                $this->error("超级管理员角色不能被删除！");
            }
            $count = RoleUserModel::where('role_id', $id)->count();
            if ($count > 0) {
                $this->error("该角色已经有用户！");
            } else {
                $status = RoleModel::delete($id);
                if (!empty($status)) {
                    $this->success("删除成功！", url('rbac/index'));
                } else {
                    $this->error("删除失败！");
                }
            }
        }
    }


    public function authorize()
    {
        $content = hook_one('admin_rbac_authorize_view');

        if (!empty($content)) {
            return $content;
        }

        $adminMenuModel = new AdminMenuModel();
        //角色ID
        $roleId = $this->request->param("id", 0, 'intval');
        if (empty($roleId)) {
            $this->error("参数错误！");
        }

        $tree = new Tree();
        $tree->icon = ['│ ', '├─ ', '└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';

        $result = $adminMenuModel->menuCache();

        $newMenus = [];
        $privilegeData = AuthAccessModel::where("role_id", $roleId)->column("rule_name"); //获取权限表数据

        foreach ($result as $m) {
            $newMenus[$m['id']] = $m;
        }

        foreach ($result as $n => $t) {
            $result[$n]['checked'] = ($this->_isChecked($t, $privilegeData)) ? ' checked' : '';
            $result[$n]['level'] = $this->_getLevel($t['id'], $newMenus);
            $result[$n]['style'] = empty($t['parent_id']) ? '' : 'display:none;';
            $result[$n]['parentIdNode'] = ($t['parent_id']) ? ' class="child-of-node-' . $t['parent_id'] . '"' : '';
        }

        $str = "<tr id='node-\$id'\$parentIdNode  style='\$style'>
                   <td style='padding-left:30px;'>\$spacer<input type='checkbox' name='menuId[]' value='\$id' level='\$level' \$checked onclick='javascript:checknode(this);'> \$name</td>
    			</tr>";
        $tree->init($result);

        $category = $tree->getTree(0, $str);

        $this->assign("category", $category);
        $this->assign("roleId", $roleId);
        return $this->fetch();
    }


    public function authorizePost()
    {
        if ($this->request->isPost()) {
            $roleId = $this->request->param("roleId", 0, 'intval');
            if (!$roleId) {
                $this->error("需要授权的角色不存在！");
            }
            $menuIds = $this->request->param('menuId/a');
            if (is_array($menuIds) && count($menuIds) > 0) {

                AuthAccessModel::where(["role_id" => $roleId, 'type' => 'admin_url'])->delete();
                foreach ($menuIds as $menuId) {
                    $menu = AdminMenuModel::where("id", $menuId)->field("app,controller,action")->find();
                    if ($menu) {
                        $app = $menu['app'];
                        $model = $menu['controller'];
                        $action = $menu['action'];
                        $name = strtolower("$app/$model/$action");
                        AuthAccessModel::insert(["role_id" => $roleId, "rule_name" => $name, 'type' => 'admin_url']);
                    }
                }

                Cache::clear('admin_menus');

                $this->success("授权成功！");
            } else {
                //当没有数据时，清除当前角色授权
                AuthAccessModel::where("role_id", $roleId)->delete();
                $this->error("没有接收到数据，执行清除授权成功！");
            }
        }
    }


    private function _isChecked($menu, $privData)
    {
        $app = $menu['app'];
        $model = $menu['controller'];
        $action = $menu['action'];
        $name = strtolower("$app/$model/$action");
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