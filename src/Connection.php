<?php
namespace Cabal\DB;

use Cabal\DB\Connection\ConnectionInterface;



class Connection
{
    protected $manager;

    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Connection\ConnectionInterface
     */
    protected $realConnection;

    protected $lastActivedAt = 0;

    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Structure
     */
    protected $structure;

    protected $queryLogs = [];

    public function __construct(Manager $manager, ConnectionInterface $realConnection, $structure = null)
    {
        $this->manager = $manager;
        $this->realConnection = $realConnection;
        $this->structure = $structure;
    }

    public function setStructure($structure)
    {
        $this->structure = $structure;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Connection\ConnectionInterface
     */
    public function getRealConnection()
    {
        $this->lastActivedAt = time();
        return $this->realConnection;
    }

    /**
     * Undocumented function
     *
     * @param [type] $structure
     * @return \Cabal\DB\Structure
     */
    public function getStructure()
    {
        if (!$this->structure) {
            $this->structure = new Structure();
        }
        return $this->structure;
    }

    public function getPrimaryKey($table)
    {
        return $this->getStructure()->primaryKey($table);
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
            $this->manager,
            $this,
            $tableName
        );
    }


    public function prepare($sql)
    {
        $query = $this->getRealConnection()->prepare($sql);
        if ($query === false) {
            throw new Exception($this->realConnection->error . "SQL: {$sql}", $this->realConnection->errno);
        }
        return $query;
    }

    public function query($sql, $params = [])
    {
        $startAt = microtime(true);
        $query = $this->prepare($sql);
        $result = $query->execute($params);

        $this->queryLogs[] = [
            'sql' => $sql,
            'params' => $params,
            'millisecond' => (microtime(true) - $startAt) * 1000,
            'errno' => $this->realConnection->errno,
            'error' => $this->realConnection->error,
        ];

        if ($result === false && $this->realConnection->errno) {
            throw new Exception($this->realConnection->error, $this->realConnection->errno);
        }
        return $result;
    }

    public function begin()
    {
        $this->getRealConnection()->query("BEGIN;");
    }
    public function rollback()
    {
        $this->getRealConnection()->query("ROLLBACK;");
    }
    public function commit()
    {
        $this->getRealConnection()->query("COMMIT;");
    }
    public function transaction(\Closure $callable)
    {
        $this->begin();
        try {
            $result = $callable($this);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        return $result;
    }

    public function affectedRows()
    {
        return $this->realConnection->affectedRows();
    }

    public function lastInsertId()
    {
        return $this->realConnection->lastInsertId();
    }

    public function getQueryLogs()
    {
        return $this->queryLogs;
    }

    public function __destruct()
    {
        if ($this->realConnection->getQueryTimes() <= 2000) {
            $this->manager->push($this->realConnection);
        }
    }
}