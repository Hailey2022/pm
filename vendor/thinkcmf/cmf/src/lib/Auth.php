<?php
namespace cmf\lib;
use cmf\model\AuthAccessModel;
use cmf\model\AuthRuleModel;
use cmf\model\RoleUserModel;
use cmf\model\UserModel;
class Auth
{
    //默认配置
    protected $_config = [];
    public function __construct()
    {
    }
    public function check($uid, $name, $relation = 'or')
    {
        if (empty($uid)) {
            return false;
        }
        if ($uid == 1) {
            return true;
        }
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $findAuthRuleCount = AuthRuleModel::where([
                    'name' => $name
                ])->count();
                if ($findAuthRuleCount == 0) {//没有规则时,不验证!
                    return true;
                }
                $name = [$name];
            }
        }
        $list   = []; //保存验证通过的规则名
        $groups = RoleUserModel::alias("a")
            ->join('role r', 'a.role_id = r.id')
            ->where(["a.user_id" => $uid, "r.status" => 1])
            ->column("role_id");
        if (in_array(1, $groups)) {
            return true;
        }
        if (empty($groups)) {
            return false;
        }
        $rules = AuthAccessModel::alias("a")
            ->join('auth_rule b ', ' a.rule_name = b.name')
            ->where('a.role_id', 'in', $groups)
            ->where('b.name', 'in', $name)
            ->select();
        foreach ($rules as $rule) {
            if (!empty($rule['condition'])) { //根据condition进行验证
                $user = $this->getUserInfo($uid);//获取用户信息,一维数组
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                //dump($command);//debug
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $list[] = strtolower($rule['name']);
                }
            } else {
                $list[] = strtolower($rule['name']);
            }
        }
        if ($relation == 'or' and !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {
            return true;
        }
        return false;
    }
    private function getUserInfo($uid)
    {
        return UserModel::where('id', $uid)->find();
    }
}
