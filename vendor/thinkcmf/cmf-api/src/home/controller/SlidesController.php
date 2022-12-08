<?php











namespace api\home\controller;

use api\home\service\SlideService;
use cmf\controller\RestBaseController;

class SlidesController extends RestBaseController
{
    
    public function read()
    {
        //slide为空或不存在抛出异常
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('缺少ID参数');
        }

        $map['id']    = $id;
        $slideService = new SlideService();
        $data         = $slideService->SlideList($map);
        //剔除分类状态隐藏 剔除分类下显示数据为空
        if (empty($data) || $data['items']->isEmpty()) {
            $this->error('该组幻灯片显示数据为空');
        }

        if (empty($this->apiVersion) || $this->apiVersion == '1.0.0') {
            $response = [$data];
        } else {
            $response = $data;
        }

        $this->success("该组幻灯片获取成功!", $response);
    }

}
