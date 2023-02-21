<?php
namespace mindplay\annotations\standard;
use mindplay\annotations\AnnotationException;
use mindplay\annotations\AnnotationFile;
use mindplay\annotations\IAnnotationFileAware;
use mindplay\annotations\IAnnotationParser;
use mindplay\annotations\Annotation;
class ParamAnnotation extends Annotation implements IAnnotationParser, IAnnotationFileAware
{
    public $type;
    public $name;
    protected $file;
    public static function parseAnnotation($value)
    {
        $parts = \explode(' ', \trim($value), 3);
        if (\count($parts) < 2) {
            return array();
        }
        return array('type' => $parts[0], 'name' => \substr($parts[1], 1));
    }
    public function initAnnotation(array $properties)
    {
        $this->map($properties, array('type', 'name'));
        parent::initAnnotation($properties);
        if (!isset($this->type)) {
            throw new AnnotationException('ParamAnnotation requires a type property');
        }
        if (!isset($this->name)) {
            throw new AnnotationException('ParamAnnotation requires a name property');
        }
        $this->type = $this->file->resolveType($this->type);
    }
    public function setAnnotationFile(AnnotationFile $file)
    {
        $this->file = $file;
    }
}
