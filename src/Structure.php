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
        $tableName = $this->prefix . $tableName;
        $tableName = explode('.', $tableName);
        $tableName = array_map(function ($str) {
            return trim($str, '`');
        }, $tableName);
        return '`' . implode('`.`', $tableName) . '`';
    }

    public function foreignKey($tableName, $foreignTable)
    {
        return "{$tableName}_id";
    }
}