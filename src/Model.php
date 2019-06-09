<?php
namespace Cabal\DB;

use Carbon\Carbon;

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

    protected $timestamps = false;

    protected $dates = array('created_at', 'deleted_at', 'updated_at');
    protected $fillable = array();
    protected $visible = array();
    protected $hidden = array();
    protected $appends = array();
    protected $guarded = array('*');
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Is the row exists in database
     *
     * @var boolean
     */
    protected $exists = false;

    // public function __construct(Origin $dbData, Rows $rows = null)
    public function __construct(Origin $dbData = null, Rows $rows = null)
    {
        if ($dbData) {
            $this->dbData = $dbData;
            $this->exists = true;
        } else {
            $this->dbData = new Origin();
        }
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
    protected function newTable($table = null)
    {
        return new Table(
            static::$dbManager,
            $this->connectionName,
            $table ?: $this->tableName,
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
            if (count($dirty) > 0) {
                if ($this->timestamps) {
                    $this->updateTimestamps();
                }
                $dirty = $this->dbData->getDirty();
                $result = $this->getRows()->getTable()
                    ->where("`{$primaryKey}` = ?", $this->getId())
                    ->update($dirty);

                $this->dbData->flushOrigin();
                return $result;
            }
        } else {
            if ($this->timestamps) {
                $this->updateTimestamps();
            }
            $insertId = $this->getRows()->getTable()->insert($this->dbData->toArray());
            if ($insertId && !$this->$primaryKey) {
                $this->dbData->quietSet($primaryKey, $insertId);
            }
            $this->exists = true;
            $this->dbData->flushOrigin();
            return $insertId;
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
    static function query($table = null)
    {
        $model = new static();
        return $model->newTable($table = null)->asModel(get_class($model));
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
                Coroutine::callUserFuncArray($callback, [$table]);
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
        $obj = new $model;
        return parent::belongs($obj->tableName, $foreignKey, function (Table $table) use ($model, $callback) {
            $table->asModel($model);
            if ($callback) {
                Coroutine::callUserFuncArray($callback, [$table]);
            }
        }, $storeKey);
    }


    public function toArray()
    {
        $array = parent::toArray();
        if ($this->visible) {
            $array = array_only($array, $this->visible);
        } elseif ($this->hidden) {
            $array = array_except($array, $this->hidden);
        }
        foreach ($this->appends as $field) {
            $array[$field] = $this->__get($field);
            if ($array[$field] instanceof \DateTime) {
                $array[$field] = $array[$field]->format($this->getDateFormat());
            }
        }
        return $array;
    }

    public function __isset($key)
    {
        $migicMethod = explode('_', $key);
        $migicMethod = array_map('ucfirst', $migicMethod);
        $migicMethod = "__get" . implode('', $migicMethod);
        return isset($this->dbData[$key]) || method_exists($this, $migicMethod);
    }


    public function __get($name)
    {
        $value = $this->dbData[$name] ?? null;

        $migicMethod = explode('_', $name);
        $migicMethod = array_map('ucfirst', $migicMethod);
        $migicMethod = "__get" . implode('', $migicMethod);
        if (method_exists($this, $migicMethod)) {
            return $this->$migicMethod($value);
        }
        if ($value && in_array($name, $this->getDates())) {
            $value = $this->asDateTime($value);
        }
        return $value;
    }

    public function __set($name, $value)
    {
        $migicMethod = explode('_', $name);
        $migicMethod = array_map('ucfirst', $migicMethod);
        $migicMethod = "__set" . implode('', $migicMethod);
        if (method_exists($this, $migicMethod)) {
            return $this->$migicMethod($value);
        }
        if (in_array($name, $this->getDates())) {
            $value = $this->fromDateTime($value);
        }
        return $this->dbData[$name] = $value;
    }


    protected function fillableFromArray($attributes)
    {
        if (count($this->fillable) > 0) {
            return array_intersect_key($attributes, array_flip($this->fillable));
        }
        //默认都不允许自担填充
        // return array();
        return in_array('*', $this->guarded) ? [] : $attributes;
    }

    public function isGuarded($key)
    {
        return in_array($key, $this->guarded) || in_array('*', $this->guarded);
    }

    public function isFillable($key)
    {
        if (in_array($key, $this->fillable)) return true;

        if ($this->isGuarded($key)) return false;

        return empty($this->fillable) and substr($key, 0, 1) != '_';
    }

    public function fill(array $attributes)
    {
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            if ($this->isFillable($key)) {
                $this->__set($key, $value);
            }
        }

        return $this;
    }


    function getDates()
    {
        return $this->dates;
    }


    protected function updateTimestamps()
    {
        $time = date($this->getDateFormat());

        if (!$this->isDirty('updated_at')) {
            $this->updated_at = $time;
        }

        if (!$this->exists && !$this->isDirty('created_at')) {
            $this->created_at = $time;
        }
    }


    public function fromDateTime($value)
    {
        $format = $this->getDateFormat();

        if ($value instanceof \DateTime) { } elseif (is_numeric($value)) {
            $value = Carbon::createFromTimestamp($value);
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            $value = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } elseif (!$value instanceof \DateTime) {
            $value = Carbon::createFromFormat($format, $value);
        }

        return $value->format($format);
    }
    protected function asDateTime($value)
    {
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value);
        } elseif (!$value instanceof \DateTime) {
            $format = $this->getDateFormat();
            return Carbon::createFromFormat($format, $value);
        }
        return Carbon::instance($value);
    }

    protected function getDateFormat()
    {
        return $this->dateFormat;
    }
}
