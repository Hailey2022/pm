<?php
namespace app\admin\controller;
use app\admin\model\RecycleBinModel;
use app\admin\model\SlideItemModel;
use app\admin\model\SlideModel;
use cmf\controller\AdminBaseController;
class SlideController extends AdminBaseController
{
    public function index()
    {
        $content = hook_one('admin_slide_index_view');
        if (!empty($content)) {
            return $content;
        }
        $slidePostModel = new SlideModel();
        $slides = $slidePostModel->where('delete_time', 0)->select();
        $this->assign('slides', $slides);
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
            $slidePostModel = new SlideModel();
            $result = $this->validate($data, 'Slide');
            if ($result !== true) {
                $this->error($result);
            }
            $slidePostModel->save($data);
            $this->success("添加成功！", url("Slide/index"));
        }
    }
    public function edit()
    {
        $id = $this->request->param('id');
        $slidePostModel = new SlideModel();
        $result = $slidePostModel->where('id', $id)->find();
        $this->assign('result', $result);
        return $this->fetch();
    }
    public function editPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $result = $this->validate($data, 'Slide');
            if ($result !== true) {
                $this->error($result);
            }
            $slidePostModel = SlideModel::find($data['id']);
            $slidePostModel->save($data);
            $this->success("保存成功！", url("Slide/index"));
        }
    }
    public function delete()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            $slidePostModel = SlideModel::where('id', $id)->find();
            if (empty($slidePostModel)) {
                $this->error('幻灯片不存在!');
            }
            //如果存在页面。则不能删除。
            $slidePostCount = SlideItemModel::where('slide_id', $id)->count();
            if ($slidePostCount > 0) {
                $this->error('此幻灯片有页面无法删除!');
            }
            $data = [
                'object_id' => $id,
                'create_time' => time(),
                'table_name' => 'slide',
                'name' => $result['name']
            ];
            $resultSlide = $slidePostModel->save(['delete_time' => time()]);
            if ($resultSlide) {
                RecycleBinModel::insert($data);
            }
            $this->success("删除成功！", url("Slide/index"));
        }
    }
}