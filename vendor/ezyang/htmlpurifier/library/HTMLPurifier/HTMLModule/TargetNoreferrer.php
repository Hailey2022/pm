<?php
class HTMLPurifier_HTMLModule_TargetNoreferrer extends HTMLPurifier_HTMLModule
{
    public $name = 'TargetNoreferrer';
    public function setup($config) {
        $a = $this->addBlankElement('a');
        $a->attr_transform_post[] = new HTMLPurifier_AttrTransform_TargetNoreferrer();
    }
}
