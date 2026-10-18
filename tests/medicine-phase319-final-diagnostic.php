<?php
declare(strict_types=1);
define('MEDICINE_PHASE319_DEPLOYMENT_LIBRARY',true);
require __DIR__.'/medicine-phase319-deployment.php';
$p=oConnect(true);$pre=oLoad('phase319-final-preflight.json');$expected=oLoad('phase319-final-expected.json');$now=p319Capture($p);
$checks=[];try{p319Preserved($now,$pre);$checks['all_existing_data_schema_triggers_state_preserved']=true;}catch(Throwable $e){$checks['all_existing_data_schema_triggers_state_preserved']=$e->getMessage();}
try{$safety=p319Safety($p);$checks['authority_exposure_safety']=true;}catch(Throwable $e){$safety=[];$checks['authority_exposure_safety']=$e->getMessage();}
$actual=['definition'=>$now['metadata']['definitions'][P319_TABLE],'triggers'=>array_values(array_filter($now['metadata']['triggers'],fn($t)=>$t['EVENT_OBJECT_TABLE']===P319_TABLE)),'show_create'=>array_intersect_key($now['triggers'],$expected['show_create'])];
$differences=[];
$walk=function($a,$b,string $path)use(&$walk,&$differences){if($a===$b)return;if(is_array($a)&&is_array($b)){foreach(array_unique(array_merge(array_keys($a),array_keys($b))) as $k){$walk($a[$k]??null,$b[$k]??null,$path.'/'.$k);}}else{$differences[]=['path'=>$path,'expected'=>$a,'actual'=>$b];}};
foreach($actual as $k=>$v){$checks[$k.'_exact_match']=$expected[$k]===$v;$walk($expected[$k],$v,$k);}
$e=oLoad('phase319-final-normal-ddl.json');
$codeMatches=true;foreach($pre['code'] as $file=>$hash){if(hash_file('sha256',dirname(__DIR__).'/'.$file)!==$hash){$codeMatches=false;}}
$backup=oLoad('phase319-final-backup.json');
$checks['application_and_schema_files_unchanged']=$codeMatches;
$checks['fresh_backup_identity_preserved']=hash_file('sha256',$backup['path'])===$backup['sha256']&&filesize($backup['path'])===$backup['bytes'];
$proof=['status'=>'BLOCKED','observed_utc'=>$p->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn(),'tables'=>count($now['data']),'triggers'=>count($now['triggers']),'provisional_rows'=>$now['data'][P319_TABLE]['count'],'migration_statements'=>$e['statements_executed'],'migration_warnings'=>count($e['warnings']),'migration_errors'=>count($e['errors']),'checks'=>$checks,'differences'=>$differences,'safety'=>$safety,'actual'=>$now];
oSave('phase319-final-blocked.json',$proof);unset($proof['actual']);echo json_encode($proof,JSON_PRETTY_PRINT).PHP_EOL;
