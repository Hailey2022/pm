<?php



namespace mindplay\demo\annotations;


use mindplay\annotations\AnnotationManager;

abstract class Package
{
    public static function register(AnnotationManager $annotationManager)
    {
        $annotationManager->registry['length'] = 'mindplay\demo\annotations\LengthAnnotation';
        $annotationManager->registry['required'] = 'mindplay\demo\annotations\RequiredAnnotation';
        $annotationManager->registry['text'] = 'mindplay\demo\annotations\TextAnnotation';
        $annotationManager->registry['range'] = 'mindplay\demo\annotations\RangeAnnotation';
     }
}
