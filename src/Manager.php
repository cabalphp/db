<?php
namespace Cabal\DB;

use Cabal\DB\Connection\ConnectionInterface;


class Manager
{
    protected $pools = [];

    protected $configs;
    protected $structures = [];
    protected $default;
    protected $isTaskWorker;

    public function __construct($configs, $isTaskWorker)
    {
        $this->default = $configs['default'];
        $this->configs = $configs;
        $this->isTaskWorker = $isTaskWorker;
    }

    /**
     * Undocumented function
     *
     * @param [type] $name
     * @param boolean $writeable
     * @return \Cabal\DB\Connection
     */
    public function getConnection($name = null, $writeable = false)
    {
        $name = $name ? : $this->default;

        if ($writeable) {
            $config = $this->getDbConfig($name, true);
        } else {
            $config = $this->getDbConfig($name);
        }

        if (!isset($this->pools[$config['id']])) {
            $this->pools[$config['id']] = new \SplQueue;
        }
        $connection = $this->pools[$config['id']]->isEmpty() ? null : $this->pools[$config['id']]->shift();
        if (!$connection) {
            if (!$this->isTaskWorker) {
                $connection = new Connection\CoroutineMySQL();
                $connection->connect([
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'user' => $config['user'],
                    'password' => $config['password'],
                    'database' => $config['database'],
                ]);

                if ($connection->connected) {
                    $connection->setId($config['id']);
                    $connection->setName($name);
                } else {
                    throw new Exception("数据库连接失败:" . $connection->error, $connection->errno);
                }
            } else {

                $connection = new Connection\MySQL();
                $connection->connect([
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'user' => $config['user'],
                    'password' => $config['password'],
                    'database' => $config['database'],
                ]);

                if ($connection->connected) {
                    $connection->setId($config['id']);
                    $connection->setName($name);
                } else {
                    throw new Exception("数据库连接失败:" . $connection->error, $connection->errno);
                }
            }

        }

        return new Connection($this, $connection, $this->getStructure($name));
    }

    public function getDbConfig($name, $writeable = false)
    {
        if (!isset($this->configs[$name])) {
            return null;
        }
        $config = $this->configs[$name];
        $extraConfig = [];
        if ($writeable) {
            $extraConfig = isset($this->configs[$name]['write']) ? $this->configs[$name]['write'] : [];
        } else {
            $extraConfig = isset($this->configs[$name]['read']) ? $this->configs[$name]['read'] : [];
        }
        $config = array_merge($config, $extraConfig);
        if (is_array($config['host'])) {
            $config['host'] = $config['host'][mt_rand(0, count($config['host']) - 1)];
        }
        if (!isset($config['id'])) {
            $config['id'] = sprintf(
                "%s@%s:%s/%s",
                $config['user'],
                $config['host'],
                $config['port'],
                $config['database']
            );
        }
        return $config;
    }

    public function push(ConnectionInterface $connection)
    {
        $this->pools[$connection->getId()]->push($connection);
    }



    /**
     * Undocumented function
     *
     * @param [type] $connectionName
     * @return \Cabal\DB\TempManager
     */
    public function on($connectionName)
    {
        return new TempManager($this, $connectionName);
    }

    // -------- Connection Maps:
    /**
     * Undocumented function
     *
     * @param [type] $name
     * @return \Cabal\DB\StructureInterface 
     */
    public function getStructure($name = null)
    {
        $name = $name ? : $this->default;
        if (!isset($this->structures[$name])) {
            $this->structures[$name] = new Structure($this->getDbConfig($name));
        }
        return $this->structures[$name];
    }


    /**
     * Undocumented function
     *
     * @param string $tableName
     * @return \Cabal\DB\Table
     */
    public function table($tableName, $connectionName = null)
    {
        return new Table(
            $this,
            $connectionName,
            $tableName,
            $this->getStructure($connectionName)
        );
    }

    public function prepare($sql)
    {
        return $this->getConnection()->prepare($sql);
    }

    public function query($sql, $params = [])
    {
        return $this->getConnection()->query($sql, $params);
    }

    public function __sleep()
    {
        return [
            'configs',
            'structures',
            'default',
            'isTaskWorker',
        ];
    }

}