<?php
class HTMLPurifier_HTMLModule_SafeScripting extends HTMLPurifier_HTMLModule
{
    public $name = 'SafeScripting';
    public function setup($config)
    {
        $allowed = $config->get('HTML.SafeScripting');
        $script = $this->addElement(
            'script',
            'Inline',
            'Optional:', 
            null,
            array(
                'type' => 'Enum#text/javascript',
                'src*' => new HTMLPurifier_AttrDef_Enum(array_keys($allowed),  true)
            )
        );
        $script->attr_transform_pre[] =
        $script->attr_transform_post[] = new HTMLPurifier_AttrTransform_ScriptRequired();
    }
}
