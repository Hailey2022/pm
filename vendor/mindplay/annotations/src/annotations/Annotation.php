<?php



namespace mindplay\annotations;


abstract class Annotation implements IAnnotation
{
    
    public function __get($name)
    {
        throw new AnnotationException(\get_class($this) . "::\${$name} is not a valid property name");
    }

    
    public function __set($name, $value)
    {
        throw new AnnotationException(\get_class($this) . "::\${$name} is not a valid property name");
    }

    
    protected function map(array &$properties, array $indexes)
    {
        foreach ($indexes as $index => $name) {
            if (isset($properties[$index])) {
                $this->$name = $properties[$index];
                unset($properties[$index]);
            }
        }
    }

    
    public function initAnnotation(array $properties)
    {
        foreach ($properties as $name => $value) {
            $this->$name = $value;
        }
    }
}
