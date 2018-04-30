<?php
namespace Cabal\DB\Connection;

class MySQLStmt
{
    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Connection\MySQL
     */
    protected $connection;

    /**
     * Undocumented variable
     *
     * @var \PDOStatement
     */
    protected $stmt;

    function __construct($connection, $stmt)
    {
        $this->connection = $connection;
        $this->stmt = $stmt;
    }

    function execute($params = [])
    {
        $result = $this->stmt->execute($params);
        list($errno,, $error) = $this->stmt->errorInfo();
        $this->connection->setErrno($errno);
        $this->connection->setError($error);
        $this->connection->setAffectedRows($this->stmt->rowCount());
        return $result ? $this->stmt->fetchAll(\PDO::FETCH_ASSOC) : $result;
    }
}