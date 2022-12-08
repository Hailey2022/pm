<?php










namespace think\exception;

use think\Exception;


class DbException extends Exception
{
    
    public function __construct($message, array $config = [], $sql = '', $code = 10500)
    {
        $this->message = $message;
        $this->code    = $code;

        $this->setData('Database Status', [
            'Error Code'    => $code,
            'Error Message' => $message,
            'Error SQL'     => $sql,
        ]);

        unset($config['username'], $config['password']);
        $this->setData('Database Config', $config);
    }

}
