<?php
namespace Cabal\DB;

// class Row implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
class Model extends Row
{
    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Manager
     */
    static protected $dbManager;

    protected $connectionName;

    /**
     * Define table name
     *
     * @var [type]
     */
    protected $tableName;

    /**
     * Is the row exists in database
     *
     * @var boolean
     */
    protected $exists = false;

    // public function __construct(Origin $dbData, Rows $rows = null)
    public function __construct()
    {
        $this->dbData = new Origin();
    }

    static public function setDBManager($dbManager)
    {
        self::$dbManager = $dbManager;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Rows
     */
    public function getRows()
    {
        if (!$this->rows) {
            $this->rows = new Rows([$this->dbData], $this->newTable());
        }
        return $this->rows;
    }

    protected function newTable()
    {
        return new Table(
            static::$dbManager,
            $this->connectionName,
            $this->tableName,
            static::$dbManager->getStructure($this->connectionName)
        );
    }

    public function on($connectionName)
    {
        $this->connectionName = $connectionName;
        $this->getRows()->getTable()->setConnectionName($connectionName);
        $this->getRows()->getTable()->setStructure(static::$dbManager->getStructure($connectionName));
        return $this;
    }

    public function save()
    {
        if ($this->exists) {
            $data = $this->dbData->toArray();
            $primaryKey = $this->getRows()->getTable()->getPrimaryKey();
            unset($data[$primaryKey]);
            return $this->getRows()->getTable()
                ->where("`{$primaryKey}` = ?", $this->getId())
                ->update($data);
        } else {
            return $this->getRows()->getTable()->insert($this->dbData->toArray());
        }
    }

    static function query()
    {
        return $this->newTable();
    }
}