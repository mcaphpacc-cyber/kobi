<?php
declare(strict_types=1);
// Recovery diagnostics only: no deployment dispatch, no normal DDL/data writes.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('MEDICINE_PHASE319_DEPLOYMENT_LIBRARY',true);
require __DIR__.'/medicine-phase319-deployment.php';
const D319_FIXTURE='kobi_phase319_diagnostics_fixture';
function d319Backup():array {
    p319Artifacts();$b=p319Fresh();$doc=file_get_contents(dirname(__DIR__).'/docs/MEDICINE_PHASE_3_19_CONTROLLED_DEPLOYMENT.md');oAssert(str_contains($doc,$b['sha256'])&&str_contains($doc,(string)str_replace('/','\\',$b['path'])),'Failed-restore checkpoint not identified in report.');oAssert($b['bytes']===5353453&&$b['sha256']==='f4587999dc9e6f3f7dcd50bca9517bbce70ba40217f244434e7891ec4d52b984','Diagnostic checkpoint differs.');return $b;
}
function d319Normal():array {
    $p=oConnect(true);$v=p319Capture($p);p319Same($v,oLoad('phase319-preflight.json'));$v['safety']=p319Safety($p);$v['at_utc']=$p->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn();return $v;
}
function d319Result(array $entries):array {
    $all=[];foreach($entries as $e){foreach($e['warnings'] as $w){$all[]=['statement'=>$e['statement'],'line'=>$e['line'],'sql_sha256'=>$e['sql_sha256'],'sql_mode_directive'=>$e['sql_mode_directive'],'warning'=>$w];}}
    return ['conditions'=>count($all),'warnings'=>$all,'status'=>$all===[]?'ZERO_WARNINGS':'WARNINGS_PRESENT','recovery_acceptance'=>$all===[]?'WARNING_GATE_SATISFIED':'BLOCKED'];
}
function d319Run(string $mode):void {
    $backup=d319Backup();oAssert(in_array($mode,['preflight','selftest','restore1','restore2','finalcheck'],true),'Diagnostics-only modes required.');
    if($mode==='preflight'||$mode==='finalcheck'){$v=d319Normal();oSave('phase319-diagnostic-'.$mode.'.json',['backup'=>$backup,'normal'=>$v,'normal_ddl'=>0,'normal_data_writes'=>0,'authority_writes'=>0]);echo json_encode(['status'=>'NORMAL_UNCHANGED','tables'=>54,'triggers'=>8,'backup'=>$backup,'owner_expiry'=>null,'extra_authority'=>0,'pilot'=>false,'treatment'=>'hold'],JSON_PRETTY_PRINT).PHP_EOL;return;}
    $p=oConnect(false);
    if($mode==='selftest'){d319Selftest($p);return;}
    oAssert(oLoad('phase319-diagnostic-selftest.json')['failures']===0,'Warning-capture self-tests must pass first.');
    $name='phase319-diagnostic-'.$mode.'.json';oAssert(!file_exists(O_DIR.'/'.$name),'Independent diagnostic result already exists; never overwrite.');$instance=trim(file_get_contents(__DIR__.'/.medicine-owner-recovery/instance-id.txt'));
    if($mode==='restore2'){$first=oLoad('phase319-diagnostic-restore1.json');oAssert($first['capture_complete']&&$first['all_fidelity_checks_pass']&&$first['instance']!==$instance,'Independent first restore proof missing or instance reused.');}
    $normal=d319Normal();$pre=oLoad('phase319-preflight.json');$state=p319State($p);$priv=hash('sha256',serialize($p->query('SHOW GRANTS')->fetchAll()));oAssert($priv===$pre['privileges_sha256'],'Disposable privilege context differs.');oAssert((int)$p->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".P319_DB."'")->fetchColumn()===0,'Fresh recovery database required.');
    $p->exec('CREATE DATABASE '.P319_DB.' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');$p->exec('USE '.P319_DB);
    $proof=['status'=>'RESTORE_STARTED','instance'=>$instance,'backup'=>$backup,'started_at_utc'=>gmdate('Y-m-d H:i:s').' UTC','version'=>$state['version'],'php_version'=>PHP_VERSION,'pdo_mysql_client'=>$p->getAttribute(PDO::ATTR_CLIENT_VERSION),'helper_sha256'=>hash_file('sha256',__DIR__.'/medicine-owner-schema-deployment.php'),'before_state'=>$state,'entries'=>[],'completed_statements'=>0,'capture_complete'=>false,'errors'=>[]];oSave($name,$proof);
    $source=file_get_contents($backup['path']);$operation=[];
    $before=function(string $sql,int $number)use(&$operation){
        $operation=['sql_sha256'=>hash('sha256',$sql),'type'=>'other'];
        foreach(['trigger'=>'/\bTRIGGER `([^`]+)`/','table'=>'/CREATE TABLE `([^`]+)`/','data'=>'/INSERT INTO `([^`]+)`/','view'=>'/\bVIEW `([^`]+)`/'] as $type=>$pattern){if(preg_match($pattern,$sql,$m)){$operation=['type'=>$type,'object'=>$m[1],'sql_sha256'=>hash('sha256',$sql)];break;}}
        if(preg_match('/\bGRANT\b|FLUSH PRIVILEGES/i',$sql)){$operation['type']='privilege';}
    };
    $retain=function(array $entry)use(&$proof,&$operation,$name){$entry['operation']=$operation;$proof['entries'][]=$entry;if($entry['succeeded']){$proof['completed_statements']++;}oSave($name,$proof);};
    try{$import=oImport($p,$source,$before,$retain);$proof['import']=$import;$proof['capture_complete']=true;}catch(Throwable $e){$proof['status']='BLOCKED_RESTORE_ERROR';$proof['errors'][]=['class'=>get_class($e),'code'=>$e->getCode()];oSave($name,$proof);throw $e;}
    $actual=p319Capture($p);$proof['actual']=$actual;$checks=[];foreach(['data','metadata','triggers','state','defaults','privileges_sha256'] as $k){$checks[$k]=$actual[$k]===$pre[$k];}$checks['final_state_matches_initial']=p319State($p)===$state;$proof['checks']=$checks;$proof['all_fidelity_checks_pass']=!in_array(false,$checks,true);$proof['diagnostics']=d319Result($proof['entries']);$proof['status']=$proof['all_fidelity_checks_pass']&&$proof['diagnostics']['conditions']===0?'ZERO_WARNING_RESTORE_VERIFIED':'BLOCKED_WARNINGS_PRESENT';$proof['ended_at_utc']=gmdate('Y-m-d H:i:s').' UTC';
    if($mode==='restore2'){
        $a=oLoad('phase319-diagnostic-restore1.json');$comparison=['same_backup'=>$a['backup']===$backup,'independent_instances'=>$a['instance']!==$instance,'warnings'=>$a['diagnostics']===$proof['diagnostics'],'actual'=>$a['actual']===$actual,'mode_transitions'=>$a['import']['executed_sql_mode_directives']===$import['executed_sql_mode_directives'],'statements'=>$a['import']['statements']===$import['statements']];$proof['independent_comparison']=$comparison;oAssert(!in_array(false,$comparison,true),'Independent restore diagnostic/fidelity mismatch.');
    }
    oSave($name,$proof);d319Normal();oAssert($proof['capture_complete']&&$proof['all_fidelity_checks_pass'],'Restore capture/fidelity failed.');
    // Diagnostic completion is not recovery acceptance; warnings remain BLOCKED.
    echo json_encode(['status'=>$proof['status'],'instance'=>$instance,'statements'=>$import['statements'],'conditions'=>$proof['diagnostics']['conditions'],'warnings'=>$proof['diagnostics']['warnings'],'fidelity_checks'=>$checks,'independent_comparison'=>$proof['independent_comparison']??null],JSON_PRETTY_PRINT).PHP_EOL;
}
function d319Selftest(PDO $p):void {
    oAssert((int)$p->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".D319_FIXTURE."'")->fetchColumn()===0,'Fresh synthetic fixture required.');$before=p319State($p);$p->exec('CREATE DATABASE '.D319_FIXTURE.' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');$p->exec('USE '.D319_FIXTURE);$cases=[];$assertions=0;
    $check=function(bool $ok)use(&$assertions){$assertions++;oAssert($ok,'Diagnostic fixture assertion failed.');};
    $test=function(string $name,callable $f)use(&$cases){try{$f();$cases[]=['name'=>$name,'status'=>'PASS'];}catch(Throwable $e){$cases[]=['name'=>$name,'status'=>'FAIL','error'=>$e->getMessage()];}};
    $run=function(string $sql)use($p){$entries=[];$r=oImport($p,$sql,null,function(array $e)use(&$entries){$entries[]=$e;});oSave('phase319-diagnostic-last-fixture.json',$entries);return [$r,$entries];};
    $single="DO CAST('single-warning' AS UNSIGNED);\n";
    $test('single known warning retains exact severity code message',function()use($run,$single,$check){[$r,$e]=$run($single);$check($e[0]['condition_count']===1);$check($e[0]['warnings']===[['Level'=>'Warning','Code'=>1292,'Message'=>"Truncated incorrect INTEGER value: 'single-warning'"]]);$check(count($r['warnings'])===1);});
    $test('multiple warnings retained in original order',function()use($run,$check){[$r,$e]=$run("DO CAST('first-warning' AS UNSIGNED),CAST('second-warning' AS UNSIGNED);\n");$check($e[0]['condition_count']===2);$check(array_column($e[0]['warnings'],'Message')===["Truncated incorrect INTEGER value: 'first-warning'","Truncated incorrect INTEGER value: 'second-warning'"]);});
    $test('capture persists before next SQL clears warning state',function()use($run,$single,$p,$check){[$r,$e]=$run($single."SELECT TABLE_NAME FROM information_schema.TABLES LIMIT 1;\n");$check(count($e[0]['warnings'])===1&&$e[1]['warnings']===[]);$check($p->query('SHOW WARNINGS')->fetchAll()===[]);$check(count($r['warnings'])===1);});
    $test('zero warning restore reports zero',function()use($run,$check){[$r,$e]=$run("CREATE TABLE zero_fixture(id int NOT NULL PRIMARY KEY);\nINSERT INTO zero_fixture VALUES(1);\n");$check($r['warnings']===[]&&d319Result($e)['status']==='ZERO_WARNINGS');$check(count($e)===2&&$e[0]['condition_count']===0&&$e[1]['condition_count']===0);});
    $test('trigger creation warning has exact source binding',function()use($run,$check){[$r,$e]=$run("DROP TRIGGER IF EXISTS missing_diagnostic_trigger;\n");$check($e[0]['warnings']===[['Level'=>'Note','Code'=>1360,'Message'=>'Trigger does not exist']]);$check($e[0]['statement']===1&&strlen($e[0]['sql_sha256'])===64);});
    $test('trigger creation warning captured on duplicate guarded fixture',function()use($run,$check){[$r,$e]=$run("CREATE TABLE trigger_fixture(id int);\nCREATE TRIGGER diagnostic_warning BEFORE INSERT ON trigger_fixture FOR EACH ROW SET NEW.id=NEW.id;\nCREATE TRIGGER IF NOT EXISTS diagnostic_warning BEFORE INSERT ON trigger_fixture FOR EACH ROW SET NEW.id=NEW.id;\n");$check($e[0]['warnings']===[]&&$e[1]['warnings']===[]);$check($e[2]['warnings']===[['Level'=>'Note','Code'=>1359,'Message'=>"Trigger '".D319_FIXTURE.".diagnostic_warning' already exists"]]);});
    $test('SQL-mode context directive warning captured without changing mode',function()use($run,$check,$p,$before){[$r,$e]=$run("/*!50003 SET @diagnostic_sql_mode_copy = CONCAT(@@sql_mode,CAST('mode-warning' AS UNSIGNED)) */;\n");$check($e[0]['sql_mode_directive']&&$e[0]['warnings']===[['Level'=>'Warning','Code'=>1292,'Message'=>"Truncated incorrect INTEGER value: 'mode-warning'"]]);$check($r['executed_sql_mode_directives'][0]['before']===$before['mode']&&$r['executed_sql_mode_directives'][0]['after']===$before['mode']);$check($p->query('SELECT @@sql_mode')->fetchColumn()===$before['mode']);});
    $test('failed restore retains earlier warnings and immediate error diagnostics',function()use($p,$single,$check){$entries=[];$path=O_DIR.'/phase319-diagnostic-failure-fixture.json';$caught=false;try{oImport($p,$single."INSERT INTO nonexistent_fixture VALUES(1);\n",null,function(array $e)use(&$entries,$path){$entries[]=$e;file_put_contents($path,json_encode($entries,JSON_THROW_ON_ERROR));});}catch(PDOException $e){$caught=true;}$saved=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$check($caught&&$saved===$entries&&count($entries)===2);$check($entries[0]['warnings'][0]['Code']===1292&&!$entries[1]['succeeded']);$check($entries[1]['error']['code']===1146&&$entries[1]['warnings'][0]['Level']==='Error'&&$entries[1]['warnings'][0]['Code']===1146);});
    $test('warning path invokes retention hook and suppresses no conditions',function()use($p,$single,$check){$saved=[];$r=oImport($p,$single,null,function(array $e)use(&$saved){$saved[]=$e;});$check($saved[0]['condition_count']===count($saved[0]['warnings']));$check($saved[0]['warnings']===$r['warnings'][0]['warnings']);});
    $test('warnings cannot automatically become recovery success',function()use($run,$single,$check){[$r,$e]=$run($single);$result=d319Result($e);$check($result['status']==='WARNINGS_PRESENT'&&$result['recovery_acceptance']==='BLOCKED');});
    $p->exec('DROP DATABASE '.D319_FIXTURE);$check(p319State($p)===$before);$failures=count(array_filter($cases,fn($c)=>$c['status']==='FAIL'));$proof=['cases'=>count($cases),'assertions'=>$assertions,'failures'=>$failures,'results'=>$cases,'state_unchanged'=>true];oSave('phase319-diagnostic-selftest.json',$proof);echo json_encode($proof,JSON_PRETTY_PRINT).PHP_EOL;oAssert($failures===0,'Diagnostic self-tests failed.');
}
try{d319Run($argv[1]??'');}catch(Throwable $e){fwrite(STDERR,'STOP: '.$e->getMessage().PHP_EOL);exit(1);}
