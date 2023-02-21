<?php
namespace cmf\controller;
use cmf\model\UserModel;
class AdminBaseController extends BaseController
{
    public function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
    protected function initialize()
    {
        hook('admin_init');
        parent::initialize();
        $sessionAdminId = session('ADMIN_ID');
        if (!empty($sessionAdminId)) {
            $user = UserModel::where('id', $sessionAdminId)->find();
            if (!$this->checkAccess($sessionAdminId)) {
                $this->error("您没有访问权限！");
            }
            $this->assign("admin", $user);
        } else {
            if ($this->request->isPost()) {
                $this->error("您还没有登录！", url("admin/public/login"));
            } else {
                $this->redirect(url("admin/Public/login"));
            }
        }
        $data = input();
        $allowExts = ['.jpg', '.jpeg', '.png', '.bmp', '.wbmp', '.gif'];
        if (isset($data['file_urls'])) {
            foreach ($data['file_urls'] as $k => $v) {
                foreach ($allowExts as $allowExt) {
                    if ($this->endsWith($v, $allowExt)) {
                        $dir = WEB_ROOT . "upload/mini/default/" . date("Ymd");
                        if (!file_exists($dir) || !is_dir($dir)) {
                            mkdir($dir);
                        }
                        if (!file_exists(WEB_ROOT . "upload/mini/" . $v)) {
                            $comporess = new ImgController('upload/' . $v);
                            $comporess->compressImg('upload/mini/' . $v);
                        }
                    }
                }
            }
        }
        if (isset($data['file_url']) && $data['file_url'] != '') {
            foreach ($allowExts as $allowExt) {
                if ($this->endsWith($data['file_url'], $allowExt)) {
                    $comporess = new ImgController('upload/' . $data['file_url']);
                    $comporess->compressImg('upload/mini/' . $data['file_url']);
                }
            }
        }
    }
    public function _initializeView()
    {
        $cmfAdminThemePath = config('template.cmf_admin_theme_path');
        $cmfAdminDefaultTheme = cmf_get_current_admin_theme();
        $themePath = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}";
        $root = cmf_get_root();
        $cdnSettings = cmf_get_option('cdn_settings');
        if (empty($cdnSettings['cdn_static_root'])) {
            $viewReplaceStr = [
                '__ROOT__' => $root,
                '__TMPL__' => "{$root}/{$themePath}",
                '__STATIC__' => "{$root}/static",
                '__WEB_ROOT__' => $root
            ];
        } else {
            $cdnStaticRoot = rtrim($cdnSettings['cdn_static_root'], '/');
            $viewReplaceStr = [
                '__ROOT__' => $root,
                '__TMPL__' => "{$cdnStaticRoot}/{$themePath}",
                '__STATIC__' => "{$cdnStaticRoot}/static",
                '__WEB_ROOT__' => $cdnStaticRoot
            ];
        }
        config('template.view_base', WEB_ROOT . "$themePath/");
        config('template.tpl_replace_string', $viewReplaceStr);
    }
    public function initMenu()
    {
    }
    private function checkAccess($userId)
    {
        if ($userId == 1) {
            return true;
        }
        $app = $this->request->module();
        $controller = $this->request->controller();
        $action = $this->request->action();
        $rule = $app . $controller . $action;
        $notRequire = ["adminIndexindex", "adminMainindex"];
        if (!in_array($rule, $notRequire)) {
            return cmf_auth_check($userId);
        } else {
            return true;
        }
    }
}