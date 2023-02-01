<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use app\admin\model\NavModel;

class NavController extends AdminBaseController
{

    public function index()
    {
        $content = hook_one('admin_nav_index_view');

        if (!empty($content)) {
            return $content;
        }

        $navModel = new NavModel();

        $navs = $navModel->select();
        $this->assign('navs', $navs);

        return $this->fetch();

    }


    public function add()
    {
        return $this->fetch();
    }


    public function addPost()
    {
        if ($this->request->isPost()) {
            $navModel = new NavModel();
            $arrData = $this->request->post();

            if (empty($arrData["is_main"])) {
                $arrData["is_main"] = 0;
            } else {
                $navModel->where("is_main", 1)->update(["is_main" => 0]);
            }

            $navModel->insert($arrData);
            $this->success(lang("EDIT_SUCCESS"), url("Nav/index"));
        }

    }


    public function edit()
    {
        $navModel = new NavModel();
        $intId = $this->request->param("id", 0, 'intval');

        $objNavCat = $navModel->where("id", $intId)->find();
        $arrNavCat = $objNavCat ? $objNavCat->toArray() : [];

        $this->assign($arrNavCat);
        return $this->fetch();
    }



    public function editPost()
    {
        if ($this->request->isPost()) {
            $navModel = new NavModel();
            $arrData = $this->request->post();

            if (empty($arrData["is_main"])) {
                $arrData["is_main"] = 0;
            } else {
                $navModel->where("is_main", 1)->update(["is_main" => 0]);
            }

            $navModel->where("id", intval($arrData["id"]))->update($arrData);
            $this->success(lang("EDIT_SUCCESS"), url("nav/index"));
        }
    }


    public function delete()
    {
        if ($this->request->isPost()) {
            $navModel = new NavModel();
            $intId = $this->request->param("id", 0, "intval");

            if (empty($intId)) {
                $this->error(lang("NO_ID"));
            }

            $navModel->where("id", $intId)->delete();
            $this->success(lang("DELETE_SUCCESS"), url("Nav/index"));
        }

    }


}