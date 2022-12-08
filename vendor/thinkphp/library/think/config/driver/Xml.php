<?php










namespace think\config\driver;

class Xml
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function parse()
    {
        if (is_file($this->config)) {
            $content = simplexml_load_file($this->config);
        } else {
            $content = simplexml_load_string($this->config);
        }

        $result = (array) $content;
        foreach ($result as $key => $val) {
            if (is_object($val)) {
                $result[$key] = (array) $val;
            }
        }

        return $result;
    }
}
