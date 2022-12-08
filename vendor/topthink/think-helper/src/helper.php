<?php










if (!function_exists('class_basename')) {
    
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('class_uses_recursive')) {
    
    function class_uses_recursive($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_merge([$class => $class], class_parents($class)) as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (!function_exists('trait_uses_recursive')) {
    
    function trait_uses_recursive($trait)
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}
if (!function_exists('classnames')) {
    
    function classnames()
    {
        $args    = func_get_args();
        $classes = array_map(function ($arg) {
            if (is_array($arg)) {
                return implode(" ", array_filter(array_map(function ($expression, $class) {
                    return $expression ? $class : false;
                }, $arg, array_keys($arg))));
            }
            return $arg;
        }, $args);
        return implode(" ", array_filter($classes));
    }
}