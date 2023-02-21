<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\db\Query;

class ReportController extends AdminBaseController
{
    function isAdmin($uid)
    {
        return ($uid == 1 || $uid == 9);
    }
    public function reports()
    {
        $projectId = $this->request->param('projectId');
        $uid = cmf_get_current_admin_id();
        $reports = Db::name('contract c, pm_reportType rt, pm_project p,pm_user u, pm_report r')
            ->where('r.contractId = c.contractId')
            ->where('c.projectId = p.projectId')
            ->where('r.reportTypeId = rt.id')
            ->where('c.clientId = u.id')
            ->where(function (Query $query) use ($projectId, $uid) {
                if ($projectId != null) {
                    $query->where('p.projectId', $projectId);
                }
                if ($this->isAdmin($uid) !== true) {
                    $query->where('c.clientId', $uid);
                }
            })
            ->order('reportTime', 'desc')
            ->select();
        $this->assign('reports', $reports);
        return $this->fetch();
    }
    public function updatePic()
    {
        $uid = cmf_get_current_admin_id();
        $picId = $this->request->param('picId');
        $projects = Db::name('contract c, pm_project p')
            ->where('p.projectId = c.projectId')
            ->where(function (Query $query) use ($uid) {
                if ($this->isAdmin($uid) !== true) {
                    $query->where('c.clientId', $uid);
                }
            })
            ->group('p.projectId')
            ->order('p.updateTime', 'desc')
            ->select();
        $this->assign('projects', $projects);
        $users = Db::name('user')
            ->where('id', '<>', '1')
            ->where('id', '<>', '9')
            ->order('user_login')
            ->select();
        $this->assign("users", $users);
        $types = Db::name('type')->select();
        $this->assign("types", $types);
        $pics = Db::name('project p, pm_contract c, pm_pics r')
            ->where('r.contractId = c.contractId')
            ->where('c.projectId = p.projectId')
            ->where('picId', $picId)
            ->find();
        $this->assign("pics", $pics);
        $urls = json_decode($pics["file_urls"]);
        $names = json_decode($pics["file_names"]);
        if ($urls != null and $names != null) {
            $files = array_combine($urls, $names);
            $this->assign("files", $files);
        }
        $contracts = Db::name('contract c, pm_user u, pm_type t')
            ->where('t.id=c.clientType')
            ->where('u.id = c.clientId')
            ->where(function (Query $query) use ($uid) {
                if ($this->isAdmin($uid) !== true) {
                    $query->where('u.id', $uid);
                }
            })
            ->order('updateTime', 'desc')
            ->select();
        $this->assign('contracts', $contracts);
        return $this->fetch();
    }
    public function updateReport()
    {
        $uid = cmf_get_current_admin_id();
        $reportId = $this->request->param('reportId');
        $projects = Db::name('contract c, pm_project p')
            ->where('p.projectId = c.projectId')
            ->where(function (Query $query) use ($uid) {
                if ($this->isAdmin($uid) !== true) {
                    $query->where('c.clientId', $uid);
                }
            })
            ->group('p.projectId')
            ->order('p.updateTime', 'desc')
            ->select();
        $this->assign('projects', $projects);
        $users = Db::name('user')->where('id', '<>', '1')->where('id', '<>', '9')->order('user_login')->select();
        $this->assign("users", $users);
        $types = Db::name('type')->select();
        $this->assign("types", $types);
        $reportTypes = Db::name('reportType')->select();
        $this->assign("reportTypes", $reportTypes);
        $report = Db::name('project p, pm_reportType rt, pm_contract c, pm_report r')
            ->where('r.contractId = c.contractId')
            ->where('c.projectId = p.projectId')
            ->where('r.reportTypeId = rt.id')
            ->where('reportId', $reportId)
            ->find();
        $this->assign("report", $report);
        $urls = json_decode($report["file_urls"]);
        $names = json_decode($report["file_names"]);
        if ($urls != null and $names != null) {
            $files = array_combine($urls, $names);
            $this->assign("files", $files);
        }
        $contracts = Db::name('contract c, pm_user u, pm_type t')
            ->where('t.id=c.clientType')
            ->where('u.id = c.clientId')
            ->where(function (Query $query) use ($uid) {
                if ($this->isAdmin($uid) !== true) {
                    $query->where('u.id', $uid);
                }
            })
            ->order('updateTime', 'desc')
            ->select();
        $this->assign('contracts', $contracts);
        return $this->fetch();
    }
    public function addReport()
    {
        $uid = cmf_get_current_admin_id();
        $projects = Db::name('contract c, pm_project p')
            ->where('p.projectId = c.projectId')
            ->where(function (Query $query) use ($uid) {
                if ($this->isAdmin($uid) !== true) {
                    $query->where('c.clientId', $uid);
                }
            })
            ->group('p.projectId')
            ->order('p.updateTime', 'desc')
            ->select();
        $this->assign('projects', $projects);
        $uid = cmf_get_current_admin_id();
        $contracts = Db::name('contract c, pm_user u, pm_type t')
            ->where('t.id=c.clientType')
            ->where('u.id = c.clientId')
            ->where(function (Query $query) use ($uid) {
                if ($this->isAdmin($uid) !== true) {
                    $query->where('u.id', $uid);
                }
            })
            ->order('updateTime', 'desc')
            ->select();
        $this->assign('contracts', $contracts);
        $reportTypes = Db::name('reportType')->select();
        $this->assign("reportTypes", $reportTypes);
        return $this->fetch();
    }
    public function pics()
    {
        $uid = cmf_get_current_admin_id();
        $projectId = $this->request->param('projectId');
        $pics = Db::name('contract c, pm_project p, pm_user u, pm_pics r')
            ->where('r.contractId = c.contractId')
            ->where('c.projectId = p.projectId')
            ->where('u.id = c.clientId')
            ->where(function (Query $query) use ($projectId, $uid) {
                if ($projectId != null) {
                    $query->where('p.projectId', $projectId);
                }
                if ($this->isAdmin($uid) !== true) {
                    $query->where('c.clientId', $uid);
                }
            })
            ->order('picTime', 'desc')
            ->select();
        $this->assign('pics', $pics);
        return $this->fetch();
    }
    public function addPic()
    {
        $uid = cmf_get_current_admin_id();
        $projects = Db::name('contract c, pm_project p')
            ->where('p.projectId = c.projectId')
            ->where(function (Query $query) use ($uid) {
                if ($this->isAdmin($uid) !== true) {
                    $query->where('c.clientId', $uid);
                }
            })
            ->group('p.projectId')
            ->order('p.updateTime', 'desc')
            ->select();
        $this->assign('projects', $projects);
        $contracts = Db::name('contract c, pm_user u, pm_type t')
            ->where('t.id=c.clientType')
            ->where('u.id = c.clientId')
            ->where(function (Query $query) use ($uid) {
                if ($this->isAdmin($uid) !== true) {
                    $query->where('c.clientId', $uid);
                }
            })
            ->order('updateTime', 'desc')->select();
        $this->assign('contracts', $contracts);
        return $this->fetch();
    }
    public function postReportAdd()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $report = [
                'reportId' => uniqid(),
                'reportName' => $request["reportName"],
                'reportTypeId' => $request["reportTypeId"],
                'contractId' => $request["contractId"],
                'reportTime' => $request["reportTime"],
            ];
            if (array_key_exists('file_urls', $request) && array_key_exists('file_names', $request)) {
                $report['file_urls'] = $request["file_urls"];
                $report['file_names'] = $request["file_names"];
            }
            $res = Db::name('report')->insert($report);
            if ($res === false) {
                $this->error("Error while adding a report.");
            } else {
                $this->success("成功增加", url('report/reports'));
            }
        } else {
            $this->error("非法访问");
        }
    }
    public function postPicAdd()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $pic = [
                'picId' => uniqid(),
                'picName' => $request["picName"],
                'contractId' => $request["contractId"],
                'picTime' => $request["picTime"],
            ];
            if (array_key_exists('file_urls', $request) && array_key_exists('file_names', $request)) {
                $pic['file_urls'] = $request["file_urls"];
                $pic['file_names'] = $request["file_names"];
            }
            $res = Db::name('pics')->insert($pic);
            if ($res === false) {
                $this->error("Error while adding a pic.");
            } else {
                $this->success("成功增加", url('report/pics'));
            }
        } else {
            $this->error("非法访问");
        }
    }
    public function postPicUpdate()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $pic = [
                'contractId' => $request["contractId"],
                'picTime' => $request["picTime"],
                'picName' => $request["picName"],
            ];
            if (array_key_exists('file_urls', $request) && array_key_exists('file_names', $request)) {
                $pic['file_urls'] = $request["file_urls"];
                $pic['file_names'] = $request["file_names"];
            } else {
                $pic['file_urls'] = null;
                $pic['file_names'] = null;
            }
            $res = Db::name('pics')->where('picId', $request["picId"])->update($pic);
            if ($res === false) {
                $this->error("Error while updating the pic.");
            } else {
                $this->success("已更新", url('report/pics'));
            }
        } else {
            $this->error("非法访问");
        }
    }
    public function postReportUpdate()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $report = [
                'reportTypeId' => $request["reportTypeId"],
                'contractId' => $request["contractId"],
                'reportTime' => $request["reportTime"],
                'reportName' => $request["reportName"],
            ];
            if (array_key_exists('file_urls', $request) && array_key_exists('file_names', $request)) {
                $report['file_urls'] = $request["file_urls"];
                $report['file_names'] = $request["file_names"];
            } else {
                $report['file_urls'] = null;
                $report['file_names'] = null;
            }
            $res = Db::name('report')->where('reportId', $request["reportId"])->update($report);
            if ($res === false) {
                $this->error("Error while updating the report.");
            } else {
                $this->success("已更新", url('report/reports'));
            }
        } else {
            $this->error("非法访问");
        }
    }
    public function deleteReport()
    {
        $reportId = $this->request->param('reportId');
        $res = Db::name('report')->where('reportId', $reportId)->delete();
        if ($res > 0) {
            $this->success("已删除");
        } else {
            $this->error("出现了错误");
        }
    }
    public function deletePic()
    {
        $picId = $this->request->param('picId');
        $res = Db::name('pics')->where('picId', $picId)->delete();
        if ($res > 0) {
            $this->success("已删除");
        } else {
            $this->error("出现了错误");
        }
    }
    public function listReportFiles()
    {
        $rid = $this->request->param("reportId");
        $all = Db::name('report')->where('reportId', $rid)->find();
        $urls = json_decode($all["file_urls"]);
        $names = json_decode($all["file_names"]);
        if ($urls != null and $names != null) {
            $files = array_combine($urls, $names);
            $this->assign("files", $files);
        }
        return $this->fetch();
    }
    public function listPicFiles()
    {
        $rid = $this->request->param("picId");
        $all = Db::name('pics')->where('picId', $rid)->find();
        $urls = json_decode($all["file_urls"]);
        $names = json_decode($all["file_names"]);
        if ($urls != null and $names != null) {
            $files = array_combine($urls, $names);
            $this->assign("files", $files);
        }
        return $this->fetch();
    }
}