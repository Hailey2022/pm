<?php

namespace app\admin\controller;

use app\admin\model\RouteModel;
use cmf\controller\AdminBaseController;

class RouteController extends AdminBaseController
{

    public function index()
    {
        global $CMF_GV_routes;
        $routeModel = new RouteModel();
        $routes = RouteModel::order("list_order asc")->select();
        $routeModel->getRoutes(true);
        unset($CMF_GV_routes);
        $this->assign("routes", $routes);
        return $this->fetch();
    }


    public function add()
    {
        return $this->fetch();
    }


    public function addPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $routeModel = new RouteModel();
            $result = $this->validate($data, 'Route');
            if ($result !== true) {
                $this->error($result);
            }
            $routeModel->save($data);

            $this->success("添加成功！", url("Route/index", ['id' => $routeModel->id]));
        }
    }


    public function edit()
    {
        $id = $this->request->param("id", 0, 'intval');
        $route = RouteModel::where('id', $id)->find()->toArray();
        $this->assign($route);
        return $this->fetch();
    }


    public function editPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $routeModel = new RouteModel();
            $result = $this->validate($data, 'Route');
            if ($result !== true) {
                $this->error($result);
            }
            $routeModel->where('id', $data['id'])->update($data);

            $this->success("保存成功！", url("Route/index"));
        }
    }


    public function delete()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            RouteModel::destroy($id);

            $this->success("删除成功！");
        }
    }


    public function ban()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param("id", 0, 'intval');
            $data = [];
            $data['status'] = 0;
            $data['id'] = $id;
            $routeModel = new RouteModel();

            $routeModel->save($data);
            $this->success("禁用成功！");
        }
    }


    public function open()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param("id", 0, 'intval');
            $data = [];
            $data['status'] = 1;
            $data['id'] = $id;
            $routeModel = new RouteModel();

            $routeModel->save($data);
            $this->success("启用成功！");
        }
    }


    public function listOrder()
    {
        $routeModel = new RouteModel();
        parent::listOrders($routeModel);
        $this->success("排序更新成功！");
    }


    public function select()
    {
        $routeModel = new RouteModel();
        $urls = $routeModel->getAppUrls();

        $this->assign('urls', $urls);
        return $this->fetch();
    }

    function _suggest_url($action, $url)
    {
        $actionArr = explode('/', $action);

        $params = array_keys($url['vars']);

        $urlDepr1Params = [];

        $urlDepr2Params = [];

        if (!empty($params)) {

            foreach ($params as $param) {
                if (empty($url['vars'][$param]['require'])) {
                    array_push($urlDepr1Params, "[:$param]");
                } else {
                    array_push($urlDepr1Params, ":$param");
                }

                array_push($urlDepr2Params, htmlspecialchars('<') . $param . htmlspecialchars('>'));
            }

        }

        if ($actionArr[2] == 'index') {
            $actionArr[1] = cmf_parse_name($actionArr[1]);
            return empty($params) ? $actionArr[1] . '$' : ($actionArr[1] . '/' . implode('/', $urlDepr1Params));
        } else {
            $actionArr[2] = cmf_parse_name($actionArr[2]);
            return empty($params) ? $actionArr[2] . '$' : ($actionArr[2] . '/' . implode('/', $urlDepr1Params));
        }

    }

    function _url_vars($url)
    {
        if (!empty($url['vars'])) {
            return implode(',', array_keys($url['vars']));
        }

        return '';
    }

}