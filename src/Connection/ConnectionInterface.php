<?php
namespace Cabal\DB\Connection;

interface ConnectionInterface
{
    // public function connect($server);
    public function lastInsertId();
    public function affectedRows();
    public function query($sql);
    // public function prepare($query);

    public function setId($id);
    public function setName($name);
    public function getId();
    public function getName();
    public function setWriteable($writeable = true);
    public function getWriteable($writeable);

    public function incrQueryTimes();
    public function getQueryTimes();
}