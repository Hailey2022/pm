<?php



namespace mindplay\annotations;


class AnnotationManager
{
    const CACHE_FORMAT_VERSION = 3;

    const MEMBER_CLASS = 'class';

    const MEMBER_PROPERTY = 'property';

    const MEMBER_METHOD = 'method';

    
    public $autoload = true;

    
    public $suffix = 'Annotation';

    
    public $namespace = '';

    
    public $cache;

    
    public $registry = array(
        'api'            => false,
        'abstract'       => false,
        'access'         => false,
        'author'         => false,
        'category'       => false,
        'copyright'      => false,
        'deprecated'     => false,
        'example'        => false,
        'filesource'     => false,
        'final'          => false,
        'global'         => false,
        'ignore'         => false,
        'internal'       => false,
        'license'        => false,
        'link'           => false,
        'method'         => 'mindplay\annotations\standard\MethodAnnotation',
        'name'           => false,
        'package'        => false,
        'param'          => 'mindplay\annotations\standard\ParamAnnotation',
        'property'       => 'mindplay\annotations\standard\PropertyAnnotation',
        'property-read'  => 'mindplay\annotations\standard\PropertyReadAnnotation',
        'property-write' => 'mindplay\annotations\standard\PropertyWriteAnnotation',
        'return'         => 'mindplay\annotations\standard\ReturnAnnotation',
        'see'            => false,
        'since'          => false,
        'source'         => false,
        'static'         => false,
        'staticvar'      => false,
        'subpackage'     => false,
        'todo'           => false,
        'tutorial'       => false,
        'throws'         => false,
        'type'           => 'mindplay\annotations\standard\TypeAnnotation',
        'usage'          => 'mindplay\annotations\UsageAnnotation',
        'stop'           => 'mindplay\annotations\StopAnnotation',
        'uses'           => false,
        'var'            => 'mindplay\annotations\standard\VarAnnotation',
        'version'        => false,
    );

    
    public $debug = false;

    
    protected $parser;

    
    protected $files = array();

    
    protected $annotations = array();

    
    protected $initialized = array();

    
    protected $usage = array();

    
    private $_usageAnnotation;

    
    private $_cacheSeed = '';

    
    private $_traitsSupported;

    
    public function __construct($cacheSeed = '')
    {
        $this->_cacheSeed = $cacheSeed;
        $this->_usageAnnotation = new UsageAnnotation();
        $this->_usageAnnotation->class = true;
        $this->_usageAnnotation->inherited = true;
        $this->_traitsSupported = \version_compare(PHP_VERSION, '5.4.0', '>=');
    }

    
    public function getParser()
    {
        if (!isset($this->parser)) {
            $this->parser = new AnnotationParser($this);
            $this->parser->debug = $this->debug;
            $this->parser->autoload = $this->autoload;
        }

        return $this->parser;
    }

    
    protected function getAnnotationFile($path)
    {
        if (!isset($this->files[$path])) {
            if ($this->cache === null) {
                throw new AnnotationException("AnnotationManager::\$cache is not configured");
            }

            if ($this->cache === false) {
                # caching is disabled
                $code = $this->getParser()->parseFile($path);
                $data = eval($code);
            } else {
                $checksum = \crc32($path . ':' . $this->_cacheSeed . ':' . self::CACHE_FORMAT_VERSION);
                $key = \basename($path) . '-' . \sprintf('%x', $checksum);

                if (($this->cache->exists($key) === false) || (\filemtime($path) > $this->cache->getTimestamp($key))) {
                    $code = $this->getParser()->parseFile($path);
                    $this->cache->store($key, $code);
                }

                $data = $this->cache->fetch($key);
            }

            $this->files[$path] = new AnnotationFile($path, $data);
        }

        return $this->files[$path];
    }

    
    public function resolveName($name)
    {
        if (\strpos($name, '\\') !== false) {
            return $name . $this->suffix; 
        }

        $type = \lcfirst($name);

        if (isset($this->registry[$type])) {
            return $this->registry[$type]; 
        }

        $type = \ucfirst(\strtr($name, '-', '_')) . $this->suffix;

        return \strlen($this->namespace)
            ? $this->namespace . '\\' . $type
            : $type;
    }

    
    protected function getAnnotations($class_name, $member_type = self::MEMBER_CLASS, $member_name = null)
    {
        $key = $class_name . ($member_name ? '::' . $member_name : '');

        if (!isset($this->initialized[$key])) {
            $annotations = array();
            $classAnnotations = array();

            if ($member_type !== self::MEMBER_CLASS) {
                $classAnnotations = $this->getAnnotations($class_name, self::MEMBER_CLASS);
            }

            $reflection = new \ReflectionClass($class_name);

            if ($reflection->getFileName() && !$reflection->isInternal()) {
                $file = $this->getAnnotationFile($reflection->getFileName());
            }

            $inherit = true; 

            if (isset($file)) {
                if (isset($file->data[$key])) {
                    foreach ($file->data[$key] as $spec) {
                        $name = $spec['#name']; 
                        $type = $spec['#type'];

                        unset($spec['#name'], $spec['#type']);

                        if (!\class_exists($type, $this->autoload)) {
                            throw new AnnotationException("Annotation type '{$type}' does not exist");
                        }

                        $annotation = new $type;

                        if (!($annotation instanceof IAnnotation)) {
                            throw new AnnotationException("Annotation type '{$type}' does not implement the mandatory IAnnotation interface");
                        }

                        if ($annotation instanceof IAnnotationFileAware) {
                            $annotation->setAnnotationFile($file);
                        }

                        $annotation->initAnnotation($spec);

                        $annotations[] = $annotation;
                    }

                    if ($member_type === self::MEMBER_CLASS) {
                        $classAnnotations = $annotations;
                    }
                } else if ($this->_traitsSupported && $member_name !== null) {
                    $traitAnnotations = array();

                    if (isset($file->traitMethodOverrides[$class_name][$member_name])) {
                        list($traitName, $originalMemberName) = $file->traitMethodOverrides[$class_name][$member_name];
                        $traitAnnotations = $this->getAnnotations($traitName, $member_type, $originalMemberName);
                    } else {
                        foreach ($reflection->getTraitNames() as $traitName) {
                            if ($this->classHasMember($traitName, $member_type, $member_name)) {
                                $traitAnnotations = $this->getAnnotations($traitName, $member_type, $member_name);
                                break;
                            }
                        }
                    }

                    $annotations = \array_merge($traitAnnotations, $annotations);
                }
            }

            foreach ($classAnnotations as $classAnnotation) {
                if ($classAnnotation instanceof StopAnnotation) {
                    $inherit = false; 
                }
            }

            if ($inherit && $parent = get_parent_class($class_name)) {
                $parent_annotations = array();

                if ($parent !== __NAMESPACE__ . '\Annotation') {
                    foreach ($this->getAnnotations($parent, $member_type, $member_name) as $annotation) {
                        if ($this->getUsage(\get_class($annotation))->inherited) {
                            $parent_annotations[] = $annotation;
                        }
                    }
                }

                $annotations = \array_merge($parent_annotations, $annotations);
            }

            $this->annotations[$key] = $this->applyConstraints($annotations, $member_type);

            $this->initialized[$key] = true;
        }

        return $this->annotations[$key];
    }

    
    protected function classHasMember($className, $memberType, $memberName)
    {
        if ($memberType === self::MEMBER_METHOD) {
            return \method_exists($className, $memberName);
        } else if ($memberType === self::MEMBER_PROPERTY) {
            return \property_exists($className, \ltrim($memberName, '$'));
        }
        return false;
    }

    
    protected function applyConstraints(array $annotations, $member)
    {
        $result = array();
        $annotationCount = \count($annotations);

        foreach ($annotations as $outerIndex => $annotation) {
            $type = \get_class($annotation);
            $usage = $this->getUsage($type);

            
            if (!$usage->$member) {
                throw new AnnotationException("Annotation type '{$type}' cannot be applied to a {$member}");
            }

            if (!$usage->multiple) {
                
                for ($innerIndex = $outerIndex + 1; $innerIndex < $annotationCount; $innerIndex += 1) {
                    if (!$annotations[$innerIndex] instanceof $type) {
                        continue;
                    }

                    if ($usage->inherited) {
                        continue 2; 
                    }

                    throw new AnnotationException("Only one annotation of '{$type}' type may be applied to the same {$member}");
                }
            }

            $result[] = $annotation;
        }

        return $result;
    }

    
    protected function filterAnnotations(array $annotations, $type)
    {
        if (\substr($type, 0, 1) === '@') {
            $type = $this->resolveName(\substr($type, 1));
        }

        if ($type === false) {
            return array();
        }

        $result = array();

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $type) {
                $result[] = $annotation;
            }
        }

        return $result;
    }

    
    public function getUsage($class)
    {
        if ($class === $this->registry['usage']) {
            return $this->_usageAnnotation;
        }

        if (!isset($this->usage[$class])) {
            if (!\class_exists($class, $this->autoload)) {
                throw new AnnotationException("Annotation type '{$class}' does not exist");
            }

            $usage = $this->getAnnotations($class);

            if (\count($usage) === 0) {
                throw new AnnotationException("The class '{$class}' must have exactly one UsageAnnotation");
            } else {
                if (\count($usage) !== 1 || !($usage[0] instanceof UsageAnnotation)) {
                    throw new AnnotationException("The class '{$class}' must have exactly one UsageAnnotation (no other Annotations are allowed)");
                } else {
                    $usage = $usage[0];
                }
            }

            $this->usage[$class] = $usage;
        }

        return $this->usage[$class];
    }

    
    public function getClassAnnotations($class, $type = null)
    {
        if ($class instanceof \ReflectionClass) {
            $class = $class->getName();
        } elseif (\is_object($class)) {
            $class = \get_class($class);
        } else {
            $class = \ltrim($class, '\\');
        }

        if (!\class_exists($class, $this->autoload) &&
            !(\function_exists('trait_exists') && \trait_exists($class, $this->autoload))
        ) {
            if (\interface_exists($class, $this->autoload)) {
                throw new AnnotationException("Reading annotations from interface '{$class}' is not supported");
            }

            throw new AnnotationException("Unable to read annotations from an undefined class/trait '{$class}'");
        }

        if ($type === null) {
            return $this->getAnnotations($class);
        } else {
            return $this->filterAnnotations($this->getAnnotations($class), $type);
        }
    }

    
    public function getMethodAnnotations($class, $method = null, $type = null)
    {
        if ($class instanceof \ReflectionClass) {
            $class = $class->getName();
        } elseif ($class instanceof \ReflectionMethod) {
            $method = $class->name;
            $class = $class->class;
        } elseif (\is_object($class)) {
            $class = \get_class($class);
        } else {
            $class = \ltrim($class, '\\');
        }

        if (!\class_exists($class, $this->autoload)) {
            throw new AnnotationException("Unable to read annotations from an undefined class '{$class}'");
        }

        if (!\method_exists($class, $method)) {
            throw new AnnotationException("Unable to read annotations from an undefined method {$class}::{$method}()");
        }

        if ($type === null) {
            return $this->getAnnotations($class, self::MEMBER_METHOD, $method);
        } else {
            return $this->filterAnnotations($this->getAnnotations($class, self::MEMBER_METHOD, $method), $type);
        }
    }

    
    public function getPropertyAnnotations($class, $property = null, $type = null)
    {
        if ($class instanceof \ReflectionClass) {
            $class = $class->getName();
        } elseif ($class instanceof \ReflectionProperty) {
            $property = $class->name;
            $class = $class->class;
        } elseif (\is_object($class)) {
            $class = \get_class($class);
        } else {
            $class = \ltrim($class, '\\');
        }

        if (!\class_exists($class, $this->autoload)) {
            throw new AnnotationException("Unable to read annotations from an undefined class '{$class}'");
        }

        if (!\property_exists($class, $property)) {
            throw new AnnotationException("Unable to read annotations from an undefined property {$class}::\${$property}");
        }

        if ($type === null) {
            return $this->getAnnotations($class, self::MEMBER_PROPERTY, '$' . $property);
        } else {
            return $this->filterAnnotations($this->getAnnotations($class, self::MEMBER_PROPERTY, '$' . $property), $type);
        }
    }
}
