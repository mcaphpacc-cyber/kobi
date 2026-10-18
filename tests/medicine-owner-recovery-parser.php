<?php
declare(strict_types=1);
// Offline regression: real protected dump, no database connection and no row output.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('MEDICINE_OWNER_RECOVERY_LIBRARY',true);
require __DIR__.'/medicine-owner-schema-deployment.php';
class OwnerRecoveryStatement extends PDOStatement {
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array {return [];}
    public function fetchColumn(int $column=0):mixed {return O_MODE;}
}
class OwnerRecoveryRecordingPDO extends PDO {
    public array $statements=[];
    public function __construct(){}
    public function exec(string $statement):int|false {$this->statements[]=$statement;return 0;}
    public function query(string $query,?int $fetchMode=null,mixed ...$fetchModeArgs):PDOStatement|false {return new OwnerRecoveryStatement();}
}
$path=O_DIR.'/before.sql';oAssert(hash_file('sha256',$path)==='f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2','Protected backup changed.');
$pdo=new OwnerRecoveryRecordingPDO();$proof=oImport($pdo,file_get_contents($path));$pre=oLoad('preflight.json');$assertions=0;
oAssert($proof['statements']===499&&count($pdo->statements)===499,'Statement inventory changed.');$assertions++;
oAssert($proof['omitted_sql_mode_directives']===[]&&count($proof['executed_sql_mode_directives'])===26,'Mode directives omitted/missing.');$assertions++;
$creates=array_filter($pdo->statements,fn($sql)=>(bool)preg_match('/^CREATE TABLE `/m',$sql));oAssert(count($creates)===54,'Table inventory changed.');$assertions++;
foreach($pre['metadata']['triggers'] as $trigger){$found=array_filter($pdo->statements,fn($sql)=>str_contains($sql,'TRIGGER `'.$trigger['TRIGGER_NAME'].'`'));oAssert(count($found)===1,'Trigger statement missing/duplicated.');$assertions++;oAssert(str_contains(array_values($found)[0],$trigger['ACTION_STATEMENT']),'Trigger body bytes changed.');$assertions++;}
echo "Offline protected-dump parser: $assertions assertions passed; 499 statements, 54 tables, 8 byte-preserved trigger bodies, 26 mode directives. No database connection.\n";
