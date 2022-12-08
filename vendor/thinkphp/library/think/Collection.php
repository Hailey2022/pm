<?php










namespace think;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    
    protected $items = [];

    public function __construct($items = [])
    {
        $this->items = $this->convertToArray($items);
    }

    public static function make($items = [])
    {
        return new static($items);
    }

    
    public function isEmpty()
    {
        return empty($this->items);
    }

    public function toArray()
    {
        return array_map(function ($value) {
            return ($value instanceof Model || $value instanceof self) ? $value->toArray() : $value;
        }, $this->items);
    }

    public function all()
    {
        return $this->items;
    }

    
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->convertToArray($items)));
    }

    
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    
    public function dictionary($items = null, &$indexKey = null)
    {
        if ($items instanceof self || $items instanceof Paginator) {
            $items = $items->all();
        }

        $items = is_null($items) ? $this->items : $items;

        if ($items && empty($indexKey)) {
            $indexKey = is_array($items[0]) ? 'id' : $items[0]->getPk();
        }

        if (isset($indexKey) && is_string($indexKey)) {
            return array_column($items, null, $indexKey);
        }

        return $items;
    }

    
    public function diff($items, $indexKey = null)
    {
        if ($this->isEmpty() || is_scalar($this->items[0])) {
            return new static(array_diff($this->items, $this->convertToArray($items)));
        }

        $diff       = [];
        $dictionary = $this->dictionary($items, $indexKey);

        if (is_string($indexKey)) {
            foreach ($this->items as $item) {
                if (!isset($dictionary[$item[$indexKey]])) {
                    $diff[] = $item;
                }
            }
        }

        return new static($diff);
    }

    
    public function intersect($items, $indexKey = null)
    {
        if ($this->isEmpty() || is_scalar($this->items[0])) {
            return new static(array_diff($this->items, $this->convertToArray($items)));
        }

        $intersect  = [];
        $dictionary = $this->dictionary($items, $indexKey);

        if (is_string($indexKey)) {
            foreach ($this->items as $item) {
                if (isset($dictionary[$item[$indexKey]])) {
                    $intersect[] = $item;
                }
            }
        }

        return new static($intersect);
    }

    
    public function keys()
    {
        $current = current($this->items);

        if (is_scalar($current)) {
            $array = $this->items;
        } elseif (is_array($current)) {
            $array = $current;
        } else {
            $array = $current->toArray();
        }

        return array_keys($array);
    }

    
    public function pop()
    {
        return array_pop($this->items);
    }

    
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    
    public function reverse()
    {
        return new static(array_reverse($this->items));
    }

    
    public function shift()
    {
        return array_shift($this->items);
    }

    
    public function push($value, $key = null)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    
    public function chunk($size, $preserveKeys = false)
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size, $preserveKeys) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    
    public function unshift($value, $key = null)
    {
        if (is_null($key)) {
            array_unshift($this->items, $value);
        } else {
            $this->items = [$key => $value] + $this->items;
        }
    }

    
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            $result = $callback($item, $key);

            if (false === $result) {
                break;
            } elseif (!is_object($item)) {
                $this->items[$key] = $result;
            }
        }

        return $this;
    }

    
    public function map(callable $callback)
    {
        return new static(array_map($callback, $this->items));
    }

    
    public function filter(callable $callback = null)
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    
    public function where($field, $operator, $value = null)
    {
        if (is_null($value)) {
            $value    = $operator;
            $operator = '=';
        }

        return $this->filter(function ($data) use ($field, $operator, $value) {
            if (strpos($field, '.')) {
                list($field, $relation) = explode('.', $field);

                $result = isset($data[$field][$relation]) ? $data[$field][$relation] : null;
            } else {
                $result = isset($data[$field]) ? $data[$field] : null;
            }

            switch (strtolower($operator)) {
                case '===':
                    return $result === $value;
                case '!==':
                    return $result !== $value;
                case '!=':
                case '<>':
                    return $result != $value;
                case '>':
                    return $result > $value;
                case '>=':
                    return $result >= $value;
                case '<':
                    return $result < $value;
                case '<=':
                    return $result <= $value;
                case 'like':
                    return is_string($result) && false !== strpos($result, $value);
                case 'not like':
                    return is_string($result) && false === strpos($result, $value);
                case 'in':
                    return is_scalar($result) && in_array($result, $value, true);
                case 'not in':
                    return is_scalar($result) && !in_array($result, $value, true);
                case 'between':
                    list($min, $max) = is_string($value) ? explode(',', $value) : $value;
                    return is_scalar($result) && $result >= $min && $result <= $max;
                case 'not between':
                    list($min, $max) = is_string($value) ? explode(',', $value) : $value;
                    return is_scalar($result) && $result > $max || $result < $min;
                case '==':
                case '=':
                default:
                    return $result == $value;
            }
        });
    }

    
    public function column($columnKey, $indexKey = null)
    {
        return array_column($this->toArray(), $columnKey, $indexKey);
    }

    
    public function sort(callable $callback = null)
    {
        $items = $this->items;

        $callback = $callback ?: function ($a, $b) {
            return $a == $b ? 0 : (($a < $b) ? -1 : 1);

        };

        uasort($items, $callback);

        return new static($items);
    }

    
    public function order($field, $order = null, $intSort = true)
    {
        return $this->sort(function ($a, $b) use ($field, $order, $intSort) {
            $fieldA = isset($a[$field]) ? $a[$field] : null;
            $fieldB = isset($b[$field]) ? $b[$field] : null;

            if ($intSort) {
                return 'desc' == strtolower($order) ? $fieldB >= $fieldA : $fieldA >= $fieldB;
            } else {
                return 'desc' == strtolower($order) ? strcmp($fieldB, $fieldA) : strcmp($fieldA, $fieldB);
            }
        });
    }

    
    public function shuffle()
    {
        $items = $this->items;

        shuffle($items);

        return new static($items);
    }

    
    public function slice($offset, $length = null, $preserveKeys = false)
    {
        return new static(array_slice($this->items, $offset, $length, $preserveKeys));
    }

    
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    //Countable
    public function count()
    {
        return count($this->items);
    }

    //IteratorAggregate
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    //JsonSerializable
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    
    public function toJson($options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->toArray(), $options);
    }

    public function __toString()
    {
        return $this->toJson();
    }

    
    protected function convertToArray($items)
    {
        if ($items instanceof self) {
            return $items->all();
        }

        return (array) $items;
    }
}
