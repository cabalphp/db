<?php
namespace Cabal\DB;

class Origin implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
    protected $array;

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
}