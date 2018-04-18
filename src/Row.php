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
    public function has($name, $foreignKey = null)
    {
        $foreignKey = $foreignKey ? : $this->rows
            ->getTable()
            ->getStructure()
            ->foreignKey($this->getRows()->getTable()->getTableName(), $name);
        $relations = $this->getRows()->loadHasRelations($name, $foreignKey);
        return $relations->group($foreignKey, $this->getId());
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @param string $foreignKey
     * @return \Cabal\DB\Row
     */
    public function belongs($name, $foreignKey = null)
    {
        $foreignKey = $foreignKey ? : $this->rows
            ->getTable()
            ->getStructure()
            ->foreignKey($name, $this->getRows()->getTable()->getTableName());
        $relations = $this->getRows()->loadBelongRelations($name, $foreignKey);
        return $relations->find($this->$foreignKey);
    }

    public function toArray()
    {
        return $this->dbData->toArray();
    }

    public function jsonSerialize()
    {
        return $this->dbData->jsonSerialize();
    }

    public function __get($name)
    {
        return $this->dbData[$name] ?? null;
    }


    public function __set($name, $value)
    {
        return $this->dbData[$name] = $value;
    }
}