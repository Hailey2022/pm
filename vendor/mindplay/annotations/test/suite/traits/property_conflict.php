<?php

trait PropertyConflictTraitOne
{
    
    protected $traitAndTraitAndParent = 1;

    
    protected $unannotatedTraitAndAnnotatedTrait = 1;

    
    protected $traitAndParentAndChild = 1;

    
    protected $traitAndChild = 1;
}

trait PropertyConflictTraitTwo
{
    
    protected $traitAndTraitAndParent = 1;

    protected $unannotatedTraitAndAnnotatedTrait = 1;
}

class PropertyConflictBaseTraitTester
{
    
    protected $traitAndTraitAndParent = 1;

    
    protected $traitAndParentAndChild = 1;
}


class PropertyConflictTraitTester extends PropertyConflictBaseTraitTester
{
    use PropertyConflictTraitTwo, PropertyConflictTraitOne;

    
    protected $traitAndChild = 1;

    
    protected $traitAndParentAndChild = 1;
}
