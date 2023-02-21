<?php
namespace think\exception;
class PDOException extends DbException
{
    public function __construct(\PDOException $exception, array $config, $sql, $code = 10501)
    {
        $error = $exception->errorInfo;
        $this->setData('PDO Error Info', [
            'SQLSTATE'             => $error[0],
            'Driver Error Code'    => isset($error[1]) ? $error[1] : 0,
            'Driver Error Message' => isset($error[2]) ? $error[2] : '',
        ]);
        parent::__construct($exception->getMessage(), $config, $sql, $code);
    }
}
