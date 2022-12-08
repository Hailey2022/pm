<?php



namespace mindplay\annotations\standard;

use mindplay\annotations\AnnotationException;
use mindplay\annotations\AnnotationFile;
use mindplay\annotations\IAnnotationFileAware;
use mindplay\annotations\IAnnotationParser;
use mindplay\annotations\Annotation;


class ReturnAnnotation extends Annotation implements IAnnotationParser, IAnnotationFileAware
{
    
    public $type;

    
    protected $file;

    
    public static function parseAnnotation($value)
    {
        $parts = \explode(' ', \trim($value), 2);

        return array('type' => \array_shift($parts));
    }

    
    public function initAnnotation(array $properties)
    {
        $this->map($properties, array('type'));

        parent::initAnnotation($properties);

        if (!isset($this->type)) {
            throw new AnnotationException('ReturnAnnotation requires a type property');
        }

        $this->type = $this->file->resolveType($this->type);
    }

    
    public function setAnnotationFile(AnnotationFile $file)
    {
        $this->file = $file;
    }
}
