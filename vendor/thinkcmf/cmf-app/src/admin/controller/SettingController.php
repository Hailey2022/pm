<?php
namespace app\admin\controller;
use app\admin\model\RouteModel;
use app\admin\model\UserModel;
use cmf\controller\AdminBaseController;
class SettingController extends AdminBaseController
{
    public function site()
    {
        $content = hook_one('admin_setting_site_view');
        if (!empty($content)) {
            return $content;
        }
        $noNeedDirs = [".", "..", ".svn", 'fonts'];
        $adminThemesDir = WEB_ROOT . config('template.cmf_admin_theme_path') . config('template.cmf_admin_default_theme') . '/public/assets/themes/';
        $adminStyles = cmf_scan_dir($adminThemesDir . '*', GLOB_ONLYDIR);
        $adminStyles = array_diff($adminStyles, $noNeedDirs);
        $cdnSettings = cmf_get_option('cdn_settings');
        $cmfSettings = cmf_get_option('cmf_settings');
        $adminSettings = cmf_get_option('admin_settings');
        $adminThemes = [];
        $themes = cmf_scan_dir(WEB_ROOT . config('template.cmf_admin_theme_path') . '/*', GLOB_ONLYDIR);
        foreach ($themes as $theme) {
            if (strpos($theme, 'admin_') === 0) {
                array_push($adminThemes, $theme);
            }
        }
        if (APP_DEBUG && false) {
            $apps = cmf_scan_dir(APP_PATH . '*', GLOB_ONLYDIR);
            $apps = array_diff($apps, $noNeedDirs);
            $this->assign('apps', $apps);
        }
        $this->assign('site_info', cmf_get_option('site_info'));
        $this->assign("admin_styles", $adminStyles);
        $this->assign("templates", []);
        $this->assign("admin_themes", $adminThemes);
        $this->assign("cdn_settings", $cdnSettings);
        $this->assign("admin_settings", $adminSettings);
        $this->assign("cmf_settings", $cmfSettings);
        return $this->fetch();
    }
    public function sitePost()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'SettingSite');
            if ($result !== true) {
                $this->error($result);
            }
            $options = $this->request->param('options/a');
            cmf_set_option('site_info', $options);
            $cmfSettings = $this->request->param('cmf_settings/a');
            $bannedUsernames = preg_replace("/[^0-9A-Za-z_\\x{4e00}-\\x{9fa5}-]/u", ",", $cmfSettings['banned_usernames']);
            $cmfSettings['banned_usernames'] = $bannedUsernames;
            cmf_set_option('cmf_settings', $cmfSettings);
            $cdnSettings = $this->request->param('cdn_settings/a');
            cmf_set_option('cdn_settings', $cdnSettings);
            $adminSettings = $this->request->param('admin_settings/a');
            $routeModel = new RouteModel();
            if (!empty($adminSettings['admin_password'])) {
                $routeModel->setRoute($adminSettings['admin_password'] . '$', 'admin/Index/index', [], 2, 5000);
            } else {
                $routeModel->deleteRoute('admin/Index/index', []);
            }
            $routeModel->getRoutes(true);
            if (!empty($adminSettings['admin_theme'])) {
                $result = cmf_set_dynamic_config([
                    'template' => [
                        'cmf_admin_default_theme' => $adminSettings['admin_theme']
                    ]
                ]);
                if ($result === false) {
                    $this->error('??????????????????!');
                }
            }
            cmf_set_option('admin_settings', $adminSettings);
            $this->success("???????????????", '');
        }
    }
    public function password()
    {
        return $this->fetch();
    }
    public function passwordPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['old_password'])) {
                $this->error("???????????????????????????");
            }
            if (empty($data['password'])) {
                $this->error("????????????????????????");
            }
            $userId = cmf_get_current_admin_id();
            $admin = UserModel::where("id", $userId)->find();
            $oldPassword = $data['old_password'];
            $password = $data['password'];
            $rePassword = $data['re_password'];
            if (cmf_compare_password($oldPassword, $admin['user_pass'])) {
                if ($password == $rePassword) {
                    if (cmf_compare_password($password, $admin['user_pass'])) {
                        $this->error("???????????????????????????????????????");
                    } else {
                        UserModel::where('id', $userId)->update(['user_pass' => cmf_password($password)]);
                        $this->success("?????????????????????");
                    }
                } else {
                    $this->error("????????????????????????");
                }
            } else {
                $this->error("????????????????????????");
            }
        }
    }
    public function upload()
    {
        $uploadSetting = cmf_get_upload_setting();
        $this->assign('upload_setting', $uploadSetting);
        return $this->fetch();
    }
    public function uploadPost()
    {
        if ($this->request->isPost()) {
            //TODO ????????????
            $uploadSetting = $this->request->post();
            cmf_set_option('upload_setting', $uploadSetting);
            $this->success('???????????????');
        }
    }
    public function clearCache()
    {
        $content = hook_one('admin_setting_clear_cache_view');
        if (!empty($content)) {
            return $content;
        }
        cmf_clear_cache();
        return $this->fetch();
    }
}