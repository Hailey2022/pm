<?php










namespace think\db\exception;

use think\exception\DbException;

class ModelNotFoundException extends DbException
{
    protected $model;

    
    public function __construct($message, $model = '', array $config = [])
    {
        $this->message = $message;
        $this->model   = $model;

        $this->setData('Database Config', $config);
    }

    
    public function getModel()
    {
        return $this->model;
    }

}
