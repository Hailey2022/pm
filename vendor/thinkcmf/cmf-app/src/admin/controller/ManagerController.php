<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ManagerController extends AdminBaseController
{
    public function index()
    {
        return $this->fetch();
    }

    public function addProject()
    {
        $users = Db::name('client')->where("status",0)->select();
        $this->assign("users", $users);
        $types = Db::name('type')->select();
        $this->assign("types", $types);
        return $this->fetch();
    }

    public function getProjectIdFromContractId($contractId = null)
    {
        if ($contractId != null) {
            $contract = Db::name("contract")->where("contractId", $contractId)->find();
            if ($contract != null) {
                return $contract["projectId"];
            }
        }
        return null;
    }

    public function getContractNameByContractId($contractId = null)
    {
        if ($contractId != null) {
            $contract = Db::name("contract")->where("contractId", $contractId)->find();
            if ($contract != null) {
                return $contract["contractName"];
            }
        }
        return null;
    }

    public function getProjectNameFromProjectId($projectId = null)
    {
        if ($projectId != null) {
            $projectId = Db::name("project")->where("projectId", $projectId)->find();
            if ($projectId != null) {
                return $projectId["projectName"];
            }
        }
        return null;
    }

    public function getUsernameByUserId($userId = null)
    {
        $usersInfo = Db::name("client")->where("id", $userId)->find();
        if ($usersInfo === null) {
            return null;
        }
        return $usersInfo["user_login"];
    }

    public function calculateProjectAmount($projectId = null)
    {
        if ($projectId != null) {
            $contract = Db::name("contract")->where("projectId", $projectId)->select();
            if (count($contract) <= 0) {
                $this->error("找不到相应项目合同");
            } else {
                $total = 0;
                $paid = 0;
                foreach ($contract as $c) {
                    $t = $c["contractAmount"];
                    if ($t != null) {
                        $total += $t;
                    }
                    $p = $c["paid"];
                    if ($t != null) {
                        $paid += $p;
                    }
                }

                Db::name('project')->where('projectId', $projectId)->update(['total' => $total, 'paid' => $paid]);
            }
        }
    }

    public function calculateContractAmount($contractId = null)
    {
        if ($contractId != null) {
            $payment = Db::name("payment")->where("contractId", $contractId)->select();
            if (count($payment) <= 0) {
                $this->error("找不到相应合同支付");
            } else {
                $paid = 0;
                foreach ($payment as $p) {
                    $t = $p["total"];
                    if ($t != null) {
                        $paid += $t;
                    }
                }
                Db::name('contract')->where('contractId', $contractId)->update(['paid' => $paid]);
                $this->calculateProjectAmount($this->getProjectIdFromContractId($contractId));
            }
        }
    }

    public function viewContracts()
    {
        $cid = $this->request->param("contractId");
        $all = Db::name('contract')->where('contractId', $cid)->find();
        $this->assign("data", $all);
        $urls = json_decode($all["file_urls"]);
        $names = json_decode($all["file_names"]);
        if ($urls != null and $names != null) {
            $files = array_combine($urls, $names);
            $this->assign("files", $files);
        }
        return $this->fetch();
    }

    public function view()
    {
        $all = Db::name('project')->select();
        $this->assign("data", $all);
        return $this->fetch();
    }

    public function listContracts()
    {
        $all = Db::name('project p, pm_type t, pm_contract c, pm_client u')->where("c.projectId=p.projectId")->where("c.clientId=u.id")->where("c.clientType=t.id")->select();
        $this->assign("data", $all);
        return $this->fetch();
    }

    public function listContract()
    {
        $projectId = $this->request->param("projectId");
        $all = Db::name('contract c, pm_project p, pm_type t, pm_client u')->where("c.projectId=p.projectId")->where("c.clientType=t.id")->where("c.clientId=u.id")->where("c.projectId", $projectId)->select();
        $this->assign("data", $all);
        $this->assign("projectName", $this->getProjectNameFromProjectId($projectId));
        return $this->fetch();
    }

    public function updateContract()
    {
        $cid = $this->request->param("contractId");
        $all = Db::name('contract c, pm_client u')->where("c.clientId=u.id")->where('c.contractId', $cid)->find();
        $this->assign("data", $all);
        $urls = json_decode($all["file_urls"]);
        $names = json_decode($all["file_names"]);
        if ($urls != null and $names != null) {
            $files = array_combine($urls, $names);
            $this->assign("files", $files);
        }
        return $this->fetch();
    }

    public function postContractUpdate()
    {
        $all = $this->request->param();
        $res = Db::name('contract')->where('contractId', $all["contractId"])->update($all);
        if ($res !== false) {
            $this->calculateProjectAmount($this->getProjectIdFromContractId($all["contractId"]));
            $this->success("更新成功！", url('manager/listContracts'));
        } else {
            $this->error("出错了！");
        }
    }

    public function postProjectAdd()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $data = [
                'projectName' => $request['project-name'],
                'projectId' => uniqid(),
                'total' => "0",
                'paid' => "0"
            ];
            $contractsCount = count($request["clientId"]);
            for ($i = 0; $i < $contractsCount; $i++) {
                $contract = [
                    'contractId' => uniqid(),
                    'contractAmount' => "0",
                    'clientId' => $request["clientId"][$i],
                    'clientAlias' => $request["clientAlias"][$i],
                    'projectId' => $data["projectId"],
                    'clientType' => $request["clientType"][$i],
                ];
                $res = Db::name('contract')->insert($contract);
                if ($res === false) {
                    $this->error("Error while adding contracts...");
                }
            }
            $res = Db::name('project')->insert($data);
            if ($res !== false) {
                $this->success("保存成功！", url('manager/view'));
            } else {
                $this->error("保存时出错！");
            }
        }
    }

    public function pay()
    {
        $contractId = $this->request->param("contractId");
        $data = Db::name("contract")->where("contractId", $contractId)->find();
        $this->assign("data", $data);
        return $this->fetch();
    }

    public function postPaymentAdd()
    {
        $request = $this->request->param();
        if ($this->request->isPost()) {
            $request = $this->request->param();
            if (array_key_exists('file_urls', $request) && array_key_exists('file_names', $request)) {
                $data = [
                    'paymentId' => uniqid(),
                    'comment' => $request['comment'],
                    'contractId' => $request['contractId'],
                    'installment' => $request['installment'],
                    'ccp' => $request['ccp'],
                    'provice' => $request['provice'],
                    'city' => $request['city'],
                    'bond' => $request['bond'],
                    'budget' => $request['budget'],
                    'others' => $request['others'],
                    'file_urls' => $request['file_urls'],
                    'file_names' => $request['file_names'],
                    'total' => $request['provice'] + $request['city'] + $request['bond'] + $request['budget'] + $request['others']
                ];
            } else {
                $data = [
                    'paymentId' => uniqid(),
                    'comment' => $request['comment'],
                    'contractId' => $request['contractId'],
                    'installment' => $request['installment'],
                    'ccp' => $request['ccp'],
                    'provice' => $request['provice'],
                    'city' => $request['city'],
                    'bond' => $request['bond'],
                    'budget' => $request['budget'],
                    'others' => $request['others'],
                    'total' => $request['provice'] + $request['city'] + $request['bond'] + $request['budget'] + $request['others']
                ];
            }
            $res = Db::name('payment')->insert($data);
            if ($res !== false) {
                $this->calculateContractAmount($request['contractId']);
                $this->success("保存成功！", url('manager/listPayments'));
            } else {
                $this->error("保存时出错！");
            }
        }
        $this->error("非法支付请求");
    }

    public function postPaymentUpdate()
    {
        $request = $this->request->param();
        if ($this->request->isPost()) {
            $request = $this->request->param();
            if (array_key_exists('file_urls', $request) && array_key_exists('file_names', $request)) {
                $data = [
                    'comment' => $request['comment'],
                    'installment' => $request['installment'],
                    'ccp' => $request['ccp'],
                    'provice' => $request['provice'],
                    'city' => $request['city'],
                    'bond' => $request['bond'],
                    'budget' => $request['budget'],
                    'others' => $request['others'],
                    'file_urls' => $request['file_urls'],
                    'file_names' => $request['file_names'],
                    'total' => $request['provice'] + $request['city'] + $request['bond'] + $request['budget'] + $request['others']
                ];
            } else {
                $data = [
                    'comment' => $request['comment'],
                    'installment' => $request['installment'],
                    'ccp' => $request['ccp'],
                    'provice' => $request['provice'],
                    'city' => $request['city'],
                    'bond' => $request['bond'],
                    'budget' => $request['budget'],
                    'others' => $request['others'],
                    'total' => $request['ccp'] + $request['provice'] + $request['city'] + $request['bond'] + $request['budget'] + $request['others']
                ];
            }
            $res = Db::name('payment')->where("paymentId", $request['paymentId'])->update($data);
            if ($res !== false) {
                $this->calculateContractAmount($request['contractId']);
                $this->success("保存成功！", url('manager/listPayments'));
            } else {
                $this->error("保存时出错！");
            }
        }
        $this->error("非法支付请求");
    }

    public function listPayments()
    {
        $data = Db::name("payment p, pm_contract c")->where("p.contractId=c.contractId")->select();
        $this->assign("data", $data);
        return $this->fetch();
    }

    public function updatePayment()
    {
        $paymentId = $this->request->param("paymentId");
        $all = Db::name("contract c, pm_payment p")->where("p.contractId=c.contractId")->where("p.paymentId", $paymentId)->find();
        $this->assign("data", $all);
        $urls = json_decode($all["file_urls"]);
        $names = json_decode($all["file_names"]);
        if ($urls != null and $names != null) {
            $files = array_combine($urls, $names);
            $this->assign("files", $files);
        }
        return $this->fetch();
    }

    public function listPayment()
    {
        $request = $this->request->param();
        $data = Db::name("payment p, pm_contract c")->where("p.contractId=c.contractId")->where("p.contractId", $request["contractId"])->select();
        $this->assign("data", $data);
        $this->assign("contractName", $this->getContractNameByContractId($request["contractId"]));
        return $this->fetch();
    }

    public function viewPaymentFiles()
    {
        $cid = $this->request->param("paymentId");
        $all = Db::name('payment')->where('paymentId', $cid)->find();
        $this->assign("data", $all);
        $urls = json_decode($all["file_urls"]);
        $names = json_decode($all["file_names"]);
        if ($urls != null and $names != null) {
            $files = array_combine($urls, $names);
            $this->assign("files", $files);
        }
        return $this->fetch();
    }

    public function deleteProject()
    {
        $projectId = $this->request->param("projectId");
        $contracts = Db::name("contract")->where("projectId", $projectId)->select();
        foreach ($contracts as $contract) {
            $contractId = $contract["contractId"];
            $paymentCount = Db::name("payment")->where("contractId", $contractId)->delete();
        }
        $contractCount = Db::name("contract")->where("projectId", $projectId)->delete();
        $projectCount = Db::name("project")->where("projectId", $projectId)->delete();
        if ($projectCount > 0) {
            $this->success("成功删除了{$contractCount}个合同，{$paymentCount}个支付记录");
        }
    }

    public function client()
    {
        $clients = Db::name("client")->where("status",0)->select();
        $this->assign("clients", $clients);
        return $this->fetch();
    }

    public function postClientAdd()
    {
        $user_login = $this->request->param('user_login');
        Db::name("client")->insert(['user_login' => $user_login, 'status' => 0]);
    }

    public function postClientUpdate()
    {
        $user_login = $this->request->param('user_login');
        $id = $this->request->param('id');
        Db::name("client")->where("id", $id)->update(['user_login' => $user_login]);
    }

    public function postClientDelete()
    {
        $id = $this->request->param('id');
        Db::name("client")->where("id", $id)->update(['status' => 1]);
    }

}