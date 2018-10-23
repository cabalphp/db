<?php
namespace Cabal\DB;


use Countable;
use ArrayAccess;
use ArrayIterator;
use CachingIterator;
use IteratorAggregate;
use JsonSerializable;

class Paginate implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Rows
     */
    protected $items = array();

    protected $lastPage;
    protected $perPage;
    protected $currentPage;
    protected $total;


    protected $options = array();

    static $defaultOptions = array();


    public function __construct($items, $perPage, $currentPage, $total, $options = array())
    {
        $this->items = $items;
        $this->perPage = $perPage;
        $this->currentPage = intval($currentPage > 0 ? $currentPage : 1);
        $this->total = $total;
        $this->lastPage = ceil($total / $perPage);
        $this->options = array_merge(self::$defaultOptions, $options);
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Rows
     */
    public function getItems()
    {
        return $this->items;
    }

    public function setItems($items)
    {
        $this->items = $items;;
    }

    static function setViewFactory($viewFactory)
    {
        self::$viewFactory = $viewFactory;
    }

    static function setDefaultOptions($options)
    {
        self::$defaultOptions = array_merge(self::$defaultOptions, $options);
    }

    public function getIterator()
    {
        return $this->items;
    }

    public function getCachingIterator($flags = CachingIterator::CALL_TOSTRING)
    {
        return new CachingIterator($this->getIterator(), $flags);
    }

    public function count()
    {
        return count($this->items);
    }

    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    public function toArray()
    {
        return array(
            'lastPage' => $this->lastPage,
            'perPage' => $this->perPage,
            'currentPage' => $this->currentPage,
            'total' => $this->total,
            'offset' => ($this->currentPage - 1) * $this->perPage,
            'limit' => $this->perPage,
            'data' => $this->items->toArray(),
        );
    }

    public function getTotal()
    {
        return $this->total;
    }

    function getLastPage()
    {
        return $this->lastPage;
    }

    function getCurrentPage()
    {
        return $this->currentPage;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
    public function __toString()
    {
        return $this->toJson();
    }

}