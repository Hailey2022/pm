<?php
namespace mindplay\demo\annotations;
use mindplay\annotations\AnnotationException;
class LengthAnnotation extends ValidationAnnotationBase
{
    public $min = null;
    public $max = null;
    public function initAnnotation(array $properties)
    {
        if (isset($properties[0])) {
            if (isset($properties[1])) {
                $this->min = $properties[0];
                $this->max = $properties[1];
                unset($properties[1]);
            } else {
                $this->max = $properties[0];
            }
            unset($properties[0]);
        }
        parent::initAnnotation($properties);
        if ($this->min !== null && !is_int($this->min)) {
            throw new AnnotationException('LengthAnnotation requires an (integer) min property');
        }
        if ($this->max !== null && !is_int($this->max)) {
            throw new AnnotationException('LengthAnnotation requires an (integer) max property');
        }
        if ($this->min === null && $this->max === null) {
            throw new AnnotationException('LengthAnnotation requires a min and/or max property');
        }
    }
}
