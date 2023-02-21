<?php
class HTMLPurifier_HTMLModule_TargetNoopener extends HTMLPurifier_HTMLModule
{
    public $name = 'TargetNoopener';
    public function setup($config) {
        $a = $this->addBlankElement('a');
        $a->attr_transform_post[] = new HTMLPurifier_AttrTransform_TargetNoopener();
    }
}
