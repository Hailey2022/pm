<?php
namespace think\helper;
class Arr
{
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }
    public static function sortRecursive($array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::sortRecursive($value);
            }
        }
        if (static::isAssoc($array)) {
            ksort($array);
        } else {
            sort($array);
        }
        return $array;
    }
}