<?php
use PHPUnit\Framework\TestCase;
use Cabal\DB\Manager;

require_once __DIR__ . '/../vendor/autoload.php';

class DatabaseTest extends TestCase
{
    /**
     * Undocumented variable
     *
     * @var \Cabal\DB\Manager
     */
    static $db;
    public static function setUpBeforeClass()
    {
        if (!self::$db) {
            self::$db = new Manager([
                'default' => 'mysql',

                'mysql' => [
                    'read' => [
                        'user' => 'root',
                    ],
                    'write' => [
                        'user' => 'root',
                    ],
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'password' => '123456',
                    'database' => 'cabal_test',
                ],
            ]);
            self::$db->query("DROP TABLE IF EXISTS `test`;");
            self::$db->query("CREATE TABLE `test` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
    }

    public function testEmpty()
    {
        $db = self::$db;
        $this->assertEmpty($db->table('test')->rows());

    }

    public function testInsert()
    {
        $db = self::$db;
        $name = uniqid();
        $createdAt = date('Y-m-d H:i:s');
        $insertId = $db->table('test')->insert([
            'name' => $name,
            'created_at' => $createdAt,
        ]);
        $this->assertNotEmpty($insertId);
        $row = $db->table('test')->first();
        $this->assertEquals($row->id, $insertId);
        $this->assertEquals($row->name, $name);
        $this->assertEquals($row->created_at, $createdAt);

    }

    /**
     * @depends testInsert
     */
    public function testDelete()
    {
        $db = self::$db;
        $row = $db->table('test')->first();
        $result = $db->table('test')->where('id = ?', $row->id)->delete();
        $this->assertEquals(1, $result);
        $this->assertEmpty($db->table('test')->rows());
    }
}