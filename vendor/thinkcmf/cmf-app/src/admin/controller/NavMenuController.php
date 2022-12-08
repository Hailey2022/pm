<?php









namespace app\admin\controller;

use app\admin\model\NavMenuModel;
use cmf\controller\AdminBaseController;
use tree\Tree;


class NavMenuController extends AdminBaseController
{
    
    public function index()
    {
        $intNavId     = $this->request->param("nav_id", 0, 'intval');
        $navMenuModel = new NavMenuModel();

        if (empty($intNavId)) {
            $this->error("请指定导航!");
        }

        $objResult = $navMenuModel->where("nav_id", $intNavId)->order(["list_order" => "ASC"])->select();
        $arrResult = $objResult ? $objResult->toArray() : [];

        $tree       = new Tree();
        $tree->icon = ['&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';

        $array = [];
        foreach ($arrResult as $r) {
            $r['str_manage'] = '<a class="btn btn-xs btn-primary" href="' . url("NavMenu/add", ["parent_id" => $r['id'], "nav_id" => $r['nav_id']]) . '">添加子菜单</a>
            <a class="btn btn-xs btn-primary" href="' . url("NavMenu/edit", ["id" => $r['id'], "parent_id" => $r['parent_id'], "nav_id" => $r['nav_id']]) . '">编辑</a> 
            <a class="btn btn-xs btn-danger js-ajax-delete" href="' . url("NavMenu/delete", ["id" => $r['id'], 'nav_id' => $r['nav_id']]) . '">删除</a> ';
            $r['status']     = $r['status'] ? "显示" : "隐藏";
            $array[]         = $r;
        }

        $tree->init($array);
        $str = "<tr>
            <td><input name='list_orders[\$id]' type='text' size='3' value='\$list_order' class='input input-order'></td>
            <td>\$id</td>
            <td >\$spacer\$name</td>
            <td>\$status</td>
            <td>\$str_manage</td>
        </tr>";

        $categories = $tree->getTree(0, $str);

        $this->assign("categories", $categories);
        $this->assign('nav_id', $intNavId);

        return $this->fetch();
    }

    
    public function add()
    {
        $navMenuModel = new NavMenuModel();
        $intNavId     = $this->request->param("nav_id", 0, 'intval');
        $intParentId  = $this->request->param("parent_id", 0, 'intval');
        $objResult    = $navMenuModel->where("nav_id", $intNavId)->order(["list_order" => "ASC"])->select();
        $arrResult    = $objResult ? $objResult->toArray() : [];

        $tree       = new Tree();
        $tree->icon = ['&nbsp;│ ', '&nbsp;├─ ', '&nbsp;└─ '];
        $tree->nbsp = '&nbsp;';
        $array      = [];

        foreach ($arrResult as $r) {
            $r['str_manage'] = '<a href="' . url("NavMenu/add", ["parent_id" => $r['id']]) . '">添加子菜单</a> | <a href="'
                . url("NavMenu/edit", ["id" => $r['id']]) . '">编辑</a> | <a class="J_ajax_del" href="'
                . url("NavMenu/delete", ["id" => $r['id']]) . '">删除</a> ';
            $r['status']     = $r['status'] ? "显示" : "隐藏";
            $r['selected']   = $r['id'] == $intParentId ? "selected" : "";
            $array[]         = $r;
        }

        $tree->init($array);
        $str      = "<option value='\$id' \$selected>\$spacer\$name</option>";
        $navTrees = $tree->getTree(0, $str);
        $this->assign("nav_trees", $navTrees);

        $navs = $navMenuModel->selectNavs();
        $this->assign('navs', $navs);

        $this->assign("nav_id", $intNavId);
        return $this->fetch();
    }

    
    public function addPost()
    {
        if ($this->request->isPost()) {
            $navMenuModel = new NavMenuModel();
            $arrData      = $this->request->post();

            if (isset($arrData['external_href'])) {
                $arrData['href'] = htmlspecialchars_decode($arrData['external_href']);
            } else {
                $arrData['href'] = htmlspecialchars_decode($arrData['href']);
                $arrData['href'] = base64_decode($arrData['href']);
            }

            unset($arrData['external_href']);

            $navMenuModel->save($arrData);

            $this->success(lang("EDIT_SUCCESS"), url("NavMenu/index", ['nav_id' => $arrData['nav_id']]));
        }
    }

    
    public function edit()
    {
        $navMenuModel = new NavMenuModel();
        $intNavId     = $this->request->param("nav_id", 0, 'intval');
        $intId        = $this->request->param("id", 0, 'intval');
        $intParentId  = $this->request->param("parent_id", 0, 'intval');
        $objResult    = $navMenuModel
            ->where("nav_id", $intNavId)
            ->where("id", "<>", $intId)
            ->order(["list_order" => "ASC"])
            ->select();
        $arrResult    = $objResult ? $objResult->toArray() : [];

        $tree       = new Tree();
        $tree->icon = ['&nbsp;│ ', '&nbsp;├─ ', '&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $array      = [];
        foreach ($arrResult as $r) {
            $r['str_manage'] = '<a href="' . url("NavMenu/add", ["parent_id" => $r['id'], "nav_id" => $intNavId]) . '">添加子菜单</a> | <a href="'
                . url("NavMenu/edit", ["id" => $r['id'], "nav_id" => $intNavId]) . '">编辑</a> | <a class="js-ajax-delete" href="'
                . url("NavMenu/delete", ["id" => $r['id'], "nav_id" => $intNavId]) . '">删除</a> ';
            $r['status']     = $r['status'] ? "显示" : "隐藏";
            $r['selected']   = $r['id'] == $intParentId ? "selected" : "";
            $array[]         = $r;
        }

        $tree->init($array);
        $str       = "<option value='\$id' \$selected>\$spacer\$name</option>";
        $nav_trees = $tree->getTree(0, $str);
        $this->assign("nav_trees", $nav_trees);

        $objNav = $navMenuModel->where("id", $intId)->find();
        $arrNav = $objNav ? $objNav->toArray() : [];

        $arrNav['href_old'] = $arrNav['href'];

        if (strpos($arrNav['href'], "{") === 0 || $arrNav['href'] == 'home') {
            $arrNav['href'] = base64_encode($arrNav['href']);
        }

        $this->assign($arrNav);

        $navs = $navMenuModel->selectNavs();
        $this->assign('navs', $navs);

        $this->assign("nav_id", $intNavId);
        $this->assign("parent_id", $intParentId);

        return $this->fetch();
    }

    
    public function editPost()
    {
        if ($this->request->isPost()) {
            $navMenuModel = new NavMenuModel();
            $intId        = $this->request->param('id', 0, 'intval');
            $arrData      = $this->request->post();

            if (isset($arrData['external_href'])) {
                $arrData['href'] = htmlspecialchars_decode($arrData['external_href']);
            } else {
                $arrData['href'] = htmlspecialchars_decode($arrData['href']);
                $arrData['href'] = base64_decode($arrData['href']);
            }

            unset($arrData['external_href']);

            $navMenuModel->where('id', $intId)->update($arrData);

            $this->success(lang("EDIT_SUCCESS"), url("NavMenu/index", ['nav_id' => $arrData['nav_id']]));
        }
    }

    
    public function delete()
    {
        if ($this->request->isPost()) {
            $navMenuModel = new NavMenuModel();

            $intId    = $this->request->param("id", 0, "intval");
            $intNavId = $this->request->param("nav_id", 0, "intval");

            if (empty($intId)) {
                $this->error(lang("NO_ID"));
            }

            $count = $navMenuModel->where("parent_id", $intId)->count();
            if ($count > 0) {
                $this->error("该菜单下还有子菜单，无法删除！");
            }

            $navMenuModel->where("id", $intId)->delete();
            $this->success(lang("DELETE_SUCCESS"), url("NavMenu/index", ['nav_id' => $intNavId]));
        }
    }

    
    public function listOrder()
    {
        $navMenuModel = new NavMenuModel();
        $status       = parent::listOrders($navMenuModel);
        if ($status) {
            $this->success("排序更新成功！");
        } else {
            $this->error("排序更新失败！");
        }
    }


}