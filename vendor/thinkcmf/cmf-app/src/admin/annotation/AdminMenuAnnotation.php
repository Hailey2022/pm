<?php
namespace app\admin\annotation;
use mindplay\annotations\Annotation;
class AdminMenuAnnotation extends Annotation
{
    public $remark = '';
    public $icon = '';
    public $name = '';
    public $param = '';
    public $parent = '';
    public $display = false;
    public $order = 10000;
    public $hasView = true;
    public function initAnnotation(array $properties)
    {
        parent::initAnnotation($properties);
    }
}
