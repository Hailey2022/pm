<?php










namespace think\config\driver;

class Ini
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function parse()
    {
        if (is_file($this->config)) {
            return parse_ini_file($this->config, true);
        } else {
            return parse_ini_string($this->config, true);
        }
    }
}
