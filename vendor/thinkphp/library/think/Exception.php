<?php
namespace think;
class Exception extends \Exception
{
    protected $data = [];
    final protected function setData($label, array $data)
    {
        $this->data[$label] = $data;
    }
    final public function getData()
    {
        return $this->data;
    }
}
