<?php

namespace mindplay\test\Sample;

use mindplay\annotations\Annotation;


class SampleAnnotation extends Annotation
{
    public $test = 'ok';
}

class DefaultSampleAnnotation extends SampleAnnotation
{

}
