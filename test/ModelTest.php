<?php
use PHPUnit\Framework\TestCase;
use Cabal\DB\Manager;
use Cabal\DB\Model;
use Cabal\DB\Table;

require_once __DIR__ . '/../vendor/autoload.php';

class User extends Model
{
    protected $tableName = 'user';
}
class Article extends Model
{
    protected $tableName = 'article';
}
class ModelTest extends TestCase
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
            self::$db->query("DROP TABLE IF EXISTS `user`;");
            self::$db->query("DROP TABLE IF EXISTS `tag`;");
            self::$db->query("DROP TABLE IF EXISTS `article_tag`;");
            self::$db->query("DROP TABLE IF EXISTS `article`;");

            self::$db->query("CREATE TABLE `user` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            self::$db->query("CREATE TABLE `tag` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            self::$db->query("CREATE TABLE `article_tag` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `tag_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            self::$db->query("ALTER TABLE `article_tag`ADD UNIQUE (`article_id`,`tag_id`);");

            self::$db->query("CREATE TABLE `article` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` int(11) NOT NULL default 0,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }

        Model::setDBManager(self::$db);
    }

    public function testEmpty()
    {
        $db = self::$db;
        $this->assertEmpty($db->table('user')->rows());

    }

    public function testInsert()
    {
        $user = new User;
        $user->name = uniqid();
        $user->save();
        $this->assertNotEmpty($user->id);
        $this->assertEquals($user->id, 1);

        $exists = User::query()->first();
        $this->assertEquals($exists->id, $user->id);
        $this->assertEquals($exists->name, $user->name);
        $article = new Article;
        $article->title = uniqid();
        $article->user_id = $user->id;
        $article->status = 1;
        $article->save();
    }

    public function testRelation()
    {
        $user = User::query()->first();
        $articles = $user->has(Article::class, function (Table $table) {
            $table->where('status = ?', 1);
        }, 'published-article');
        $this->assertInstanceOf(Article::class, $articles->first());
        $this->assertEquals($articles->first()->id, 1);

        $articles = $user->has(Article::class, function (Table $table) {
            $table->where('status = ?', -1);
        }, 'unpublished-article');
        $this->assertEmpty($articles);
    }

    /**
     * @depends testInsert
     */
    public function testDelete()
    {
        $exists = User::query()->first();
        $exists->delete();

        $exists = User::query()->first();
        $this->assertEmpty($exists);
    }
}