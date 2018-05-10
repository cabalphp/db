<?php
namespace Cabal\DB;

use Swoole\Coroutine;

class Rows implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Table 
     */
    protected $table;
    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Origin[]
     */
    protected $rows = [];
    protected $realRows;
    protected $keys;
    protected $cursor = 0;
    protected $relations = [];

    protected $model;

    public function __construct(array $dbRows = [], Table $table = null, $realRows = null, $model = null)
    {
        foreach ($dbRows as $dbRow) {
            if ($dbRow instanceof Row) {
                $this[] = new Origin($dbRow->toArray());
            } elseif ($dbRow instanceof Origin) {
                $this[] = $dbRow;
            } elseif (is_array($dbRow)) {
                $this[] = new Origin($dbRow);
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid data "%s"; must be an array or object',
                    gettype($dbRow)
                ));
            }
        }
        $this->realRows = $realRows;
        $this->keys = array_keys($this->rows);
        $this->table = $table;
        $this->model = $model;
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Table 
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Row[]|\Cabal\DB\Rows
     */
    public function toDictionary($key = null)
    {
        $rows = [];
        $key = $key ? : $this->getTable()->getPrimaryKey();
        foreach ($this->rows as $row) {
            $rows[$row->$key] = $row;
        }
        $this->rows = $rows;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param [type] $id
     * @return \Cabal\DB\Row
     */
    public function find($id)
    {
        return isset($this->rows[$id]) ? $this->instanceRow($this->rows[$id], $this->realRows ? : $this) : null;
    }

    public function count()
    {
        return count($this->rows);
    }
    public function current()
    {
        return $this->instanceRow($this->rows[current($this->keys)], $this->realRows ? : $this);
    }
    public function key()
    {
        return current($this->keys);
    }
    public function next()
    {
        next($this->keys);
    }
    public function rewind()
    {
        $this->keys = array_keys($this->rows);
        reset($this->rows);
    }
    public function valid()
    {
        return current($this->keys) !== false;
    }

    /**
     * Undocumented function
     *
     * @param [type] $name
     * @param [type] $foreignKey
     * @return \Cabal\DB\Rows
     */
    public function loadHasRelations($name, $foreignKey, $callback = null, $storeKey = null)
    {
        $storeKey = $storeKey ? : $name;
        if (!isset($this->relations[$storeKey])) {
            $keys = array_unique($this->pluck($this->table->getPrimaryKey()));

            if (count($keys) > 0) {
                $relationRows = $this->table->similarTable($name);
                if (count($keys) === 1) {
                    $relationRows->where("`{$foreignKey}` = ?", $keys[0]);
                } else {
                    $relationRows->whereIn($foreignKey, $keys);
                }
                if ($callback) {
                    Coroutine::call_user_func_array($callback, [$relationRows]);
                }
                $relationRows = $relationRows->rows();
            } else {
                $relationRows = new Rows([], $this->table->similarTable($name));
            }
            $this->relations[$storeKey] = $relationRows;
        }
        return $this->relations[$storeKey];
    }

    /**
     * Undocumented function
     *
     * @param [type] $name
     * @param [type] $foreignKey
     * @return \Cabal\DB\Rows
     */
    public function loadBelongRelations($name, $foreignKey, $callback = null, $storeKey = null)
    {
        $storeKey = $storeKey ? : $name;
        if (!isset($this->relations[$storeKey])) {
            $keys = array_unique($this->pluck($foreignKey));
            if (count($keys) > 0) {
                $relationRows = $this->table->similarTable($name);
                $primaryKey = $this->table->similarTable($name)->getPrimaryKey();

                if (count($keys) === 1) {
                    $relationRows->where("`{$primaryKey}` = ?", $keys[0]);
                } else {
                    $relationRows->whereIn($primaryKey, $keys);
                }
                if ($callback) {
                    Coroutine::call_user_func_array($callback, [$relationRows]);
                }
                $relationRows = $relationRows->rows()->toDictionary();
            } else {
                $relationRows = new Rows([], $this->table->similarTable($name));
            }

            $this->relations[$storeKey] = $relationRows;
        }
        return $this->relations[$storeKey];
    }

    /**
     * Undocumented function
     *
     * @param [type] $field
     * @param [type] $id
     * @return \Cabal\DB\Rows
     */
    public function group($field, $id)
    {
        if (!isset($this->grouped[$field])) {
            foreach ($this->rows as $row) {
                if (!isset($this->grouped[$field][$row->$field])) {
                    $this->grouped[$field][$row->$field] = [];
                }
                $this->grouped[$field][$row->$field][] = $row;
            }
        }
        $origins = isset($this->grouped[$field][$id]) ? $this->grouped[$field][$id] : [];
        return new Rows($origins, $this->getTable(), $this, $this->model);
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\ROW
     */
    public function fetch()
    {
        if ($this->valid()) {
            $result = $this->current();
            $this->next();
            return $result;
        }
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\ROW
     */
    public function first()
    {
        $keys = array_keys($this->rows);
        return current($keys) === false ? null : $this->instanceRow($this->rows[current($keys)], $this->realRows ? : $this);
    }

    protected function instanceRow($origin)
    {
        $model = $this->model ? : Row::class;
        return new $model($origin, $this->realRows ? : $this);
    }

    public function pluck($field)
    {
        $array = [];
        foreach ($this->rows as $row) {
            $array[] = $row->$field;
        }
        return $array;
    }

    public function offsetExists($key)
    {
        return isset($this->rows[$key]);
    }

    public function offsetGet($key)
    {
        return isset($this->rows[$key]) ? $this->instanceRow($this->rows[$key], $this->realRows ? : $this) : null;
    }

    public function offsetSet($key, $value)
    {
        if ($value instanceof Row) {
            $this->rows[] = new Origin($value->toArray());
        } elseif ($value instanceof Origin) {
            $this->rows[] = $value;
        } elseif (is_array($value)) {
            $this->rows[] = new Origin($value);
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Invalid data "%s"; must be an array or object',
                gettype($value)
            ));
        }
    }

    public function offsetUnset($key)
    {
        unset($this->rows[$key]);
    }

    public function jsonSerialize()
    {
        $array = [];
        foreach ($this->rows as $row) {
            $row = $this->instanceRow($row, $this->realRows ? : $this);
            $array[] = $row->jsonSerialize();
        }
        return $array;
    }


}