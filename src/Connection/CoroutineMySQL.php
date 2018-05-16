<?php
namespace Cabal\DB\Connection;

class CoroutineMySQL extends \Swoole\Coroutine\MySQL implements ConnectionInterface
{
    protected $id;
    protected $name;
    protected $queryTimes = 0;

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

    public function lastInsertId()
    {
        return $this->insert_id;
    }

    public function affectedRows()
    {
        return $this->affected_rows;
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