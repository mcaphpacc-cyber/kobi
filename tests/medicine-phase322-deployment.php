<?php
declare(strict_types=1);
// Read-only preflight. No DDL, data writes, grants, fixtures or authentication substitution.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('MEDICINE_OWNER_RECOVERY_LIBRARY',true);
require __DIR__.'/medicine-owner-schema-deployment.php';
try {
    oAssert(($argv[1]??'')==='preflight','Only read-only preflight is supported.');
    $root=dirname(__DIR__);$report=file_get_contents($root.'/docs/MEDICINE_PHASE_3_21A_OWNER_DEPLOYMENT.md');
    oAssert(preg_match('/^- Path: `([^`]+)`/m',$report,$match)===1,'Authoritative backup path unavailable in deployment documentation.');
    $backup=realpath($match[1]);oAssert($backup!==false&&is_readable($backup),'Documented backup unavailable.');
    $hash='f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2';
    oAssert(filesize($backup)===5352130&&hash_file('sha256',$backup)===$hash,'Documented backup identity failed.');
    $ddl=$root.'/database/medicine-phase322-reviewer-evidence.sql';$ddlHash='c7796b079be60b19931969641907e45ce9e6b4156bf0f825c99ef89696bf5dc9';
    oAssert(hash_file('sha256',$ddl)===$ddlHash,'Exact Phase 3.22 DDL identity failed.');
    $structure=file_get_contents($root.'/database/structure.sql');$marker=strpos($structure,'/* Phase 3.22:');oAssert($marker!==false&&trim(substr($structure,$marker))===trim(file_get_contents($ddl)),'Structure/deployment artifact differs.');
    $tested=oLoad('phase322-isolated.json');oAssert($tested['failures']===[]&&$tested['additive_statements']===6&&isset($tested['metadata']['definitions']['medicine_evidence_appraisals']),'Retained validated schema proof unavailable.');
    $p=oConnect(true);$data=oData($p);$metadata=oMeta($p);$privileges=hash('sha256',serialize($p->query('SHOW GRANTS')->fetchAll()));$prior=oLoad('phase322-normal-baseline.json');
    oAssert($data===$prior['data']&&$metadata===$prior['metadata']&&$privileges===$prior['privileges_sha256'],'Normal data/schema/privilege state differs from retained baseline.');
    oAssert(count($data)===54&&count($metadata['triggers'])===8,'Normal inventory differs.');
    oAssert((int)$p->query("SELECT COUNT(*) FROM users WHERE id=1 AND status='active'")->fetchColumn()===1,'Owner inactive.');
    oAssert((int)$p->query('SELECT COUNT(*) FROM medicine_authority_grants')->fetchColumn()===1&&(int)$p->query("SELECT COUNT(*) FROM medicine_authority_grants WHERE user_id=1 AND granted_by=1 AND capability='administrator' AND scope='catalog' AND is_active=1 AND revoked_at IS NULL AND expires_at IS NULL")->fetchColumn()===1,'Owner authority differs.');
    oAssert((int)$p->query('SELECT COUNT(*) FROM medicine_governance_events')->fetchColumn()===1,'Governance event count differs.');
    foreach(['kobi_medicine_chemical_refs','kobi_medicine_identities','medicine_pilot_records'] as $table){oAssert($data[$table]['count']===0,'Unexpected Medicine approval/pilot data.');}
    $config=require $root.'/app/Config/Config.php';$c=$config['medicine_governance'];oAssert(!$c['pilot_enabled']&&!$c['pilot_internal_enabled']&&$c['pilot_activation']===[]&&$c['reviewer_verifications']===[]&&$c['evidence_sources']===[]&&$config['treatment_governance']['publication_mode']==='hold','Exposure/verification/Treatment configuration differs.');
    $state=$p->query('SELECT DATABASE() db,@@port port,@@version version,@@sql_mode mode,@@global.sql_mode global_mode,@@character_set_client charset_client,@@character_set_connection charset_connection,@@character_set_results charset_results,@@collation_connection collation_connection,@@character_set_server charset_server,@@collation_server collation_server,UTC_TIMESTAMP(6) observed_at_utc')->fetch();
    $candidates=$p->query('SELECT substance_id,canonical_name,identity_status,identity_type,is_active FROM kobi_substances WHERE substance_id IN (15,54,319,481,2064) ORDER BY substance_id')->fetchAll();
    oAssert(count($candidates)===5,'Candidate inventory differs.');
    foreach($candidates as $row){oAssert($row['identity_status']==='CANDIDATE'&&$row['identity_type']==='UNKNOWN'&&(int)$row['is_active']===1,'Candidate scientific/lifecycle state differs.');}
    $required=['users','medicine_authority_grants','medicine_provisional_identity_revisions'];$missing=array_values(array_filter($required,fn($name)=>!isset($metadata['definitions'][$name])));
    $unexpected=array_values(array_filter(['medicine_reviewer_qualifications','medicine_evidence_appraisals'],fn($name)=>isset($metadata['definitions'][$name])));oAssert($unexpected===[],'Deployment objects already present; no automatic retry.');
    $proof=['status'=>$missing===[]?'PREFLIGHT_READY':'BLOCKED_MISSING_SCHEMA_DEPENDENCY','state'=>$state,'backup'=>['path'=>$backup,'bytes'=>5352130,'sha256'=>$hash],'artifact'=>['path'=>$ddl,'sha256'=>$ddlHash],'missing_dependencies'=>$missing,'data'=>$data,'metadata'=>$metadata,'privileges_sha256'=>$privileges,'candidates'=>$candidates,'grants'=>1,'events'=>1,'reviewer_authority'=>0,'migration_statements_executed'=>0,'normal_writes'=>0];
    oSave('phase323-preflight.json',$proof);unset($proof['data'],$proof['metadata']);echo json_encode($proof,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
    oAssert($missing===[],'Phase 3.22 requires absent medicine_provisional_identity_revisions; prerequisite deployment is outside the authorized Phase 3.22-only scope.');
}catch(Throwable $error){fwrite(STDERR,'STOP: '.$error->getMessage().PHP_EOL);exit(1);}
