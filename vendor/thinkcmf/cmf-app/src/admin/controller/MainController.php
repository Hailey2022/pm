<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;

class MainController extends AdminBaseController
{
    public function index()
    {
        $dashboardWidgets = [];
        $widgets = cmf_get_option('admin_dashboard_widgets');

        $defaultDashboardWidgets = [
            '_SystemCmfHub' => ['name' => 'CmfHub', 'is_system' => 1],
            '_SystemCmfDocuments' => ['name' => 'CmfDocuments', 'is_system' => 1],
            '_SystemMainContributors' => ['name' => 'MainContributors', 'is_system' => 1],
            '_SystemContributors' => ['name' => 'Contributors', 'is_system' => 1],
            '_SystemCustom1' => ['name' => 'Custom1', 'is_system' => 1],
            '_SystemCustom2' => ['name' => 'Custom2', 'is_system' => 1],
            '_SystemCustom3' => ['name' => 'Custom3', 'is_system' => 1],
            '_SystemCustom4' => ['name' => 'Custom4', 'is_system' => 1],
            '_SystemCustom5' => ['name' => 'Custom5', 'is_system' => 1],
        ];

        if (empty($widgets)) {
            $dashboardWidgets = $defaultDashboardWidgets;
        } else {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    $dashboardWidgets['_System' . $widget['name']] = ['name' => $widget['name'], 'is_system' => 1];
                } else {
                    $dashboardWidgets[$widget['name']] = ['name' => $widget['name'], 'is_system' => 0];
                }
            }

            foreach ($defaultDashboardWidgets as $widgetName => $widget) {
                $dashboardWidgets[$widgetName] = $widget;
            }


        }

        $dashboardWidgetPlugins = [];

        $hookResults = hook('admin_dashboard');

        if (!empty($hookResults)) {
            foreach ($hookResults as $hookResult) {
                if (isset($hookResult['width']) && isset($hookResult['view']) && isset($hookResult['plugin'])) { //验证插件返回合法性
                    $dashboardWidgetPlugins[$hookResult['plugin']] = $hookResult;
                    if (!isset($dashboardWidgets[$hookResult['plugin']])) {
                        $dashboardWidgets[$hookResult['plugin']] = ['name' => $hookResult['plugin'], 'is_system' => 0];
                    }
                }
            }
        }

        $smtpSetting = cmf_get_option('smtp_setting');

        $this->assign('dashboard_widgets', $dashboardWidgets);
        $this->assign('dashboard_widget_plugins', $dashboardWidgetPlugins);
        $this->assign('has_smtp_setting', empty($smtpSetting) ? false : true);

        $userid = cmf_get_current_admin_id();
        $res = Db::name('role_user r, pm_role l, pm_user u')
            ->where('r.user_id = u.id')
            ->where('r.role_id = l.id')
            ->where('u.id', $userid)
            ->find();

        if ($res !== null) {
            $username = $res['user_login'];
            $name = $res['name'];
            $this->assign('username', $username);
            $this->assign('type', $name);
        }

        $dailyReportCount = Db::name('contract c, pm_report r')
            ->where('c.clientId', $userid)
            ->where('c.contractId = r.contractId')
            ->where('r.reportTypeId', '1')
            ->count();

        $monthlyReportCount = Db::name('contract c, pm_report r')
            ->where('c.clientId', $userid)
            ->where('c.contractId = r.contractId')
            ->where('r.reportTypeId', '<>', '1')
            ->count();

        $this->assign('dailyReportCount', $dailyReportCount);
        $this->assign('monthlyReportCount', $monthlyReportCount);

        $res = Db::name('contract c, pm_report r')
            ->where('c.clientId', $userid)
            ->where('c.contractId = r.contractId')
            ->order('reportTime', 'desc')
            ->find('reportTime');
        if ($res != null) {
            $this->assign('reportTime', "最后上传时间：" . $res['reportTime']);
        }

        $res = Db::name('contract c, pm_pics r')
            ->where('c.clientId', $userid)
            ->where('c.contractId = r.contractId')
            ->order('picTime', 'desc')
            ->find('picTime');
        if ($res != null) {
            $this->assign('picTime', "最后上传时间：" . $res['picTime']);
        }

        return $this->fetch();
    }

    public function dashboardWidget()
    {
        $dashboardWidgets = [];
        $widgets = $this->request->param('widgets/a');
        if (!empty($widgets)) {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 1]);
                } else {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 0]);
                }
            }
        }

        cmf_set_option('admin_dashboard_widgets', $dashboardWidgets, true);

        $this->success('更新成功!');

    }

}