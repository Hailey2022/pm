<?php









namespace cmf\controller;

class UserBaseController extends HomeBaseController
{

    public function initialize()
    {
        parent::initialize();
        $this->checkUserLogin();
    }


}