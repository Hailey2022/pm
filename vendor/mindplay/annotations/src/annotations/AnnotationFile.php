<?php
namespace mindplay\annotations;
class AnnotationFile
{
    private static $simpleTypes = array(
        'array',
        'bool',
        'boolean',
        'callback',
        'double',
        'float',
        'int',
        'integer',
        'mixed',
        'number',
        'object',
        'string',
        'void',
    );
    public $data;
    public $path;
    public $namespace;
    public $uses;
    public $traitMethodOverrides;
    public function __construct($path, array $data)
    {
        $this->path = $path;
        $this->data = $data;
        $this->namespace = $data['#namespace'];
        $this->uses = $data['#uses'];
        if (isset($data['#traitMethodOverrides'])) {
            foreach ($data['#traitMethodOverrides'] as $class => $methods) {
                $this->traitMethodOverrides[$class] = \array_map(array($this, 'resolveMethod'), $methods);
            }
        }
    }
    public function resolveMethod($raw_method)
    {
        list($class, $method) = \explode('::', $raw_method, 2);
        return array(\ltrim($this->resolveType($class), '\\'), $method);
    }
    public function resolveType($raw_type)
    {
        $type_parts = \explode('[]', $raw_type, 2);
        $type = $type_parts[0];
        if (!$this->isSimple($type)) {
            if (isset($this->uses[$type])) {
                $type_parts[0] = $this->uses[$type];
            } elseif ($this->namespace && \substr($type, 0, 1) != '\\') {
                $type_parts[0] = $this->namespace . '\\' . $type;
            }
        }
        return \implode('[]', $type_parts);
    }
    protected function isSimple($type)
    {
        return \in_array(\strtolower($type), self::$simpleTypes);
    }
}
