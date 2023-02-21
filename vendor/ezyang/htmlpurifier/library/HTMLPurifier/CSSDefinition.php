<?php
class HTMLPurifier_CSSDefinition extends HTMLPurifier_Definition
{
    public $type = 'CSS';
    public $info = array();
    protected function doSetup($config)
    {
        $this->info['text-align'] = new HTMLPurifier_AttrDef_Enum(
            array('left', 'right', 'center', 'justify'),
            false
        );
        $border_style =
            $this->info['border-bottom-style'] =
            $this->info['border-right-style'] =
            $this->info['border-left-style'] =
            $this->info['border-top-style'] = new HTMLPurifier_AttrDef_Enum(
                array(
                    'none',
                    'hidden',
                    'dotted',
                    'dashed',
                    'solid',
                    'double',
                    'groove',
                    'ridge',
                    'inset',
                    'outset'
                ),
                false
            );
        $this->info['border-style'] = new HTMLPurifier_AttrDef_CSS_Multiple($border_style);
        $this->info['clear'] = new HTMLPurifier_AttrDef_Enum(
            array('none', 'left', 'right', 'both'),
            false
        );
        $this->info['float'] = new HTMLPurifier_AttrDef_Enum(
            array('none', 'left', 'right'),
            false
        );
        $this->info['font-style'] = new HTMLPurifier_AttrDef_Enum(
            array('normal', 'italic', 'oblique'),
            false
        );
        $this->info['font-variant'] = new HTMLPurifier_AttrDef_Enum(
            array('normal', 'small-caps'),
            false
        );
        $uri_or_none = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_Enum(array('none')),
                new HTMLPurifier_AttrDef_CSS_URI()
            )
        );
        $this->info['list-style-position'] = new HTMLPurifier_AttrDef_Enum(
            array('inside', 'outside'),
            false
        );
        $this->info['list-style-type'] = new HTMLPurifier_AttrDef_Enum(
            array(
                'disc',
                'circle',
                'square',
                'decimal',
                'lower-roman',
                'upper-roman',
                'lower-alpha',
                'upper-alpha',
                'none'
            ),
            false
        );
        $this->info['list-style-image'] = $uri_or_none;
        $this->info['list-style'] = new HTMLPurifier_AttrDef_CSS_ListStyle($config);
        $this->info['text-transform'] = new HTMLPurifier_AttrDef_Enum(
            array('capitalize', 'uppercase', 'lowercase', 'none'),
            false
        );
        $this->info['color'] = new HTMLPurifier_AttrDef_CSS_Color();
        $this->info['background-image'] = $uri_or_none;
        $this->info['background-repeat'] = new HTMLPurifier_AttrDef_Enum(
            array('repeat', 'repeat-x', 'repeat-y', 'no-repeat')
        );
        $this->info['background-attachment'] = new HTMLPurifier_AttrDef_Enum(
            array('scroll', 'fixed')
        );
        $this->info['background-position'] = new HTMLPurifier_AttrDef_CSS_BackgroundPosition();
        $border_color =
            $this->info['border-top-color'] =
            $this->info['border-bottom-color'] =
            $this->info['border-left-color'] =
            $this->info['border-right-color'] =
            $this->info['background-color'] = new HTMLPurifier_AttrDef_CSS_Composite(
                array(
                    new HTMLPurifier_AttrDef_Enum(array('transparent')),
                    new HTMLPurifier_AttrDef_CSS_Color()
                )
            );
        $this->info['background'] = new HTMLPurifier_AttrDef_CSS_Background($config);
        $this->info['border-color'] = new HTMLPurifier_AttrDef_CSS_Multiple($border_color);
        $border_width =
            $this->info['border-top-width'] =
            $this->info['border-bottom-width'] =
            $this->info['border-left-width'] =
            $this->info['border-right-width'] = new HTMLPurifier_AttrDef_CSS_Composite(
                array(
                    new HTMLPurifier_AttrDef_Enum(array('thin', 'medium', 'thick')),
                    new HTMLPurifier_AttrDef_CSS_Length('0') //disallow negative
                )
            );
        $this->info['border-width'] = new HTMLPurifier_AttrDef_CSS_Multiple($border_width);
        $this->info['letter-spacing'] = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_Enum(array('normal')),
                new HTMLPurifier_AttrDef_CSS_Length()
            )
        );
        $this->info['word-spacing'] = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_Enum(array('normal')),
                new HTMLPurifier_AttrDef_CSS_Length()
            )
        );
        $this->info['font-size'] = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_Enum(
                    array(
                        'xx-small',
                        'x-small',
                        'small',
                        'medium',
                        'large',
                        'x-large',
                        'xx-large',
                        'larger',
                        'smaller'
                    )
                ),
                new HTMLPurifier_AttrDef_CSS_Percentage(),
                new HTMLPurifier_AttrDef_CSS_Length()
            )
        );
        $this->info['line-height'] = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_Enum(array('normal')),
                new HTMLPurifier_AttrDef_CSS_Number(true), 
                new HTMLPurifier_AttrDef_CSS_Length('0'),
                new HTMLPurifier_AttrDef_CSS_Percentage(true)
            )
        );
        $margin =
            $this->info['margin-top'] =
            $this->info['margin-bottom'] =
            $this->info['margin-left'] =
            $this->info['margin-right'] = new HTMLPurifier_AttrDef_CSS_Composite(
                array(
                    new HTMLPurifier_AttrDef_CSS_Length(),
                    new HTMLPurifier_AttrDef_CSS_Percentage(),
                    new HTMLPurifier_AttrDef_Enum(array('auto'))
                )
            );
        $this->info['margin'] = new HTMLPurifier_AttrDef_CSS_Multiple($margin);
        $padding =
            $this->info['padding-top'] =
            $this->info['padding-bottom'] =
            $this->info['padding-left'] =
            $this->info['padding-right'] = new HTMLPurifier_AttrDef_CSS_Composite(
                array(
                    new HTMLPurifier_AttrDef_CSS_Length('0'),
                    new HTMLPurifier_AttrDef_CSS_Percentage(true)
                )
            );
        $this->info['padding'] = new HTMLPurifier_AttrDef_CSS_Multiple($padding);
        $this->info['text-indent'] = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_CSS_Length(),
                new HTMLPurifier_AttrDef_CSS_Percentage()
            )
        );
        $trusted_wh = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_CSS_Length('0'),
                new HTMLPurifier_AttrDef_CSS_Percentage(true),
                new HTMLPurifier_AttrDef_Enum(array('auto', 'initial', 'inherit'))
            )
        );
        $trusted_min_wh = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_CSS_Length('0'),
                new HTMLPurifier_AttrDef_CSS_Percentage(true),
                new HTMLPurifier_AttrDef_Enum(array('initial', 'inherit'))
            )
        );
        $trusted_max_wh = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_CSS_Length('0'),
                new HTMLPurifier_AttrDef_CSS_Percentage(true),
                new HTMLPurifier_AttrDef_Enum(array('none', 'initial', 'inherit'))
            )
        );
        $max = $config->get('CSS.MaxImgLength');
        $this->info['width'] =
        $this->info['height'] =
            $max === null ?
                $trusted_wh :
                new HTMLPurifier_AttrDef_Switch(
                    'img',
                    new HTMLPurifier_AttrDef_CSS_Composite(
                        array(
                            new HTMLPurifier_AttrDef_CSS_Length('0', $max),
                            new HTMLPurifier_AttrDef_Enum(array('auto'))
                        )
                    ),
                    $trusted_wh
                );
        $this->info['min-width'] =
        $this->info['min-height'] =
            $max === null ?
                $trusted_min_wh :
                new HTMLPurifier_AttrDef_Switch(
                    'img',
                    new HTMLPurifier_AttrDef_CSS_Composite(
                        array(
                            new HTMLPurifier_AttrDef_CSS_Length('0', $max),
                            new HTMLPurifier_AttrDef_Enum(array('initial', 'inherit'))
                        )
                    ),
                    $trusted_min_wh
                );
        $this->info['max-width'] =
        $this->info['max-height'] =
            $max === null ?
                $trusted_max_wh :
                new HTMLPurifier_AttrDef_Switch(
                    'img',
                    new HTMLPurifier_AttrDef_CSS_Composite(
                        array(
                            new HTMLPurifier_AttrDef_CSS_Length('0', $max),
                            new HTMLPurifier_AttrDef_Enum(array('none', 'initial', 'inherit'))
                        )
                    ),
                    $trusted_max_wh
                );
        $this->info['text-decoration'] = new HTMLPurifier_AttrDef_CSS_TextDecoration();
        $this->info['font-family'] = new HTMLPurifier_AttrDef_CSS_FontFamily();
        $this->info['font-weight'] = new HTMLPurifier_AttrDef_Enum(
            array(
                'normal',
                'bold',
                'bolder',
                'lighter',
                '100',
                '200',
                '300',
                '400',
                '500',
                '600',
                '700',
                '800',
                '900'
            ),
            false
        );
        $this->info['font'] = new HTMLPurifier_AttrDef_CSS_Font($config);
        $this->info['border'] =
        $this->info['border-bottom'] =
        $this->info['border-top'] =
        $this->info['border-left'] =
        $this->info['border-right'] = new HTMLPurifier_AttrDef_CSS_Border($config);
        $this->info['border-collapse'] = new HTMLPurifier_AttrDef_Enum(
            array('collapse', 'separate')
        );
        $this->info['caption-side'] = new HTMLPurifier_AttrDef_Enum(
            array('top', 'bottom')
        );
        $this->info['table-layout'] = new HTMLPurifier_AttrDef_Enum(
            array('auto', 'fixed')
        );
        $this->info['vertical-align'] = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_Enum(
                    array(
                        'baseline',
                        'sub',
                        'super',
                        'top',
                        'text-top',
                        'middle',
                        'bottom',
                        'text-bottom'
                    )
                ),
                new HTMLPurifier_AttrDef_CSS_Length(),
                new HTMLPurifier_AttrDef_CSS_Percentage()
            )
        );
        $this->info['border-spacing'] = new HTMLPurifier_AttrDef_CSS_Multiple(new HTMLPurifier_AttrDef_CSS_Length(), 2);
        $this->info['white-space'] = new HTMLPurifier_AttrDef_Enum(
            array('nowrap', 'normal', 'pre', 'pre-wrap', 'pre-line')
        );
        if ($config->get('CSS.Proprietary')) {
            $this->doSetupProprietary($config);
        }
        if ($config->get('CSS.AllowTricky')) {
            $this->doSetupTricky($config);
        }
        if ($config->get('CSS.Trusted')) {
            $this->doSetupTrusted($config);
        }
        $allow_important = $config->get('CSS.AllowImportant');
        foreach ($this->info as $k => $v) {
            $this->info[$k] = new HTMLPurifier_AttrDef_CSS_ImportantDecorator($v, $allow_important);
        }
        $this->setupConfigStuff($config);
    }
    protected function doSetupProprietary($config)
    {
        $this->info['scrollbar-arrow-color'] = new HTMLPurifier_AttrDef_CSS_Color();
        $this->info['scrollbar-base-color'] = new HTMLPurifier_AttrDef_CSS_Color();
        $this->info['scrollbar-darkshadow-color'] = new HTMLPurifier_AttrDef_CSS_Color();
        $this->info['scrollbar-face-color'] = new HTMLPurifier_AttrDef_CSS_Color();
        $this->info['scrollbar-highlight-color'] = new HTMLPurifier_AttrDef_CSS_Color();
        $this->info['scrollbar-shadow-color'] = new HTMLPurifier_AttrDef_CSS_Color();
        $this->info['-moz-opacity'] = new HTMLPurifier_AttrDef_CSS_AlphaValue();
        $this->info['-khtml-opacity'] = new HTMLPurifier_AttrDef_CSS_AlphaValue();
        $this->info['filter'] = new HTMLPurifier_AttrDef_CSS_Filter();
        $this->info['page-break-after'] =
        $this->info['page-break-before'] = new HTMLPurifier_AttrDef_Enum(
            array(
                'auto',
                'always',
                'avoid',
                'left',
                'right'
            )
        );
        $this->info['page-break-inside'] = new HTMLPurifier_AttrDef_Enum(array('auto', 'avoid'));
        $border_radius = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_CSS_Percentage(true), 
                new HTMLPurifier_AttrDef_CSS_Length('0') 
            ));
        $this->info['border-top-left-radius'] =
        $this->info['border-top-right-radius'] =
        $this->info['border-bottom-right-radius'] =
        $this->info['border-bottom-left-radius'] = new HTMLPurifier_AttrDef_CSS_Multiple($border_radius, 2);
        $this->info['border-radius'] = new HTMLPurifier_AttrDef_CSS_Multiple($border_radius, 4);
    }
    protected function doSetupTricky($config)
    {
        $this->info['display'] = new HTMLPurifier_AttrDef_Enum(
            array(
                'inline',
                'block',
                'list-item',
                'run-in',
                'compact',
                'marker',
                'table',
                'inline-block',
                'inline-table',
                'table-row-group',
                'table-header-group',
                'table-footer-group',
                'table-row',
                'table-column-group',
                'table-column',
                'table-cell',
                'table-caption',
                'none'
            )
        );
        $this->info['visibility'] = new HTMLPurifier_AttrDef_Enum(
            array('visible', 'hidden', 'collapse')
        );
        $this->info['overflow'] = new HTMLPurifier_AttrDef_Enum(array('visible', 'hidden', 'auto', 'scroll'));
        $this->info['opacity'] = new HTMLPurifier_AttrDef_CSS_AlphaValue();
    }
    protected function doSetupTrusted($config)
    {
        $this->info['position'] = new HTMLPurifier_AttrDef_Enum(
            array('static', 'relative', 'absolute', 'fixed')
        );
        $this->info['top'] =
        $this->info['left'] =
        $this->info['right'] =
        $this->info['bottom'] = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_CSS_Length(),
                new HTMLPurifier_AttrDef_CSS_Percentage(),
                new HTMLPurifier_AttrDef_Enum(array('auto')),
            )
        );
        $this->info['z-index'] = new HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new HTMLPurifier_AttrDef_Integer(),
                new HTMLPurifier_AttrDef_Enum(array('auto')),
            )
        );
    }
    protected function setupConfigStuff($config)
    {
        $support = "(for information on implementing this, see the " .
            "support forums) ";
        $allowed_properties = $config->get('CSS.AllowedProperties');
        if ($allowed_properties !== null) {
            foreach ($this->info as $name => $d) {
                if (!isset($allowed_properties[$name])) {
                    unset($this->info[$name]);
                }
                unset($allowed_properties[$name]);
            }
            foreach ($allowed_properties as $name => $d) {
                $name = htmlspecialchars($name);
                trigger_error("Style attribute '$name' is not supported $support", E_USER_WARNING);
            }
        }
        $forbidden_properties = $config->get('CSS.ForbiddenProperties');
        if ($forbidden_properties !== null) {
            foreach ($this->info as $name => $d) {
                if (isset($forbidden_properties[$name])) {
                    unset($this->info[$name]);
                }
            }
        }
    }
}
