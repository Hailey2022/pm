<?php
class HTMLPurifier_AttrTransform_TargetNoopener extends HTMLPurifier_AttrTransform
{
    public function transform($attr, $config, $context)
    {
        if (isset($attr['rel'])) {
            $rels = explode(' ', $attr['rel']);
        } else {
            $rels = array();
        }
        if (isset($attr['target']) && !in_array('noopener', $rels)) {
            $rels[] = 'noopener';
        }
        if (!empty($rels) || isset($attr['rel'])) {
            $attr['rel'] = implode(' ', $rels);
        }
        return $attr;
    }
}
