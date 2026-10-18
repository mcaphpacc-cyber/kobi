<?php
declare(strict_types=1);
// Operator-only exact projection of the six explicitly authorized Phase 3.22 additions.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
const P322_HASH='c7796b079be60b19931969641907e45ce9e6b4156bf0f825c99ef89696bf5dc9';
const P322_TABLES=['medicine_reviewer_qualifications','medicine_evidence_appraisals'];
function p322Certificate():array {
    $c=oLoad('phase322-final-certified.json');$journal=oLoad('phase322-final-normal-ddl.json');
    oAssert($c['status']==='SCHEMA_DEPLOYMENT_VERIFIED'&&$c['artifact_sha256']===P322_HASH
        &&hash_file('sha256',dirname(__DIR__).'/database/medicine-phase322-reviewer-evidence.sql')===P322_HASH
        &&$journal['status']==='EXECUTED'&&$journal['statements_executed']===6&&$journal['warnings']===[]&&$journal['errors']===[], 'Authorized Phase 3.22 deployment certificate failed.');
    return $c;
}
function p322Project(PDO $p,array $actual):array {
    $c=p322Certificate();$expected=$c['expected'];
    oAssert(count($actual['data'])===57&&count($actual['triggers'])===15,'Exact authorized Phase 3.22 inventory differs.');
    foreach(P322_TABLES as $n){oAssert(isset($actual['data'][$n])&&$actual['data'][$n]['count']===0&&$actual['metadata']['definitions'][$n]===$expected['definitions'][$n],'Phase 3.22 schema/data differs: '.$n);}
    $triggers=array_values(array_filter($actual['metadata']['triggers'],fn($t)=>in_array($t['EVENT_OBJECT_TABLE'],P322_TABLES,true)));
    $shows=array_intersect_key($actual['triggers'],$expected['show_create']);
    oAssert($triggers===$expected['triggers']&&$shows===$expected['show_create'],'Phase 3.22 exact trigger definitions/context differ.');
    $rows=$p->query('SELECT * FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME')->fetchAll();$complete=[];
    foreach($rows as $r){if(!in_array($r['EVENT_OBJECT_TABLE'],P322_TABLES,true)){continue;}unset($r['TRIGGER_SCHEMA'],$r['EVENT_OBJECT_SCHEMA'],$r['CREATED']);$complete[]=$r;}
    oAssert($complete===$expected['information_schema'],'Phase 3.22 complete trigger metadata differs.');
    $copy=$actual;foreach(P322_TABLES as $n){unset($copy['data'][$n],$copy['metadata']['definitions'][$n]);}
    $copy['metadata']['triggers']=array_values(array_filter($copy['metadata']['triggers'],fn($t)=>!in_array($t['EVENT_OBJECT_TABLE'],P322_TABLES,true)));
    foreach(array_keys($expected['show_create']) as $n){unset($copy['triggers'][$n]);}
    p319Same($copy,oLoad('phase322-final-preflight.json')['actual']);return $copy;
}
