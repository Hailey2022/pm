<?php

namespace api\demo\controller;

use cmf\controller\RestBaseController;

class ArticlesController extends RestBaseController
{
    public function index()
    {
        $articles = [
            ['title' => 'article title1'],
            ['title' => 'article title2'],
        ];
        $this->success('请求成功!', ['articles' => $articles]);
    }

    public function save()
    {
    }

    public function read($id)
    {
    }

    public function update($id)
    {
    }

    public function delete($id)
    {
    }
}
