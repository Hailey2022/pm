<?php

namespace mindplay\test\traits;

trait AnotherSimpleTrait
{
    
    protected $sampleFromAnotherTrait = 'test';

    
    public function runFromAnotherTrait()
    {
    }
}

trait AliasBaseTrait
{
    
    public function run()
    {
    }
}

trait AliasTrait
{
    use \mindplay\test\traits\AliasBaseTrait {
        \mindplay\test\traits\AliasBaseTrait::run as baseTraitRun;
    }

    
    public function run()
    {
    }
}

trait InsteadofBaseTraitA
{
    
    public function baseTrait()
    {
    }
}

trait InsteadofBaseTraitB
{
    
    public function baseTrait()
    {
    }
}

trait InsteadofTraitA
{
    use InsteadofBaseTraitA, InsteadofBaseTraitB {
        InsteadofBaseTraitA::baseTrait insteadof InsteadofBaseTraitB;
    }

    
    public function trate()
    {
    }
}

trait InsteadofTraitB
{
    use InsteadofBaseTraitA, InsteadofBaseTraitB {
        InsteadofBaseTraitB::baseTrait insteadof InsteadofBaseTraitA;
    }

    
    public function trate()
    {
    }
}
