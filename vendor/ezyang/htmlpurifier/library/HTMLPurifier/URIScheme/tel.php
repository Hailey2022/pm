<?php



class HTMLPurifier_URIScheme_tel extends HTMLPurifier_URIScheme
{
    
    public $browsable = false;

    
    public $may_omit_host = true;

    
    public function doValidate(&$uri, $config, $context)
    {
        $uri->userinfo = null;
        $uri->host     = null;
        $uri->port     = null;

        
        
        $uri->path = preg_replace('/(?!^\+)[^\dx]/', '',
                     
                     str_replace('X', 'x', $uri->path));

        return true;
    }
}


