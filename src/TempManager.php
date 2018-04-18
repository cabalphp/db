<?php
namespace Cabal\DB;

class TempManager
{
    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Manager
     */
    protected $dbManager;
    protected $connectionName;

    public function __construct($dbManager, $connectionName)
    {
        $this->dbManager = $dbManager;
        $this->connectionName = $connectionName;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\StructureInterface 
     */
    public function getStructure($name = null)
    {
        return $this->dbManager->getStructure($name ? : $this->connectionName);
    }


    /**
     * Undocumented function
     *
     * @param string $tableName
     * @return \Cabal\DB\Table
     */
    public function table($tableName)
    {
        return new Table(
            $this->dbManager,
            $this->connectionName,
            $tableName,
            $this->getStructure($this->connectionName)
        );
    }

    public function prepare($sql)
    {
        return $this->dbManager->getConnection()->prepare($sql);
    }

    public function query($sql, $params)
    {
        return $this->dbManager->getConnection()->query($sql, $params);
    }
}