<?php



use mindplay\annotations\Annotation;
use mindplay\annotations\IAnnotationParser;
use mindplay\annotations\AnnotationException;
use mindplay\annotations\standard\TypeAnnotation;


class NoteAnnotation extends Annotation
{
    public $note;

    public function initAnnotation(array $params)
    {
        $this->map($params, array('note'));

        if (!isset($this->note)) {
            throw new AnnotationException("NoteAnnotation requires a note property");
        }
    }
}


class UselessAnnotation extends Annotation
{


}


class DocAnnotation extends Annotation implements IAnnotationParser
{
    public $value;

    public static function parseAnnotation($value)
    {
        return array('value' => intval($value));
    }
}


class SingleAnnotation extends Annotation
{
    public $test;
}


class OverrideAnnotation extends Annotation
{
    public $test;
}


class SampleAnnotation extends Annotation
{
    public $test;
}


class UninheritableAnnotation extends Annotation
{
    public $test;
}

class InheritUsageAnnotation extends SampleAnnotation
{


}


class UsageAndNonUsageAnnotation extends Annotation
{

}


class SingleNonUsageAnnotation extends Annotation
{


}

class WrongInterfaceAnnotation
{

}

class TypeAwareAnnotation extends TypeAnnotation
{


}

class NoUsageAnnotation
{

}




class TestBase
{
    
    protected $sample = 'test';

    
    protected $only_one;

    
    private $override_me;

    
    private $mixed;

    
    public function run()
    {
    }
}


class Test extends TestBase
{
    
    public $hello = 'World';
    
    private $override_me;
    
    private $mixed;

    
    public function run()
    {
    }
}


class FirstClass
{
    
    protected $prop;

    
    protected function someMethod()
    {

    }
}


class SecondClass extends FirstClass
{
    
    protected $prop;

    
    protected function someMethod()
    {

    }
}


class ThirdClass extends SecondClass
{
    
    protected $prop;

    
    protected function someMethod()
    {

    }
}



class TestClassExtendingCore extends ReflectionClass
{

}


class TestClassExtendingExtension extends SplFileObject
{

}


class TestClassExtendingUserDefinedBase
{

}


class TestClassExtendingUserDefined extends TestClassExtendingUserDefinedBase
{

}

class TestClassWrongInterface
{

}

class TestClassFileAwareAnnotation
{

    
    public $prop;

}

interface TestInterface
{

}

class BrokenParamAnnotationClass
{

    
    protected function brokenParamAnnotation($paramName)
    {

    }
}
