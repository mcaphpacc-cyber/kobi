<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('MEDICINE_PHASE319_FINAL_LIBRARY',true);
require __DIR__.'/medicine-phase319-final-deployment.php';
require_once __DIR__.'/medicine-phase322-deployed-prerequisite.php';
$p=oConnect(true);$actual=p319Capture($p);$cases=[];
$projected=p322Project($p,$actual);p319Same($projected,oLoad('phase322-final-preflight.json')['actual']);
$cases[]=['name'=>'exact authorized additions preserve predecessor','passed'=>true];
$mutations=[
    'new data rejected'=>function(&$a){$a['data']['medicine_reviewer_qualifications']['count']=1;},
    'unexpected table rejected'=>function(&$a){$a['data']['unexpected_table']=['count'=>0];},
    'new definition mutation rejected'=>function(&$a){$a['metadata']['definitions']['medicine_evidence_appraisals'].=' changed';},
    'new trigger body mutation rejected'=>function(&$a){foreach($a['metadata']['triggers'] as &$r){if($r['EVENT_OBJECT_TABLE']==='medicine_evidence_appraisals'){$r['ACTION_STATEMENT'].=' changed';break;}}},
    'new trigger context mutation rejected'=>function(&$a){$a['triggers']['medicine_appraisal_no_update']['character_set_client']='utf8';},
    'existing data mutation rejected'=>function(&$a){$a['data']['medicine_authority_grants']['sha256']=str_repeat('0',64);},
    'existing trigger mutation rejected'=>function(&$a){$a['triggers']['medicine_events_no_update']['SQL Original Statement'].=' changed';},
];
foreach($mutations as $name=>$mutate){$copy=$actual;$mutate($copy);$rejected=false;try{p322Project($p,$copy);}catch(RuntimeException $e){$rejected=true;}$cases[]=['name'=>$name,'passed'=>$rejected];}
$proof=['cases'=>count($cases),'failures'=>count(array_filter($cases,fn($r)=>!$r['passed'])),'normal_ddl'=>0,'normal_data_writes'=>0,'results'=>$cases];oSave('phase322-final-prerequisite-tests.json',$proof);echo json_encode($proof,JSON_PRETTY_PRINT).PHP_EOL;exit($proof['failures']?1:0);
