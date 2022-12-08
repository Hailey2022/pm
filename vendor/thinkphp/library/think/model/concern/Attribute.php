<?php










namespace think\model\concern;

use InvalidArgumentException;
use think\db\Expression;
use think\Exception;
use think\Loader;
use think\model\Relation;

trait Attribute
{
    
    protected $pk = 'id';

    
    protected $field = [];

    
    protected $json = [];

    
    protected $jsonAssoc = false;

    
    protected $jsonType = [];

    
    protected $disuse = [];

    
    protected $readonly = [];

    
    protected $type = [];

    
    private $data = [];

    
    private $set = [];

    
    private $origin = [];

    
    private $withAttr = [];

    
    public function getPk()
    {
        return $this->pk;
    }

    
    protected function isPk($key)
    {
        $pk = $this->getPk();
        if (is_string($pk) && $pk == $key) {
            return true;
        } elseif (is_array($pk) && in_array($key, $pk)) {
            return true;
        }

        return false;
    }

    
    public function getKey()
    {
        $pk = $this->getPk();
        if (is_string($pk) && array_key_exists($pk, $this->data)) {
            return $this->data[$pk];
        }

        return;
    }

    
    public function allowField($field)
    {
        if (is_string($field)) {
            $field = explode(',', $field);
        }

        $this->field = $field;

        return $this;
    }

    
    public function readonly($field)
    {
        if (is_string($field)) {
            $field = explode(',', $field);
        }

        $this->readonly = $field;

        return $this;
    }

    
    public function data($data, $value = null)
    {
        if (is_string($data)) {
            $this->data[$data] = $value;
            return $this;
        }

        
        $this->data = [];

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if ($this->disuse) {
            
            foreach ((array) $this->disuse as $key) {
                if (array_key_exists($key, $data)) {
                    unset($data[$key]);
                }
            }
        }

        if (true === $value) {
            
            foreach ($data as $key => $value) {
                $this->setAttr($key, $value, $data);
            }
        } elseif (is_array($value)) {
            foreach ($value as $name) {
                if (isset($data[$name])) {
                    $this->data[$name] = $data[$name];
                }
            }
        } else {
            $this->data = $data;
        }

        return $this;
    }

    
    public function appendData($data, $set = false)
    {
        if ($set) {
            
            foreach ($data as $key => $value) {
                $this->setAttr($key, $value, $data);
            }
        } else {
            if (is_object($data)) {
                $data = get_object_vars($data);
            }

            $this->data = array_merge($this->data, $data);
        }

        return $this;
    }

    
    public function getOrigin($name = null)
    {
        if (is_null($name)) {
            return $this->origin;
        }
        return array_key_exists($name, $this->origin) ? $this->origin[$name] : null;
    }

    
    public function getData($name = null)
    {
        if (is_null($name)) {
            return $this->data;
        } elseif (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        } elseif (array_key_exists($name, $this->relation)) {
            return $this->relation[$name];
        }
        throw new InvalidArgumentException('property not exists:' . static::class . '->' . $name);
    }

    
    public function getChangedData()
    {
        if ($this->force) {
            $data = $this->data;
        } else {
            $data = array_udiff_assoc($this->data, $this->origin, function ($a, $b) {
                if ((empty($a) || empty($b)) && $a !== $b) {
                    return 1;
                }

                return is_object($a) || $a != $b ? 1 : 0;
            });
        }

        if (!empty($this->readonly)) {
            
            foreach ($this->readonly as $key => $field) {
                if (isset($data[$field])) {
                    unset($data[$field]);
                }
            }
        }

        return $data;
    }

    
    public function setAttr($name, $value, $data = [])
    {
        if (isset($this->set[$name])) {
            return;
        }

        if (is_null($value) && $this->autoWriteTimestamp && in_array($name, [$this->createTime, $this->updateTime])) {
            
            $value = $this->autoWriteTimestamp($name);
        } else {
            
            $method = 'set' . Loader::parseName($name, 1) . 'Attr';

            if (method_exists($this, $method)) {
                $origin = $this->data;
                $value  = $this->$method($value, array_merge($this->data, $data));

                $this->set[$name] = true;
                if (is_null($value) && $origin !== $this->data) {
                    return;
                }
            } elseif (isset($this->type[$name])) {
                
                $value = $this->writeTransform($value, $this->type[$name]);
            }
        }

        
        $this->data[$name] = $value;
    }

    
    public function isAutoWriteTimestamp($auto)
    {
        $this->autoWriteTimestamp = $auto;

        return $this;
    }

    
    protected function autoWriteTimestamp($name)
    {
        if (isset($this->type[$name])) {
            $type = $this->type[$name];

            if (strpos($type, ':')) {
                list($type, $param) = explode(':', $type, 2);
            }

            switch ($type) {
                case 'datetime':
                case 'date':
                    $value = $this->formatDateTime('Y-m-d H:i:s.u');
                    break;
                case 'timestamp':
                case 'integer':
                default:
                    $value = time();
                    break;
            }
        } elseif (is_string($this->autoWriteTimestamp) && in_array(strtolower($this->autoWriteTimestamp), [
            'datetime',
            'date',
            'timestamp',
        ])) {
            $value = $this->formatDateTime('Y-m-d H:i:s.u');
        } else {
            $value = time();
        }

        return $value;
    }

    
    protected function writeTransform($value, $type)
    {
        if (is_null($value)) {
            return;
        }

        if ($value instanceof Expression) {
            return $value;
        }

        if (is_array($type)) {
            list($type, $param) = $type;
        } elseif (strpos($type, ':')) {
            list($type, $param) = explode(':', $type, 2);
        }

        switch ($type) {
            case 'integer':
                $value = (int) $value;
                break;
            case 'float':
                if (empty($param)) {
                    $value = (float) $value;
                } else {
                    $value = (float) number_format($value, $param, '.', '');
                }
                break;
            case 'boolean':
                $value = (bool) $value;
                break;
            case 'timestamp':
                if (!is_numeric($value)) {
                    $value = strtotime($value);
                }
                break;
            case 'datetime':
                $value = is_numeric($value) ? $value : strtotime($value);
                $value = $this->formatDateTime('Y-m-d H:i:s.u', $value);
                break;
            case 'object':
                if (is_object($value)) {
                    $value = json_encode($value, JSON_FORCE_OBJECT);
                }
                break;
            case 'array':
                $value = (array) $value;
            case 'json':
                $option = !empty($param) ? (int) $param : JSON_UNESCAPED_UNICODE;
                $value  = json_encode($value, $option);
                break;
            case 'serialize':
                $value = serialize($value);
                break;
        }

        return $value;
    }

    
    public function getAttr($name, &$item = null)
    {
        try {
            $notFound = false;
            $value    = $this->getData($name);
        } catch (InvalidArgumentException $e) {
            $notFound = true;
            $value    = null;
        }

        
        $fieldName = Loader::parseName($name);
        $method    = 'get' . Loader::parseName($name, 1) . 'Attr';

        if (isset($this->withAttr[$fieldName])) {
            if ($notFound && $relation = $this->isRelationAttr($name)) {
                $modelRelation = $this->$relation();
                $value         = $this->getRelationData($modelRelation);
            }

            $closure = $this->withAttr[$fieldName];
            $value   = $closure($value, $this->data);
        } elseif (method_exists($this, $method)) {
            if ($notFound && $relation = $this->isRelationAttr($name)) {
                $modelRelation = $this->$relation();
                $value         = $this->getRelationData($modelRelation);
            }

            $value = $this->$method($value, $this->data);
        } elseif (isset($this->type[$name])) {
            
            $value = $this->readTransform($value, $this->type[$name]);
        } elseif ($this->autoWriteTimestamp && in_array($name, [$this->createTime, $this->updateTime])) {
            if (is_string($this->autoWriteTimestamp) && in_array(strtolower($this->autoWriteTimestamp), [
                'datetime',
                'date',
                'timestamp',
            ])) {
                $value = $this->formatDateTime($this->dateFormat, $value);
            } else {
                $value = $this->formatDateTime($this->dateFormat, $value, true);
            }
        } elseif ($notFound) {
            $value = $this->getRelationAttribute($name, $item);
        }

        return $value;
    }

    
    protected function getRelationAttribute($name, &$item)
    {
        $relation = $this->isRelationAttr($name);

        if ($relation) {
            $modelRelation = $this->$relation();
            if ($modelRelation instanceof Relation) {
                $value = $this->getRelationData($modelRelation);

                if ($item && method_exists($modelRelation, 'getBindAttr') && $bindAttr = $modelRelation->getBindAttr()) {

                    foreach ($bindAttr as $key => $attr) {
                        $key = is_numeric($key) ? $attr : $key;

                        if (isset($item[$key])) {
                            throw new Exception('bind attr has exists:' . $key);
                        } else {
                            $item[$key] = $value ? $value->getAttr($attr) : null;
                        }
                    }

                    return false;
                }

                
                $this->relation[$name] = $value;

                return $value;
            }
        }

        throw new InvalidArgumentException('property not exists:' . static::class . '->' . $name);
    }

    
    protected function readTransform($value, $type)
    {
        if (is_null($value)) {
            return;
        }

        if (is_array($type)) {
            list($type, $param) = $type;
        } elseif (strpos($type, ':')) {
            list($type, $param) = explode(':', $type, 2);
        }

        switch ($type) {
            case 'integer':
                $value = (int) $value;
                break;
            case 'float':
                if (empty($param)) {
                    $value = (float) $value;
                } else {
                    $value = (float) number_format($value, $param, '.', '');
                }
                break;
            case 'boolean':
                $value = (bool) $value;
                break;
            case 'timestamp':
                if (!is_null($value)) {
                    $format = !empty($param) ? $param : $this->dateFormat;
                    $value  = $this->formatDateTime($format, $value, true);
                }
                break;
            case 'datetime':
                if (!is_null($value)) {
                    $format = !empty($param) ? $param : $this->dateFormat;
                    $value  = $this->formatDateTime($format, $value);
                }
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
            case 'array':
                $value = empty($value) ? [] : json_decode($value, true);
                break;
            case 'object':
                $value = empty($value) ? new \stdClass() : json_decode($value);
                break;
            case 'serialize':
                try {
                    $value = unserialize($value);
                } catch (\Exception $e) {
                    $value = null;
                }
                break;
            default:
                if (false !== strpos($type, '\\')) {
                    
                    $value = new $type($value);
                }
        }

        return $value;
    }

    
    public function withAttribute($name, $callback = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $key = Loader::parseName($key);

                $this->withAttr[$key] = $val;
            }
        } else {
            $name = Loader::parseName($name);

            $this->withAttr[$name] = $callback;
        }

        return $this;
    }
}
