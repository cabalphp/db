<?php
namespace Cabal\DB;

interface StructureInterface
{
    public function primaryKey($tableName);

    public function tableName($tableName);

    public function foreignKey($tableName, $foreignTable);
}