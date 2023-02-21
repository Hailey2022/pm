<?php
namespace think\response;
use think\Collection;
use think\Model;
use think\Response;
class Xml extends Response
{
    protected $options = [
        'root_node' => 'think',
        'root_attr' => '',
        //数字索引的子节点名
        'item_node' => 'item',
        'item_key'  => 'id',
        'encoding'  => 'utf-8',
    ];
    protected $contentType = 'text/xml';
    protected function output($data)
    {
        if (is_string($data)) {
            if (0 !== strpos($data, '<?xml')) {
                $encoding = $this->options['encoding'];
                $xml      = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
                $data     = $xml . $data;
            }
            return $data;
        }
        return $this->xmlEncode($data, $this->options['root_node'], $this->options['item_node'], $this->options['root_attr'], $this->options['item_key'], $this->options['encoding']);
    }
    protected function xmlEncode($data, $root, $item, $attr, $id, $encoding)
    {
        if (is_array($attr)) {
            $array = [];
            foreach ($attr as $key => $value) {
                $array[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $array);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml  = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml .= "<{$root}{$attr}>";
        $xml .= $this->dataToXml($data, $item, $id);
        $xml .= "</{$root}>";
        return $xml;
    }
    protected function dataToXml($data, $item, $id)
    {
        $xml = $attr = '';
        if ($data instanceof Collection || $data instanceof Model) {
            $data = $data->toArray();
        }
        foreach ($data as $key => $val) {
            if (is_numeric($key)) {
                $id && $attr = " {$id}=\"{$key}\"";
                $key         = $item;
            }
            $xml .= "<{$key}{$attr}>";
            $xml .= (is_array($val) || is_object($val)) ? $this->dataToXml($val, $item, $id) : $val;
            $xml .= "</{$key}>";
        }
        return $xml;
    }
}
