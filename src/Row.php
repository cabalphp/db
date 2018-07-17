<?php
namespace Cabal\DB;

// class Row implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
class Row implements \JsonSerializable
{
    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Rows
     */
    protected $rows;

    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Origin
     */
    protected $dbData;

    protected $loadedRelations = [];

    public function __construct(Origin $dbData, Rows $rows = null)
    {
        $this->dbData = $dbData;
        $this->rows = $rows;
    }

    public function getId()
    {
        $key = $this->getRows()->getTable()->getPrimaryKey();
        return $this->dbData->$key;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Rows
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @param string $foreignKey
     * @return \Cabal\DB\Row[]|\Cabal\DB\Rows
     */
    public function has($name, $foreignKeyOrCallback = null, $callback = null, $storeKey = null)
    {
        $foreignKey = null;
        if (func_num_args() < 4 && is_callable($foreignKeyOrCallback)) {
            $storeKey = $callback;
            $callback = $foreignKeyOrCallback;
        } else {
            $foreignKey = $foreignKeyOrCallback;
        }
        $foreignKey = $foreignKey ? : $this->rows
            ->getTable()
            ->getStructure()
            ->foreignKey($this->getRows()->getTable()->getTableName(), $name);
        $relations = $this->getRows()->loadHasRelations($name, $foreignKey, $callback, $storeKey);
        $this->loadedRelations[] = [
            $storeKey ? : $name, 'has', $foreignKey
        ];
        return $relations->group($foreignKey, $this->getId());
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @param string $foreignKey
     * @return \Cabal\DB\Row
     */
    public function belongs($name, $foreignKeyOrCallback = null, $callback = null, $storeKey = null)
    {
        $foreignKey = null;
        if (func_num_args() < 4 && is_callable($foreignKeyOrCallback)) {
            $storeKey = $callback;
            $callback = $foreignKeyOrCallback;
        } else {
            $foreignKey = $foreignKeyOrCallback;
        }
        $foreignKey = $foreignKey ? : $this->rows
            ->getTable()
            ->getStructure()
            ->foreignKey($name, $this->getRows()->getTable()->getTableName());
        $relations = $this->getRows()->loadBelongRelations($name, $foreignKey, $callback, $storeKey);
        $this->loadedRelations[] = [
            $storeKey ? : $name, 'belongs', $foreignKey
        ];
        return $relations->find($this->$foreignKey);
    }

    public function toArray()
    {
        $data = $this->dbData->toArray();
        $array = [];
        foreach ($data as $field => $val) {
            $array[$field] = $this->__get($field);
        }
        foreach ($this->loadedRelations as $loadedRelation) {
            list($storeKey, $type, $foreignKey) = $loadedRelation;
            switch ($type) {
                case 'has':
                    $array[$storeKey] = $this->getRows()->getExistsRelations($storeKey)->group($foreignKey, $this->getId())->toArray();
                    break;
                case 'belongs':
                    $array[$storeKey] = $this->getRows()->getExistsRelations($storeKey)->find($this->$foreignKey)->toArray();
                    break;
            }
        }
        return $array;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function getDirty()
    {
        return $this->dbData->getDirty();
    }
    public function isDirty($key)
    {
        return $this->dbData->isDirty($key);
    }
    public function getOrigin($key = null)
    {
        return $this->dbData->getOrigin($key);
    }
    public function flushOrigin($key = null)
    {
        return $this->dbData->flushOrigin($key);
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Origin
     */
    public function getOriginData()
    {
        return $this->dbData;
    }

    public function __get($name)
    {
        return $this->dbData[$name] ?? null;
    }

    public function __set($name, $value)
    {
        return $this->dbData[$name] = $value;
    }

    public function __isset($key)
    {
        return isset($this->dbData[$key]);
    }

    public function __unset($key)
    {
        unset($this->dbData[$key]);
    }
}