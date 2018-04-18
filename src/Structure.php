<?php
namespace Cabal\DB;

class Structure implements StructureInterface
{
    protected $prefix = '';

    public function primaryKey($tableName)
    {
        return 'id';
    }

    public function tableName($tableName)
    {
        return $this->prefix . $tableName;
    }

    public function foreignKey($tableName, $foreignTable)
    {
        return "{$tableName}_id";
    }
}