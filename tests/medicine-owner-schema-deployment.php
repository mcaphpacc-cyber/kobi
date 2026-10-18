<?php
declare(strict_types=1);
// Operator-only deployment helper. No authentication impersonation or grant writes.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/vendor/autoload.php';
const O_DIR=__DIR__.'/../storage/logs/medicine321a-owner-recovery';
const O_MODE='NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION';
const O_HASH='2c96acc2e198b9838c9388c45abedb6c355c7b8ae1cd750bbad7c1365b97ebd1';
const O_RESTORE='kobi_owner_recovery_20261010';
function oAssert(bool $ok,string $message):void {if(!$ok){throw new RuntimeException($message);}}
function oSave(string $name,array $value):void {oAssert(file_put_contents(O_DIR.'/'.$name,json_encode($value,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR))!==false,'Evidence write failed.');}
function oLoad(string $name):array {return json_decode(file_get_contents(O_DIR.'/'.$name),true,512,JSON_THROW_ON_ERROR);}
function oConnect(bool $normal):PDO {
    try{$p=$normal?(new App\Core\Database())->connection():new PDO('mysql:host=127.0.0.1;port=3342;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}catch(Throwable $e){throw new RuntimeException('Connection unavailable; credentials suppressed.');}
    $m=$p->query('SELECT DATABASE() db,@@port port,@@version version,@@datadir datadir,@@sql_mode mode,@@global.sql_mode global_mode')->fetch();
    oAssert(str_starts_with($m['version'],'10.4.27-')&&$m['mode']===O_MODE&&$m['global_mode']===O_MODE,'Version/mode guard failed.');
    if($normal){oAssert($m['db']==='medical1'&&(int)$m['port']===3306,'Normal target guard failed.');}
    else{$path=realpath(__DIR__.'/.medicine-owner-recovery/data');oAssert($path!==false&&(int)$m['port']===3342&&strcasecmp(rtrim(str_replace('\\','/',$m['datadir']),'/'),str_replace('\\','/',$path))===0,'Disposable isolation guard failed.');}
    return $p;
}
function oData(PDO $p):array {
    $out=[];foreach($p->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME") as $r){$n=$r['TABLE_NAME'];oAssert((bool)preg_match('/\A[a-zA-Z0-9_]+\z/D',$n),'Unsupported identifier.');$hashes=[];$q=$p->query('SELECT * FROM `'.$n.'`');while($row=$q->fetch()){$hashes[]=hash('sha256',serialize($row));}sort($hashes,SORT_STRING);$out[$n]=['count'=>count($hashes),'sha256'=>hash('sha256',implode('',$hashes))];}return $out;
}
function oMeta(PDO $p):array {
    $out=['definitions'=>[]];foreach($p->query('SELECT TABLE_NAME,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME') as $r){$q=$p->query('SHOW CREATE TABLE `'.$r['TABLE_NAME'].'`')->fetch();$out['definitions'][$r['TABLE_NAME']]=$q[$r['TABLE_TYPE']==='VIEW'?'Create View':'Create Table'];}
    $out['triggers']=$p->query('SELECT TRIGGER_NAME,EVENT_MANIPULATION,EVENT_OBJECT_TABLE,ACTION_TIMING,ACTION_STATEMENT,SQL_MODE,DEFINER FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME')->fetchAll();
    $out['routines']=$p->query('SELECT ROUTINE_NAME,ROUTINE_TYPE,ROUTINE_DEFINITION,SQL_MODE,DEFINER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE() ORDER BY ROUTINE_NAME')->fetchAll();
    $out['events']=$p->query('SELECT EVENT_NAME,EVENT_DEFINITION,SQL_MODE,DEFINER,STATUS FROM information_schema.EVENTS WHERE EVENT_SCHEMA=DATABASE() ORDER BY EVENT_NAME')->fetchAll();return $out;
}
function oPrecondition(PDO $p):void {
    oAssert((int)$p->query("SELECT COUNT(*) FROM users WHERE id=1 AND status='active'")->fetchColumn()===1,'Designated owner inactive/missing.');
    foreach(['medicine_authority_grants','medicine_governance_events'] as $n){oAssert((int)$p->query('SELECT COUNT(*) FROM '.$n)->fetchColumn()===0,'Authority history already initialized.');}
    oAssert($p->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='medicine_authority_grants' AND COLUMN_NAME='expires_at'")->fetchColumn()==='NO','Migration prestate differs.');
    $config=require dirname(__DIR__).'/app/Config/Config.php';$c=$config['medicine_governance'];oAssert($c['owner_bootstrap']['owner_user_id']===1&&$c['owner_bootstrap']['administrator_user_id']===1&&$c['owner_user_ids']===[]&&$c['registry_operator_user_ids']===[]&&$c['deployment_operator_user_ids']===[]&&!$c['pilot_enabled']&&!$c['pilot_internal_enabled']&&$c['pilot_activation']===[],'Governance config precondition differs.');
}
function oImport(PDO $p,string $source,?callable $beforeExecute=null,?callable $retainDiagnostics=null):array {
    // Restore executable comments verbatim, including session save/set/restore.
    // The isolation caller must verify initial/final modes and trigger metadata.
    $buffer='';$delimiter=';';$n=0;$modes=[];$warnings=[];
    // Keep dump line endings: trigger ACTION_STATEMENT retains its original bytes.
    foreach(preg_split('/(?<=\n)/',$source) as $index=>$line){
        if(preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i',$line,$m)){$delimiter=$m[1];continue;}
        $buffer.=$line;$trim=rtrim($buffer);if(str_ends_with($trim,$delimiter)){
            $modeDirective=(bool)preg_match('/^\/\*!\d+ SET .*sql_mode/i',trim($line));
            $before=$modeDirective?$p->query('SELECT @@sql_mode')->fetchColumn():null;
            $sql=substr($trim,0,-strlen($delimiter));
            if($beforeExecute!==null){$beforeExecute($sql,$n+1);}
            $failure=null;
            try{
                $statement=$p->query($sql);
                // Drain result-producing diagnostic fixtures before SHOW WARNINGS.
                // This does not issue another SQL statement or alter its semantics.
                $statement->closeCursor();
            }catch(PDOException $error){$failure=$error;}
            // SHOW WARNINGS must be the first SQL after execution, including failure.
            // Persist before any mode query, next statement or exception escapes.
            $found=$p->query('SHOW WARNINGS')->fetchAll();
            $conditionCount=(int)$p->query('SHOW COUNT(*) WARNINGS')->fetchColumn();
            $entry=['statement'=>$n+1,'line'=>$index+1,'sql_sha256'=>hash('sha256',$sql),'captured_at_utc'=>gmdate('Y-m-d H:i:s').' UTC','sql_mode_directive'=>$modeDirective,'condition_count'=>$conditionCount,'warnings'=>$found,'succeeded'=>$failure===null];
            if($failure!==null){$entry['error']=['sqlstate'=>$failure->errorInfo[0]??null,'code'=>$failure->errorInfo[1]??null,'message'=>$failure->errorInfo[2]??$failure->getMessage()];}
            if($retainDiagnostics!==null){$retainDiagnostics($entry);}
            oAssert(count($found)===$conditionCount,'MariaDB diagnostic list incomplete; retained count differs.');
            if($failure!==null){throw $failure;}
            $n++;
            if($found!==[]){$warnings[]=['statement'=>$n,'line'=>$index+1,'warnings'=>$found];}
            if($modeDirective){$modes[]=['line'=>$index+1,'directive'=>trim($line),'before'=>$before,'after'=>$p->query('SELECT @@sql_mode')->fetchColumn()];}
            $buffer='';
        }
    }oAssert(preg_match('/\A(?:\s+|--[^\r\n]*(?:\r?\n|\z)|\/\*(?!\!)[\s\S]*?\*\/)*\z/D',$buffer)===1,'Incomplete dump import.');return ['statements'=>$n,'omitted_sql_mode_directives'=>[],'executed_sql_mode_directives'=>$modes,'warnings'=>$warnings];
}
function oDDL(PDO $p,string $evidence):void {oSave($evidence,['status'=>'ATTEMPT_STARTED','artifact_sha256'=>O_HASH]);$p->exec(file_get_contents(dirname(__DIR__).'/database/medicine-phase321a-owner-authority.sql'));$warnings=$p->query('SHOW WARNINGS')->fetchAll();oSave($evidence,['status'=>'EXECUTED','warnings'=>$warnings,'artifact_sha256'=>O_HASH]);oAssert($warnings===[],'Migration warnings; stop.');}
function oRun(string $mode):void {
    oAssert(in_array($mode,['prepare','rehearse','deploy','verify','check_unchanged','cleanup'],true),'Use prepare/rehearse/deploy/verify/check_unchanged/cleanup.');
    oAssert(hash_file('sha256',dirname(__DIR__).'/database/medicine-phase321a-owner-authority.sql')===O_HASH,'Approved artifact differs.');
    if($mode==='cleanup'){$p=oConnect(false);$p->exec('DROP DATABASE IF EXISTS '.O_RESTORE);oAssert((int)$p->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".O_RESTORE."'")->fetchColumn()===0,'Restore cleanup failed.');echo "Isolated recovery database removed.\n";return;}
    $p=oConnect(true);
    if($mode==='prepare'){
        oPrecondition($p);oAssert(!file_exists(O_DIR.'/before.sql')&&!file_exists(O_DIR.'/preflight.json'),'Existing backup; never overwrite.');
        $pre=['data'=>oData($p),'metadata'=>oMeta($p),'privileges_sha256'=>hash('sha256',serialize($p->query('SHOW GRANTS')->fetchAll())),'observed_at'=>$p->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn()];oSave('preflight.json',$pre);
        $quote=function($s){oAssert(!preg_match('/[\r\n\x00]/',(string)$s),'Unsupported credential encoding.');return '"'.str_replace(['\\','"'],['\\\\','\\"'],(string)$s).'"';};$ini=O_DIR.'/client.cnf';
        file_put_contents($ini,"[client]\nuser=".$quote(env('DB_USERNAME'))."\npassword=".$quote(env('DB_PASSWORD'))."\nhost=".$quote(env('DB_HOST','localhost'))."\nport=3306\n");
        try{$proc=proc_open(['D:/xampp/mysql/bin/mysqldump.exe','--defaults-extra-file='.realpath($ini),'--single-transaction','--skip-lock-tables','--skip-add-locks','--skip-add-drop-table','--routines','--events','--triggers','--hex-blob','--result-file='.realpath(O_DIR).'/before.sql','medical1'],[0=>['pipe','r'],1=>['file',O_DIR.'/dump.stdout','w'],2=>['file',O_DIR.'/dump.stderr','w']],$pipes);oAssert(is_resource($proc),'Backup unavailable.');fclose($pipes[0]);oAssert(proc_close($proc)===0,'Backup failed; protected diagnostics retained.');}finally{if(is_file($ini)){unlink($ini);}}
        oAssert(is_readable(O_DIR.'/before.sql')&&filesize(O_DIR.'/before.sql')>0&&$pre['data']===oData($p)&&$pre['metadata']===oMeta($p),'Backup prestate changed or backup unreadable.');
        oSave('backup.json',['sha256'=>hash_file('sha256',O_DIR.'/before.sql'),'bytes'=>filesize(O_DIR.'/before.sql')]);echo "Backup created; restore verification still required.\n";return;
    }
    $pre=oLoad('preflight.json');$backup=oLoad('backup.json');oAssert(hash_file('sha256',O_DIR.'/before.sql')===$backup['sha256'],'Recovery backup hash differs.');
    if($mode==='rehearse'){
        oPrecondition($p);oAssert($pre['data']===oData($p)&&$pre['metadata']===oMeta($p),'Normal prestate changed.');$r=oConnect(false);oAssert((int)$r->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".O_RESTORE."'")->fetchColumn()===0,'Recovery DB already exists.');
        $r->exec('CREATE DATABASE '.O_RESTORE.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$r->exec('USE '.O_RESTORE);$import=oImport($r,file_get_contents(O_DIR.'/before.sql'));
        oAssert($pre['data']===oData($r),'Recovered data differs.');oAssert($pre['metadata']===oMeta($r),'Recovered schema/triggers/routines/events differ.');oDDL($r,'rehearsal-ddl.json');$expected=oMeta($r);
        $strip=fn($s)=>preg_replace('/,\n\)/',"\n)",preg_replace('/^.*(?:`expires_at` datetime|CONSTRAINT `chk_medicine_grant_active`).*\n/m','',$s));
        oAssert($strip($expected['definitions']['medicine_authority_grants'])===$strip($pre['metadata']['definitions']['medicine_authority_grants']),'Unrelated grant schema changed.');
        $compare=$expected;$compare['definitions']['medicine_authority_grants']=$pre['metadata']['definitions']['medicine_authority_grants'];oAssert($compare===$pre['metadata']&&oData($r)===$pre['data'],'Unrelated rehearsal metadata/data changed.');
        oSave('expected-metadata.json',$expected);oSave('recovery-verification.json',['verified'=>true,'backup'=>$backup,'import'=>$import,'tables'=>count($pre['data']),'data_matches'=>true,'metadata_matches'=>true,'migration_rehearsal'=>true]);echo json_encode(['status'=>'RECOVERY_AND_MIGRATION_REHEARSAL_VERIFIED','tables'=>count($pre['data']),'backup'=>$backup,'import'=>$import],JSON_PRETTY_PRINT).PHP_EOL;return;
    }
    if($mode==='check_unchanged'){
        oPrecondition($p);oAssert($pre['data']===oData($p)&&$pre['metadata']===oMeta($p)&&hash('sha256',serialize($p->query('SHOW GRANTS')->fetchAll()))===$pre['privileges_sha256'],'Normal state changed.');
        $out=['status'=>'STOPPED_BEFORE_NORMAL_DDL_AND_BOOTSTRAP','at'=>$p->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn(),'tables_unchanged'=>count($pre['data']),'triggers_unchanged'=>count($pre['metadata']['triggers']),'all_data_unchanged'=>true,'all_metadata_unchanged'=>true,'privileges_unchanged'=>true,'sql_mode'=>O_MODE,'global_sql_mode'=>O_MODE,'owner_active'=>true,'expires_at_nullable'=>false,'grants'=>0,'events'=>0,'backup'=>$backup,'recovery_verified'=>false];oSave('stopped-verification.json',$out);echo json_encode($out,JSON_PRETTY_PRINT).PHP_EOL;return;
    }
    $proof=oLoad('recovery-verification.json');oAssert($proof['verified']&&$proof['backup']===$backup,'Verified recovery unavailable.');
    if($mode==='deploy'){oPrecondition($p);oAssert(!file_exists(O_DIR.'/normal-ddl.json'),'Already attempted; no automatic retry.');oAssert($pre['data']===oData($p)&&$pre['metadata']===oMeta($p)&&hash('sha256',serialize($p->query('SHOW GRANTS')->fetchAll()))===$pre['privileges_sha256'],'Normal state changed; stop.');oDDL($p,'normal-ddl.json');}
    oAssert(oMeta($p)===oLoad('expected-metadata.json'),'Normal metadata differs from exact rehearsal.');oAssert(oData($p)===$pre['data'],'Normal data changed.');oAssert(hash('sha256',serialize($p->query('SHOW GRANTS')->fetchAll()))===$pre['privileges_sha256'],'Privileges changed.');
    $out=['status'=>'NORMAL_MIGRATION_VERIFIED_BOOTSTRAP_NOT_ATTEMPTED','target'=>'medical1','port'=>3306,'at'=>$p->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn(),'tables_preserved'=>count($pre['data']),'triggers_preserved'=>count($pre['metadata']['triggers']),'data_preserved'=>true,'metadata_matches_rehearsal'=>true,'privileges_unchanged'=>true,'sql_mode'=>O_MODE,'global_sql_mode'=>O_MODE,'grants'=>(int)$p->query('SELECT COUNT(*) FROM medicine_authority_grants')->fetchColumn(),'events'=>(int)$p->query('SELECT COUNT(*) FROM medicine_governance_events')->fetchColumn()];oSave('post-deployment.json',$out);echo json_encode($out,JSON_PRETTY_PRINT).PHP_EOL;
}
if(!defined('MEDICINE_OWNER_RECOVERY_LIBRARY')){
    try{oRun($argv[1]??'');}catch(Throwable $e){fwrite(STDERR,"STOP: ".$e->getMessage().PHP_EOL);exit(1);}
}
