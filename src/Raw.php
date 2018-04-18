<?php
namespace Cabal\DB;


class Raw
{
    protected $raw;

    public function __construct($raw)
    {
        $this->raw = $raw;
    }

    public function toString()
    {
        return $this->raw;
    }

}