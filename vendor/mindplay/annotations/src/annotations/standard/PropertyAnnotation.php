<?php
namespace mindplay\annotations\standard;
use mindplay\annotations\Annotation;
use mindplay\annotations\AnnotationException;
use mindplay\annotations\AnnotationFile;
use mindplay\annotations\IAnnotationFileAware;
use mindplay\annotations\IAnnotationParser;
class PropertyAnnotation extends Annotation implements IAnnotationParser, IAnnotationFileAware
{
    public $type;
    public $name;
    public $description;
    protected $file;
    public static function parseAnnotation($value)
    {
        $parts = \explode(' ', \trim($value), 3);
        if (\count($parts) < 2) {
            return array();
        }
        $result = array('type' => $parts[0], 'name' => \substr($parts[1], 1));
        if (isset($parts[2])) {
            $result['description'] = $parts[2];
        }
        return $result;
    }
    public function initAnnotation(array $properties)
    {
        $this->map($properties, array('type', 'name', 'description'));
        parent::initAnnotation($properties);
        if (!isset($this->type)) {
            throw new AnnotationException(basename(__CLASS__).' requires a type property');
        }
        if (!isset($this->name)) {
            throw new AnnotationException(basename(__CLASS__).' requires a name property');
        }
        $this->type = $this->file->resolveType($this->type);
    }
    public function setAnnotationFile(AnnotationFile $file)
    {
        $this->file = $file;
    }
}
