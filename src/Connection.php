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

    protected $discardConnection = false;

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
        $retryTimes = 0;
        $query = null;
        do {
            try {
                $query = $this->getRealConnection()->prepare($sql);
            } catch (\Exception $ex) {
                if (!$retryTimes && in_array($this->realConnection->errno, [2006, 2013])) {
                    $retryTimes++;
                    $config = $this->manager->getDbConfig(
                        $this->getRealConnection()->getName(),
                        $this->getRealConnection()->getWriteable()
                    );
                    $this->getRealConnection()->connect([
                        'host' => $config['host'],
                        'port' => $config['port'],
                        'user' => $config['user'],
                        'password' => $config['password'],
                        'database' => $config['database'],
                    ]);
                } else {
                    $this->discardConnection = true;
                    throw new Exception("[{$this->realConnection->errno}]" . $this->realConnection->error . "[SQL] {$sql};", intval($this->realConnection->errno));
                }
            }
        } while (!$query);

        if ($query === false) {
            $this->discardConnection = true;
            throw new Exception("[{$this->realConnection->errno}]" . $this->realConnection->error . "[SQL] {$sql};", intval($this->realConnection->errno));
        }
        return $query;
    }

    public function query($sql, $params = [])
    {
        $this->realConnection->incrQueryTimes();

        $startAt = microtime(true);
        $query = $this->prepare($sql);

        foreach ($params as $i => $param) {
            if ($param instanceof \DateTime) {
                $params[$i] = $param->format('Y-m-d H:i:s');
            }
        }

        $result = $query->execute($params);

        $this->queryLogs[] = [
            'sql' => $sql,
            'params' => $params,
            'millisecond' => (microtime(true) - $startAt) * 1000,
            'errno' => $this->realConnection->errno,
            'error' => $this->realConnection->error,
        ];

        if ($result === false) {
            $this->discardConnection = true;
            throw new Exception($this->realConnection->error . "[SQL] {$sql}; [PATAMS] " . json_encode($params), intval($this->realConnection->errno));
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
        $queryTimes = $this->realConnection->getQueryTimes();
        if ($queryTimes <= 500) {
            if ($this->discardConnection) {
                echo "Discard connection " . $this->realConnection->getId() . "\r\n";
            } else {
                $this->manager->push($this->realConnection);
            }
        }
    }
}