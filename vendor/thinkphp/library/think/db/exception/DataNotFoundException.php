<?php










namespace think\db\exception;

use think\exception\DbException;

class DataNotFoundException extends DbException
{
    protected $table;

    
    public function __construct($message, $table = '', array $config = [])
    {
        $this->message = $message;
        $this->table   = $table;

        $this->setData('Database Config', $config);
    }

    
    public function getTable()
    {
        return $this->table;
    }
}
