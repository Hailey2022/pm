<?php
namespace mindplay\annotations;
abstract class Annotations
{
    public static $config;
    private static $manager;
    public static function getManager()
    {
        if (!isset(self::$manager)) {
            self::$manager = new AnnotationManager;
        }
        if (\is_array(self::$config)) {
            foreach (self::$config as $key => $value) {
                self::$manager->$key = $value;
            }
        }
        return self::$manager;
    }
    public static function getUsage($class)
    {
        return self::getManager()->getUsage($class);
    }
    public static function ofClass($class, $type = null)
    {
        return self::getManager()->getClassAnnotations($class, $type);
    }
    public static function ofMethod($class, $method = null, $type = null)
    {
        return self::getManager()->getMethodAnnotations($class, $method, $type);
    }
    public static function ofProperty($class, $property = null, $type = null)
    {
        return self::getManager()->getPropertyAnnotations($class, $property, $type);
    }
}
