<?php









namespace app\admin\annotation;

use mindplay\annotations\AnnotationException;
use mindplay\annotations\Annotation;


class AdminMenuRootAnnotation extends Annotation
{
    
    public $remark = '';

    
    public $icon = '';

    
    public $name = '';

    public $action = '';

    public $param = '';

    public $parent = '';

    public $display = false;

    public $order = 10000;

    
    public function initAnnotation(array $properties)
    {
        parent::initAnnotation($properties);
    }
}
