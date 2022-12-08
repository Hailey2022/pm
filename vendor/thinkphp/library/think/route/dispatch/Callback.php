<?php










namespace think\route\dispatch;

use think\route\Dispatch;

class Callback extends Dispatch
{
    public function exec()
    {
        
        $vars = array_merge($this->request->param(), $this->param);

        return $this->app->invoke($this->dispatch, $vars);
    }

}
