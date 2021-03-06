<?php
namespace Cabal\DB;

class Table
{
    const LOCK_FOR_UPDATE = 1;
    const SHARED_LOCK = 2;
    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Manager
     */
    protected $dbManager;
    protected $connectionName;
    protected $connection;
    protected $tableName;

    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Structure
     */
    protected $structure;
    protected $logStore;

    protected $select = [];
    protected $distinct = false;
    protected $from = [];
    protected $joins = [];
    protected $where = [];
    protected $groupBy = [];
    protected $orderBy = [];
    protected $limit = null;
    protected $offset = null;
    protected $lockMode = null;

    protected $model = null;

    public function __construct($dbManager, $connection, $tableName, $structure)
    {
        $this->dbManager = $dbManager;
        if ($connection instanceof Connection) {
            $this->connection = $connection;
            $this->connectionName = $connection->getRealConnection()->getName();
        } else {
            $this->connectionName = $connection;
        }
        $this->tableName = $tableName;
        $this->structure = $structure;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Connection
     */
    public function getConnection($writeable = false)
    {
        if ($this->connection) {
            return $this->connection;
        }
        return $this->dbManager->getConnection($this->connectionName, $writeable);
    }

    /**
     * Undocumented function
     *
     * @param [type] $tableName
     * @return \Cabal\DB\Table
     */
    public function similarTable($tableName)
    {
        if ($this->connection) {
            $table = $this->connection->table($tableName);
        } else {
            $table = $this->dbManager->on($this->connectionName)->table($tableName);
        }
        if ($this->logStore !== null) {
            $table->logQueryTo($this->logStore);
        }
        return $table;
    }

    public function getPrimaryKey()
    {
        return $this->getStructure()->primaryKey($this->tableName);
    }

    public function getTableName()
    {
        return $this->getStructure()->tableName($this->tableName);
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\StructureInterface 
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Table 
     */
    public function setStructure($structure)
    {
        $this->structure = $structure;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param string $connectionName
     * @return \Cabal\DB\Table
     */
    public function setConnectionName($connectionName)
    {
        $this->connectionName = $connectionName;
        return $this;
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
        $this->connectionName = $connection->getRealConnection()->getName();
        return $this;
    }

    public function on($connection)
    {
        if ($connection instanceof Connection) {
            $this->setConnection($connection);
        } else {
            $this->setConnectionName($connection);
        }
        return $this;
    }
    /**
     * Undocumented function
     *
     * @param boolean $distinct
     * @return \Cabal\DB\Table
     */
    public function distinct($distinct = true)
    {
        $this->distinct = $distinct;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param [type] $cond
     * @param [type] $params
     * @param string $symbol
     * @return \Cabal\DB\Table
     */
    public function where($cond, $params, $symbol = 'AND')
    {
        if ($cond instanceof \Closure) {
            $this->where[] = '(';
            $cond();
            $this->where[] = ')';
        }
        $this->where[] = [
            $symbol, $cond, is_array($params) ? $params : [$params]
        ];
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param [type] $field
     * @param array $in
     * @param string $symbol
     * @return \Cabal\DB\Table
     */
    public function whereIn($field, array $in, $symbol = 'AND')
    {
        $q = array_fill(0, count($in), '?');
        $this->where[] = ['AND', "{$field} IN (" . implode(', ', $q) . ")", $in];
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param [type] $field
     * @param [type] $in
     * @return \Cabal\DB\Table
     */
    public function orWhereIn($field, $in)
    {
        return $this->whereIn($field, $in, 'OR');
    }

    /**
     * Undocumented function
     *
     * @param [type] $cond
     * @param [type] $params
     * @return \Cabal\DB\Table
     */
    public function and($cond, $params)
    {
        return $this->where($cond, $params, 'AND');
    }

    /**
     * Undocumented function
     *
     * @param [type] $cond
     * @param [type] $params
     * @return \Cabal\DB\Table
     */
    public function or($cond, $params)
    {
        return $this->where($cond, $params, 'OR');
    }

    /**
     * Undocumented function
     *
     * @param [type] $fields
     * @param boolean $append
     * @return \Cabal\DB\Table
     */
    public function select($fields, $append = false)
    {
        $fields = is_array($fields) ? $fields : func_get_args();
        if ($append) {
            $this->select = array_merge($this->select, $fields);
        } else {
            $this->select = $fields;
        }
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param [type] $fields
     * @return \Cabal\DB\Table
     */
    public function groupBy($fields)
    {
        $fields = is_array($fields) ? $fields : func_get_args();
        $this->groupBy = array_merge($this->groupBy, $fields);
        return $this;
    }

    public function join($way, $tableName, $on, $params = [])
    {
        $this->joins[] = [$way, $tableName, $on, $params];
        return $this;
    }
    public function leftJoin($tableName, $on, $params = [])
    {
        return $this->join('LEFT', $tableName, $on, $params);
    }
    public function rightJoin($tableName, $on, $params = [])
    {
        return $this->join('RIGHT', $tableName, $on, $params);
    }
    /**
     * Undocumented function
     *
     * @param [type] $tableName
     * @param [type] $on
     * @param array $params
     * @return \Cabal\DB\Table
     */
    public function innerJoin($tableName, $on, $params = [])
    {
        return $this->join('INNER', $tableName, $on, $params);
    }


    /**
     * Undocumented function
     *
     * @param [type] $field
     * @param string $sort
     * @return \Cabal\DB\Table
     */
    public function orderBy($field, $sort = 'ASC')
    {
        $this->orderBy[] = [$field, $sort];
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param integer $limit
     * @param integer $offset
     * @return \Cabal\DB\Table
     */
    public function limit($limit, $offset = null)
    {
        $this->limit = $limit;
        if ($offset !== null) {
            $this->offset = $offset;
        }
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param integer $offset
     * @return \Cabal\DB\Table
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function sql()
    {
        $params = [];
        $statements = [
            'SELECT' => [],
            'FROM' => [],
            'JOIN' => '',
            'WHERE' => '',
            'GROUP BY' => [],
            'ORDER BY' => [],
            'LIMIT' => $this->limit,
            'OFFSET' => $this->offset,
        ];
        $tableName = $this->getTableName();
        if ($this->select) {
            foreach ($this->select as $field) {
                $statements['SELECT'][] = ($field instanceof Raw) ? $field->toString() : $field;
            }
        } else {
            $statements['SELECT'][] = "{$tableName}.*";
        }
        $this->from = $this->from ?: ["{$tableName}"];
        foreach ($this->from as $from) {
            $statements['FROM'][] = $from;
        }
        foreach ($this->where as $i => $where) {
            if (is_array($where)) {
                list($symbol, $cond, $subParams) = $where;
                $symbol = $i === 0 ? '' : " {$symbol} ";
                $statements['WHERE'] .= "{$symbol}{$cond}";
                $params = array_merge($params, $subParams);
            } else {
                $statements['WHERE'][] = $where;
            }
        }
        foreach ($this->joins as $join) {
            list($way, $tableName, $on, $subParams) = $join;
            $statements['JOIN'] .= "{$way} JOIN {$tableName} ON {$on}";
            $params = array_merge($params, $subParams);
        }
        foreach ($this->orderBy as $orderBy) {
            list($field, $sort) = $orderBy;
            if ($field instanceof Raw) {
                $field = $field->toString();
            }
            $statements['ORDER BY'][] = "{$field} {$sort}";
        }
        $sql = [];
        foreach ($statements as $key => $values) {
            if ($values) {
                if ($key != 'JOIN') {
                    $sql[] = $key;
                    if ($key == 'SELECT' && $this->distinct) {
                        $sql[] = 'DISTINCT';
                    }
                }
                if (is_array($values)) {
                    $sql[] = implode(',', $values);
                } elseif ($values) {
                    $sql[] = $values;
                }
            }
        }
        return [implode(' ', $sql), $params];
    }

    public function lockForUpdate($lock = true)
    {
        if (!$this->connection) {
            throw new \Exception("must provide connection and in transaction when using lock");
        }
        if ($lock) {
            $this->lockMode = self::LOCK_FOR_UPDATE;
        } else {
            $this->lockMode = 0;
        }
        return $this;
    }

    public function sharedLock($lock = true)
    {
        if (!$this->connection) {
            throw new \Exception("must provide connection and in transaction when using lock");
        }
        if ($lock) {
            $this->lockMode = self::SHARED_LOCK;
        } else {
            $this->lockMode = 0;
        }
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Table
     */
    protected function extract()
    {
        list($sql, $params) = $this->sql();

        if ($this->lockMode === self::LOCK_FOR_UPDATE) {
            $sql .= ' FOR UPDATE';
        } elseif ($this->lockMode === self::LOCK_FOR_UPDATE) {
            $sql .= ' LOCK IN SHARE MODE';
        }

        $connection = $this->getConnection();
        $dbRows = $connection->query($sql, $params);
        $this->storeLogs($connection->getQueryLogs());
        return new Rows($dbRows ?: [], $this, null, $this->model);
    }

    /**
     * Undocumented function
     *
     * @param array $logStore
     * @return \Cabal\DB\Table
     */
    public function logQueryTo(array &$logStore)
    {
        $this->logStore = &$logStore;
        return $this;
    }

    public function storeLogs($logs)
    {
        if ($this->logStore !== null) {
            foreach ($logs as $log) {
                $this->logStore[] = $log;
            }
        }
        return $this;
    }

    public function asModel($model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Row[]|\Cabal\DB\Rows
     */
    public function rows()
    {
        return $this->extract();
    }

    /**
     * Undocumented function
     *
     * @param [type] $currentPage
     * @param integer $perPage
     * @param array $columns
     * @return \Cabal\DB\Paginate
     */
    public function paginate($currentPage, $perPage = 20, $columns = '*')
    {
        $rowsQuery = clone $this;
        $count = $rowsQuery->count($columns);

        $currentPage = $currentPage < 1 ? 1 : $currentPage;
        if ($perPage < 1) {
            $perPage = $count;
        }
        $offset = ($currentPage - 1) * $perPage;
        $limit = $perPage;
        $rows = $this->offset($offset)->limit($limit)->rows();
        return new Paginate($rows, $perPage, $currentPage, $count);
    }

    /**
     * Undocumented function
     *
     * @return \Cabal\DB\Row
     */
    public function first()
    {
        return $this->limit(1)->rows()->fetch();
    }

    /**
     * Undocumented function
     *
     * @return int
     */
    public function count($field = '*')
    {
        $field = $this->distinct ? "DISTINCT {$field}" : $field;
        return intval($this->distinct(false)->select(new Raw("COUNT({$field}) as `aggregation`"))->first(1)->aggregation);
    }

    /**
     * Undocumented function
     *
     * @return int
     */
    public function sum($field)
    {
        $field = $field instanceof Raw ? $field->toString() : "`{$field}`";
        return intval($this->select(new Raw("SUM({$field}) as `aggregation`"))->limit(1)->fetch()->aggregation);
    }

    /**
     * Undocumented function
     *
     * @return int
     */
    public function max($field)
    {
        $field = $field instanceof Raw ? $field->toString() : "`{$field}`";
        return $this->select(new Raw("MAX({$field}) as `aggregation`"))->limit(1)->fetch()->aggregation;
    }
    /**
     * Undocumented function
     *
     * @return int
     */
    public function min($field)
    {
        $field = $field instanceof Raw ? $field->toString() : "`{$field}`";
        return $this->select(new Raw("MAX({$field}) as `aggregation`"))->limit(1)->fetch()->aggregation;
    }

    public function insert($data)
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        $values = array_values($data);

        $sql = sprintf(
            'INSERT into %s (`%s`) VALUES (%s);',
            $this->getTableName(),
            implode('`, `', $fields),
            implode(', ', $placeholders)
        );
        $connection = $this->getConnection(true);
        $connection->query($sql, $values);
        $this->storeLogs($connection->getQueryLogs());
        return $connection->lastInsertId();
    }
    public function replace($data)
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        $values = array_values($data);

        $sql = sprintf(
            'REPLACE into %s (`%s`) VALUES (%s);',
            $this->getTableName(),
            implode('`, `', $fields),
            implode(', ', $placeholders)
        );
        $connection = $this->getConnection(true);
        $connection->query($sql, $values);
        $this->storeLogs($connection->getQueryLogs());
        return $connection->lastInsertId();
    }


    protected function updateSql($sets, $values)
    {
        $tableName = $this->getTableName();
        $params = $values;
        $statements = [
            'UPDATE' => [],
            'SET' => $sets,
            'JOIN' => '',
            'WHERE' => '',
            'GROUP BY' => [],
            'ORDER BY' => [],
            'LIMIT' => $this->limit,
            'OFFSET' => $this->offset,
        ];
        $statements['UPDATE'][] = $tableName;

        foreach ($this->where as $i => $where) {
            if (is_array($where)) {
                list($symbol, $cond, $subParams) = $where;
                $symbol = $i === 0 ? '' : " {$symbol} ";
                $statements['WHERE'] .= "{$symbol}{$cond}";
                $params = array_merge($params, $subParams);
            } else {
                $statements['WHERE'][] = $where;
            }
        }
        foreach ($this->joins as $join) {
            list($way, $tableName, $on, $subParams) = $join;
            $statements['JOIN'] .= "{$way} JOIN {$tableName} ON {$on}";
            $params = array_merge($params, $subParams);
        }
        $sql = [];
        foreach ($statements as $key => $values) {
            if ($values) {
                if ($key != 'JOIN') {
                    $sql[] = $key;
                }
                if (is_array($values)) {
                    $sql[] = implode(',', $values);
                } elseif ($values) {
                    $sql[] = $values;
                }
            }
        }
        return [implode(' ', $sql), $params];
    }

    public function update($data)
    {
        $sets = [];
        $values = [];
        foreach ($data as $field => $value) {
            if ($value instanceof Raw) {
                $sets[] = "{$field} = " . $value->toString();
            } else {
                $sets[] = "{$field} = ?";
                $values[] = $value;
            }
        }
        list($sql, $params) = $this->updateSql($sets, $values);
        $connection = $this->getConnection(true);
        $connection->query($sql, $params);
        $this->storeLogs($connection->getQueryLogs());
        return $connection->affectedRows();
    }


    public function delete()
    {
        $tableName = $this->getTableName();
        $params = [];
        $statements = [
            'DELETE FROM' => [],
            'JOIN' => '',
            'WHERE' => '',
            'GROUP BY' => [],
            'ORDER BY' => [],
            'LIMIT' => $this->limit,
            'OFFSET' => $this->offset,
        ];
        $statements['DELETE FROM'][] = $tableName;

        foreach ($this->where as $i => $where) {
            if (is_array($where)) {
                list($symbol, $cond, $subParams) = $where;
                $symbol = $i === 0 ? '' : " {$symbol} ";
                $statements['WHERE'] .= "{$symbol}{$cond}";
                $params = array_merge($params, $subParams);
            } else {
                $statements['WHERE'][] = $where;
            }
        }
        foreach ($this->joins as $join) {
            list($way, $tableName, $on, $subParams) = $join;
            $statements['JOIN'] .= "{$way} JOIN {$tableName} ON {$on}";
            $params = array_merge($params, $subParams);
        }
        $sql = [];
        foreach ($statements as $key => $values) {
            if ($values) {
                if ($key != 'JOIN') {
                    $sql[] = $key;
                }
                if (is_array($values)) {
                    $sql[] = implode(',', $values);
                } elseif ($values) {
                    $sql[] = $values;
                }
            }
        }
        $sql = implode(' ', $sql);
        $connection = $this->getConnection();
        $connection->query($sql, $params);
        $this->storeLogs($connection->getQueryLogs());
        return $connection->affectedRows();
    }



    public function __sleep()
    {
        return [
            'dbManager',
            'connectionName',
            'tableName',
            'structure',
        ];
    }
}
