<?php



namespace mindplay\test\annotations;


use mindplay\annotations\AnnotationManager;

abstract class Package
{
    public static function register(AnnotationManager $annotationManager)
    {
        $annotationManager->registry['required'] = 'mindplay\test\annotations\RequiredAnnotation';
    }
}
