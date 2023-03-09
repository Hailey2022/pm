<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ManagerController extends AdminBaseController
{
    public function ip()
    {
        return $this->request->ip();
    }
    public function checkProject($projectId = null)
    {
        $project = Db::name('project')->where('projectId', $projectId)->find();
        return $project !== null;
    }
    public function addProject()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $users = Db::name('user')
            ->where('id', '<>', '1')
            ->where('id', '<>', '9')
            ->order('user_login')
            ->select();
        $this->assign("users", $users);
        $types = Db::name('type')->select();
        $this->assign("types", $types);
        $tenderingMethods = Db::name('tenderingmethod')->select();
        $this->assign("tenderingMethods", $tenderingMethods);
        $theProjectStatus = Db::name('implementationPhase')->select();
        $this->assign("theProjectStatus", $theProjectStatus);
        return $this->fetch();
    }

    public function getProjectIdByContractId($contractId = null)
    {
        if ($contractId != null) {
            $contract = Db::name("contract")
                ->where("contractId", $contractId)
                ->find();
            if ($contract != null) {
                return $contract["projectId"];
            }
        }
        return null;
    }
    public function getClientIdByClientName($clientName = null, $type = null)
    {
        if ($clientName != null) {
            $res = Db::name('user')->where('user_login', $clientName)->find();
            if ($res == null) {
                $id = Db::name('user')
                    ->insertGetId([
                        'user_login' => $clientName,
                        'user_type' => 1,
                        'sex' => 0,
                        'birthday' => 0,
                        'last_login_time' => 0,
                        'score' => 0,
                        'coin' => 0,
                        'balance' => 0,
                        'create_time' => 0,
                        'user_status' => 1,
                        'user_pass' => '###c8cbdeb1f2df0a3731012f17c74f4a96',
                        'user_nickname' => '',
                        'user_email' => '',
                        'user_url' => '',
                        'avatar' => '',
                        'signature' => '',
                        'last_login_ip' => '',
                        'user_activation_key' => '',
                        'mobile' => ''
                    ]);
                $role = 3;
                if ($type != null) {
                    $role = Db::name('type')->where('id', $type)->find();
                    if ($role == null) {
                        $role = 3;
                    } else {
                        $role = $role['role'];
                    }
                }
                Db::name('role_user')->insert(['role_id' => $role, 'user_id' => $id]);
                return $id;
            } else {
                return $res['id'];
            }
        } else {
            return 0;
        }
    }
    public function getContractNameByContractId($contractId = null)
    {
        if ($contractId != null) {
            $contract = Db::name("contract")
                ->where("contractId", $contractId)
                ->find();
            if ($contract != null) {
                return $contract["contractName"];
            }
        }
        return null;
    }
    public function getContractIdByPaymentId($paymentId = null)
    {
        if ($paymentId != null) {
            $payment = Db::name("payment")
                ->where("paymentId", $paymentId)
                ->find();
            if ($payment != null) {
                return $payment["contractId"];
            }
        }
        return null;
    }
    public function getProjectIdByPaymentId($paymentId = null)
    {
        return $this->getProjectIdByContractId($this->getContractIdByPaymentId($paymentId));
    }
    public function getProjectNameByProjectId($projectId = null)
    {
        if ($projectId != null) {
            $projectId = Db::name("project")
                ->where("projectId", $projectId)
                ->find();
            if ($projectId != null) {
                return $projectId["projectName"];
            }
        }
        return null;
    }
    public function getApproximatePriceByProjectId($projectId = null)
    {
        $result = "未填写";
        if ($projectId != null) {
            $projectId = Db::name("project")
                ->where("projectId", $projectId)
                ->find();
            if ($projectId != null && $projectId["approximatePrice"] != null) {
                $result = $projectId["approximatePrice"];
            }
        }
        return $result;
    }
    public function getUsernameByUserId($userId = null)
    {
        $usersInfo = Db::name("user")
            ->where("id", $userId)
            ->find();
        if ($usersInfo === null) {
            return null;
        }
        return $usersInfo["user_login"];
    }
    public function calculateProjectAmount($projectId = null)
    {
        if ($projectId != null) {
            $contract = Db::name("contract")
                ->where("projectId", $projectId)
                ->select();
            $total = 0;
            $paid = 0;
            $ccpProjectSum = 0;
            $provinceProjectSum = 0;
            $cityProjectSum = 0;
            $bondProjectSum = 0;
            $budgetProjectSum = 0;
            $othersProjectSum = 0;
            foreach ($contract as $c) {
                $t = $c["contractAmount"];
                if ($t != null) {
                    $total += $t;
                }
                $t = $c["paid"];
                if ($t != null) {
                    $paid += $t;
                }
                $t = $c["ccpSum"];
                if ($t != null) {
                    $ccpProjectSum += $t;
                }
                $t = $c["provinceSum"];
                if ($t != null) {
                    $provinceProjectSum += $t;
                }
                $t = $c["citySum"];
                if ($t != null) {
                    $cityProjectSum += $t;
                }
                $t = $c["bondSum"];
                if ($t != null) {
                    $bondProjectSum += $t;
                }
                $t = $c["budgetSum"];
                if ($t != null) {
                    $budgetProjectSum += $t;
                }
                $t = $c["othersSum"];
                if ($t != null) {
                    $othersProjectSum += $t;
                }
            }
            Db::name('project')
                ->where('projectId', $projectId)
                ->update([
                    'total' => $total,
                    'paid' => $paid,
                    'ccpProjectSum' => $ccpProjectSum,
                    'provinceProjectSum' => $provinceProjectSum,
                    'cityProjectSum' => $cityProjectSum,
                    'bondProjectSum' => $bondProjectSum,
                    'budgetProjectSum' => $budgetProjectSum,
                    'othersProjectSum' => $othersProjectSum
                ]);
        }
    }
    public function calculateContractAmount($contractId = null)
    {
        if ($contractId != null) {
            $payment = Db::name("payment")
                ->where("contractId", $contractId)
                ->select();
            $paid = 0;
            $ccp = 0;
            $province = 0;
            $city = 0;
            $bond = 0;
            $budget = 0;
            $others = 0;
            foreach ($payment as $p) {
                $t = $p["total"];
                if ($t != null) {
                    $paid += $t;
                }
                $t = $p["ccp"];
                if ($t != null) {
                    $ccp += $t;
                }
                $t = $p["province"];
                if ($t != null) {
                    $province += $t;
                }
                $t = $p["city"];
                if ($t != null) {
                    $city += $t;
                }
                $t = $p["bond"];
                if ($t != null) {
                    $bond += $t;
                }
                $t = $p["budget"];
                if ($t != null) {
                    $budget += $t;
                }
                $t = $p["others"];
                if ($t != null) {
                    $others += $t;
                }
            }
            Db::name('contract')
                ->where('contractId', $contractId)
                ->update([
                    'paid' => $paid,
                    'ccpSum' => $ccp,
                    'provinceSum' => $province,
                    'citySum' => $city,
                    'bondSum' => $bond,
                    'budgetSum' => $budget,
                    'othersSum' => $others
                ]);
            $this->calculateProjectAmount($this->getProjectIdByContractId($contractId));
        }
    }
    public function viewContracts()
    {
        $cid = $this->request->param("contractId");
        $all = Db::name('contract')
            ->where('contractId', $cid)
            ->find();
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
        $all = Db::name('project p, pm_tenderingmethod t, pm_implementation_phase i')
            ->where('p.tenderingMethod = t.id')
            ->where('p.projectStatusId = i.id')
            ->order('createTime', 'desc')
            ->select();
        $this->assign("data", $all);
        return $this->fetch();
    }
    public function listContract()
    {
        $projectId = $this->request->param("projectId");
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign('projectId', $projectId);
        $all = Db::name('user u, pm_project p, pm_type t, pm_contract c')
            ->where("c.projectId=p.projectId")
            ->where("c.clientType=t.id")
            ->where("c.clientId=u.id")
            ->where("c.projectId", $projectId)
            ->select();
        $this->assign("data", $all);
        return $this->fetch();
    }
    public function updateContract()
    {
        $projectId = $this->request->param("projectId");
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign('projectId', $projectId);
        $cid = $this->request->param("contractId");
        $projectName = $this->getProjectNameByProjectId($this->getProjectIdByContractId($cid));
        $this->assign("projectName", $projectName);
        $all = Db::name('contract c, pm_user u')
            ->where("c.clientId=u.id")
            ->where('c.contractId', $cid)
            ->find();
        for ($x = 1; $x <= 5; $x++) {
            $names = json_decode($all['file_name_' . $x]);
            $urls = json_decode($all['file_url_' . $x]);
            if ($urls != null and $names != null) {
                $files = array_combine($urls, $names);
                $this->assign('file_' . $x, $files);
            }
        }
        $this->assign("data", $all);
        return $this->fetch();
    }
    public function postContractUpdate()
    {
        $request = $this->request->param();
        for ($i = 1; $i <= 5; $i++) {
            if (!(array_key_exists("file_name_" . $i, $request) && array_key_exists("file_url_" . $i, $request))) {
                $request["file_name_" . $i] = "null";
                $request["file_url_" . $i] = "null";
            }
        }
        $res = Db::name('contract')->where('contractId', $request["contractId"])->update($request);
        if ($res !== false) {
            $this->calculateProjectAmount($this->getProjectIdByContractId($request["contractId"]));
            $this->success("更新成功！", url('manager/listContract', ["projectId" => $this->getProjectIdByContractId($request["contractId"])]));
        } else {
            $this->error("出错了！");
        }
    }
    public function postProjectAdd()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $projectName = $request['project-name'];
            $projects = Db::name("project")->select();
            foreach ($projects as $project) {
                if (array_key_exists('projectName', $project)) {
                    if ($project['projectName'] == $projectName) {
                        $this->error("已有相同工程，请修改工程名");
                    }
                }
            }
            $data = [
                'projectName' => $projectName,
                'projectId' => uniqid(),
                'total' => "0",
                'paid' => "0",
                'approximatePrice' => $request['approximatePrice'],
                'constructionCompany' => $request['constructionCompany'],
                'constructionYear' => $request['constructionYear'],
                'estimatedPrice' => $request['estimatedPrice'],
                'reportedBudget' => $request['reportedBudget'],
                'approvedBudget' => $request['approvedBudget'],
                'tenderingMethod' => $request['tenderingMethod'],
                'projectStatusId' => $request['projectStatusId'],
                'fee1' => $request['fee1'],
                'fee2' => $request['fee2'],
                'fee3' => $request['fee3'],
                'fee4' => $request['fee4'],
                'fee5' => $request['fee5'],
                'file_date_2' => $request['file_date_2'],
                'file_date_4' => $request['file_date_4'],
                'file_date_9' => $request['file_date_9'],
            ];
            for ($i = 1; $i <= 21; $i++) {
                if (array_key_exists("file_name_" . $i, $request) && array_key_exists("file_url_" . $i, $request)) {
                    $data["file_name_" . $i] = $request["file_name_" . $i];
                    $data["file_url_" . $i] = $request["file_url_" . $i];
                }
            }
            // $contractsCount = count($request["clientName"]);
            // for ($i = 0; $i < $contractsCount; $i++) {
            //     $contract = [
            //         'contractId' => uniqid(),
            //         'clientId' => $this->getClientIdByClientName($request["clientName"][$i]),
            //         'clientAlias' => $request["clientAlias"][$i],
            //         'projectId' => $data["projectId"],
            //         'clientType' => $request["clientType"][$i],
            //         'contractName' => $request["contractName"][$i],
            //         'contractAmount' => $request["contractAmount"][$i] === "" ? 0 : $request["contractAmount"][$i],
            //         'contractTime' => $request["contractTime"][$i],
            //         'contractNumber' => $request["contractNumber"][$i],
            //         'paid' => 0
            //     ];
            //     if ($request["uploadfiles"][$i] != "" && $request["uploadfilenames"][$i] != "") {
            //         $urls = explode('|', $request["uploadfiles"][$i]);
            //         $names = explode('|', $request["uploadfilenames"][$i]);
            //         $contract['file_urls'] = $urls;
            //         $contract['file_names'] = $names;
            //     }
            //     $res = Db::name('contract')->insert($contract);
            //     if ($res === false) {
            //         $this->error("Error while adding contracts...");
            //     }
            // }
            $res = Db::name('project')->insert($data);
            if ($res !== false) {
                // $this->calculateProjectAmount($data['projectId']);
                $this->success("保存成功！", url('manager/listProjectInfo', ["projectId" => $data["projectId"]]));
            } else {
                $this->error("保存时出错！");
            }
        } else {
            $this->error("非法访问");
        }
    }
    public function pay()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $projectName = $this->getProjectNameByProjectId($projectId);
        if ($projectName === null) {
            $this->error('非法访问..');
        } else {
            $this->assign("projectName", $projectName);
        }
        $contracts = Db::name('user u, pm_contract c')
            ->where('u.id = c.clientId')
            ->where('projectId', $projectId)
            ->select();
        if (count($contracts) == 0) {
            $this->error('先录入合同再录入支付');
        }
        $this->assign('contracts', $contracts);
        // $contractId = $this->request->param("contractId");
        // $data = Db::name("contract")
        //     ->where("contractId", $contractId)
        //     ->find();
        // $this->assign("data", $data);
        return $this->fetch();
    }
    public function postPaymentAdd()
    {
        $request = $this->request->param();
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $data = [
                'paymentId' => uniqid(),
                'comment' => $request['comment'],
                'contractId' => $request['contractId'],
                'installment' => $request['installment'],
                'ccp' => $request['ccp'],
                'province' => $request['province'],
                'city' => $request['city'],
                'bond' => $request['bond'],
                'budget' => $request['budget'],
                'others' => $request['others'],
                'total' => $request['province'] + $request['city'] + $request['bond'] + $request['budget'] + $request['others']
            ];
            if (array_key_exists('file_url_1', $request) && array_key_exists('file_name_1', $request)) {
                $data['file_url_1'] = $request['file_url_1'];
                $data['file_name_1'] = $request['file_name_1'];
            }
            if (array_key_exists('file_url_2', $request) && array_key_exists('file_name_2', $request)) {
                $data['file_url_2'] = $request['file_url_2'];
                $data['file_name_2'] = $request['file_name_2'];
            }
            $res = Db::name('payment')->insert($data);
            if ($res !== false) {
                $this->calculateContractAmount($request['contractId']);
                $this->success("保存成功！", url('manager/listProjectPayments', ["projectId" => $this->getProjectIdByContractId($request['contractId'])]));
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
            $data = [
                'comment' => $request['comment'],
                'installment' => $request['installment'],
                'ccp' => $request['ccp'],
                'province' => $request['province'],
                'city' => $request['city'],
                'bond' => $request['bond'],
                'budget' => $request['budget'],
                'others' => $request['others'],
                'total' => $request['ccp'] + $request['province'] + $request['city'] + $request['bond'] + $request['budget'] + $request['others']
            ];
            if (array_key_exists('file_urls', $request) && array_key_exists('file_names', $request)) {
                $data['file_urls'] = $request['file_urls'];
                $data['file_names'] = $request['file_names'];
            } else {
                $data['file_urls'] = null;
                $data['file_names'] = null;
            }
            if (array_key_exists('file1_urls', $request) && array_key_exists('file1_names', $request)) {
                $data['file1_urls'] = $request['file1_urls'];
                $data['file1_names'] = $request['file1_names'];
            } else {
                $data['file1_urls'] = null;
                $data['file1_names'] = null;
            }
            $res = Db::name('payment')->where("paymentId", $request['paymentId'])->update($data);
            if ($res !== false) {
                $this->calculateContractAmount($request['contractId']);
                $this->success("保存成功！", url('manager/listProjectPayments', ["projectId" => $this->getProjectIdByPaymentId($request['paymentId'])]));
            } else {
                $this->error("保存时出错！");
            }
        }
        $this->error("非法支付请求");
    }
    public function listPayments()
    {
        $data = Db::name("payment p, pm_contract c, pm_project")
            ->where("p.contractId=c.contractId")
            ->where("pm_project.projectId=c.projectId")
            ->select();
        $this->assign("data", $data);
        return $this->fetch();
    }
    public function updatePayment()
    {
        $projectId = $this->request->param("projectId");
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign('projectId', $projectId);
        $projectName = $this->getProjectNameByProjectId($projectId);
        $this->assign('projectName', $projectName);
        $paymentId = $this->request->param("paymentId");
        $all = Db::name("contract c, pm_payment p, pm_user u")
            ->where("p.contractId=c.contractId")
            ->where("c.clientId=u.id")
            ->where("p.paymentId", $paymentId)
            ->find();
        $this->assign("data", $all);
        for ($x = 1; $x <= 2; $x++) {
            $names = json_decode($all['file_name_' . $x]);
            $urls = json_decode($all['file_url_' . $x]);
            if ($urls != null and $names != null) {
                $files = array_combine($urls, $names);
                $this->assign('file_' . $x, $files);
            }
        }
        return $this->fetch();
    }
    public function listPayment()
    {
        $request = $this->request->param();
        $data = Db::name("payment p, pm_contract c")
            ->where("p.contractId=c.contractId")
            ->where("p.contractId", $request["contractId"])
            ->select();
        $this->assign("data", $data);
        $this->assign("contractName", $this->getContractNameByContractId($request["contractId"]));
        return $this->fetch();
    }
    public function viewPaymentFiles()
    {
        $cid = $this->request->param("paymentId");
        $all = Db::name('payment')
            ->where('paymentId', $cid)
            ->find();
        $this->assign("data", $all);
        if ($this->request->param("type") == 1) {
            $urls = json_decode($all["file1_urls"]);
            $names = json_decode($all["file1_names"]);
            if ($urls != null and $names != null) {
                $files = array_combine($urls, $names);
                $this->assign("files", $files);
            }
        } else {
            $urls = json_decode($all["file_urls"]);
            $names = json_decode($all["file_names"]);
            if ($urls != null and $names != null) {
                $files = array_combine($urls, $names);
                $this->assign("files", $files);
            }
        }
        return $this->fetch();
    }
    public function deleteProject()
    {
        $projectId = $this->request->param("projectId");
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $contracts = Db::name("contract")
            ->where("projectId", $projectId)
            ->select();
        $paymentCount = 0;
        foreach ($contracts as $contract) {
            $contractId = $contract["contractId"];
            $paymentCount = Db::name("payment")
                ->where("contractId", $contractId)
                ->delete();
        }
        $contractCount = Db::name("contract")
            ->where("projectId", $projectId)
            ->delete();
        $projectCount = Db::name("project")
            ->where("projectId", $projectId)
            ->delete();
        if ($projectCount > 0) {
            $this->success("成功删除了{$contractCount}个合同，{$paymentCount}个支付记录");
        }
    }
    public function postClientAdd()
    {
        $user_login = $this->request->param('user_login');
        $clients = Db::name("user")->select();
        foreach ($clients as $client) {
            if ($client['user_login'] == $user_login) {
                $this->error("服务商已存在");
            }
        }
        Db::name("user")->insert(['user_login' => $user_login]);
    }
    public function postPaymentDelete()
    {
        $paymentId = $this->request->param("paymentId");
        $contractId = $this->getContractIdByPaymentId($paymentId);
        $res = Db::name("payment")
            ->where("paymentId", $paymentId)
            ->delete();
        if ($res > 0) {
            $this->calculateContractAmount($contractId);
            $this->success("已删除");
        }
        $this->error("出错了");
    }
    public function listProjectPayments()
    {
        $projectId = $this->request->param("projectId");
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $data = Db::name("contract c, pm_payment p")
            ->where("p.contractId=c.contractId")
            ->where("projectId", $projectId)->select();
        $this->assign("data", $data);
        $this->assign("projectName", $this->getProjectNameByProjectId($projectId));
        $this->assign("projectId", $projectId);
        $contracts = Db::name("project p, pm_contract c, pm_user u")
            ->where("c.clientId=u.id")
            ->where("p.projectId=c.projectId")
            ->where("c.projectId", $projectId)
            ->select();
        $this->assign("contracts", $contracts);
        $project = Db::name("project")
            ->where("projectId", $projectId)
            ->find();
        $this->assign("project", $project);
        return $this->fetch();
    }
    public function addContract()
    {
        $projectId = $this->request->param("projectId");
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $this->assign("projectName", $this->getProjectNameByProjectId($projectId));
        $users = Db::name('user')
            ->where('id', '<>', '1')
            ->where('id', '<>', '9')
            ->order('user_login')
            ->select();
        $this->assign("users", $users);
        $types = Db::name('type')->select();
        $this->assign("types", $types);
        return $this->fetch();
    }
    public function postContractAdd()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $contract = [
                'contractId' => uniqid(),
                'clientId' => $this->getClientIdByClientName($request["clientName"], $request["clientType"]),
                // 'clientAlias' => $request["clientAlias"],
                'projectId' => $request["projectId"],
                'clientType' => $request["clientType"],
                'contractName' => $request["contractName"],
                'contractAmount' => $request["contractAmount"] === "" ? 0 : $request["contractAmount"],
                'contractTime' => $request["contractTime"],
                'contractExpTime' => $request["contractExpTime"],
                'contractNumber' => $request["contractNumber"],
                'paid' => 0,
                'paymentTerms' => $request["paymentTerms"],
                'firstParty' => $request["firstParty"],
                'secondParty' => $request["secondParty"],
                'managerA' => $request["managerA"],
                'managerB' => $request["managerB"],
            ];
            for ($i = 1; $i <= 5; $i++) {
                if (array_key_exists("file_name_" . $i, $request) && array_key_exists("file_url_" . $i, $request)) {
                    $contract["file_name_" . $i] = $request["file_name_" . $i];
                    $contract["file_url_" . $i] = $request["file_url_" . $i];
                }
            }
            $res = Db::name('contract')->insert($contract);
            if ($res === false) {
                $this->error("Error while adding contracts.");
            } else {
                $this->calculateProjectAmount($this->getProjectIdByContractId($contract["contractId"]));
                $this->success("成功新增一个合同", url('manager/listContract', ["projectId" => $request['projectId']]));
            }
        } else {
            $this->error("非法访问");
        }
    }
    public function deleteContract()
    {
        $contractId = $this->request->param("contractId");
        $projectId = $this->getProjectIdByContractId($contractId);
        $paymentCount = Db::name("payment")
            ->where("contractId", $contractId)
            ->delete();
        $contractCount = Db::name("contract")
            ->where("contractId", $contractId)
            ->delete();
        $this->calculateProjectAmount($projectId);
        if ($contractCount > 0) {
            if ($paymentCount > 0) {
                $this->success("成功删除了合同和相应{$paymentCount}个支付记录");
            } else {
                $this->success("成功删除合同");
            }
        }
    }
    public function postProjectUpdate()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $projectName = $request['project-name'];
            $projectId = $request['projectId'];
            if (Db::name('project')->where('projectName', $projectName)->where('projectId', '<>', $projectId)->find() != null) {
                $this->error('存在相同名字的工程，请检查');
            }
            $data = [
                'projectName' => $projectName,
                'approximatePrice' => $request['approximatePrice'],
                'constructionCompany' => $request['constructionCompany'],
                'constructionYear' => $request['constructionYear'],
                'estimatedPrice' => $request['estimatedPrice'],
                'reportedBudget' => $request['reportedBudget'],
                'approvedBudget' => $request['approvedBudget'],
                'tenderingMethod' => $request['tenderingMethod'],
                'projectStatusId' => $request['projectStatusId'],
                'fee1' => $request['fee1'],
                'fee2' => $request['fee2'],
                'fee3' => $request['fee3'],
                'fee4' => $request['fee4'],
                'fee5' => $request['fee5'],
                'file_date_2' => $request['file_date_2'],
                'file_date_4' => $request['file_date_4'],
                'file_date_9' => $request['file_date_9'],
            ];
            for ($i = 1; $i <= 21; $i++) {
                if (array_key_exists("file_name_" . $i, $request) && array_key_exists("file_url_" . $i, $request)) {
                    $data["file_name_" . $i] = $request["file_name_" . $i];
                    $data["file_url_" . $i] = $request["file_url_" . $i];
                } else {
                    $data["file_name_" . $i] = "null";
                    $data["file_url_" . $i] = "null";
                }
            }
            $res = Db::name("project")->where('projectId', $projectId)->update($data);

            if ($res !== false) {
                $this->success("工程已更新", url('manager/listProjectInfo', ['projectId' => $projectId]));
            } else {
                $this->error("更新工程时出现了一个错误");
            }
        } else {
            $this->error("非法访问");
        }
    }
    public function updateProject()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign('projectId', $projectId);
        $project = Db::name('project')
            ->where('projectId', $projectId)
            ->find();
        for ($x = 1; $x <= 21; $x++) {
            $names = json_decode($project['file_name_' . $x]);
            $urls = json_decode($project['file_url_' . $x]);
            if ($urls != null and $names != null) {
                $files = array_combine($urls, $names);
                $this->assign('file_' . $x, $files);
            }
        }
        $this->assign("project", $project);
        $tenderingMethods = Db::name('tenderingmethod')->select();
        $this->assign("tenderingMethods", $tenderingMethods);
        $theProjectStatus = Db::name('implementationPhase')->select();
        $this->assign("theProjectStatus", $theProjectStatus);
        return $this->fetch();
    }

    public function listProjectInfo()
    {

        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign('projectId', $projectId);
        $project = Db::name('project')
            ->where('projectId', $projectId)
            ->find();
        for ($x = 1; $x <= 21; $x++) {
            $names = json_decode($project['file_name_' . $x]);
            $urls = json_decode($project['file_url_' . $x]);
            if ($urls != null and $names != null) {
                $files = array_combine($urls, $names);
                $this->assign('file_' . $x, $files);
            }
        }
        $this->assign("project", $project);
        $tenderingMethods = Db::name('tenderingmethod')->select();
        $this->assign("tenderingMethods", $tenderingMethods);
        $theProjectStatus = Db::name('implementationPhase')->select();
        $this->assign("theProjectStatus", $theProjectStatus);
        return $this->fetch();
    }
    public function addDesign()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign('projectId', $projectId);
        $designContracts = Db::name('role_user r, pm_user u, pm_project p, pm_contract c')
            ->where("c.projectId = p.projectId")
            ->where("c.clientId = r.user_id")
            ->where("r.user_id = u.id")
            ->where("p.projectId", $projectId)
            ->where("r.role_id", 5)
            ->select();
        $this->assign('contracts', $designContracts);
        return $this->fetch();
    }

    public function postDesignAdd()
    {
        if ($this->request->isPost()) {
            $projectId = $this->request->param('projectId');
            if (!$this->checkProject($projectId)) {
                $this->error('非法访问项目');
            }
            $this->assign('projectId', $projectId);
        } else {
            $this->error("非法提交..");
        }
        $request = $this->request->param();
        if (array_key_exists("version", $request) && array_key_exists("contractId", $request)) {
            $res = Db::name('design')->where('version', $request['version'])->where('contractId', $request['contractId'])->find();
            if ($res != null) {
                $this->error("已有相同版本");
            }
        } else {
            $this->error("非法提交");
        }

        $data = [
            'contractId' => $request['contractId'],
            'designer' => $request['designer'],
            'budget' => $request['budget'],
            'version' => $request['version'],
            'comment' => $request['comment'],
            'commitTime' => $request['commitTime'],
            'contributer' => $request['contributer'],
        ];
        for ($i = 1; $i <= 1; $i++) {
            if (array_key_exists("file_name_" . $i, $request) && array_key_exists("file_url_" . $i, $request)) {
                $data["file_name_" . $i] = $request["file_name_" . $i];
                $data["file_url_" . $i] = $request["file_url_" . $i];
            } else {
                $data["file_name_" . $i] = "null";
                $data["file_url_" . $i] = "null";
            }
        }
        Db::name("design")->insert($data);
        $this->success("提交成功", url("manager/listDesign", ["projectId", $projectId]));
    }
    public function listDesign()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign('projectId', $projectId);
        $designContracts = Db::name('role_user r, pm_user u, pm_project p, pm_contract c')
            ->where("c.projectId = p.projectId")
            ->where("c.clientId = r.user_id")
            ->where("r.user_id = u.id")
            ->where("p.projectId", $projectId)
            ->where("r.role_id", 5)
            ->select();
        $this->assign('contracts', $designContracts);
        $data = Db::name('contract c, pm_design d')
            ->where('d.contractId=c.contractId')
            ->where('c.projectId', $projectId)
            ->order('commitTime', 'desc')
            ->select();
        if (count($data) > 0) {
            $this->assign('data', $data);
        }
        return $this->fetch();
    }

    public function listDesigns()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign('projectId', $projectId);
        $designContracts = Db::name('role_user r, pm_user u, pm_project p, pm_contract c')
            ->where("c.projectId = p.projectId")
            ->where("c.clientId = r.user_id")
            ->where("r.user_id = u.id")
            ->where("p.projectId", $projectId)
            ->where("r.role_id", 5)
            ->select();
        $this->assign('contracts', $designContracts);
        $data = Db::name('contract c, pm_design d')
            ->where('d.contractId=c.contractId')
            ->where('c.projectId', $projectId)
            ->order('commitTime', 'desc')
            ->select();
        if (count($data) > 0) {
            $this->assign('data', $data);
        }
        return $this->fetch();
    }

    public function updateDesign()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function deleteDesign()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function addSupervision()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign('projectId', $projectId);
        $contracts = Db::name('role_user r, pm_user u, pm_project p, pm_contract c')
            ->where("c.projectId = p.projectId")
            ->where("c.clientId = r.user_id")
            ->where("r.user_id = u.id")
            ->where("p.projectId", $projectId)
            ->where("r.role_id", 6)
            ->select();
        $this->assign('contracts', $contracts);
        $data = Db::name('project p, pm_contract c, pm_supervision s')
            ->where('p.projectId = c.projectId')
            ->where('c.contractId = s.contractId')
            ->order('s.id', 'desc')
            ->find();
        if ($data != null) {
            $this->assign('supervisor', $data['supervisor']);
        }
        return $this->fetch();
    }

    public function postSupervisionAdd()
    {
        if ($this->request->isPost()) {
            $projectId = $this->request->param('projectId');
            if (!$this->checkProject($projectId)) {
                $this->error('非法访问项目');
            }
            $request = $this->request->param();
            $data = [
                "contractId" => $request["contractId"],
                "type" => $request["type"],
                "supervisor" => $request["supervisor"],
            ];
            for ($i = 1; $i <= 7; $i++) {
                if (array_key_exists("file_name_" . $i, $request) && array_key_exists("file_url_" . $i, $request)) {
                    $data["file_name_" . $i] = $request["file_name_" . $i];
                    $data["file_url_" . $i] = $request["file_url_" . $i];
                }
            }
            $res = Db::name('supervision')->insert($data);
            if ($res !== false) {
                $this->success("提交成功", url('manager/listsupervision', ['projectId' => $projectId]));
            } else {
                $this->error("提交失败");
            }
        } else {
            $this->error("非法提交..");
        }
    }

    public function postSupervisionUpdate()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function listSupervision()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign('projectId', $projectId);
        $data = Db::name('project p, pm_contract c, pm_supervision s')
            ->where('p.projectId = c.projectId')
            ->where('c.contractId = s.contractId')
            ->order('s.id', 'desc')
            ->select();
        $this->assign('data', $data);
        // var_dump($data);
        // for ($x = 1; $x <= 7; $x++) {
        //     $names = json_decode($data['file_name_' . $x]);
        //     $urls = json_decode($data['file_url_' . $x]);
        //     if ($urls != null and $names != null) {
        //         $files = array_combine($urls, $names);
        //         $this->assign('file_' . $x, $files);
        //     }
        // }
        return $this->fetch();
    }

    public function updateSupervision()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function deleteSupervision()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function addConstruction()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $contracts = Db::name('role_user r, pm_user u, pm_project p, pm_contract c')
            ->where("c.projectId = p.projectId")
            ->where("c.clientId = r.user_id")
            ->where("r.user_id = u.id")
            ->where("p.projectId", $projectId)
            ->where("r.role_id", 4)
            ->select();
        $this->assign('contracts', $contracts);
        return $this->fetch();
    }

    public function updateConstruction()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function deleteConstruction()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function listConstruction()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        return $this->fetch();
    }

    public function postConstructionUpdate()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function postConstructionAdd()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $request = $this->request->param();
        var_dump($request);
    }

    public function addIncome()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function updateIncome()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function deleteIncome()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function listIncome()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function postIncomeAdd()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function postIncomeUpdate()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function addSavety()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function updateSavety()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function deleteSavety()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function listSavety()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }
    public function postSavetyAdd()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

    public function postSavetyUpdate()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
    }

// public function 
}