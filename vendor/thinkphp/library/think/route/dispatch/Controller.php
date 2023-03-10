<?php
namespace think\route\dispatch;
use think\route\Dispatch;
class Controller extends Dispatch
{
    public function exec()
    {
        $vars = array_merge($this->request->param(), $this->param);
        return $this->app->action(
            $this->dispatch, $vars,
            $this->rule->getConfig('url_controller_layer'),
            $this->rule->getConfig('controller_suffix')
        );
    }
}
