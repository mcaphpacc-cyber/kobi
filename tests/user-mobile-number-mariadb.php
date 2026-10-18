<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/vendor/autoload.php';
$db=new App\Core\Database();$pdo=$db->connection();
if($pdo->query('SELECT DATABASE()')->fetchColumn()!=='medical1'){throw new RuntimeException('Unexpected source.');}
$proof=json_decode(file_get_contents(__DIR__.'/.mobile-number-recovery/verified.json'),true,512,JSON_THROW_ON_ERROR);
$target='kobi_mobile_number_recovery_20261014';
if($proof['status']!=='RECOVERY_VERIFIED'||$proof['recovery_database']!==$target){throw new RuntimeException('Missing recovery certificate.');}
$pdo->exec('USE `'.$target.'`');
if($pdo->query('SELECT DATABASE()')->fetchColumn()!==$target){throw new RuntimeException('Isolation guard failed.');}
class MobileDbSession extends App\Core\Session {
    public ?int $uid=1;
    public function get(string $key,mixed $default=null): mixed { return $key==='auth_user_id'?$this->uid:$default; }
}
$session=new MobileDbSession();$repo=new App\Repositories\UserRepository($db);
$auth=(new ReflectionClass(App\Services\AuthService::class))->newInstanceWithoutConstructor();
foreach(['userRepository'=>$repo,'session'=>$session] as $field=>$value){(new ReflectionProperty(App\Services\AuthService::class,$field))->setValue($auth,$value);}
$checks=0;$check=function(bool $ok,string $name)use(&$checks):void{if(!$ok){throw new LogicException($name);}++$checks;};
$before=$pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();$mode=$pdo->query('SELECT @@sql_mode,@@global.sql_mode')->fetch();
$pdo->beginTransaction();
try{
    $check($repo->findById(1)['mobile_number']===null,'NULL default');
    $auth->updateMobileNumber('+1 (202) 555-0100');$check($repo->findById(1)['mobile_number']==='+12025550100','normalized roundtrip');
    $auth->updateMobileNumber('+12025550100');$check($repo->findById(1)['mobile_number']==='+12025550100','unchanged save succeeds');
    $other=$repo->findById(2);$check($other!==null,'second account fixture exists');
    $auth->updateMobileNumber('+12025550101');$check($repo->findById(1)['mobile_number']==='+12025550101'&&$repo->findById(2)===$other,'own update only');
    $session->uid=2;$auth->updateMobileNumber('+12025550101');$check($repo->findById(1)['mobile_number']===$repo->findById(2)['mobile_number'],'non-unique numbers allowed');
    foreach(['012345','+0','++12','+12abc',''] as $invalid){
        try{$repo->updateMobileNumber(2,$invalid);throw new LogicException('CHECK accepted invalid format.');}catch(PDOException $e){$check(true,'format CHECK rejects');}
    }
    $session->uid=null;try{$auth->updateMobileNumber('+12');throw new LogicException('Anonymous write');}catch(RuntimeException $e){$check(true,'anonymous service rejected');}
    $session->uid=1;$auth->updateMobileNumber('');$check($repo->findById(1)['mobile_number']===null,'clear');
    $column=$pdo->query("SHOW FULL COLUMNS FROM users WHERE Field='mobile_number'")->fetch();
    $check($column['Type']==='varchar(16)'&&$column['Null']==='YES'&&$column['Default']===null&&$column['Collation']==='ascii_bin','exact column metadata');
    $indexes=$pdo->query('SHOW INDEX FROM users')->fetchAll();$check(count(array_filter($indexes,fn($i)=>$i['Column_name']==='mobile_number'))===0,'no lookup/unique index');
}finally{$pdo->rollBack();}
$check($pdo->query('SELECT * FROM users ORDER BY id')->fetchAll()===$before,'all isolated users restored');
$check($mode===$pdo->query('SELECT @@sql_mode,@@global.sql_mode')->fetch(),'SQL mode unchanged');
file_put_contents(__DIR__.'/.mobile-number-recovery/mariadb-passed.json',json_encode(['status'=>'PASS','checks'=>$checks,'artifact_sha256'=>hash_file('sha256',dirname(__DIR__).'/database/user-mobile-number.sql')],JSON_PRETTY_PRINT));
echo "PASS: $checks isolated MariaDB checks; all fixture changes rolled back.\n";
