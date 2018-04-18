<?php
namespace Cabal\DB;


class Connection
{
    protected $manager;

    protected $swooleConnection;

    protected $lastActivedAt = 0;

    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Structure
     */
    protected $structure;

    protected $queryLogs = [];

    public function __construct(Manager $manager, $swooleConnection, $structure = null)
    {
        $this->manager = $manager;
        $this->swooleConnection = $swooleConnection;
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
     * @return \Cabal\DB\Coroutine\MySQL
     */
    public function getSwooleConnection()
    {
        $this->lastActivedAt = time();
        return $this->swooleConnection;
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
        $query = $this->getSwooleConnection()->prepare($sql);
        if ($query === false) {
            throw new Exception($this->swooleConnection->error . "SQL: {$sql}", $this->swooleConnection->errno);
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
            'errno' => $this->swooleConnection->errno,
            'error' => $this->swooleConnection->error,
        ];

        if ($result === false && $this->swooleConnection->errno) {
            throw new Exception($this->swooleConnection->error, $this->swooleConnection->errno);
        }
        return $result;
    }

    public function begin()
    {
        $this->getSwooleConnection()->query("BEGIN;");
    }
    public function rollback()
    {
        $this->getSwooleConnection()->query("ROLLBACK;");
    }
    public function commit()
    {
        $this->getSwooleConnection()->query("COMMIT;");
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
        return $this->swooleConnection->affected_rows;
    }

    public function lastInsertId()
    {
        return $this->swooleConnection->insert_id;
    }

    public function getQueryLogs()
    {
        return $this->queryLogs;
    }

    public function __destruct()
    {
        $this->manager->push($this->swooleConnection);
    }
}