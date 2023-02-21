<?php
namespace mindplay\annotations;
class UsageAnnotation extends Annotation
{
    public $class = false;
    public $property = false;
    public $method = false;
    public $multiple = false;
    public $inherited = false;
}
