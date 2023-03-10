<?php
namespace app\admin\logic;
use app\admin\model\AdminMenuModel;
use app\admin\model\AuthRuleModel;
use think\facade\Env;
use mindplay\annotations\Annotations;
class MenuLogic
{
    public static function importMenus($app)
    {
        Annotations::$config['cache']                 = false;
        $annotationManager                            = Annotations::getManager();
        $annotationManager->registry['adminMenu']     = 'app\admin\annotation\AdminMenuAnnotation';
        $annotationManager->registry['adminMenuRoot'] = 'app\admin\annotation\AdminMenuRootAnnotation';
        $newMenus = [];
        if ($app == 'admin') {
            $filePatten         = CMF_ROOT . "vendor/thinkcmf/cmf-app/src/{$app}/controller/*Controller.php";
            $coreAppControllers = cmf_scan_dir($filePatten);
            $filePatten  = APP_PATH . $app . '/controller/*Controller.php';
            $controllers = cmf_scan_dir($filePatten);
            $controllers = array_merge($coreAppControllers, $controllers);
        } else if ($app == 'user') {
            $filePatten         = CMF_ROOT  . "vendor/thinkcmf/cmf-app/src/{$app}/controller/Admin*Controller.php";
            $coreAppControllers = cmf_scan_dir($filePatten);
            $filePatten  = APP_PATH . $app . '/controller/Admin*Controller.php';
            $controllers = cmf_scan_dir($filePatten);
            $controllers = array_merge($coreAppControllers, $controllers);
        } else {
            $filePatten  = APP_PATH . $app . '/controller/Admin*Controller.php';
            $controllers = cmf_scan_dir($filePatten);
        }
        if (!empty($controllers)) {
            foreach ($controllers as $controller) {
                $controller      = preg_replace('/\.php$/', '', $controller);
                $controllerName  = preg_replace("/Controller$/", '', $controller);
                $controllerClass = "app\\$app\\controller\\$controller";
                $menuAnnotations = Annotations::ofClass($controllerClass, '@adminMenuRoot');
                if (!empty($menuAnnotations)) {
                    foreach ($menuAnnotations as $menuAnnotation) {
                        $name      = $menuAnnotation->name;
                        $icon      = $menuAnnotation->icon;
                        $type      = 0;//1:????????????????????????,2:????????????????????????,0:???????????????
                        $action    = $menuAnnotation->action;
                        $status    = empty($menuAnnotation->display) ? 0 : 1;
                        $listOrder = floatval($menuAnnotation->order);
                        $param     = $menuAnnotation->param;
                        $remark    = $menuAnnotation->remark;
                        if (empty($menuAnnotation->parent)) {
                            $parentId = 0;
                        } else {
                            $parent      = explode('/', $menuAnnotation->parent);
                            $countParent = count($parent);
                            if ($countParent > 3) {
                                throw new \Exception($controllerClass . ':' . $action . '  @adminMenuRoot parent???????????????!');
                            }
                            $parentApp        = $app;
                            $parentController = $controllerName;
                            $parentAction     = '';
                            switch ($countParent) {
                                case 1:
                                    $parentAction = $parent[0];
                                    break;
                                case 2:
                                    $parentController = $parent[0];
                                    $parentAction     = $parent[1];
                                    break;
                                case 3:
                                    $parentApp        = $parent[0];
                                    $parentController = $parent[1];
                                    $parentAction     = $parent[2];
                                    break;
                            }
                            $findParentAdminMenu = AdminMenuModel::where([
                                'app'        => $parentApp,
                                'controller' => $parentController,
                                'action'     => $parentAction
                            ])->find();
                            if (empty($findParentAdminMenu)) {
                                $parentId = AdminMenuModel::insertGetId([
                                    'app'        => $parentApp,
                                    'controller' => $parentController,
                                    'action'     => $parentAction,
                                    'name'       => '--new--'
                                ]);
                            } else {
                                $parentId = $findParentAdminMenu['id'];
                            }
                        }
                        $findAdminMenu = AdminMenuModel::where([
                            'app'        => $app,
                            'controller' => $controllerName,
                            'action'     => $action
                        ])->find();
                        if (empty($findAdminMenu)) {
                            AdminMenuModel::insert([
                                'parent_id'  => $parentId,
                                'type'       => $type,
                                'status'     => $status,
                                'list_order' => $listOrder,
                                'app'        => $app,
                                'controller' => $controllerName,
                                'action'     => $action,
                                'param'      => $param,
                                'name'       => $name,
                                'icon'       => $icon,
                                'remark'     => $remark
                            ]);
                            $menuName = $name;
                            array_push($newMenus, "$app/$controllerName/$action ?????????");
                        } else {
                            if ($findAdminMenu['name'] == '--new--') {
                                AdminMenuModel::where([
                                    'app'        => $app,
                                    'controller' => $controllerName,
                                    'action'     => $action
                                ])->update([
                                    'parent_id'  => $parentId,
                                    'type'       => $type,
                                    'status'     => $status,
                                    'list_order' => $listOrder,
                                    'param'      => $param,
                                    'name'       => $name,
                                    'icon'       => $icon,
                                    'remark'     => $remark
                                ]);
                                $menuName = $name;
                            } else {
                                AdminMenuModel::where([
                                    'app'        => $app,
                                    'controller' => $controllerName,
                                    'action'     => $action
                                ])->update([
                                    //'parent_id' => $parentId,
                                    'type' => $type,
                                ]);
                                $menuName = $findAdminMenu['name'];
                            }
                            array_push($newMenus, "$app/$controllerName/$action ?????????????????????");
                        }
                        $authRuleName      = "{$app}/{$controllerName}/{$action}";
                        $findAuthRuleCount = AuthRuleModel::where([
                            'app'  => $app,
                            'name' => $authRuleName,
                            'type' => 'admin_url'
                        ])->count();
                        if ($findAuthRuleCount == 0) {
                            AuthRuleModel::insert([
                                'app'   => $app,
                                'name'  => $authRuleName,
                                'type'  => 'admin_url',
                                'param' => $param,
                                'title' => $menuName
                            ]);
                        } else {
                            AuthRuleModel::where([
                                'app'  => $app,
                                'name' => $authRuleName,
                                'type' => 'admin_url',
                            ])->update([
                                'param' => $param,
                                'title' => $menuName
                            ]);
                        }
                    }
                }
                $reflect = new \ReflectionClass($controllerClass);
                $methods = $reflect->getMethods(\ReflectionMethod::IS_PUBLIC);
                if (!empty($methods)) {
                    foreach ($methods as $method) {
                        if ($method->class == $controllerClass && strpos($method->name, '_') !== 0) {
                            $menuAnnotations = Annotations::ofMethod($controllerClass, $method->name, '@adminMenu');
                            if (!empty($menuAnnotations)) {
                                $menuAnnotation = $menuAnnotations[0];
                                $name      = $menuAnnotation->name;
                                $icon      = $menuAnnotation->icon;
                                $type      = $menuAnnotation->hasView ? 1 : 2;//1:????????????????????????,2:????????????????????????,0:???????????????
                                $action    = $method->name;
                                $status    = empty($menuAnnotation->display) ? 0 : 1;
                                $listOrder = floatval($menuAnnotation->order);
                                $param     = $menuAnnotation->param;
                                $remark    = $menuAnnotation->remark;
                                if (empty($menuAnnotation->parent)) {
                                    $parentId = 0;
                                } else {
                                    $parent      = explode('/', $menuAnnotation->parent);
                                    $countParent = count($parent);
                                    if ($countParent > 3) {
                                        throw new \Exception($controllerClass . ':' . $action . '  @menuRoot parent???????????????!');
                                    }
                                    $parentApp        = $app;
                                    $parentController = $controllerName;
                                    $parentAction     = '';
                                    switch ($countParent) {
                                        case 1:
                                            $parentAction = $parent[0];
                                            break;
                                        case 2:
                                            $parentController = $parent[0];
                                            $parentAction     = $parent[1];
                                            break;
                                        case 3:
                                            $parentApp        = $parent[0];
                                            $parentController = $parent[1];
                                            $parentAction     = $parent[2];
                                            break;
                                    }
                                    $findParentAdminMenu = AdminMenuModel::where([
                                        'app'        => $parentApp,
                                        'controller' => $parentController,
                                        'action'     => $parentAction
                                    ])->find();
                                    if (empty($findParentAdminMenu)) {
                                        $parentId = AdminMenuModel::insertGetId([
                                            'app'        => $parentApp,
                                            'controller' => $parentController,
                                            'action'     => $parentAction,
                                            'name'       => '--new--'
                                        ]);
                                    } else {
                                        $parentId = $findParentAdminMenu['id'];
                                    }
                                }
                                $findAdminMenu = AdminMenuModel::where([
                                    'app'        => $app,
                                    'controller' => $controllerName,
                                    'action'     => $action
                                ])->find();
                                if (empty($findAdminMenu)) {
                                    AdminMenuModel::insert([
                                        'parent_id'  => $parentId,
                                        'type'       => $type,
                                        'status'     => $status,
                                        'list_order' => $listOrder,
                                        'app'        => $app,
                                        'controller' => $controllerName,
                                        'action'     => $action,
                                        'param'      => $param,
                                        'name'       => $name,
                                        'icon'       => $icon,
                                        'remark'     => $remark
                                    ]);
                                    $menuName = $name;
                                    array_push($newMenus, "$app/$controllerName/$action ?????????");
                                } else {
                                    if ($findAdminMenu['name'] == '--new--') {
                                        AdminMenuModel::where([
                                            'app'        => $app,
                                            'controller' => $controllerName,
                                            'action'     => $action
                                        ])->update([
                                            'parent_id'  => $parentId,
                                            'type'       => $type,
                                            'status'     => $status,
                                            'list_order' => $listOrder,
                                            'param'      => $param,
                                            'name'       => $name,
                                            'icon'       => $icon,
                                            'remark'     => $remark
                                        ]);
                                        $menuName = $name;
                                    } else {
                                        AdminMenuModel::where([
                                            'app'        => $app,
                                            'controller' => $controllerName,
                                            'action'     => $action
                                        ])->update([
                                            //'parent_id' => $parentId,
                                            'type' => $type,
                                        ]);
                                        $menuName = $findAdminMenu['name'];
                                    }
                                    array_push($newMenus, "$app/$controllerName/$action ?????????");
                                }
                                $authRuleName      = "{$app}/{$controllerName}/{$action}";
                                $findAuthRuleCount = AuthRuleModel::where([
                                    'app'  => $app,
                                    'name' => $authRuleName,
                                    'type' => 'admin_url'
                                ])->count();
                                if ($findAuthRuleCount == 0) {
                                    AuthRuleModel::insert([
                                        'app'   => $app,
                                        'name'  => $authRuleName,
                                        'type'  => 'admin_url',
                                        'param' => $param,
                                        'title' => $menuName
                                    ]);
                                } else {
                                    AuthRuleModel::where([
                                        'app'  => $app,
                                        'name' => $authRuleName,
                                        'type' => 'admin_url',
                                    ])->update([
                                        'param' => $param,
                                        'title' => $menuName
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $newMenus;
    }
}
