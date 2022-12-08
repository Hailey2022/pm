<?php

use mindplay\test\traits\AliasTrait;
use mindplay\test\traits\InsteadofTraitA;


trait SimpleTrait
{
    
    protected $sampleFromTrait = 'test';

    
    public function runFromTrait()
    {
    }
}

class SimpleTraitTester
{
    use SimpleTrait, mindplay\test\traits\AnotherSimpleTrait;
}

trait InheritanceBaseTrait
{
    
    public function traitAndParent()
    {
    }

    
    public function baseTraitAndParent()
    {
    }
}

trait InheritanceTrait
{
    use InheritanceBaseTrait;

    
    public function traitAndParent()
    {
    }

    
    public function traitAndChild()
    {
    }

    
    public function traitAndParentAndChild()
    {
    }
}

class InheritanceBaseTraitTester
{
    
    public function baseTraitAndParent()
    {
    }

    
    public function traitAndParent()
    {
    }

    
    public function traitAndParentAndChild()
    {
    }
}

class InheritanceTraitTester extends InheritanceBaseTraitTester
{
    use InheritanceTrait;

    
    public function traitAndChild()
    {
    }

    
    public function traitAndParentAndChild()
    {
    }
}

class AliasBaseTraitTester
{
    
    public function baseTraitRun()
    {
    }

    
    public function traitRun()
    {
    }

    
    public function run()
    {
    }
}

class AliasTraitTester extends AliasBaseTraitTester
{
    use AliasTrait {
        AliasTrait::run as traitRun;
    }

    
    public function run()
    {
    }
}

class InsteadofBaseTraitTester
{
    
    public function trate()
    {
    }

    
    public function baseTrait()
    {
    }
}

class InsteadofTraitTester extends InsteadofBaseTraitTester
{
    use InsteadofTraitA, mindplay\test\traits\InsteadofTraitB {
        InsteadofTraitA::trate insteadof mindplay\test\traits\InsteadofTraitB;
        mindplay\test\traits\InsteadofTraitB::baseTrait insteadof InsteadofTraitA;
    }
}
