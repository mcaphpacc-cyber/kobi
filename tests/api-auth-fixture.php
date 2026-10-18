<?php
declare(strict_types=1);
namespace App\Core {
    final class Logger {
        public static array $messages=[];
        public static function info(string $message):void{self::$messages[]=$message;}
        public static function warning(string $message):void{self::$messages[]=$message;}
        public static function error(string $message):void{self::$messages[]=$message;}
    }
}
namespace {
if (PHP_SAPI!=='cli' && PHP_SAPI!=='cli-server') { http_response_code(404);exit; }
ini_set('zend.exception_ignore_args','1');
require dirname(__DIR__).'/vendor/autoload.php';
const API_TEST_DATABASE='kobi_api_auth_50b_test_20261016';
class ApiFixtureClock extends App\Services\ApiAuthClock { public int $time;public function __construct(){ $this->time=time()-1200; }public function now():int{return $this->time;} }
class ApiFixtureSession extends App\Core\Session {
    private array $values=['auth_user_id'=>100001];
    public function get(string $key,mixed $default=null):mixed{return $this->values[$key]??$default;}
    public function has(string $key):bool{return array_key_exists($key,$this->values);}
    public function remove(string $key):void{unset($this->values[$key]);}
    public function put(string $key,mixed $value):void{$this->values[$key]=$value;}
    public function regenerate():void{}
}
class ApiFixtureLimit extends App\Services\ApiAuthRateLimiter {
    public function __construct(){}
    public function hit(string $category,string $identity,int $limit,int $window):void{}
    public function check(string $category,string $identity,int $limit,int $window):void{}
    public function assertLoginBudget(string $email):void{}
    public function loginFailure(string $email):void{}
}
function apiFixtureSource():App\Core\Database{
    $db=new App\Core\Database();
    if($db->connection()->query('SELECT DATABASE()')->fetchColumn()!=='medical1'){throw new RuntimeException('Unexpected source');}
    return $db;
}
function apiFixtureConnect():App\Core\Database{
    env('DB_DATABASE');$_ENV['DB_DATABASE']=API_TEST_DATABASE;
    $db=new App\Core\Database();
    if($db->connection()->query('SELECT DATABASE()')->fetchColumn()!==API_TEST_DATABASE){throw new RuntimeException('Isolation guard failed');}
    return $db;
}
function apiFixtureCreate(App\Core\Database $source):void{
    $p=$source->connection();$s=$p->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');$s->execute([API_TEST_DATABASE]);
    if((int)$s->fetchColumn()!==0){throw new RuntimeException('Disposable target exists; refusing reuse');}
    $p->exec('CREATE DATABASE `'.API_TEST_DATABASE.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    $db=apiFixtureConnect();$p=$db->connection();
    $schema=file_get_contents(dirname(__DIR__).'/database/structure.sql');
    foreach(['users','user_auth_tokens'] as $table){
        if(!preg_match('/CREATE TABLE `'.preg_quote($table,'/').'` \([\s\S]*?ENGINE=InnoDB[^;]*;/',$schema,$m)){throw new RuntimeException('Fixture schema missing');}
        $p->exec($m[0]);
    }
    $sql=file_get_contents(dirname(__DIR__).'/database/api-auth-foundation.sql');
    preg_match_all('/CREATE TABLE user_api_[\s\S]*?ENGINE=InnoDB[^;]*;/',$sql,$tables);
    if(count($tables[0])!==2 || !preg_match('/CREATE TRIGGER[\s\S]*?END\$\$/',$sql,$trigger)){throw new RuntimeException('Unexpected schema artifact');}
    foreach($tables[0] as $statement){$p->exec($statement);}
    $p->exec(substr($trigger[0],0,-2));
}
function apiFixtureDrop(App\Core\Database $source):void{
    // Only this hard-coded disposable name can be removed; never the configured normal database.
    $source->connection()->exec('DROP DATABASE `'.API_TEST_DATABASE.'`');
}
function apiFixtureService(App\Core\Database $db,?App\Services\ApiAuthClock $clock=null):App\Services\ApiAuthService{
    $tokens=new App\Services\UserAuthTokenService(new App\Repositories\UserAuthTokenRepository($db));
    $auth=new App\Services\AuthService(new App\Repositories\UserRepository($db),new ApiFixtureSession(),$tokens,$db);
    return new App\Services\ApiAuthService(new App\Repositories\ApiAuthRepository($db),$auth,$clock??new App\Services\ApiAuthClock(),new ApiFixtureLimit());
}
function apiFixtureUser(PDO $p,string $password,int $id=100001):void{
    $s=$p->prepare("INSERT INTO users(id,name,email,password_hash,password_set_at,email_verified_at) VALUES (?,'API fixture only',?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())");
    $s->execute([$id,'fixture'.$id.'@example.invalid',password_hash($password,PASSWORD_DEFAULT)]);
}
}
