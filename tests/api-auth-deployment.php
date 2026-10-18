<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('zend.exception_ignore_args', '1');
define('SSC_RECOVERY_LIBRARY', true);
require __DIR__.'/saved-symptom-checks-recovery.php';
const A50_DIR = 'D:/xampp/kobi-backups/step50b-20261016';
const A50_DB = 'kobi_api_auth_recovery_20261016';
const A50_TABLES = ['user_api_sessions','user_api_refresh_tokens'];
const A50_TRIGGER = 'trg_users_api_revoke';
// Observed pre-existing, undeployed Treatment objects; this deployment must preserve their absence.
const A50_DEFERRED_TABLES = ['treatment_sources','treatment_protocol_sources','treatment_item_sources','treatment_protocol_events','treatment_editorial_permissions'];
const A50_DEFERRED_TRIGGERS = ['trg_treatment_source_no_self_insert','trg_treatment_source_no_self_update'];
function a50Write(string $name, array $value): void {
    $text=json_encode($value,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
    sscAssert(file_put_contents(A50_DIR.'/'.$name,$text)===strlen($text),'Evidence write failed.');
}
function a50Schema(string $sql): string { return preg_replace('/ AUTO_INCREMENT=\d+/','',$sql); }
function a50Apply(PDO $p,string $sql): void {
    preg_match_all('/CREATE TABLE user_api_[\s\S]*?ENGINE=InnoDB[^;]*;/',$sql,$tables);
    sscAssert(count($tables[0])===2 && preg_match('/CREATE TRIGGER trg_users_api_revoke[\s\S]*?END\$\$/',$sql,$trigger)===1,'Artifact parse differs.');
    foreach([...$tables[0],substr($trigger[0],0,-2)] as $statement){
        $p->exec($statement);sscAssert($p->query('SHOW WARNINGS')->fetchAll()===[],'Approved DDL produced warnings; stopped.');
    }
}
function a50Proof(): array {
    $proof=json_decode(file_get_contents(A50_DIR.'/recovery-verified.json'),true,512,JSON_THROW_ON_ERROR);
    sscAssert($proof['status']==='RECOVERY_VERIFIED' && hash_file('sha256',A50_DIR.'/before.bin')===$proof['backup_sha256']
        && hash_file('sha256',dirname(__DIR__).'/database/api-auth-foundation.sql')===$proof['artifact_sha256'],'Recovery/artifact proof differs.');
    return $proof;
}
function a50Verify(PDO $p,array $proof): array {
    $current=sscCapture($p);$before=unserialize(file_get_contents(A50_DIR.'/before.bin'),['allowed_classes'=>false]);
    $counts=[];
    foreach(A50_TABLES as $name){
        sscAssert(isset($current['tables'][$name]) && a50Schema($current['tables'][$name]['ddl'])===a50Schema($proof['expected_tables'][$name]),'Approved table differs: '.$name);
        $counts[$name]=count($current['tables'][$name]['rows']);unset($current['tables'][$name]);
    }
    sscAssert(($current['triggers'][A50_TRIGGER]??null)===$proof['expected_trigger'],'Approved trigger metadata differs.');
    unset($current['triggers'][A50_TRIGGER]);
    sscAssert(sscDigest($current,'medical1')===sscDigest($before,'medical1'),'Unrelated normal objects/data/defaults/SQL modes changed.');
    return ['status'=>'PASS','approved_row_counts'=>$counts,'unrelated_schema_and_rows_identical'=>true,'sql_mode'=>$current['mode']];
}
$action=$argv[1]??'preflight';
try {
    $p=(new App\Core\Database())->connection();
    sscAssert($p->query('SELECT DATABASE()')->fetchColumn()==='medical1','Unexpected normal database.');
    sscAssert(str_starts_with($p->query('SELECT VERSION()')->fetchColumn(),'10.4.27-MariaDB'),'Unexpected MariaDB version.');
    $sql=file_get_contents(dirname(__DIR__).'/database/api-auth-foundation.sql');
    sscAssert(hash('sha256',$sql)==='52ba71caea0f08b64542987ca96218c5f3ac7e4330e4d480758c22e80ce2eb12','Validated artifact hash differs.');
    $structure=file_get_contents(dirname(__DIR__).'/database/structure.sql');
    sscAssert(str_ends_with(str_replace("\r\n","\n",trim($structure)),str_replace("\r\n","\n",trim($sql))),'Structure/artifact differ.');
    if($action==='preflight' || $action==='backup-and-rehearse'){
        $p->beginTransaction();try{$normal=sscCapture($p);}finally{$p->rollBack();}
        foreach(A50_TABLES as $name){sscAssert(!isset($normal['tables'][$name]),'Approved object already exists; inspect before changes.');}
        sscAssert(!isset($normal['triggers'][A50_TRIGGER]),'Approved trigger already exists; inspect before changes.');
        preg_match_all('/^CREATE TABLE\s+`?([a-zA-Z0-9_]+)`?\s*\(/m',$structure,$names);
        $expected=array_values(array_diff($names[1],A50_TABLES));sort($expected);$actual=array_keys($normal['tables']);sort($actual);
        $missing=array_values(array_diff($expected,$actual));$deferred=A50_DEFERRED_TABLES;sort($missing);sort($deferred);
        sscAssert(array_diff($actual,$expected)===[] && $missing===$deferred,'Unexpected unrelated table-name inventory difference.');
        preg_match_all('/^CREATE TRIGGER\s+`?([a-zA-Z0-9_]+)`?\s/m',$structure,$names);
        $expected=array_values(array_diff($names[1],[A50_TRIGGER]));sort($expected);$actual=array_keys($normal['triggers']);sort($actual);
        $missingTriggers=array_values(array_diff($expected,$actual));$deferredTriggers=A50_DEFERRED_TRIGGERS;sort($missingTriggers);sort($deferredTriggers);
        sscAssert(array_diff($actual,$expected)===[] && $missingTriggers===$deferredTriggers,'Unexpected unrelated trigger-name inventory difference.');
        if($action==='preflight'){
            echo json_encode(['status'=>'PASS','tables'=>count($normal['tables']),'views'=>count($normal['views']),'triggers'=>count($normal['triggers']),'target_objects_absent'=>true,'preexisting_deferred_tables'=>A50_DEFERRED_TABLES,'preexisting_deferred_triggers'=>A50_DEFERRED_TRIGGERS,'unexpected_object_inventory_differences'=>false,'sql_mode'=>$normal['mode']],JSON_PRETTY_PRINT),"\n";exit;
        }
        sscAssert(is_dir(A50_DIR) && !file_exists(A50_DIR.'/before.bin') && !file_exists(A50_DIR.'/recovery-verified.json'),'Protected fresh backup location unavailable/already used.');
        $serialized=serialize($normal);sscAssert(file_put_contents(A50_DIR.'/before.bin',$serialized)===strlen($serialized),'Incomplete backup.');
        $restored=unserialize(file_get_contents(A50_DIR.'/before.bin'),['allowed_classes'=>false]);
        sscAssert($restored===$normal && hash_file('sha256',A50_DIR.'/before.bin')===hash('sha256',$serialized),'Backup readback differs.');
        foreach($normal['triggers'] as $trigger){sscAssert($trigger['sql_mode']===$normal['mode']['session_mode'],'Existing trigger SQL mode differs; no recovery or deployment.');}
        $q=$p->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');$q->execute([A50_DB]);sscAssert((int)$q->fetchColumn()===0,'Disposable recovery name already exists.');
        $defaults=$normal['database_defaults'];
        $p->exec('CREATE DATABASE '.sscIdent(A50_DB).' CHARACTER SET '.sscIdent($defaults['DEFAULT_CHARACTER_SET_NAME']).' COLLATE '.sscIdent($defaults['DEFAULT_COLLATION_NAME']));
        $p->exec('USE '.sscIdent(A50_DB));$p->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach($restored['tables'] as $name=>$table){
            $p->exec($table['ddl']);sscAssert($p->query('SHOW WARNINGS')->fetchAll()===[],'Recovery table warning.');
            $cols=array_values(array_filter($table['columns'],fn($c)=>!str_contains($c['Extra'],'GENERATED')));$fields=array_column($cols,'Field');
            $insert=$p->prepare('INSERT INTO '.sscIdent($name).' ('.implode(',',array_map('sscIdent',$fields)).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')');
            foreach($table['rows'] as $row){$insert->execute(array_map(fn($c)=>$row[$c],$fields));sscAssert($p->query('SHOW WARNINGS')->fetchAll()===[],'Recovery row warning.');}
        }
        $p->exec('SET FOREIGN_KEY_CHECKS=1');
        foreach($restored['views'] as $view){$p->exec(str_replace('`medical1`','`'.A50_DB.'`',$view));}
        foreach($restored['triggers'] as $trigger){
            $p->exec('SET NAMES '.sscIdent($trigger['character_set_client']).' COLLATE '.sscIdent($trigger['collation_connection']));
            $p->exec(str_replace('`medical1`','`'.A50_DB.'`',$trigger['SQL Original Statement']));sscAssert($p->query('SHOW WARNINGS')->fetchAll()===[],'Recovery trigger warning.');
        }
        $p->exec('SET NAMES utf8mb4');
        sscAssert(sscDigest(sscCapture($p),A50_DB)===sscDigest($normal,'medical1'),'Recovered definitions/columns/rows/views/trigger metadata differ.');
        a50Apply($p,$sql);$copy=sscCapture($p);$expectedTables=[];
        foreach(A50_TABLES as $name){$expectedTables[$name]=$copy['tables'][$name]['ddl'];}
        $expectedTrigger=$copy['triggers'][A50_TRIGGER];
        // The trigger itself is unqualified; normalize only a quoted recovery-database reference if present.
        foreach($expectedTrigger as &$value){if(is_string($value)){$value=str_replace('`'.A50_DB.'`','`medical1`',$value);}}unset($value);
        $p->exec('USE medical1');sscAssert(sscDigest(sscCapture($p),'medical1')===sscDigest($normal,'medical1'),'Normal state changed during rehearsal.');
        $proof=['status'=>'RECOVERY_VERIFIED','database'=>'medical1','recovery_database'=>A50_DB,'backup_sha256'=>hash_file('sha256',A50_DIR.'/before.bin'),'backup_bytes'=>filesize(A50_DIR.'/before.bin'),'normal_fingerprint'=>sscDigest($normal,'medical1'),'artifact_sha256'=>hash('sha256',$sql),'sql_mode'=>$normal['mode'],'expected_tables'=>$expectedTables,'expected_trigger'=>$expectedTrigger,'tables'=>count($normal['tables']),'views'=>count($normal['views']),'triggers'=>count($normal['triggers'])];
        a50Write('recovery-verified.json',$proof);echo "PASS: full recovery fidelity, exact approved DDL rehearsal, normal state unchanged; protected backup retained.\n";exit;
    }
    $proof=a50Proof();
    if($action==='deploy-normal'){
        sscAssert(sscDigest(sscCapture($p),'medical1')===$proof['normal_fingerprint'],'Normal pre-deployment checkpoint changed.');
        a50Apply($p,$sql);$result=a50Verify($p,$proof);a50Write('deployed.json',$result);echo json_encode($result,JSON_PRETTY_PRINT),"\n";
    }elseif($action==='verify'){
        $result=a50Verify($p,$proof);a50Write('final-state.json',$result);echo json_encode($result,JSON_PRETTY_PRINT),"\n";
    }elseif($action==='provision-limiter'){
        a50Verify($p,$proof);$path=dirname(__DIR__).'/.env';$before=file_get_contents($path);
        sscAssert(strlen((string)env('API_AUTH_RATE_KEY',''))===0 && !preg_match('/^API_AUTH_RATE_KEY=.+$/m',$before),'Limiter key already exists; no replacement.');
        sscAssert(!file_exists(A50_DIR.'/env-before.bin'),'Environment backup already exists.');
        sscAssert(file_put_contents(A50_DIR.'/env-before.bin',$before)===strlen($before),'Environment backup failed.');
        $line='API_AUTH_RATE_KEY='.bin2hex(random_bytes(32));
        $after=preg_match('/^API_AUTH_RATE_KEY=.*$/m',$before)?preg_replace('/^API_AUTH_RATE_KEY=.*$/m',$line,$before):$before.(str_ends_with($before,"\n")?'':"\n").$line."\n";
        sscAssert(file_put_contents($path,$after,LOCK_EX)===strlen($after),'Limiter provision write failed.');
        a50Write('limiter-provisioned.json',['status'=>'PASS','dedicated_key_present'=>true,'environment_other_bytes_preserved'=>preg_replace('/^API_AUTH_RATE_KEY=.*\R?/m','',$after)===preg_replace('/^API_AUTH_RATE_KEY=.*\R?/m','',$before)]);
        echo "PASS: dedicated limiter key provisioned through existing .env mechanism; no secret output.\n";
    }elseif($action==='cleanup'){
        a50Verify($p,$proof);sscAssert($proof['recovery_database']===A50_DB,'Disposable recovery identity differs.');
        $p->exec('DROP DATABASE '.sscIdent(A50_DB));echo "PASS: only disposable recovery database removed; verified backup retained.\n";
    }else{throw new RuntimeException('Unknown action.');}
}catch(Throwable $e){fwrite(STDERR,'STOP: '.$e->getMessage()."\n");exit(1);}
