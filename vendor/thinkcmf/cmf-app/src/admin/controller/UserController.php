<?php
namespace app\admin\controller;
use app\admin\model\RoleModel;
use app\admin\model\RoleUserModel;
use app\admin\model\UserModel;
use cmf\controller\AdminBaseController;
use think\db\Query;
use think\db;
class UserController extends AdminBaseController
{
    public function index()
    {
        $currectPage = $this->request->param('page');
        $currectRoleId = $this->request->param('roleId');
        if ($currectPage == null) {
            $currectPage = 1;
        }
        if ($currectRoleId == null) {
            $currectRoleId = "";
        }
        $ct = Db::name('role')->where('id', '>', 2)->select();
        $this->assign('clientTypes', $ct);
        $this->assign('currectPage', $currectPage);
        $this->assign('currectRoleId', $currectRoleId);
        $content = hook_one('admin_user_index_view');
        if (!empty($content)) {
            return $content;
        }
        $userLogin = $this->request->param('user_login');
        $users = Db::name('role_user r, pm_user u')->where('user_type', 1)->where('u.id=r.user_id')
            ->where(function (Query $query) use ($userLogin, $currectRoleId) {
                if ($userLogin) {
                    $query->where('user_login', 'like', "%$userLogin%");
                }
                if ($currectRoleId) {
                    $query->where('role_id', $currectRoleId);
                }
            })
            ->where('u.id', '<>', 1)
            ->where('u.id', '<>', 9)
            ->order("u.user_login")
            ->group('user_login')
            ->paginate(10);
        $page = $users->render();
        $rolesSrc = RoleModel::select();
        $roles = [];
        foreach ($rolesSrc as $r) {
            $roleId = $r['id'];
            $roles["$roleId"] = $r;
        }
        $this->assign("page", $page);
        $this->assign("roles", $roles);
        $this->assign("users", $users);
        return $this->fetch();
    }
    public function add()
    {
        $content = hook_one('admin_user_add_view');
        if (!empty($content)) {
            return $content;
        }
        $roles = RoleModel::where('status', 1)->order("id DESC")->select();
        $this->assign("roles", $roles);
        return $this->fetch();
    }
    public function addPost()
    {
        if ($this->request->isPost()) {
            $roleIds = $this->request->param('role_id/a');
            if (!empty($roleIds) && is_array($roleIds)) {
                if (count($roleIds) != 1){
                    $this->error("不可以选多个角色！");
                }
                $data = $this->request->param();
                $result = $this->validate($data, 'User');
                if ($result !== true) {
                    $this->error($result);
                } else {
                    $data['user_pass'] = cmf_password($data['user_pass']);
                    $userId = UserModel::strict(false)->insertGetId($data);
                    if ($userId !== false) {
                        foreach ($roleIds as $roleId) {
                            if (cmf_get_current_admin_id() != 1 && $roleId == 1) {
                                $this->error("为了网站的安全，不可创建超级管理员！");
                            }
                            RoleUserModel::insert(["role_id" => $roleId, "user_id" => $userId]);
                        }
                        $this->success("添加成功！", url("User/index"));
                    } else {
                        $this->error("添加失败！");
                    }
                }
            } else {
                $this->error("请为此用户指定角色！");
            }
        }
    }
    public function edit()
    {
        $content = hook_one('admin_user_edit_view');
        if (!empty($content)) {
            return $content;
        }
        $id = $this->request->param('id', 0, 'intval');
        $roles = RoleModel::where('status', 1)->order("id DESC")->select();
        $this->assign("roles", $roles);
        $role_ids = RoleUserModel::where("user_id", $id)->column("role_id");
        $this->assign("role_ids", $role_ids);
        $user = Db::name("user")->where("id", $id)->find();
        $this->assign($user);
        return $this->fetch();
    }
    public function editPost()
    {
        if ($this->request->isPost()) {
            $roleIds = $this->request->param('role_id/a');
            if (!empty($roleIds) && is_array($roleIds)) {
                if (count($roleIds) != 1){
                    $this->error("不可以选多个角色！");
                }
                $data = $this->request->param();
                if (empty($data['user_pass'])) {
                    unset($data['user_pass']);
                } else {
                    $data['user_pass'] = cmf_password($data['user_pass']);
                }
                $result = $this->validate($data, 'User.edit');
                if ($result !== true) {
                    $this->error($result);
                } else {
                    $userId = $this->request->param('id', 0, 'intval');
                    $result = UserModel::strict(false)->where('id', $userId)->update($data);
                    if ($result !== false) {
                        RoleUserModel::where("user_id", $userId)->delete();
                        foreach ($roleIds as $roleId) {
                            if (cmf_get_current_admin_id() != 1 && $roleId == 1) {
                                $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                            }
                            RoleUserModel::insert(["role_id" => $roleId, "user_id" => $userId]);
                        }
                        $this->success("保存成功！");
                    } else {
                        $this->error("保存失败！");
                    }
                }
            } else {
                $this->error("请为此用户指定角色！");
            }
        }
    }
    public function userInfo()
    {
        $id = cmf_get_current_admin_id();
        $user = UserModel::where("id", $id)->find()->toArray();
        $this->assign($user);
        return $this->fetch();
    }
    public function userInfoPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['birthday'] = strtotime($data['birthday']);
            $data['id'] = cmf_get_current_admin_id();
            $create_result = UserModel::update($data);
            ;
            if ($create_result !== false) {
                $this->success("保存成功！");
            } else {
                $this->error("保存失败！");
            }
        }
    }
    public function delete()
    {
        $this->error("删除失败！");
    }
    public function ban()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            if (!empty($id)) {
                $result = UserModel::where(["id" => $id, "user_type" => 1])->update(['user_status' => '0']);
                if ($result !== false) {
                    $this->success("管理员停用成功！", url("User/index"));
                } else {
                    $this->error('管理员停用失败！');
                }
            } else {
                $this->error('数据传入失败！');
            }
        }
    }
    public function cancelBan()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            if (!empty($id)) {
                $result = UserModel::where(["id" => $id, "user_type" => 1])->update(['user_status' => '1']);
                if ($result !== false) {
                    $this->success("管理员启用成功！", url("User/index"));
                } else {
                    $this->error('管理员启用失败！');
                }
            } else {
                $this->error('数据传入失败！');
            }
        }
    }
}