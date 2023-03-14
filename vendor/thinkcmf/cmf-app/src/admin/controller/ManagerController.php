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
    public function getExcelHeaderArray()
    {
        return array(
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X',
            'Y',
            'Z',
            'AA',
            'AB',
            'AC',
            'AD',
            'AE',
            'AF',
            'AG',
            'AH',
            'AI',
            'AJ',
            'AK'
        );
    }
    public function exportExcel($filename, $objWriter)
    {
        ob_end_clean();
        ob_start();
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Type: application/vnd.ms-excel');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        ;
        header("Content-Transfer-Encoding:binary");

        $objWriter->save('php://output');
        exit;
    }
    public function getUsername()
    {
        $id = cmf_get_current_admin_id();
        $u = Db::name('user')->where('id', $id)->find();
        if ($u != null) {
            return $u['user_login'];
        } else {
            return null;
        }
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
        $incomes = Db::name('income')
            ->where('projectId', $projectId)
            ->select();
        $this->assign('incomes', $incomes);
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
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $incomeCount = count($request['income']);
            if ($incomeCount != count($request['from']) || count($request['price']) != $incomeCount || $incomeCount == 0) {
                $this->error("请选择支付来源");
            }
            $fromList = ['ccp', 'province', 'city', 'bond', 'budget', 'others'];
            $incomes = [];
            $result = [];
            $updatedIncome = [];
            $incomeIds = [];
            foreach ($fromList as $from) {
                $result[$from] = 0;
            }
            for ($i = 0; $i < $incomeCount; $i++) {
                $id = $request['income'][$i];
                if (in_array($id, $incomeIds)) {
                    $this->error("不要有相同的来源");
                } else {
                    array_push($incomeIds, $id);
                }
                $from = $request['from'][$i];
                $price = $request['price'][$i];
                $incomes[$i] = [
                    'income' => $id,
                    'from' => $from,
                    'price' => $price
                ];
                if (!is_numeric($price)) {
                    $this->error("非正常金额");
                }
                if (!in_array($from, $fromList)) {
                    $this->error('不要乱post');
                }
                $res = Db::name('income')->where('id', $id)->find();
                if ($res != null) {
                    if (round($res[$from . 'Paid'] + $price, 2) > $res[$from]) {
                        $this->error("金额不够了， 请检查");
                    }
                } else {
                    $this->error("404 not found...");
                }

                $result[$from] += $price;
            }
            $res = Db::name('payment')->where('contractId', $request['contractId'])->where('installment', $request['installment'])->find();
            if ($res != null) {
                $this->error("有相同期数进度款");
            }
            $data = [
                'paymentId' => uniqid(),
                'comment' => $request['comment'],
                'contractId' => $request['contractId'],
                'installment' => $request['installment'],
                'ccp' => $result['ccp'],
                'province' => $result['province'],
                'city' => $result['city'],
                'bond' => $result['bond'],
                'budget' => $result['budget'],
                'others' => $result['others'],
                'total' => $result['ccp'] + $result['province'] + $result['city'] + $result['bond'] + $result['budget'] + $result['others'],
                'incomes' => $incomes
            ];
            if (array_key_exists('file_url_1', $request) && array_key_exists('file_name_1', $request)) {
                $data['file_url_1'] = $request['file_url_1'];
                $data['file_name_1'] = $request['file_name_1'];
            }
            for ($i = 0; $i < $incomeCount; $i++) {
                $id = $request['income'][$i];
                $from = $request['from'][$i];
                $price = $request['price'][$i];
                $res = Db::name('income')->where('id', $id)->find();
                foreach ($fromList as $f) {
                    if ($from == $f) {
                        $updatedIncome[$f . 'Paid'] = $res[$f . 'Paid'] + $price;
                    }
                }
                $updatedIncome['paid'] = round($res['paid'] + $price, 2);
                $res = Db::name('income')->where('id', $id)->update(
                    $updatedIncome
                );
                if ($res === false) {
                    $this->error("unknown error...");
                }
            }
            $res = Db::name('payment')->insert($data);
            if ($res !== false) {
                $this->calculateContractAmount($request['contractId']);
                $this->success("保存成功！", url('manager/listProjectPayments', ["projectId" => $this->getProjectIdByContractId($request['contractId'])]));
            } else {
                $this->error("保存时出错！");
            }

        } else {
            $this->error("非法支付请求");
        }
    }
    public function postPaymentUpdate()
    {
        // $incomes = $res['incomes'];
        // if ($incomes != null && $incomes != "" && $incomes != 'null') {
        //     $incomes = json_decode($incomes);
        //     foreach ($incomes as $income) {
        //         $id = $income->income;
        //         $price = $income->price;
        //         $res = Db::name('income')->where('id', $id)->find();
        //         if ($res != null) {
        //             $oldPrice = $res['paid'];
        //             $newPrice = round($oldPrice - $price, 2);
        //             // Db::name('income')->where('id', $id)->update(
        //             //     [
        //             //         'paid' => $newPrice
        //             //     ]
        //             // );
        //         }
        //     }
        // }
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $data = [
                'comment' => $request['comment'],
                'installment' => $request['installment'],
            ];
            if (array_key_exists('file_url_1', $request) && array_key_exists('file_name_1', $request)) {
                $data['file_url_1'] = $request['file_url_1'];
                $data['file_name_1'] = $request['file_name_1'];
            } else {
                $data['file_url_1'] = null;
                $data['file_name_1'] = null;
            }
            $res = Db::name('payment')->where("paymentId", $request['paymentId'])->update($data);
            if ($res !== false) {
                // $this->calculateContractAmount($request['contractId']);
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
        $this->assign('paymentId', $paymentId);
        $all = Db::name("contract c, pm_payment p, pm_user u")
            ->where("p.contractId=c.contractId")
            ->where("c.clientId=u.id")
            ->where("p.paymentId", $paymentId)
            ->find();
        $this->assign("data", $all);
        $incomes = Db::name('income')
            ->where('projectId', $projectId)
            ->select();
        $this->assign('incomes', $incomes);
        $oldIncomes = json_decode($all['incomes']);
        $this->assign("oldIncomes", $oldIncomes);
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
        $paymentId = $this->request->param("paymentId");
        $all = Db::name('payment')
            ->where('paymentId', $paymentId)
            ->find();
        $this->assign("data", $all);
        $urls = [];
        $names = [];
        if ($this->request->param("type") == 1) {
            $incomes = $all['incomes'];
            if ($incomes != null && $incomes != "" && $incomes != "null") {
                $incomes = json_decode($incomes);
                foreach ($incomes as $income) {
                    $incomeId = $income->income;
                    $res = Db::name('income')->where('id', $incomeId)->find();
                    if ($res == null) {
                        continue;
                    } else {
                        $incomeFileNames = json_decode($res['file_name_1'], true);
                        $incomeFileUrls = json_decode($res['file_url_1'], true);
                        if ($incomeFileNames == null or $incomeFileUrls == null) {
                            continue;
                        }
                        foreach ($incomeFileUrls as $u) {
                            array_push($urls, $u);

                        }
                        foreach ($incomeFileNames as $n) {
                            array_push($names, $n);
                        }
                    }
                }
            }
        } else {
            $urls = json_decode($all["file_url_1"]);
            $names = json_decode($all["file_name_1"]);

        }
        if ($urls != null and $names != null) {
            $files = array_combine($urls, $names);
            $this->assign("files", $files);
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
            ->find();
        if ($res == null) {
            $this->error("不存在的支付");
        }
        $incomes = $res['incomes'];
        if ($incomes != null && $incomes != "" && $incomes != 'null') {
            $incomes = json_decode($incomes);
            foreach ($incomes as $income) {
                $id = $income->income;
                $price = $income->price;
                $res = Db::name('income')->where('id', $id)->find();
                if ($res != null) {
                    $oldPrice = $res['paid'];
                    $newPrice = round($oldPrice - $price, 2);
                    Db::name('income')->where('id', $id)->update(
                        [
                            'paid' => $newPrice
                        ]
                    );
                }
            }
        }
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
        $incomes = Db::name("income")
            ->where("projectId", $projectId)
            ->select();
        $this->assign("incomes", $incomes);
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
            ->where("r.role_id = 5 OR c.clientType = 2")
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
            ->where("r.role_id = 6 OR c.clientType = 3")
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
    public function addSupervisionA()
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
            ->where("r.role_id = 6 OR c.clientType = 3")
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
    public function addSupervisionB()
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
            ->where("r.role_id = 6 OR c.clientType = 3")
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

    public function postSupervisionAAdd()
    {
        if ($this->request->isPost()) {
            $projectId = $this->request->param('projectId');
            if (!$this->checkProject($projectId)) {
                $this->error('非法访问项目');
            }
            $request = $this->request->param();
            $data = [
                "contractId" => $request["contractId"],
                "supervisor" => $request["supervisor"],
                "for" => "a",
                "time" => $request["time"]
            ];
            for ($i = 1; $i <= 5; $i++) {
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

    public function postSupervisionBAdd()
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
                "for" => "b",
                "time" => $request["time"]
            ];
            for ($i = 6; $i <= 7; $i++) {
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

    public function addConstructionA()
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
            ->where("r.role_id = 4 OR c.clientType = 1")
            ->select();
        $this->assign('contracts', $contracts);
        $data = Db::name('construction_img_type')->where('projectId', $projectId)->find();
        if ($data != null) {
            $types = $data['type'];
            if ($types != null) {
                $this->assign('types', json_decode($types));
            }
        }
        return $this->fetch();
    }

    public function addConstructionB()
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
            ->where("r.role_id = 4 OR c.clientType = 1")
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

    public function updateConstructionType()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $data = Db::name('construction_img_type')->where('projectId', $projectId)->find();
        if ($data != null) {
            $types = $data['type'];
            if ($types != null) {
                $this->assign('types', json_decode($types));
            }
        }
        return $this->fetch();
    }


    public function postConstructionTypeUpdate()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $request = $this->request->param();
        $type = $request['type'];
        $status = $request['status'];
        if (count($type) != count($status)) {
            $this->error("hacker???");
        }
        $res = Db::name('construction_img_type')->where('projectId', $projectId)->find();
        if ($res == null) {
            $res = Db::name('construction_img_type')
                ->insert([
                    'projectId' => $projectId,
                    'type' => $type,
                    'status' => $status
                ]);
            if ($res !== false) {
                $this->success("更新成功", url('manager/listconstructiona', ['projectId' => $projectId]));
            } else {
                $this->error("未知错误");
            }
        } else {
            if ($res['type'] == null || count(json_decode($res['type'])) <= count($type)) {
                $res = Db::name('construction_img_type')
                    ->where('projectId', $projectId)
                    ->update([
                        'type' => $type,
                        'status' => $status
                    ]);
                if ($res !== false) {
                    $this->success("更新成功", url('manager/listconstructiona', ['projectId' => $projectId]));
                } else {
                    $this->error("未知错误");
                }
            } else {
                $this->error("不能删!!!!!!");
            }
        }
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

    public function listConstructionA()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $constructions = Db::name('contract c, pm_construction a')
            ->where('c.contractId = a.contractId')
            ->where('c.projectId', $projectId)
            ->where('a.for', 'a')
            ->order('id', 'desc')
            ->select();
        $this->assign('constructions', $constructions);
        $type = Db::name('construction_img_type')->where('projectId', $projectId)->find();
        if ($type != null && $type['type'] != null && $type['type'] != 'null' && $type['type'] != "") {
            $this->assign('file_types', json_decode($type['type']));
        } else {
            $this->assign('file_types', []);
        }

        return $this->fetch();
    }

    public function listConstructionB()
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

    public function postConstructionAAdd()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $request = $this->request->param();
        $data = [
            'contractId' => $request['contractId'],
            'date' => $request['time'],
            'for' => 'a'
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

        for ($i = 6; $i <= 6; $i++) {
            if (array_key_exists("file_name_" . $i, $request) && array_key_exists("file_url_" . $i, $request) && array_key_exists("file_type_" . $i, $request)) {
                $names = $request["file_name_" . $i];
                $urls = $request["file_url_" . $i];
                $types = $request["file_type_" . $i];
                if (count($names) != count($urls) || count($names) != count($types)) {
                    $this->error("are you a hacker???");
                }
                $data["file_name_" . $i] = $request["file_name_" . $i];
                $data["file_url_" . $i] = $request["file_url_" . $i];
                $data["file_type_" . $i] = $request["file_type_" . $i];
            } else {
                $data["file_name_" . $i] = "null";
                $data["file_url_" . $i] = "null";
                $data["file_type_" . $i] = "null";
            }
        }
        $res = Db::name('construction')->insert(
            $data
        );
        if ($res != false) {
            $this->success("已加入", url('manager/listConstructionA', ['projectId' => $projectId]));
        } else {
            $this->error("未知error");
        }

    }

    public function postConstructionBAdd()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $request = $this->request->param();
        $data = [
            'contractId' => $request['contractId'],
            'date' => $request['time'],
            'for' => 'b'
        ];
        for ($i = 2; $i <= 5; $i++) {
            if (array_key_exists("file_name_" . $i, $request) && array_key_exists("file_url_" . $i, $request)) {
                $data["file_name_" . $i] = $request["file_name_" . $i];
                $data["file_url_" . $i] = $request["file_url_" . $i];
            } else {
                $data["file_name_" . $i] = "null";
                $data["file_url_" . $i] = "null";
            }
        }
        $res = Db::name('construction')->insert(
            $data
        );
        if ($res != false) {
            $this->success("已加入", url('manager/listConstructionB', ['projectId' => $projectId]));
        } else {
            $this->error("未知error");
        }
    }

    public function addIncome()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $this->assign('username', $this->getUsername());
        return $this->fetch();
    }

    public function updateIncome()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $incomeId = $this->request->param('incomeId');
        $this->assign('incomeId', $incomeId);
        $data = Db::name('income')
            ->where('id', $incomeId)
            ->find();
        if ($data == null) {
            $this->error("不存在的来源");
        } else {
            $this->assign('data', $data);
        }
        for ($x = 1; $x <= 1; $x++) {
            $names = json_decode($data['file_name_' . $x]);
            $urls = json_decode($data['file_url_' . $x]);
            if ($urls != null and $names != null) {
                $files = array_combine($urls, $names);
                $this->assign('file_' . $x, $files);
            }
        }
        return $this->fetch();
    }

    public function deleteIncome()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $incomeId = $this->request->param('incomeId');
        $res = Db::name('income')
            ->where('id', $incomeId)
            ->find();
        if ($res != null) {
            if ($res['paid'] > 0) {
                $this->error('这个来源有支付，不可删除');
            }
            $res = Db::name('income')
                ->where('id', $incomeId)
                ->delete();
            if ($res !== false) {
                $this->success("已删除", url('manager/listincome', ['projectId' => $projectId]));
            } else {
                $this->error("有个错误");
            }
        } else {
            $this->error('不存在的来源');
        }

        $this->error("错误");
    }
    public function expIncome()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        require_once dirname(__FILE__) . '/../../../../../phpoffice/phpexcel/Classes/PHPExcel/IOFactory.php';
        require_once dirname(__FILE__) . '/../../../../../phpoffice/phpexcel/Classes/PHPExcel.php';
        require_once dirname(__FILE__) . '/../../../../../phpoffice/phpexcel/Classes/PHPExcel/Writer/Excel2007.php';
        $root = dirname(__FILE__) . '/../../../../../../public/static/';
        $objPHPExcel = \PHPExcel_IOFactory::load($root . "excel/income.xlsx");
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet();
        $header_arr = $this->getExcelHeaderArray();
        $incomes = Db::name("income")
            ->where('projectId', $projectId)
            ->order('id', 'desc')
            ->order('year', 'desc')
            ->select();
        $firstRow = 4;
        $projectName = $this->getProjectNameByProjectId($projectId);
        foreach ($incomes as $key => $income) {
            $row = $key + 4;
            $total = $income['total'];
            $paid = $income['paid'];
            $unpaid = $total - $paid;
            $percentage = round($paid * 100 / $total, 2) . '%';
            $objPHPExcel->getActiveSheet()->setCellValue($header_arr[0] . $row, $key + 1);
            $objPHPExcel->getActiveSheet()->setCellValue($header_arr[2] . $row, $projectName);
            $objPHPExcel->getActiveSheet()->setCellValue($header_arr[12] . $row, $unpaid);
            foreach ($income as $k => $i) {
                if ($k == 'name') {
                    $objPHPExcel->getActiveSheet()->setCellValue($header_arr[1] . $row, $i);
                }
                if ($k == 'year') {
                    $objPHPExcel->getActiveSheet()->setCellValue($header_arr[3] . $row, $i);
                }
                if ($k == 'ccp') {
                    $objPHPExcel->getActiveSheet()->setCellValue($header_arr[4] . $row, $i);
                }
                if ($k == 'province') {
                    $objPHPExcel->getActiveSheet()->setCellValue($header_arr[5] . $row, $i);
                }
                if ($k == 'city') {
                    $objPHPExcel->getActiveSheet()->setCellValue($header_arr[6] . $row, $i);
                }
                if ($k == 'bond') {
                    $objPHPExcel->getActiveSheet()->setCellValue($header_arr[7] . $row, $i);
                }
                if ($k == 'budget') {
                    $objPHPExcel->getActiveSheet()->setCellValue($header_arr[8] . $row, $i);
                }
                if ($k == 'others') {
                    $objPHPExcel->getActiveSheet()->setCellValue($header_arr[9] . $row, $i);
                }
                if ($k == 'total') {
                    $objPHPExcel->getActiveSheet()->setCellValue($header_arr[10] . $row, $i);
                }
                if ($k == 'paid') {
                    $objPHPExcel->getActiveSheet()->setCellValue($header_arr[11] . $row, $i);
                }
            }
        }
        $PHPWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");
        $filename = $projectName . "资金来源.xlsx";
        $this->exportExcel($filename, $PHPWriter);
    }
    public function listIncome()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $incomes = Db::name("income")
            ->where('projectId', $projectId)
            ->order('id', 'desc')
            ->order('year', 'desc')
            ->select();
        // foreach ($incomes as $i => $income) {
        //     $urls = json_decode($income["file_url_1"]);
        //     $names = json_decode($income["file_name_1"]);
        //     if ($urls != null && $names != null) {
        //         $files_1 = array_combine($urls, $names);
        //     }

        //     if ($income['file_url_1'] != null && $income['file_url_1'] != '') {
        //         $incomes[$i]['file_count_1'] = count($urls);
        //     } else {
        //         $incomes[$i]['file_count_1'] = 0;
        //     }
        // }
        $this->assign('incomes', $incomes);
        $this->assign("projectName", $this->getProjectNameByProjectId($projectId));
        $this->assign("projectId", $projectId);
        return $this->fetch();
    }

    public function postIncomeAdd()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $request = $this->request->param();
        $res = Db::name('income')
            ->where('name', $request['name'])
            ->where('projectId', $projectId)
            ->find();
        if ($res != null) {
            $this->error('有相同的资金来源');
        }
        $data = [
            'name' => $request['name'],
            'comment' => $request['comment'],
            'ccpPaid' => 0,
            'provincePaid' => 0,
            'cityPaid' => 0,
            'bondPaid' => 0,
            'budgetPaid' => 0,
            'othersPaid' => 0,
            "paid" => 0,
            "staff" => $request['staff'],
            "year" => $request['year'],
            "projectId" => $request['projectId']
        ];
        $data[$request['from']] = $request['price'];
        $data['total'] = $request['price'];
        for ($i = 1; $i <= 1; $i++) {
            if (array_key_exists("file_name_" . $i, $request) && array_key_exists("file_url_" . $i, $request)) {
                $data["file_name_" . $i] = $request["file_name_" . $i];
                $data["file_url_" . $i] = $request["file_url_" . $i];
            }
        }
        $res = Db::name('income')->insert($data);
        if ($res !== false) {
            $this->success("成功", url('manager/listincome', ['projectId' => $projectId]));
        } else {
            $this->error("出现错误");
        }
    }

    public function postIncomeUpdate()
    {
        $projectId = $this->request->param('projectId');
        if (!$this->checkProject($projectId)) {
            $this->error('非法访问项目');
        }
        $this->assign("projectId", $projectId);
        $incomeId = $this->request->param('incomeId');
        $request = $this->request->param();
        $res = Db::name('income')
            ->where('id', $incomeId)
            ->find();
        if ($res == null) {
            $this->error('이것 없어');
        }
        $fromList = ['ccp', 'province', 'city', 'bond', 'budget', 'others'];
        $data = [
            'name' => $request['name'],
            'comment' => $request['comment'],
            "staff" => $request['staff'],
            "year" => $request['year'],
            "projectId" => $request['projectId'],
            "total" => 0
        ];
        foreach ($fromList as $i) {
            if ($request['from'] == $i) {
                $data[$i] = $request['price'];
            } else {
                $data[$i] = 0;
            }

            if ($data[$i] < $res[$i . 'Paid']) {
                $this->error('不能改，因为有下达资金的使用比下达的资金还多');
            }
            $data['total'] = $data['total'] + $data[$i];
        }
        for ($i = 1; $i <= 1; $i++) {
            if (array_key_exists("file_name_" . $i, $request) && array_key_exists("file_url_" . $i, $request)) {
                $data["file_name_" . $i] = $request["file_name_" . $i];
                $data["file_url_" . $i] = $request["file_url_" . $i];
            } else {
                $data["file_name_" . $i] = null;
                $data["file_url_" . $i] = null;
            }
        }
        $res = Db::name('income')
            ->where('id', $incomeId)
            ->update($data);
        if ($res !== false) {
            $this->success("更新成功", url('manager/listincome', ['projectId' => $projectId]));
        } else {
            $this->error("出现错误");
        }

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
    // public function viewPaymentsFiles(){
//     return $this->viewFiles();
// }
// public function viewFiles()
// {

    //     $files = $this->request->param();
//     var_dump($files);
//     $names = $this->request->param('names');
//     $urls = $this->request->param('urls');
//     $names = json_decode($names);
//     $urls = json_decode($urls);
//     // $files = array_combine($urls, $names);
//     $this->assign('files', $files);
//     return $this->fetch();
// }

    public function search()
    {
        $text = $this->request->param('text');
        $type = $this->request->param('type');
        $res = Db::name("project")->where('projectName', 'like', '%' . $text . '%')->cursor();
        echo ('--项目--<br>');
        echo ('<div class="projects">');
        foreach ($res as $r) {
            echo ('<a href="/admin/manager/listprojectinfo?projectId=' . $r['projectId'] . '">' . $r['projectName'] . '</a><br>');
        }
        echo ('</div>');
        echo ('--项目--<br>');


        $res = Db::name("contract")->where('contractName', 'like', '%' . $text . '%')->group('contractName')->cursor();
        echo ('--合同--<br>');
        echo ('<div class="contracts">');
        foreach ($res as $r) {
            echo ('<a href="/admin/manager/listcontract?projectId=' . $this->getProjectIdByContractId($r['contractId']) . '">' . $r['contractName'] . '</a><br>');
        }
        echo ('</div>');
        echo ('--合同--<br>');

        // $res = Db::name("contract")->where('contractName', 'like', '%' . $text . '%')->cursor();
        // foreach ($res as $r) {
        //     echo ('<a>' . $r['contractName'] . '</a><br>');
        // }
    }
}