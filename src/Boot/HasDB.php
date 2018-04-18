<?php
namespace Cabal\DB\Boot;

use Cabal\DB\Manager;

trait HasDB
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
            $this->cabalDB = new Manager($this->configure('db'));
        }
        return $this->cabalDB;
    }
}