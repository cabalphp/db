<?php
namespace Cabal\DB;

use Swoole\Coroutine;

// class Row implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
class Model extends Row
{
    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Manager
     */
    static protected $dbManager;

    protected $connectionName;

    /**
     * Define table name
     *
     * @var string table
     */
    protected $tableName;

    /**
     * Is the row exists in database
     *
     * @var boolean
     */
    protected $exists = false;

    // public function __construct(Origin $dbData, Rows $rows = null)
    public function __construct(Origin $dbData = null, Rows $rows = null)
    {
        $this->dbData = $dbData ? $dbData : new Origin([]);
        $this->rows = $rows;
    }

    static public function setDBManager($dbManager)
    {
        self::$dbManager = $dbManager;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Rows
     */
    public function getRows()
    {
        if (!$this->rows) {
            $this->rows = new Rows([$this->dbData], $this->newTable(), null, get_class($this));
        }
        return $this->rows;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Table
     */
    protected function newTable()
    {
        return new Table(
            static::$dbManager,
            $this->connectionName,
            $this->tableName,
            static::$dbManager->getStructure($this->connectionName)
        );
    }

    public function on($connectionName)
    {
        $this->connectionName = $connectionName;
        $this->getRows()->getTable()->setConnectionName($connectionName);
        $this->getRows()->getTable()->setStructure(static::$dbManager->getStructure($connectionName));
        return $this;
    }

    public function save()
    {
        $primaryKey = $this->getRows()->getTable()->getPrimaryKey();
        if ($this->exists) {
            $dirty = $this->dbData->getDirty();
            return $this->getRows()->getTable()
                ->where("`{$primaryKey}` = ?", $this->getId())
                ->update($dirty);
        } else {
            $insertId = $this->getRows()->getTable()->insert($this->dbData->toArray());
            if ($insertId && !$this->$primaryKey) {
                return $this->dbData->quietSet($primaryKey, $insertId);
            }
            return true;
        }
    }

    public function delete()
    {
        $primaryKey = $this->getRows()->getTable()->getPrimaryKey();
        return $this->getRows()->getTable()
            ->where("`{$primaryKey}` = ?", $this->getId())
            ->delete();
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Table
     */
    static function query()
    {
        $model = new static();
        return $model->newTable()->asModel(get_class($model));
    }

    public function has($model, $foreignKeyOrCallback = null, $callback = null, $storeKey = null)
    {
        if (!class_exists($model)) {
            return parent::has($model, $foreignKeyOrCallback, $callback, $storeKey);
        }
        $foreignKey = null;
        if (func_num_args() < 4 && is_callable($foreignKeyOrCallback)) {
            $storeKey = $callback;
            $callback = $foreignKeyOrCallback;
        } else {
            $foreignKey = $foreignKeyOrCallback;
        }
        $obj = new $model;
        return parent::has($obj->tableName, $foreignKey, function (Table $table) use ($model, $callback) {
            $table->asModel($model);
            if ($callback) {
                Coroutine::call_user_func_array($callback, [$table]);
            }
        }, $storeKey);
    }

    public function belongs($model, $foreignKeyOrCallback = null, $callback = null, $storeKey = null)
    {
        if (!class_exists($model)) {
            return parent::belongs($model, $foreignKeyOrCallback, $callback, $storeKey);
        }
        $foreignKey = null;
        if (func_num_args() < 4 && is_callable($foreignKeyOrCallback)) {
            $storeKey = $callback;
            $callback = $foreignKeyOrCallback;
        } else {
            $foreignKey = $foreignKeyOrCallback;
        }

        return parent::belongs($obj->tableName, $foreignKey, function (Table $table) use ($model, $callback) {
            $table->asModel($model);
            if ($callback) {
                Coroutine::call_user_func_array($callback, [$table]);
            }
        }, $storeKey);
    }
}