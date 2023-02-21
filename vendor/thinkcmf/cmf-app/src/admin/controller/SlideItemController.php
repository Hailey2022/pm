<?php
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use app\admin\model\SlideItemModel;
class SlideItemController extends AdminBaseController
{
    public function index()
    {
        $content = hook_one('admin_slide_item_index_view');
        if (!empty($content)) {
            return $content;
        }
        $id = $this->request->param('slide_id', 0, 'intval');
        $slideId = !empty($id) ? $id : 1;
        $result = SlideItemModel::where('slide_id', $slideId)->select();
        $this->assign('slide_id', $id);
        $this->assign('result', $result);
        return $this->fetch();
    }
    public function add()
    {
        $content = hook_one('admin_slide_item_add_view');
        if (!empty($content)) {
            return $content;
        }
        $slideId = $this->request->param('slide_id');
        $this->assign('slide_id', $slideId);
        return $this->fetch();
    }
    public function addPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            SlideItemModel::insert($data['post']);
            $this->success("添加成功！", url("slideItem/index", ['slide_id' => $data['post']['slide_id']]));
        }
    }
    public function edit()
    {
        $content = hook_one('admin_slide_item_edit_view');
        if (!empty($content)) {
            return $content;
        }
        $id = $this->request->param('id', 0, 'intval');
        $result = SlideItemModel::where('id', $id)->find();
        $this->assign('result', $result);
        $this->assign('slide_id', $result['slide_id']);
        return $this->fetch();
    }
    public function editPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['post']['image'] = cmf_asset_relative_url($data['post']['image']);
            SlideItemModel::update($data['post']);
            $this->success("保存成功！", url("SlideItem/index", ['slide_id' => $data['post']['slide_id']]));
        }
    }
    public function delete()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            $slideItem = SlideItemModel::find($id);
            $result = SlideItemModel::delete($id);
            if ($result) {
                //删除图片。
                $this->success("删除成功！", url("SlideItem/index", ["slide_id" => $slideItem['slide_id']]));
            } else {
                $this->error('删除失败！');
            }
        }
    }
    public function ban()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            if ($id) {
                $rst = SlideItemModel::where('id', $id)->update(['status' => 0]);
                if ($rst) {
                    $this->success("幻灯片隐藏成功！");
                } else {
                    $this->error('幻灯片隐藏失败！');
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
            if ($id) {
                $result = SlideItemModel::where('id', $id)->update(['status' => 1]);
                if ($result) {
                    $this->success("幻灯片启用成功！");
                } else {
                    $this->error('幻灯片启用失败！');
                }
            } else {
                $this->error('数据传入失败！');
            }
        }
    }
    public function listOrder()
    {
        $slideItemModel = new SlideItemModel();
        parent::listOrders($slideItemModel);
        $this->success("排序更新成功！");
    }
}