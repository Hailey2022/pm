<?php
namespace app\admin\controller;
use app\admin\model\RecycleBinModel;
use app\admin\model\RouteModel;
use cmf\controller\AdminBaseController;
use think\facade\Db;
use think\Exception;
use think\exception\PDOException;
class RecycleBinController extends AdminBaseController
{
    public function index()
    {
        $content = hook_one('admin_recycle_bin_index_view');
        if (!empty($content)) {
            return $content;
        }
        $recycleBinModel = new RecycleBinModel();
        $list = $recycleBinModel->order('create_time desc')->paginate(10);
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('list', $list);
        return $this->fetch();
    }
    public function restore()
    {
        if ($this->request->isPost()) {
            $ids = $this->request->param('ids');
            if (empty($ids)) {
                $ids = $this->request->param('id');
            }
            $this->operate($ids, false);
            $this->success('还原成功');
        }
    }
    public function delete()
    {
        if ($this->request->isPost()) {
            $ids = $this->request->param('ids');
            if (empty($ids)) {
                $ids = $this->request->param('id');
            }
            $this->operate($ids);
            $this->success('删除成功');
        }
    }
    public function clear()
    {
        if ($this->request->isPost()) {
            $this->operate(null);
            $this->success('回收站已清空');
        }
    }
    private function operate($ids, $isDelete = true)
    {
        if (!empty($ids) && !is_array($ids)) {
            $ids = [$ids];
        }
        $records = RecycleBinModel::all($ids);
        if ($records) {
            try {
                Db::startTrans();
                $desIds = [];
                foreach ($records as $record) {
                    $desIds[] = $record['id'];
                    if ($isDelete) {
                        if ($record['table_name'] === 'portal_post#page') {
                            Db::name('portal_post')->delete($record['object_id']);
                            $routeModel = new RouteModel();
                            $routeModel->setRoute('', 'portal/Page/index', ['id' => $record['object_id']], 2, 5000);
                            $routeModel->getRoutes(true);
                        } else {
                            Db::name($record['table_name'])->delete($record['object_id']);
                        }
                        if ($record['table_name'] === 'portal_post') {
                            Db::name('portal_category_post')->where('post_id', '=', $record['object_id'])->delete();
                            Db::name('portal_tag_post')->where('post_id', '=', $record['object_id'])->delete();
                        }
                    } else {
                        $tableNameArr = explode('#', $record['table_name']);
                        $tableName = $tableNameArr[0];
                        $result = Db::name($tableName)->where('id', '=', $record['object_id'])->update(['delete_time' => '0']);
                        if ($result) {
                            if ($tableName === 'portal_post') {
                                Db::name('portal_category_post')->where('post_id', '=', $record['object_id'])->update(['status' => 1]);
                                Db::name('portal_tag_post')->where('post_id', '=', $record['object_id'])->update(['status' => 1]);
                            }
                        }
                    }
                }
                RecycleBinModel::destroy($desIds);
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error('数据库错误', $e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($isDelete ? '删除' : '还原' . '失败', $e->getMessage());
            }
        }
    }
}