<?php
namespace Cabal\DB;

class Origin implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
    protected $array;

    protected $origin = [];

    public function __construct($array = [])
    {
        $this->array = $array;
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }
    public function __set($name, $value)
    {
        $this->offsetSet($name, $val);
    }

    public function count()
    {
        return count($this->array);
    }
    public function current()
    {
        return current($this->array);
    }
    public function key()
    {
        return key($this->array);
    }
    public function next()
    {
        next($this->keys);
    }
    public function rewind()
    {
        reset($this->array);
    }
    public function valid()
    {
        return current($this->array) !== false;
    }

    public function offsetExists($key)
    {
        return isset($this->array[$key]);
    }

    public function offsetGet($key)
    {
        return isset($this->array[$key]) ? $this->array[$key] : null;
    }

    public function offsetSet($key, $value)
    {
        if (!isset($this->origin[$key]) && (!isset($this->array[$key]) || $this->array[$key] !== $value)) {
            $this->origin[$key] = $this->array[$key] ?? null;
        }
        $this->array[$key] = $value;
    }
    public function offsetUnset($key)
    {
        unset($this->array[$key]);
    }

    public function toArray()
    {
        return $this->array;
    }

    public function jsonSerialize()
    {
        return $this->array;
    }

    public function getDirty()
    {
        if (!$this->origin) {
            return [];
        }
        return array_intersect_key($this->array, $this->origin);
    }

    public function isDirty($key)
    {
        return array_key_exists($key, $this->origin);
    }

    public function getOrigin($key = null)
    {
        if ($key) {
            return $this->origin[$key] ?? ($this->array[$key] ?? null);
        }
        return $this->origin;
    }

    public function flushOrigin($key = null)
    {
        if ($key) {
            unset($this->origin[$key]);
        }
        return $this->origin = [];
    }

    public function quietSet($key, $val)
    {
        $this->array[$key] = $val;
    }
}