<?php
declare(strict_types=1);
// Recovery only: never dispatch the deployment helper or execute the owner migration.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('MEDICINE_OWNER_RECOVERY_LIBRARY',true);
require __DIR__.'/medicine-owner-schema-deployment.php';
const R_HASH='f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2';
function rState(PDO $p):array {return $p->query('SELECT @@version version,@@port port,@@sql_mode mode,@@global.sql_mode global_mode,@@character_set_client charset_client,@@character_set_connection charset_connection,@@character_set_results charset_results,@@collation_connection collation_connection,@@character_set_server charset_server,@@collation_server collation_server')->fetch();}
function rBytes(string $bytes):array {
    $pairs=substr_count($bytes,"\r\n");return ['bytes'=>strlen($bytes),'sha256'=>hash('sha256',$bytes),'CRLF'=>$pairs,'LF_only'=>substr_count($bytes,"\n")-$pairs,'CR_only'=>substr_count($bytes,"\r")-$pairs];
}
function rBody(string $sql):string {
    oAssert((bool)preg_match('/FOR EACH ROW[ \t]*(?:\r\n|\n|\r| )?(.*)\z/s',$sql,$m),'Trigger body boundary missing.');
    return preg_replace('/\s*\*\/\s*\z/','',$m[1]);
}
function rShows(PDO $p):array {
    $out=[];foreach($p->query('SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME') as $row){$name=$row['TRIGGER_NAME'];oAssert((bool)preg_match('/\A[a-zA-Z0-9_]+\z/D',$name),'Unsafe trigger identifier.');$show=$p->query('SHOW CREATE TRIGGER `'.$name.'`')->fetch();unset($show['Created']);$out[$name]=$show;}return $out;
}
function rRun(string $mode):void {
    oAssert(in_array($mode,['fidelity-reproduce','restore1','restore2','cleanup','normalcheck'],true),'Recovery-only modes: fidelity-reproduce/restore1/restore2/cleanup/normalcheck.');
    $backup=oLoad('backup.json');$path=O_DIR.'/before.sql';oAssert(is_readable($path)&&filesize($path)===5352130&&$backup['bytes']===5352130&&hash_file('sha256',$path)===R_HASH&&$backup['sha256']===R_HASH,'Protected backup identity failed.');
    $pre=oLoad('preflight.json');oAssert(count($pre['data'])===54&&count($pre['metadata']['triggers'])===8,'Baseline inventory failed.');
    if($mode==='normalcheck'){
        $p=oConnect(true);oPrecondition($p);$out=['all_54_data_digests_match'=>oData($p)===$pre['data'],'all_schema_and_triggers_match'=>oMeta($p)===$pre['metadata'],'privileges_match'=>hash('sha256',serialize($p->query('SHOW GRANTS')->fetchAll()))===$pre['privileges_sha256'],'state'=>rState($p),'authority_grants'=>(int)$p->query('SELECT COUNT(*) FROM medicine_authority_grants')->fetchColumn(),'audit_events'=>(int)$p->query('SELECT COUNT(*) FROM medicine_governance_events')->fetchColumn()];
        $out['show_create_triggers']=rShows($p);$out['database_defaults']=$p->query('SELECT DEFAULT_CHARACTER_SET_NAME,DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')->fetch();
        oSave('fidelity-normal-read-only.json',$out);oAssert($out['all_54_data_digests_match']&&$out['all_schema_and_triggers_match']&&$out['privileges_match'],'Normal preservation check failed.');unset($out['show_create_triggers']);echo json_encode($out,JSON_PRETTY_PRINT).PHP_EOL;return;
    }
    $p=oConnect(false);
    if($mode==='cleanup'){$p->exec('DROP DATABASE IF EXISTS '.O_RESTORE);oAssert((int)$p->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".O_RESTORE."'")->fetchColumn()===0,'Isolated cleanup failed.');echo "Isolated database absent.\n";return;}
    oAssert((int)$p->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".O_RESTORE."'")->fetchColumn()===0,'Fresh isolated recovery database required.');
    $before=rState($p);$privileges=hash('sha256',serialize($p->query('SHOW GRANTS')->fetchAll()));$normal=oLoad('fidelity-normal-read-only.json');
    oAssert($normal['database_defaults']===['DEFAULT_CHARACTER_SET_NAME'=>'utf8mb4','DEFAULT_COLLATION_NAME'=>'utf8mb4_general_ci'],'Unexpected authoritative database defaults.');
    $instance=trim(file_get_contents(__DIR__.'/.medicine-owner-recovery/instance-id.txt'));oAssert((bool)preg_match('/\A[0-9a-f-]{36}\z/D',$instance),'Missing disposable instance identity.');
    $p->exec('CREATE DATABASE '.O_RESTORE.' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');$p->exec('USE '.O_RESTORE);
    $submitted=[];$source=file_get_contents($path);$reproduction=$mode==='fidelity-reproduce';
    // Reproduce exactly the retired tokenizer's reconstruction, only on disposable.
    $input=$reproduction?implode("\n",preg_split('/\r?\n/',$source)):$source;
    $observe=function(string $sql,int $number)use(&$submitted,$pre,$source,$p,$reproduction):void {
        if(!preg_match('/\/\*!50003 TRIGGER `([^`]+)`/',$sql,$match)){return;}
        $name=$match[1];$expected=array_values(array_filter($pre['metadata']['triggers'],fn($t)=>$t['TRIGGER_NAME']===$name));oAssert(count($expected)===1&&!isset($submitted[$name]),'Unexpected/duplicate trigger.');
        $body=rBody($sql);$baseline=$expected[0]['ACTION_STATEMENT'];
        $submitted[$name]=['statement'=>$number,'source_body'=>rBytes($baseline),'source_contains_body_verbatim'=>str_contains($source,$baseline),'submitted_body'=>rBytes($body),'submitted_body_equals_baseline'=>$body===$baseline,'submitted_sql_sha256'=>hash('sha256',$sql),'creation_context'=>$p->query('SELECT @@sql_mode mode,@@character_set_client charset_client,@@collation_connection collation_connection')->fetch()];
        if(!$reproduction){oAssert($body===$baseline&&str_contains($source,$baseline),'Trigger body changed before PDO execution.');}
    };
    try{$import=oImport($p,$input,$observe);}catch(Throwable $e){try{$after=rState($p);}catch(Throwable $stateError){$after=['unavailable'=>true];}oSave('fidelity-'.$mode.'-failure.json',['message'=>$e->getMessage(),'exit_code'=>1,'backup'=>$backup,'before'=>$before,'after'=>$after,'submitted'=>$submitted,'privileges_match_baseline'=>$privileges===$pre['privileges_sha256'],'helper_sha256'=>hash_file('sha256',__DIR__.'/medicine-owner-schema-deployment.php')]);throw $e;}
    $data=oData($p);$metadata=oMeta($p);$after=rState($p);
    $shows=rShows($p);foreach($metadata['triggers'] as $trigger){$name=$trigger['TRIGGER_NAME'];$body=$trigger['ACTION_STATEMENT'];$showBody=rBody($shows[$name]['SQL Original Statement']);$submitted[$name]['stored_body']=rBytes($body);$submitted[$name]['show_create_body']=rBytes($showBody);$submitted[$name]['stored_body_equals_submitted']=$submitted[$name]['stored_body']===$submitted[$name]['submitted_body'];$submitted[$name]['show_create_body_equals_stored']=$showBody===$body;}
    $out=['status'=>'RESTORED_PENDING_COMPARISON','recovery_instance_id'=>$instance,'backup'=>$backup,'import'=>$import,'before'=>$before,'after'=>$after,'table_count'=>count($data),'trigger_count'=>count($metadata['triggers']),'data'=>$data,'metadata'=>$metadata,'trigger_byte_diagnostics'=>$submitted,'show_create_triggers'=>$shows,'show_create_matches_normal'=>$shows===$normal['show_create_triggers'],'all_data_match'=>$data===$pre['data'],'all_metadata_match'=>$metadata===$pre['metadata'],'privileges_sha256'=>$privileges,'privileges_match_baseline'=>$privileges===$pre['privileges_sha256'],'privileges_unchanged'=>$privileges===hash('sha256',serialize($p->query('SHOW GRANTS')->fetchAll())),'authority_grants'=>(int)$p->query('SELECT COUNT(*) FROM medicine_authority_grants')->fetchColumn(),'audit_events'=>(int)$p->query('SELECT COUNT(*) FROM medicine_governance_events')->fetchColumn(),'owner_grants'=>(int)$p->query('SELECT COUNT(*) FROM medicine_authority_grants WHERE user_id=1')->fetchColumn(),'helper_sha256'=>hash_file('sha256',__DIR__.'/medicine-owner-schema-deployment.php')];
    oSave('fidelity-'.$mode.'.json',$out);
    if($reproduction){oAssert(!$out['all_metadata_match'],'Retired tokenizer mismatch was not reproduced.');foreach($submitted as $t){oAssert(!$t['submitted_body_equals_baseline']&&$t['stored_body_equals_submitted']&&$t['show_create_body_equals_stored'],'Reproduction byte boundary differs.');}$out['status']='EXPECTED_RETIRED_TOKENIZER_MISMATCH_REPRODUCED';oSave('fidelity-'.$mode.'.json',$out);echo "Retired tokenizer mismatch reproduced for all 8 triggers; converted before PDO, server preserves submitted bytes.\n";return;}
    oAssert($out['all_data_match']&&$out['all_metadata_match'],'Restore schema/data semantics differ.');oAssert($before===$after,'Unexpected restored session/server state.');oAssert($out['privileges_match_baseline']&&$out['privileges_unchanged'],'Privileges cannot be verified.');oAssert($out['authority_grants']===0&&$out['audit_events']===0&&$out['owner_grants']===0,'Unexpected authority/audit data.');
    oAssert(count($submitted)===8&&$out['show_create_matches_normal'],'SHOW CREATE trigger fidelity differs.');foreach($submitted as $t){oAssert($t['stored_body_equals_submitted']&&$t['show_create_body_equals_stored'],'Stored/returned trigger bytes differ.');}
    oAssert($before['charset_server']==='utf8mb4'&&$before['collation_server']==='utf8mb4_general_ci','Server defaults differ.');oAssert($import['warnings']===[]&&$import['omitted_sql_mode_directives']===[],'Import warnings/omissions; stop.');
    if($mode==='restore2'){$first=oLoad('fidelity-restore1.json');oAssert($first['status']==='RESTORED_VERIFIED'&&$first['recovery_instance_id']!==$instance,'Independent fresh environment not established.');foreach(['data','metadata','before','after','import','privileges_sha256','show_create_triggers','trigger_byte_diagnostics'] as $key){oAssert($first[$key]===$out[$key],'Independent restore differs: '.$key);}$out['independent_repeat_matches']=true;}
    $out['status']='RESTORED_VERIFIED';oSave('fidelity-'.$mode.'.json',$out);unset($out['data'],$out['metadata'],$out['show_create_triggers'],$out['trigger_byte_diagnostics'],$out['import']);echo json_encode($out,JSON_PRETTY_PRINT).PHP_EOL;
}
try{rRun($argv[1]??'');}catch(Throwable $e){fwrite(STDERR,'STOP: '.$e->getMessage().PHP_EOL);exit(1);}
