<?php
namespace Cabal\DB;

use Cabal\DB\Manager;

trait ServerHasDB
{
    protected $cabalDB;

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Manager
     */
    public function db()
    {
        if (!$this->cabalDB) {
            $this->cabalDB = new Manager($this->configure('db'), $this->taskworker);
        }
        return $this->cabalDB;
    }
}