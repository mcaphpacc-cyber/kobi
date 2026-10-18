<?php
declare(strict_types=1);

// CLI preparation only: no bootstrap, authentication session, services or writes.
define('MEDICINE_SELECTION_LIBRARY', true);
require __DIR__.'/medicine-candidate-selection.php';

function medicineReadinessScope(): array {
    return ['15'=>'Acetazolamide','54'=>'Allopurinol','319'=>'Carbamazepine','481'=>'Colchicine','2064'=>'Topiramate'];
}
// Phase 3.19 is a deployed schema prerequisite, not a clinical approval or pilot gate.
// Reuse the established SELECT/SHOW-only verifier and exact certified normal metadata.
function medicineReadinessSchemaExpected(): array {
    if(!defined('MEDICINE_PHASE319_DEPLOYMENT_LIBRARY')){
        define('MEDICINE_PHASE319_DEPLOYMENT_LIBRARY',true);
        require __DIR__.'/medicine-phase319-deployment.php';
    }
    p319Artifacts();
    $cert=oLoad('phase319-metadata-certified.json');
    oAssert($cert['status']==='CERTIFIED'&&$cert['migration_sha256']===P319_HASH
        &&hash_file('sha256',O_DIR.'/phase319-metadata-semantics.json')===$cert['semantic_certificate_sha256']
        &&hash_file('sha256',O_DIR.'/phase319-metadata-rehearsals.json')===$cert['rehearsal_certificate_sha256'],'Readiness schema certificate identity failed.');
    $inventory=$cert['normal_capture'];$extension=[];
    if(is_file(O_DIR.'/phase322-final-certified.json')){require_once __DIR__.'/medicine-phase322-deployed-prerequisite.php';$approved=p322Certificate();$inventory=$approved['actual'];$extension=['phase322_artifact_sha256'=>P322_HASH];}
    return ['status'=>'VERIFIED','migration_sha256'=>P319_HASH,'authorized_additions'=>$extension,
        'normal_tables'=>array_keys($inventory['data']),
        'normal_triggers'=>array_keys($inventory['triggers']),
        'table_definition_sha256'=>hash('sha256',$cert['expected']['definition']),
        'trigger_definition_sha256'=>hash('sha256',serialize($cert['expected']['show_create'])),
        'charset_client'=>'utf8mb4','collation_connection'=>'utf8mb4_general_ci'];
}
function medicineReadinessSchemaVerify(PDO $pdo): array {
    $expected=medicineReadinessSchemaExpected();$cert=oLoad('phase319-metadata-certified.json');
    $actual=p319Capture($pdo);if(is_file(O_DIR.'/phase322-final-certified.json')){require_once __DIR__.'/medicine-phase322-deployed-prerequisite.php';$actual=p322Project($pdo,$actual);}p319Same($actual,$cert['normal_capture']);
    p319Preserved($actual,oLoad('phase319-final-preflight.json'));
    $rows=medicineSelectionQuery($pdo,'SELECT * FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME');
    $complete=[];foreach($rows as $row){if($row['EVENT_OBJECT_TABLE']!==P319_TABLE){continue;}unset($row['TRIGGER_SCHEMA'],$row['EVENT_OBJECT_SCHEMA'],$row['CREATED']);$complete[]=$row;}
    oAssert($complete===$cert['normalized_information_schema'],'Readiness certified trigger metadata differs.');
    return $expected;
}
function medicineReadinessAnalyze(array $snapshot): array {
    $indexed=[]; foreach($snapshot['rows'] as $row){$id=(string)$row['substance_id'];if(isset($indexed[$id])){throw new RuntimeException('Duplicate selected record.');}$indexed[$id]=$row;}
    if(count($indexed)!==5||array_diff(array_keys($indexed),array_keys(medicineReadinessScope()))){throw new RuntimeException('Fixed five-record scope differs.');}
    $result=[];
    foreach(medicineReadinessScope() as $id=>$name){
        $row=$indexed[$id];$key=medicineSelectionCollisionKey($row['canonical_name']);$collisions=[];$peers=[];
        foreach($snapshot['names'] as $other){if((string)$other['substance_id']===(string)$id){continue;}if(medicineSelectionCollisionKey($other['canonical_name'])===$key||$other['canonical_name_normalized']===$row['canonical_name_normalized']){$collisions[]=(string)$other['substance_id'];}if(str_starts_with(mb_strtolower($other['canonical_name']),mb_strtolower($row['canonical_name']).' ')){$peers[]=(string)$other['substance_id'];}}
        sort($collisions,SORT_STRING);sort($peers,SORT_STRING);
        $marker=(bool)preg_match('/[+\/]|\b(?:and|with|hydrochloride|sodium|hydrate|tablet|capsule|solution|suspension|injection)\b/i',$row['canonical_name']);
        $clean=$row['canonical_name']===$name&&(string)$row['is_active']==='1'&&(string)$row['is_searchable']==='1'&&!$collisions&&!$peers&&!$marker&&(int)$row['membership_count']===0&&(int)$row['unlinked_name_members']===0&&(int)$row['form_count']===0&&(int)$row['form_component_count']===0;
        // Registry observations can establish partial technical fit, never approval.
        $result[]=['id'=>(string)$id,'name'=>$row['canonical_name'],'matches_phase314'=>$clean&&$row['identity_status']==='CANDIDATE'&&$row['identity_type']==='UNKNOWN'&&$row['naming_standard']==='WHO_INN'&&(int)$row['has_verification_source']===0&&(int)$row['has_reviewer']===0&&(int)$row['has_review_date']===0,
            'collision_ids'=>$collisions,'qualified_prefix_ids'=>$peers,'label_marker'=>$marker,
            'identity'=>$clean?'PARTIAL':'BLOCKED','evidence'=>'NOT YET PROVIDED','reviewer'=>'NOT YET PROVIDED','revision'=>'BLOCKED','pilot_gate'=>'BLOCKED','overall'=>'BLOCKED'];
    }
    return $result;
}

$mode=$argv[1]??'';
if($mode==='audit'){
    try{$pdo=(new App\Core\Database())->connection();}catch(Throwable $e){throw new RuntimeException('Read-only connection unavailable; credentials suppressed.');}
    $meta=medicineSelectionQuery($pdo,'SELECT DATABASE() database_name, @@version version, @@port port, @@sql_mode sql_mode, UTC_TIMESTAMP(6) observed_at')[0];
    if($meta['database_name']!=='medical1'||(int)$meta['port']!==3306){throw new RuntimeException('Expected normal registry instance not found.');}
    $ids=array_map('strval',array_keys(medicineReadinessScope()));$marks=implode(',',array_fill(0,5,'?'));
    $rows=medicineSelectionQuery($pdo,"SELECT s.substance_id,s.canonical_name,s.canonical_name_normalized,s.naming_standard,s.identity_type,s.identity_status,s.is_active,s.is_searchable,(s.verification_source IS NOT NULL AND TRIM(s.verification_source)<>'') has_verification_source,(s.reviewed_by IS NOT NULL) has_reviewer,(s.reviewed_at IS NOT NULL) has_review_date,
        (SELECT COUNT(*) FROM kobi_combination_items i WHERE i.substance_id=s.substance_id) membership_count,
        (SELECT COUNT(DISTINCT i.combination_id) FROM kobi_combination_items i WHERE i.substance_id=s.substance_id) combination_count,
        (SELECT COUNT(*) FROM kobi_combination_items i WHERE i.substance_id IS NULL AND i.component_name_normalized=s.canonical_name_normalized) unlinked_name_members,
        (SELECT COUNT(*) FROM kobi_substance_forms f WHERE f.substance_id=s.substance_id) form_count,
        (SELECT COUNT(*) FROM kobi_form_components f WHERE f.component_substance_id=s.substance_id) form_component_count,
        (SELECT COUNT(*) FROM kobi_identity_reviews r WHERE r.candidate_substance_id=s.substance_id) legacy_review_count,
        (SELECT COUNT(*) FROM kobi_who_inn_evidence e WHERE e.substance_id=s.substance_id) who_evidence_count,
        (SELECT COUNT(*) FROM kobi_substance_aliases a WHERE a.substance_id=s.substance_id) alias_count,
        (SELECT COUNT(*) FROM kobi_substance_aliases a WHERE a.substance_id=s.substance_id AND a.source_reference IS NOT NULL AND TRIM(a.source_reference)<>'') alias_source_reference_count
        FROM kobi_substances s WHERE s.substance_id IN ($marks) ORDER BY s.substance_id",$ids);
    // Only names/IDs beyond the fixed five, to screen collisions and qualified labels.
    $names=medicineSelectionQuery($pdo,'SELECT substance_id,canonical_name,canonical_name_normalized FROM kobi_substances ORDER BY substance_id');
    $tables=medicineSelectionQuery($pdo,"SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND (TABLE_NAME LIKE 'medicine\\_%' OR TABLE_NAME LIKE 'kobi\\_medicine\\_%' OR TABLE_NAME IN ('health_medicine_identity_links','health_medicine_selected_ingredients')) ORDER BY TABLE_NAME");
    $schemaPrerequisite=medicineReadinessSchemaVerify($pdo);
    $config=require dirname(__DIR__).'/app/Config/Config.php';$settings=$config['medicine_governance'];
    $safeConfig=[];foreach(['pilot_enabled','pilot_internal_enabled','pilot_code','pilot_limit','pilot_substances','pilot_actions','pilot_activation','owner_user_ids','registry_operator_user_ids','deployment_operator_user_ids','authoritative_tiers','evidence_sources'] as $key){$safeConfig[$key]=$settings[$key];}
    // This bounded audit deliberately stops if real configuration needs a deeper assessment.
    if($safeConfig['pilot_enabled']!==false||$safeConfig['pilot_internal_enabled']!==false||$safeConfig['pilot_substances']||$safeConfig['pilot_activation']||$safeConfig['owner_user_ids']||$safeConfig['registry_operator_user_ids']||$safeConfig['deployment_operator_user_ids']||$safeConfig['evidence_sources']){throw new RuntimeException('Readiness configuration changed; reassess without exposing personnel or evidence.');}
    $after=medicineSelectionQuery($pdo,'SELECT @@sql_mode sql_mode, UTC_TIMESTAMP(6) observed_at')[0];
    if($meta['sql_mode']!==$after['sql_mode']){throw new RuntimeException('SQL mode changed during observation.');}
    $snapshot=['meta'=>$meta,'rows'=>$rows,'names'=>$names,'medicine_tables'=>$tables,'schema_prerequisite'=>$schemaPrerequisite,'config'=>$safeConfig,'after'=>$after];
    $snapshot['readiness']=medicineReadinessAnalyze($snapshot);
    // Names used for comparison are not exported as a new registry census.
    $snapshot['name_digest']=hash('sha256',json_encode($names,JSON_THROW_ON_ERROR));unset($snapshot['names']);
    echo json_encode($snapshot,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
if($mode==='test'){
    $snapshot=json_decode(file_get_contents(__DIR__.'/.medicine315-readiness.json'),true,64,JSON_THROW_ON_ERROR);$checks=0;
    $check=function(bool $ok,string $message)use(&$checks){$checks++;if(!$ok){throw new RuntimeException($message);}};
    $check(count($snapshot['rows'])===5,'Five live records required');
    foreach($snapshot['readiness'] as $entry){$check($entry['matches_phase314'],'Selected registry state changed');$check($entry['identity']==='PARTIAL'&&$entry['evidence']==='NOT YET PROVIDED'&&$entry['reviewer']==='NOT YET PROVIDED'&&$entry['revision']==='BLOCKED'&&$entry['pilot_gate']==='BLOCKED'&&$entry['overall']==='BLOCKED','Missing prerequisites overstated');$check(!$entry['collision_ids']&&!$entry['qualified_prefix_ids']&&!$entry['label_marker'],'Collision or ambiguity overlooked');}
    $fixture=$snapshot;$fixture['names']=array_map(fn($r)=>array_intersect_key($r,array_flip(['substance_id','canonical_name','canonical_name_normalized'])),$snapshot['rows']);
    $expected=medicineReadinessAnalyze($fixture);$check($expected===$snapshot['readiness'],'Live readiness not reproducible');$fixture['rows']=array_reverse($fixture['rows']);$fixture['names']=array_reverse($fixture['names']);$check(medicineReadinessAnalyze($fixture)===$expected,'Ordering changes fixed selection');
    $check(array_column($expected,'id')===array_map('strval',array_keys(medicineReadinessScope())),'Candidate substitution');
    foreach(['membership_count','unlinked_name_members','form_count','form_component_count'] as $field){$changed=$fixture;$changed['rows'][0][$field]='1';$check(medicineReadinessAnalyze($changed)[4]['identity']==='BLOCKED','Relationship concern missed: '.$field);}
    $changed=$fixture;$changed['names'][]=['substance_id'=>'999999','canonical_name'=>'Topira-mate','canonical_name_normalized'=>'topira-mate'];$check(medicineReadinessAnalyze($changed)[4]['collision_ids']===['999999'],'Injected formatting collision missed');
    $changed=$fixture;$changed['names'][]=['substance_id'=>'999999','canonical_name'=>'Topiramate sodium','canonical_name_normalized'=>'topiramate sodium'];$check(medicineReadinessAnalyze($changed)[4]['identity']==='BLOCKED','Qualified label missed');
    $changed=$fixture;array_pop($changed['rows']);try{medicineReadinessAnalyze($changed);throw new LogicException('Missing ID accepted');}catch(RuntimeException $e){$check(true,'Missing ID rejected');}
    $changed=$fixture;$changed['rows'][0]['substance_id']='999999';try{medicineReadinessAnalyze($changed);throw new LogicException('Substitution accepted');}catch(RuntimeException $e){$check(true,'Substitution rejected');}
    foreach(['INSERT INTO x VALUES(1)','UPDATE x SET n=1','DELETE FROM x','CREATE TABLE x(n INT)','ALTER TABLE x ADD n INT','DROP TABLE x','TRUNCATE x','SELECT 1; DELETE FROM x','SELECT * FROM x FOR UPDATE',"SELECT 1 INTO OUTFILE 'x'",'SELECT GET_LOCK(\'x\',1)','SET sql_mode=\'\''] as $sql){try{medicineSelectionQuery((new ReflectionClass(PDO::class))->newInstanceWithoutConstructor(),$sql);throw new LogicException('Unsafe query accepted');}catch(RuntimeException $e){$check($e->getMessage()==='Read-only audit query rejected.','Read-only query guard');}}
    $schemaExpected=medicineReadinessSchemaExpected();
    $check($snapshot['schema_prerequisite']===$schemaExpected,'Exact deployed Phase 3.19 schema prerequisite');
    $medicineTables=array_values(array_filter($schemaExpected['normal_tables'],fn($name)=>str_starts_with($name,'medicine_')||str_starts_with($name,'kobi_medicine_')||in_array($name,['health_medicine_identity_links','health_medicine_selected_ingredients'],true)));
    $check(array_column($snapshot['medicine_tables'],'TABLE_NAME')===$medicineTables,'Exact current Medicine inventory');
    $approved322=$schemaExpected['authorized_additions']!==[];
    $check(count($schemaExpected['normal_tables'])===($approved322?57:55)&&count($schemaExpected['normal_triggers'])===($approved322?15:11),'Exact normal deployed inventory with certified additions only');
    $check($snapshot['meta']['sql_mode']===$snapshot['after']['sql_mode'],'SQL mode stable');
    echo json_encode(['checks'=>$checks,'failures'=>0,'live_candidates'=>5,'database_writes'=>0],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
throw new RuntimeException('Use audit (SELECT-only) or test (offline snapshot).');
