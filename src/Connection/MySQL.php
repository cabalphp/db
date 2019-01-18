<?php
namespace Cabal\DB\Connection;

class MySQL implements ConnectionInterface
{

    protected $id;
    protected $name;
    public $error;
    public $errno;
    public $connect_error;
    public $connect_errno;
    public $connected = false;
    protected $queryTimes = 0;
    protected $isWriteable = false;


    /**
     * Undocumented variable
     *
     * @var \PDO
     */
    protected $pdo;

    public function connect($server)
    {
        $server = array_merge([
            'port' => '3306',
            'charset' => 'utf8',
        ], $server);
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $server['host'],
            $server['port'],
            $server['database'],
            $server['charset']
        );
        $options = [];
        try {
            $this->pdo = new \PDO($dsn, $server['user'], $server['password'], $options);
            $this->connected = true;
        } catch (\PDOException $e) {
            $this->connect_error = $this->errno = $e->getCode();
            $this->connect_errno = $this->error = $e->getMessage();
        }
    }

    public function quote($string)
    {
        return $this->pdo->quote($string);
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    public function affectedRows()
    {
        return $this->affectedRows;
    }

    public function setAffectedRows($affectedRows)
    {
        $this->affectedRows = $affectedRows;
    }

    public function setError($error)
    {
        $this->error = $error;
    }
    public function setErrno($errno)
    {
        $this->errno = $errno;
    }


    public function query($sql)
    {
        $stmt = new MySQLStmt($this, $this->pdo->prepare($sql));
        return $stmt->execute();
    }

    public function prepare($sql)
    {
        return new MySQLStmt($this, $this->pdo->prepare($sql));
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
    public function setWriteable($writeable = true)
    {
        $this->isWriteable = $writeable;
    }
    public function getWriteable($writeable)
    {
        return $this->isWriteable;
    }


    public function incrQueryTimes()
    {
        return ++$this->queryTimes;
    }

    public function getQueryTimes()
    {
        return $this->queryTimes;
    }

}