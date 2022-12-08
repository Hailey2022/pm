<?php

namespace mindplay\test\Sample;

class OrphanedAnnotations
{

    
    public function someMethod()
    {
        $a = 5;

        
        if (false) {
            $a = 6;
        }
        

        $a = 5;
    }

}
