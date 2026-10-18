<?php
declare(strict_types=1);
// Authorized operator workflow only. Never authenticates an actor or writes grants.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('MEDICINE_OWNER_RECOVERY_LIBRARY',true);
require __DIR__.'/medicine-owner-schema-deployment.php';
function fShows(PDO $p):array {
    $out=[];foreach($p->query('SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME') as $r){$s=$p->query('SHOW CREATE TRIGGER `'.$r['TRIGGER_NAME'].'`')->fetch();unset($s['Created']);$out[$r['TRIGGER_NAME']]=$s;}return $out;
}
function fState(PDO $p):array {return $p->query('SELECT DATABASE() db,@@version version,@@port port,@@sql_mode mode,@@global.sql_mode global_mode,@@character_set_client charset_client,@@character_set_connection charset_connection,@@character_set_results charset_results,@@collation_connection collation_connection,@@character_set_server charset_server,@@collation_server collation_server')->fetch();}
function fCapture(PDO $p):array {return ['data'=>oData($p),'metadata'=>oMeta($p),'triggers'=>fShows($p),'privileges_sha256'=>hash('sha256',serialize($p->query('SHOW GRANTS')->fetchAll())),'state'=>fState($p),'at'=>$p->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn()];}
function fBackup():void {
    $b=oLoad('backup.json');oAssert($b['bytes']===5352130&&filesize(O_DIR.'/before.sql')===5352130&&$b['sha256']==='f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2'&&hash_file('sha256',O_DIR.'/before.sql')===$b['sha256'],'Backup identity failed.');
    $a=oLoad('fidelity-restore1.json');$z=oLoad('fidelity-restore2.json');oAssert($a['status']==='RESTORED_VERIFIED'&&$z['status']==='RESTORED_VERIFIED'&&$z['independent_repeat_matches']&&$a['backup']===$b&&$z['backup']===$b,'Independent recovery certificates failed.');
    oAssert(hash_file('sha256',dirname(__DIR__).'/database/medicine-phase321a-owner-authority.sql')===O_HASH,'Migration identity failed.');
}
function fRun(string $mode):void {
    fBackup();
    if($mode==='preflight'){
        $p=oConnect(true);oPrecondition($p);$v=fCapture($p);$old=oLoad('preflight.json');oAssert(count($v['data'])===54&&count($v['triggers'])===8&&$v['metadata']===$old['metadata']&&$v['privileges_sha256']===$old['privileges_sha256'],'Preflight schema/privileges mismatch.');
        $changed=[];foreach($v['data'] as $n=>$d){if($d!==$old['data'][$n]){$changed[]=$n;}}oAssert($changed===[]||$changed===['users'],'Unexpected pre-existing data change; stop.');
        $v['changed_since_backup']=$changed;oSave('final-preflight.json',$v);echo json_encode(['status'=>'PREFLIGHT_CAPTURED','state'=>$v['state'],'tables'=>54,'triggers'=>8,'changed_since_backup'=>$changed,'grants'=>0,'events'=>0],JSON_PRETTY_PRINT).PHP_EOL;return;
    }
    $pre=oLoad('final-preflight.json');
    if($mode==='rehearse'){
        $p=oConnect(false);oAssert((int)$p->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".O_RESTORE."'")->fetchColumn()===0,'Fresh isolated database required.');$p->exec('CREATE DATABASE '.O_RESTORE.' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');$p->exec('USE '.O_RESTORE);
        $import=oImport($p,file_get_contents(O_DIR.'/before.sql'));$old=oLoad('preflight.json');oAssert($import['warnings']===[]&&oData($p)===$old['data']&&oMeta($p)===$old['metadata']&&fShows($p)===$pre['triggers'],'Restore fidelity failed.');
        $normal=oConnect(true);oPrecondition($normal);oAssert(oData($normal)===$pre['data']&&oMeta($normal)===$pre['metadata'],'Live prestate changed during rehearsal.');
        $changes=[];$live=$normal->query('SELECT * FROM users ORDER BY id')->fetchAll();$restored=$p->query('SELECT * FROM users ORDER BY id')->fetchAll();oAssert(count($live)===count($restored),'User inventory changed.');
        foreach($live as $i=>$row){foreach($row as $field=>$value){if($value!==$restored[$i][$field]){oAssert((int)$row['id']===1&&in_array($field,['last_login_at','updated_at'],true),'Unexpected user-field change; stop.');$changes[]=['account_id'=>1,'field'=>$field];}}}
        $p->exec(file_get_contents(dirname(__DIR__).'/database/medicine-phase321a-owner-authority.sql'));oAssert($p->query('SHOW WARNINGS')->fetchAll()===[],'Rehearsal warnings.');$expected=oMeta($p);
        $strip=fn($s)=>preg_replace('/,\n\)/',"\n)",preg_replace('/^.*(?:`expires_at` datetime|CONSTRAINT `chk_medicine_grant_active`).*\n/m','',$s));oAssert($strip($expected['definitions']['medicine_authority_grants'])===$strip($old['metadata']['definitions']['medicine_authority_grants']),'Unrelated grant schema changed.');$same=$expected;$same['definitions']['medicine_authority_grants']=$old['metadata']['definitions']['medicine_authority_grants'];oAssert($same===$old['metadata']&&oData($p)===$old['data']&&fShows($p)===$pre['triggers'],'Rehearsal preservation failed.');
        oSave('final-rehearsal.json',['verified'=>true,'metadata'=>$expected,'import'=>$import,'login_metadata_changes'=>$changes,'sql_mode'=>O_MODE,'artifact_sha256'=>O_HASH]);echo json_encode(['status'=>'EXACT_MIGRATION_REHEARSAL_PASS','restored_tables'=>54,'triggers'=>8,'import_statements'=>$import['statements'],'warnings'=>0,'login_metadata_changes'=>$changes],JSON_PRETTY_PRINT).PHP_EOL;return;
    }
    $expected=oLoad('final-rehearsal.json');oAssert($expected['verified']&&$expected['artifact_sha256']===O_HASH,'Rehearsal missing.');$p=oConnect(true);
    if($mode==='deploy'){
        oPrecondition($p);$now=fCapture($p);foreach(['data','metadata','triggers','privileges_sha256','state'] as $key){oAssert($now[$key]===$pre[$key],'Pre-deployment baseline changed: '.$key);}
        oAssert(!file_exists(O_DIR.'/final-normal-ddl.json'),'Migration already attempted; no retry.');$e=['status'=>'ATTEMPT_STARTED','start_utc'=>$p->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn(),'state'=>$now['state'],'artifact_sha256'=>O_HASH,'sql'=>file_get_contents(dirname(__DIR__).'/database/medicine-phase321a-owner-authority.sql'),'statements_executed'=>0];oSave('final-normal-ddl.json',$e);
        try{$p->exec($e['sql']);$e['statements_executed']=1;$e['warnings']=$p->query('SHOW WARNINGS')->fetchAll();$e['end_utc']=$p->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn();$e['exit_status']=0;$e['errors']=[];$e['status']='EXECUTED';oSave('final-normal-ddl.json',$e);oAssert($e['warnings']===[],'Migration warnings; stop.');}catch(Throwable $error){$e['status']='STOPPED';$e['exit_status']=1;$e['errors']=[['class'=>get_class($error),'code'=>$error->getCode()]];oSave('final-normal-ddl.json',$e);throw $error;}
    }
    oAssert(in_array($mode,['deploy','verify','postbootstrap'],true),'Unknown operation.');$after=fCapture($p);
    $meta=$after['metadata'];$wanted=$expected['metadata'];
    if($mode==='postbootstrap'){
        // Inserts advance only these AUTO_INCREMENT counters, not schema objects.
        foreach(['medicine_authority_grants','medicine_governance_events'] as $n){$meta['definitions'][$n]=preg_replace('/ AUTO_INCREMENT=\d+/','',$meta['definitions'][$n]);$wanted['definitions'][$n]=preg_replace('/ AUTO_INCREMENT=\d+/','',$wanted['definitions'][$n]);}
    }
    oAssert($meta===$wanted&&$after['triggers']===$pre['triggers']&&$after['privileges_sha256']===$pre['privileges_sha256']&&$after['state']===$pre['state'],'Post-operation metadata/privileges/mode mismatch.');
    $data=$after['data'];$baseline=$pre['data'];
    if($mode==='postbootstrap'){
        $grants=$p->query('SELECT * FROM medicine_authority_grants')->fetchAll();$events=$p->query('SELECT * FROM medicine_governance_events')->fetchAll();oAssert(count($grants)===1&&count($events)===1,'Expected exactly one grant/event.');$g=$grants[0];$e=$events[0];
        oAssert((int)$g['user_id']===1&&(int)$g['granted_by']===1&&$g['capability']==='administrator'&&$g['scope']==='catalog'&&$g['expires_at']===null&&(int)$g['is_active']===1&&$g['qualification_reference']===null&&$g['qualification_assessed_by']===null&&$g['qualification_scope']===null,'Owner grant differs.');
        $s=json_decode($e['snapshot_json'],true,512,JSON_THROW_ON_ERROR);$decision=(require dirname(__DIR__).'/app/Config/Config.php')['medicine_governance']['owner_bootstrap'];
        oAssert((int)$e['actor_id']===1&&(int)$e['grant_subject_id']===(int)$g['id']&&$e['action']==='grant'&&(int)$e['revision']===1&&$e['new_state']==='active'&&$e['authority_grant_id']===null&&hash_equals(hash('sha256',$e['snapshot_json'],true),$e['snapshot_hash']),'Audit binding/hash differs.');
        oAssert($s['establishment']['mechanism']==='initial_owner_bootstrap'&&(int)$s['establishment']['owner_user_id']===1&&$s['establishment']['decision_reference']===$decision['decision_reference']&&$s['establishment']['policy_version']===$decision['policy_version'],'Owner establishment metadata differs.');
        foreach(['user_id','capability','scope','is_active','qualification_reference','qualification_assessed_by','qualification_scope','granted_by','granted_at','expires_at'] as $field){oAssert(array_key_exists($field,$s)&&($s[$field]===null?$g[$field]===null:(string)$s[$field]===(string)$g[$field]),'Audit snapshot differs from grant: '.$field);}
        oAssert($e['previous_state']===null&&$e['chemical_ref_id']===null&&$e['medicine_identity_id']===null&&$e['medicine_presentation_id']===null&&(int)$e['independent']===0&&json_decode($e['evidence_json'],true)===[]&&trim($e['rationale'])!==''&&$e['occurred_at']>=$g['granted_at'],'Audit event shape differs.');
        $config=require dirname(__DIR__).'/app/Config/Config.php';$c=$config['medicine_governance'];oAssert(!$c['pilot_enabled']&&!$c['pilot_internal_enabled']&&$c['pilot_activation']===[]&&$c['owner_user_ids']===[]&&$c['registry_operator_user_ids']===[]&&$c['deployment_operator_user_ids']===[]&&$config['treatment_governance']['publication_mode']==='hold','Activation or Treatment state changed.');
        oAssert((int)$p->query("SELECT COUNT(*) FROM users WHERE id=1 AND status='active'")->fetchColumn()===1,'Owner inactive after bootstrap.');
        $after['safety']=['pilot_enabled'=>false,'pilot_internal_enabled'=>false,'pilot_activation'=>[],'treatment_publication_mode'=>'hold','extra_authority'=>false];
        foreach(['medicine_authority_grants','medicine_governance_events'] as $n){oAssert($data[$n]['count']===1&&$baseline[$n]['count']===0,'Authority count differs.');unset($data[$n],$baseline[$n]);}
        $after['owner_result']=['grant_id'=>$g['id'],'event_id'=>$e['id'],'actor_id'=>1,'subject_id'=>1,'capability'=>'administrator','scope'=>'catalog','expires_at'=>null,'granted_at'=>$g['granted_at'],'occurred_at'=>$e['occurred_at'],'snapshot_sha256'=>bin2hex($e['snapshot_hash']),'establishment'=>$s['establishment']];
    }else{oPreconditionAfter($p);}
    oAssert($data===$baseline,'Existing data changed after operation.');oSave($mode==='postbootstrap'?'final-post-bootstrap.json':'final-post-migration.json',$after);echo json_encode(['status'=>$mode==='postbootstrap'?'OWNER_BOOTSTRAP_VERIFIED':'NORMAL_MIGRATION_VERIFIED','tables'=>54,'triggers'=>8,'existing_data_preserved'=>true,'schema_verified'=>true,'privileges_unchanged'=>true,'sql_mode'=>O_MODE,'owner_result'=>$after['owner_result']??null],JSON_PRETTY_PRINT).PHP_EOL;
}
function oPreconditionAfter(PDO $p):void {oAssert((int)$p->query("SELECT COUNT(*) FROM users WHERE id=1 AND status='active'")->fetchColumn()===1,'Owner inactive.');foreach(['medicine_authority_grants','medicine_governance_events'] as $n){oAssert((int)$p->query('SELECT COUNT(*) FROM '.$n)->fetchColumn()===0,'Unexpected pre-bootstrap authority history.');}oAssert($p->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='medicine_authority_grants' AND COLUMN_NAME='expires_at'")->fetchColumn()==='YES','Nullable migration missing.');}
try{fRun($argv[1]??'');}catch(Throwable $e){fwrite(STDERR,'STOP: '.$e->getMessage().PHP_EOL);exit(1);}

