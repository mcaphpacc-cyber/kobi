<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
env('DB_HOST');
$_ENV['DB_DATABASE']='kobi_phase2c_validation_20261008';
$_ENV['TREATMENT_PUBLICATION_MODE']='hold';
$_ENV['TREATMENT_PERMISSION_ADMIN_USER_IDS']='4';
final class LiveDatabase extends App\Core\Database {
    public function __construct(private PDO $isolated) {}
    public function connection(): PDO { return $this->isolated; }
}
final class LiveActor extends App\Services\AuthService {
    public function __construct(public int $actor=1) {}
    public function id(): ?int { return $this->actor ?: null; }
}
// Isolated deployment-policy fixture; real application configuration stays untouched.
final class LivePolicy extends App\Services\TreatmentGovernancePolicy {
    public function administrators(): array { return [4]; }
}
$root=dirname(__DIR__);
$results=['database'=>$_ENV['DB_DATABASE'],'tests'=>[],'status'=>'STARTED'];
$workflow=in_array('--workflow',$argv,true);
$resume=in_array('--resume',$argv,true) || $workflow;
if ($resume) {
    $results=json_decode(file_get_contents($root.'/docs/TREATMENT_PHASE_2C_RUNTIME_RESULTS.json'),true,512,JSON_THROW_ON_ERROR);
    $results['tests']=array_values(array_filter($results['tests'],static fn($test)=>$test['status']==='PASS'));
    $results['harness_notes'][]='Initial legacy initialization attempted real empty administrator allowlist and was denied. Resumed with isolated injected administrator-policy fixture; application configuration unchanged.';
}
function persistResults(): void { global $results,$root; file_put_contents($root.'/docs/TREATMENT_PHASE_2C_RUNTIME_RESULTS.json',json_encode($results,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n"); }
function ensure(bool $ok,string $message): void { if (!$ok) { throw new LogicException($message); } }
function runLive(string $name,callable $work): void {
    global $results;
    try { $detail=$work(); $results['tests'][]=['test'=>$name,'status'=>'PASS','detail'=>$detail]; echo "PASS $name\n"; persistResults(); }
    catch (Throwable $error) { $results['tests'][]=['test'=>$name,'status'=>'FAIL','class'=>get_class($error),'message'=>$error->getMessage()]; $results['status']='VALIDATION_FAILED'; persistResults(); echo 'FAIL '.$name.': '.$error->getMessage()."\n"; exit(2); }
}
function denied(callable $work): string {
    try { $work(); } catch (RuntimeException $error) { return $error->getMessage(); }
    throw new LogicException('Expected service rejection but operation succeeded.');
}
function sqlReject(string $name,string $sql,int $code,string $message=''): void {
    global $pdo;
    runLive($name,function () use ($sql,$code,$message) {
        global $pdo;
        $pdo->beginTransaction();
        try { $pdo->exec($sql); throw new LogicException('Expected database rejection but SQL succeeded.'); }
        catch (PDOException $error) {
            ensure((int)$error->errorInfo[1]===$code,'Unexpected MariaDB error: '.$error->getMessage());
            if ($message!=='') { ensure(str_contains($error->getMessage(),$message),'Unexpected constraint/trigger failure.'); }
            return ['sql'=>$sql,'expected'=>'REJECTED','actual'=>$error->errorInfo];
        } finally { if ($pdo->inTransaction()) { $pdo->rollBack(); } }
    });
}
$pdo=new PDO('mysql:host='.env('DB_HOST','localhost').';port='.env('DB_PORT','3306').';dbname='.$_ENV['DB_DATABASE'].';charset=utf8mb4',env('DB_USERNAME'),env('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
ensure($pdo->query('SELECT DATABASE()')->fetchColumn()==='kobi_phase2c_validation_20261008','Wrong database.');
ensure($pdo->query('SELECT @@sql_mode')->fetchColumn()==='NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION','Unexpected SQL mode.');
if (!$resume) {
runLive('actual schema metadata',function () use ($pdo) {
    global $results;
    foreach (['treatment_sources','treatment_protocol_sources','treatment_item_sources','treatment_protocol_events','treatment_editorial_permissions','treatment_protocols','treatment_protocol_items'] as $table) {
        $results['metadata'][$table]=['ddl'=>$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(),'columns'=>$pdo->query('SHOW FULL COLUMNS FROM `'.$table.'`')->fetchAll(),'indexes'=>$pdo->query('SHOW INDEX FROM `'.$table.'`')->fetchAll()];
    }
    $results['constraints']=$pdo->query("SELECT TABLE_NAME,CONSTRAINT_NAME,CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() ORDER BY TABLE_NAME,CONSTRAINT_NAME")->fetchAll();
    $results['foreign_keys']=$pdo->query('SELECT TABLE_NAME,CONSTRAINT_NAME,REFERENCED_TABLE_NAME,UPDATE_RULE,DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()')->fetchAll();
    $results['triggers']=$pdo->query('SELECT TRIGGER_NAME,ACTION_TIMING,EVENT_MANIPULATION,ACTION_STATEMENT,SQL_MODE FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE()')->fetchAll();
    ensure(count($results['triggers'])===2,'Missing triggers.');
    foreach ($results['triggers'] as $trigger) { ensure($trigger['ACTION_TIMING']==='AFTER' && $trigger['SQL_MODE']==='NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION','Unexpected trigger metadata.'); }
    return 'Actual DDL, columns/nullability/defaults, indexes, CHECK/FK/unique metadata and trigger bodies recorded.';
});
runLive('isolated synthetic fixtures',function () use ($pdo) {
    ensure((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()===0,'Refusing to overwrite existing fixtures.');
    $stmt=$pdo->prepare('INSERT INTO users(id,name,email,password_hash,password_set_at,email_verified_at) VALUES (?,?,?,?,NOW(),NOW())');
    foreach ([1=>'Editor',2=>'Reviewer A',3=>'Source manager',4=>'Administrator',5=>'Reviewer B',6=>'Wrong system',7=>'Revoked reviewer',8=>'Ordinary user'] as $id=>$name) { $stmt->execute([$id,$name,'validation'.$id.'@example.test',password_hash(bin2hex(random_bytes(20)),PASSWORD_DEFAULT)]); }
    $pdo->exec("INSERT INTO body_parts(id,name_en,name_hi) VALUES(1,'Validation body','Validation body')");
    $pdo->exec("INSERT INTO diseases(id,body_part_id,disease_en,disease_hi,slug) VALUES(1,1,'Synthetic validation disease','Synthetic validation disease','phase2c-validation')");
    $pdo->exec("INSERT INTO treatment_systems(id,name_en,slug) VALUES(1,'Validation System A','validation-a'),(2,'Validation System B','validation-b')");
    $pdo->exec("INSERT INTO treatment_editorial_permissions(user_id,treatment_system_id,can_edit,can_review,can_publish,qualification_scope,authorized_by) VALUES(1,1,1,0,0,NULL,4),(2,1,0,1,0,'Synthetic system A review scope',4),(3,1,1,0,1,NULL,4),(5,1,0,1,0,'Synthetic system A review scope',4),(6,2,0,1,0,'Synthetic system B scope',4),(7,1,0,1,0,'Revoked synthetic scope',4)");
    $pdo->exec("UPDATE treatment_editorial_permissions SET revoked_by=4,revoked_at=NOW(6),revocation_reason='Validation revocation' WHERE user_id=7");
    $pdo->exec("INSERT INTO treatment_protocols(id,disease_id,treatment_system_id,title_en,title_hi,overview_en,overview_hi) VALUES(1,1,1,'Synthetic protocol','Synthetic protocol','Synthetic overview','Synthetic overview')");
    $pdo->exec("INSERT INTO treatment_protocol_items(id,protocol_id,item_type,title_en,title_hi,instructions_en,instructions_hi,sort_order) VALUES(1,1,'lifestyle','Synthetic item','Synthetic item','Synthetic instruction','Synthetic instruction',1),(2,1,'lifestyle','Excluded item','Excluded item','Unused','Unused',2)");
    $pdo->exec("INSERT INTO treatment_sources(id,source_type,title,citation_text,created_by) VALUES(1,'other','Synthetic source A','Synthetic citation A',1),(2,'other','Synthetic source B','Synthetic citation B',1)");
    return 'Fresh synthetic rows only; no existing KOBI data copied.';
});
sqlReject('trigger direct self INSERT',"INSERT INTO treatment_sources(id,source_type,title,citation_text,created_by,replaces_source_id) VALUES(100,'other','Self','Citation',1,100)",1644,'cannot replace itself');
sqlReject('trigger direct self UPDATE','UPDATE treatment_sources SET replaces_source_id=id WHERE id=1',1644,'cannot replace itself');
runLive('valid source replacements A B C',function () use ($pdo) { $pdo->exec('UPDATE treatment_sources SET replaces_source_id=1 WHERE id=2'); $pdo->exec("INSERT INTO treatment_sources(id,source_type,title,citation_text,created_by,replaces_source_id) VALUES(3,'other','Source C','Citation C',1,2)"); return 'A exists; B replaces A; C replaces B. Both triggers allowed valid chain.'; });
sqlReject('CHECK protocol revision','UPDATE treatment_protocols SET content_revision=0 WHERE id=1',4025,'chk_treatment_revision');
sqlReject('CHECK approval pointer','UPDATE treatment_protocols SET review_state=\'approved\' WHERE id=1',4025,'chk_treatment_approval_pointer');
sqlReject('CHECK published requires approval/locales',"UPDATE treatment_protocols SET publication_status='published' WHERE id=1",4025,'chk_treatment_publication');
sqlReject('CHECK unpublished prohibits locales',"UPDATE treatment_protocols SET published_locales='en' WHERE id=1",4025,'chk_treatment_publication');
sqlReject('CHECK source identity',"UPDATE treatment_sources SET title=' ' WHERE id=1",4025,'chk_treatment_source_identity');
sqlReject('CHECK source lifecycle metadata',"UPDATE treatment_sources SET status='verified' WHERE id=1",4025,'chk_treatment_source_lifecycle');
sqlReject('CHECK item inclusion','UPDATE treatment_protocol_items SET is_included=2 WHERE id=1',4025,'chk_treatment_item_included');
sqlReject('CHECK event actor',"INSERT INTO treatment_protocol_events(protocol_id,content_revision,event_type) VALUES(1,1,'content_changed')",4025,'chk_treatment_event_actor');
sqlReject('CHECK event revision',"INSERT INTO treatment_protocol_events(protocol_id,content_revision,event_type,actor_id) VALUES(1,0,'content_changed',1)",4025,'chk_treatment_event_revision');
sqlReject('CHECK approval event required fields',"INSERT INTO treatment_protocol_events(protocol_id,content_revision,event_type,actor_id,locales,notes,content_snapshot,content_hash) VALUES(1,1,'approved',2,'en','Rationale','{}',REPEAT('a',64))",4025,'chk_treatment_event_approval');
sqlReject('CHECK submitted snapshot',"INSERT INTO treatment_protocol_events(protocol_id,content_revision,event_type,actor_id) VALUES(1,1,'submitted',1)",4025,'chk_treatment_event_snapshot');
sqlReject('CHECK event valid JSON',"INSERT INTO treatment_protocol_events(protocol_id,content_revision,event_type,actor_id,content_snapshot) VALUES(1,1,'content_changed',1,'bad JSON')",4025,'chk_treatment_event_json');
sqlReject('CHECK event rationale',"INSERT INTO treatment_protocol_events(protocol_id,content_revision,event_type,actor_id,notes) VALUES(1,1,'changes_required',2,' ')",4025,'chk_treatment_event_reason');
sqlReject('CHECK permission capability',"INSERT INTO treatment_editorial_permissions(user_id,treatment_system_id,authorized_by) VALUES(8,1,4)",4025,'chk_treatment_grant_capabilities');
sqlReject('CHECK reviewer qualification',"INSERT INTO treatment_editorial_permissions(user_id,treatment_system_id,can_review,authorized_by,qualification_scope) VALUES(8,1,1,4,' ')",4025,'chk_treatment_grant_qualification');
sqlReject('CHECK protocol claim',"INSERT INTO treatment_protocol_sources(protocol_id,source_id,claim_scope) VALUES(1,1,' ')",4025,'chk_treatment_protocol_claim');
sqlReject('CHECK item claim',"INSERT INTO treatment_item_sources(item_id,source_id,claim_scope) VALUES(1,1,' ')",4025,'chk_treatment_item_claim');
foreach ([
 'protocol disease'=>"UPDATE treatment_protocols SET disease_id=999 WHERE id=1",
 'protocol system'=>"UPDATE treatment_protocols SET treatment_system_id=999 WHERE id=1",
 'item protocol'=>"UPDATE treatment_protocol_items SET protocol_id=999 WHERE id=1",
 'protocol reference parent'=>"INSERT INTO treatment_protocol_sources(protocol_id,source_id,claim_scope) VALUES(999,1,'Claim')",
 'protocol reference source'=>"INSERT INTO treatment_protocol_sources(protocol_id,source_id,claim_scope) VALUES(1,999,'Claim')",
 'item reference parent'=>"INSERT INTO treatment_item_sources(item_id,source_id,claim_scope) VALUES(999,1,'Claim')",
 'item reference source'=>"INSERT INTO treatment_item_sources(item_id,source_id,claim_scope) VALUES(1,999,'Claim')",
 'event protocol'=>"INSERT INTO treatment_protocol_events(protocol_id,content_revision,event_type,actor_id) VALUES(999,1,'content_changed',1)",
 'permission user'=>"INSERT INTO treatment_editorial_permissions(user_id,treatment_system_id,can_edit,authorized_by) VALUES(999,1,1,4)",
 'permission system'=>"INSERT INTO treatment_editorial_permissions(user_id,treatment_system_id,can_edit,authorized_by) VALUES(8,999,1,4)",
 'approval composite relationship'=>"UPDATE treatment_protocols SET review_state='approved',approved_review_id=999 WHERE id=1",
] as $name=>$sql) { sqlReject('FK '.$name,$sql,1452); }
sqlReject('FK restrictive source predecessor','DELETE FROM treatment_sources WHERE id=1',1451,'fk_treatment_source_replaces');
sqlReject('FK restrictive protocol parent','DELETE FROM treatment_protocols WHERE id=1',1451);
sqlReject('UNIQUE active permission',"INSERT INTO treatment_editorial_permissions(user_id,treatment_system_id,can_edit,authorized_by) VALUES(1,1,1,4)",1062,'uq_treatment_active_grant');
}

$db=new LiveDatabase($pdo); $actor=new LiveActor(); $rules=new App\Services\TreatmentGovernanceRules(); $policy=new LivePolicy(); $snapshot=new App\Services\TreatmentGovernanceSnapshot($rules);
$repository=new App\Repositories\TreatmentGovernanceRepository($db); $sources=new App\Repositories\TreatmentSourceRepository($db); $permissions=new App\Repositories\TreatmentEditorialPermissionRepository($db);
$authorization=new App\Services\TreatmentEditorialAuthorizationService($actor,$permissions,$rules,$policy);
$governance=new App\Services\TreatmentGovernanceService($repository,$sources,$permissions,$authorization,$rules,$snapshot,$policy);
$editor=new App\Services\TreatmentEditorialService($repository,$sources,$permissions,$authorization,$governance,$rules);
if (!$workflow) {
runLive('legacy initialization actual persistence',function () use ($actor,$governance,$repository,$pdo) {
    $actor->actor=4; ensure($governance->initializeLegacy(1),'Legacy initialization failed'); $p=$repository->find(1);
    ensure($p['review_state']==='draft' && $p['publication_status']==='unpublished' && (int)$p['content_revision']===1 && $p['approved_review_id']===null && $p['published_locales']==='' && $p['evidence_classification']==='not_assessed','Legacy protocol invalid.');
    ensure((int)$pdo->query('SELECT SUM(is_included) FROM treatment_protocol_items WHERE protocol_id=1')->fetchColumn()===0,'Legacy item auto included.');
    ensure((int)$pdo->query('SELECT COUNT(*) FROM treatment_protocol_sources')->fetchColumn()===0,'Fabricated source support');
    $actor->actor=1; return 'Only legacy_initialized event; actor NULL; no fabricated approval/reviewer/reference.';
});
runLive('editor save revision actor history and no-op',function () use ($editor,$repository,$pdo) {
    $input=['expected_revision'=>'1','expected_state'=>'draft','form_complete'=>'1','protocol'=>['overview_en'=>'Synthetic changed overview'],'items'=>[]];
    ensure($editor->save(1,$input),'Save failed'); $p=$repository->find(1); ensure((int)$p['content_revision']===2 && (int)$p['updated_by']===1 && $p['approved_review_id']===null,'Save state incorrect');
    $input['expected_revision']='2'; ensure(!$editor->save(1,$input),'No-op changed content');
    $input['expected_revision']='1'; $input['protocol']['overview_en']='Stale overwrite'; denied(fn()=>$editor->save(1,$input));
    ensure($repository->find(1)['overview_en']==='Synthetic changed overview','Stale overwrote content'); return 'Revision 2; updated_by editor; event stored; no-op/stale unchanged.';
});
runLive('controlled evidence purpose item ENUM and locale SET validation',function () use ($governance,$rules) {
    foreach ([['evidence_classification'=>'unknown'],['content_purpose'=>'unknown']] as $changes) { denied(fn()=>$governance->edit(1,2,'draft',$changes)); }
    denied(fn()=>$governance->edit(1,2,'draft',[],[1=>['item_type'=>'unknown']]));
    denied(fn()=>$rules->locales(['xx'])); return 'Services reject invalid controlled values under unchanged permissive server SQL mode.';
});
runLive('database ENUM coercion observation not application validation',function () use ($pdo) {
    $pdo->beginTransaction();
    try { $pdo->exec("UPDATE treatment_protocols SET evidence_classification='unknown',content_purpose='unknown' WHERE id=1"); $actual=$pdo->query('SELECT evidence_classification,content_purpose FROM treatment_protocols WHERE id=1')->fetch(); return ['sql'=>"UPDATE protocol SET evidence_classification='unknown',content_purpose='unknown' (rolled back)",'expected'=>'Permissive ENUM coercion possible; service must reject','actual'=>$actual]; }
    finally { $pdo->rollBack(); }
});
runLive('explicit item inclusion and positive unique order',function () use ($governance,$repository,$pdo) {
    $governance->edit(1,2,'draft',[],[1=>['is_included'=>1,'sort_order'=>1]]);
    ensure((int)$repository->find(1)['content_revision']===3,'Item edit did not revise parent');
    denied(fn()=>$governance->edit(1,3,'draft',[],[2=>['is_included'=>1,'sort_order'=>1]]));
    denied(fn()=>$governance->edit(1,3,'draft',[],[1=>['sort_order'=>0]]));
    ensure((int)$pdo->query('SELECT is_included FROM treatment_protocol_items WHERE id=2')->fetchColumn()===0,'Excluded item automatically included'); return 'Positive unique order enforced by service; excluded legacy item remains excluded.';
});
$results['status']='CONSTRAINT_AND_INITIAL_WORKFLOW_PASSED'; persistResults();
echo count($results['tests'])." live checks passed; continuation workflow pending.\n";
exit;
}

function completeProtocol(string $title='Synthetic governed protocol'): int {
    global $pdo;
    $q=$pdo->prepare("INSERT INTO treatment_protocols(disease_id,treatment_system_id,title_en,title_hi,overview_en,overview_hi,evidence_classification,content_purpose,applicability_en,applicability_hi,limitations_en,limitations_hi) VALUES(1,1,?,?,'Synthetic overview','Synthetic overview','evidence_supported','supportive_care','Synthetic scope','Synthetic scope','Educational limitation','Educational limitation')"); $q->execute([$title,$title]); $id=(int)$pdo->lastInsertId();
    $pdo->exec("INSERT INTO treatment_protocol_items(protocol_id,item_type,title_en,title_hi,instructions_en,instructions_hi,sort_order,is_included,evidence_classification,content_purpose,limitations_en,limitations_hi) VALUES($id,'lifestyle','Synthetic included item','Synthetic included item','Synthetic instruction','Synthetic instruction',1,1,'evidence_supported','supportive_care','Educational limitation','Educational limitation')"); $item=(int)$pdo->lastInsertId();
    $pdo->exec("INSERT INTO treatment_protocol_sources(protocol_id,source_id,claim_scope) VALUES($id,1,'Synthetic overview claim')");
    $pdo->exec("INSERT INTO treatment_item_sources(item_id,source_id,claim_scope) VALUES($item,1,'Synthetic item claim')"); return $id;
}
function submitLive(int $id): array { global $actor,$governance,$repository; $actor->actor=1; $p=$repository->find($id); $event=$governance->submit($id,(int)$p['content_revision'],$p['review_state'],['en','hi']); return [(int)$p['content_revision'],$event]; }
function approveLive(int $id,int $revision,int $submission): int { global $actor,$governance; $actor->actor=2; return $governance->approve($id,$revision,$submission,['en','hi'],'Synthetic independent rationale','2099-01-01 00:00:00.000000',true); }
function eventCount(int $id,string $type='approved'): int { global $pdo; $q=$pdo->prepare('SELECT COUNT(*) FROM treatment_protocol_events WHERE protocol_id=? AND event_type=?'); $q->execute([$id,$type]); return (int)$q->fetchColumn(); }

runLive('service direct replacement and cycle validation',function () use ($governance,$rules,$sources,$pdo) {
    denied(fn()=>$rules->replacement(1,1,fn($id)=>$sources->find($id)));
    $pdo->exec('UPDATE treatment_sources SET replaces_source_id=3 WHERE id=1');
    try { denied(fn()=>$governance->createSource(1,['source_type'=>'other','title'=>'Cycle candidate','citation_text'=>'Synthetic','replaces_source_id'=>3])); }
    finally { $pdo->exec('UPDATE treatment_sources SET replaces_source_id=NULL WHERE id=1'); }
    $id=$governance->createSource(1,['source_type'=>'other','title'=>'Valid chain candidate','citation_text'=>'Synthetic','replaces_source_id'=>3]);
    return 'Database permits general multi-node cycle; service rejects it. Valid finite chain accepted; direct self validation rejected.';
});
runLive('source lifecycle explicit verification withdrawal and no restore',function () use ($actor,$governance,$sources) {
    $actor->actor=2; $governance->verifySource(1,1); ensure($sources->find(1)['status']==='verified','Explicit verify failed');
    $actor->actor=3; $governance->withdrawSource(1,2,'Synthetic unverified withdrawal'); ensure($sources->find(2)['status']==='withdrawn','Unverified withdrawal failed');
    $actor->actor=2; denied(fn()=>$governance->verifySource(1,2)); $actor->actor=1;
    return 'unverified -> verified; unverified -> withdrawn; withdrawn -> verified rejected.';
});
runLive('source references revision duplicate claim withdrawn validation',function () use ($actor,$governance,$repository,$pdo) {
    $actor->actor=1; $governance->reference(1,3,'draft',1,null,'Synthetic protocol claim',null,false,'attach');
    $governance->reference(1,4,'draft',1,1,'Synthetic included item claim',null,false,'attach');
    ensure((int)$repository->find(1)['content_revision']===5,'Reference revision incorrect');
    denied(fn()=>$governance->reference(1,5,'draft',1,null,'Duplicate',null,false,'attach'));
    denied(fn()=>$governance->reference(1,5,'draft',3,null,' '));
    denied(fn()=>$governance->reference(1,5,'draft',2,null,'Withdrawn source'));
    return 'Verified protocol/item attachments increment once each; duplicate/blank/withdrawn rejected.';
});
sqlReject('UNIQUE protocol source relationship',"INSERT INTO treatment_protocol_sources(protocol_id,source_id,claim_scope) VALUES(1,1,'Duplicate')",1062,'uq_protocol_source');
sqlReject('UNIQUE item source relationship',"INSERT INTO treatment_item_sources(item_id,source_id,claim_scope) VALUES(1,1,'Duplicate')",1062,'uq_item_source');
runLive('complete submission actual snapshot and hash',function () use ($repository,$snapshot,$policy) {
    global $results;
    $id=completeProtocol(); [$revision,$event]=submitLive($id); $p=$repository->find($id); $stored=$repository->event($event); $capture=$snapshot->capture($repository->aggregate($id),['en','hi']);
    ensure($p['review_state']==='pending_review' && $p['publication_status']==='unpublished' && $p['approved_review_id']===null && (int)$p['content_revision']===$revision,'Submission state incorrect');
    ensure($stored['content_hash']===$capture['content_hash'] && $stored['content_snapshot']===$capture['content_snapshot'] && $policy->publicTreatments()===[],'Submission snapshot/public hold incorrect');
    $results['primary_review']=['protocol'=>$id,'revision'=>$revision,'submission'=>$event]; return $results['primary_review'];
});
runLive('review actual system revoked editor and independence authorization',function () use ($actor,$editor,$repository,$governance) {
    global $results; ['protocol'=>$id,'revision'=>$rev,'submission'=>$submission]=$results['primary_review'];
    foreach ([1,6,7,8] as $unauthorized) { $actor->actor=$unauthorized; denied(fn()=>$editor->reviewDetail($id)); denied(fn()=>$governance->approve($id,$rev,$submission,['en'],'Synthetic rationale','2099-01-01 00:00:00.000000',true)); }
    $actor->actor=2; ensure($editor->reviewDetail($id)['assessment']['matches'],'Qualified reviewer cannot inspect');
    denied(fn()=>$governance->approve($id,$rev,$submission,['en'],'Synthetic rationale','2099-01-01 00:00:00.000000',false));
    $actor->actor=1; return 'Correct reviewer allowed; wrong-system, editor-only, revoked, ordinary denied; missing attestation denied.';
});
runLive('approval actual event pointer UTC expiry unpublished',function () use ($repository,$policy) {
    global $results; ['protocol'=>$id,'revision'=>$rev,'submission'=>$submission]=$results['primary_review']; $event=approveLive($id,$rev,$submission); $p=$repository->find($id); $e=$repository->event($event);
    ensure($p['review_state']==='approved' && $p['publication_status']==='unpublished' && $p['published_locales']==='' && (int)$p['content_revision']===$rev && (int)$p['approved_review_id']===$event && eventCount($id)===1,'Approval state incorrect');
    ensure((int)$e['independence_confirmed']===1 && $e['review_due_at']==='2099-01-01 00:00:00.000000' && $e['content_hash']===hash('sha256',$e['content_snapshot']) && $e['notes']!=='','Approval event missing');
    denied(fn()=>approveLive($id,$rev,$submission)); ensure($policy->publicTreatments()===[],'Approval exposed public content'); return ['approval_event'=>$event,'legacy_unknown_authorship'=>'explicit attestation persisted'];
});
runLive('material authorship self approval denied',function () use ($actor,$governance,$pdo,$repository) {
    $id=completeProtocol(); $actor->actor=1; $governance->edit($id,1,'draft',['overview_en'=>'Material authored change']); [$rev,$sub]=submitLive($id);
    $pdo->exec("INSERT INTO treatment_editorial_permissions(user_id,treatment_system_id,can_edit,can_review,qualification_scope,authorized_by) VALUES(2,2,1,1,'Synthetic system B',4)");
    // A valid review capability for the material author, only in isolated fixtures.
    $pdo->exec("UPDATE treatment_editorial_permissions SET can_review=1,qualification_scope='Synthetic author qualification' WHERE user_id=1 AND treatment_system_id=1");
    try { $actor->actor=1; denied(fn()=>$governance->approve($id,$rev,$sub,['en'],'Self rationale','2099-01-01 00:00:00.000000',true)); ensure(eventCount($id)===0,'Self approval stored'); }
    finally { $pdo->exec('UPDATE treatment_editorial_permissions SET can_review=0,qualification_scope=NULL WHERE user_id=1 AND treatment_system_id=1'); }
    return 'Valid reviewer capability and attestation do not bypass material authorship.';
});
runLive('stale approval after material editorial revision',function () use ($actor,$governance,$repository) {
    $id=completeProtocol(); [$rev,$sub]=submitLive($id); $actor->actor=1; $governance->edit($id,$rev,'pending_review',['overview_en'=>'Newer preserved revision']);
    denied(fn()=>approveLive($id,$rev,$sub)); $p=$repository->find($id); ensure((int)$p['content_revision']===2 && $p['review_state']==='draft' && $p['overview_en']==='Newer preserved revision' && eventCount($id)===0,'Stale approval corrupted state'); return 'Old revision rejected; newer content intact; no approval/publication.';
});
runLive('changes required corrected fresh submission',function () use ($actor,$governance,$repository) {
    $id=completeProtocol(); [$rev,$sub]=submitLive($id); $actor->actor=2; $before=$repository->aggregate($id); $governance->requestChanges($id,$rev,'Synthetic required corrections',$sub); $p=$repository->find($id);
    ensure($p['review_state']==='changes_required' && (int)$p['content_revision']===$rev && $p['publication_status']==='unpublished' && eventCount($id,'changes_required')===1,'Changes state incorrect');
    ensure($repository->aggregate($id)['items']===$before['items'],'Decision changed items'); $actor->actor=1; denied(fn()=>$governance->submit($id,$rev,'changes_required',['en','hi']));
    $governance->edit($id,$rev,'changes_required',['limitations_en'=>'Corrected educational limitation']); denied(fn()=>approveLive($id,$rev,$sub)); [$newRev,$newSub]=submitLive($id); approveLive($id,$newRev,$newSub);
    return 'One reason event; unchanged decision revision/content; corrected revision 2 freshly submitted and approved.';
});
runLive('same revision changed governed content snapshot rejects',function () use ($pdo,$snapshot,$repository) {
    $id=completeProtocol(); [$rev,$sub]=submitLive($id); $before=$snapshot->capture($repository->aggregate($id),['en','hi']);
    foreach (["UPDATE treatment_protocols SET title_en='Changed title' WHERE id=$id","UPDATE treatment_protocols SET overview_en='Changed overview' WHERE id=$id","UPDATE treatment_protocol_items SET instructions_en='Changed instruction' WHERE protocol_id=$id","UPDATE treatment_protocol_sources SET claim_scope='Changed claim' WHERE protocol_id=$id","UPDATE treatment_sources SET edition_or_version='Changed version' WHERE id=1"] as $sql) {
        $pdo->exec($sql); $current=$snapshot->capture($repository->aggregate($id),['en','hi']); ensure($current['content_hash']!==$before['content_hash'],'Hash did not change'); denied(fn()=>approveLive($id,$rev,$sub));
    }
    $pdo->exec('UPDATE treatment_sources SET edition_or_version=NULL WHERE id=1'); return 'Title/overview/item/relationship/source-version changes reject same-revision approval; live source status separately validated.';
});
runLive('unverified source never silently verified during approval',function () use ($pdo,$sources) {
    $id=completeProtocol(); [$rev,$sub]=submitLive($id); $pdo->exec("UPDATE treatment_sources SET status='unverified',verified_by=NULL,verified_at=NULL WHERE id=1");
    try { denied(fn()=>approveLive($id,$rev,$sub)); ensure($sources->find(1)['status']==='unverified' && eventCount($id)===0,'Approval silently verified'); }
    finally { $pdo->exec("UPDATE treatment_sources SET status='verified',verified_by=2,verified_at=NOW(6) WHERE id=1"); }
    return 'Current live status blocks approval even when canonical bibliographic hash matches.';
});
runLive('expired due approval rejected and historical expiry retained',function () use ($governance,$repository,$actor,$pdo,$editor) {
    $id=completeProtocol(); [$rev,$sub]=submitLive($id); $actor->actor=2;
    denied(fn()=>$governance->approve($id,$rev,$sub,['en'],'Synthetic expired','2001-01-01 00:00:00.000000',true));
    $event=approveLive($id,$rev,$sub); $pdo->exec("UPDATE treatment_protocol_events SET occurred_at='2000-01-01',review_due_at='2001-01-01' WHERE id=$event");
    ensure($repository->event($event)['review_due_at']==='2001-01-01 00:00:00.000000','Expired fixture missing');
    $list=$editor->reviewListing(['review_state'=>'approved','due_status'=>'expired']); ensure(in_array($id,array_map('intval',array_column($list['protocols'],'id')),true),'Expired review filter missing');
    $actor->actor=3; denied(fn()=>$governance->publish($id,$rev,['en'])); return 'Past approval due rejected; isolated historical expired approval cannot satisfy existing core eligibility; no publication was performed.';
});
final class FailingEventRepository extends App\Repositories\TreatmentGovernanceRepository {
    public function appendEvent(array $event): int { $id=parent::appendEvent($event); throw new RuntimeException('Injected isolated validation failure'); }
}
$failureRepository=new FailingEventRepository($db);
$failureGovernance=new App\Services\TreatmentGovernanceService($failureRepository,$sources,$permissions,$authorization,$rules,$snapshot,$policy);
runLive('approval and changes-required real transaction rollback',function () use ($actor,$failureGovernance,$repository) {
    $id=completeProtocol(); [$rev,$sub]=submitLive($id); $actor->actor=2; $before=$repository->find($id);
    denied(fn()=>$failureGovernance->approve($id,$rev,$sub,['en'],'Synthetic rationale','2099-01-01 00:00:00.000000',true)); ensure($repository->find($id)===$before && eventCount($id)===0,'Partial approval after failure');
    denied(fn()=>$failureGovernance->requestChanges($id,$rev,'Synthetic corrections',$sub)); ensure($repository->find($id)===$before && eventCount($id,'changes_required')===0,'Partial changes after failure'); return 'Real INSERT/state operations rolled back after injected post-insert event failure.';
});
runLive('source withdrawal transaction rollback preserves all protocols',function () use ($actor,$failureGovernance,$repository,$sources,$pdo) {
    $actor->actor=3; $before=$pdo->query('SELECT * FROM treatment_protocols ORDER BY id')->fetchAll(); $source=$sources->find(1); $count=(int)$pdo->query('SELECT COUNT(*) FROM treatment_protocol_events')->fetchColumn();
    denied(fn()=>$failureGovernance->withdrawSource(1,1,'Synthetic injected withdrawal')); ensure($sources->find(1)===$source && $pdo->query('SELECT * FROM treatment_protocols ORDER BY id')->fetchAll()===$before && (int)$pdo->query('SELECT COUNT(*) FROM treatment_protocol_events')->fetchColumn()===$count,'Partial withdrawal survived failure'); return 'Source, affected protocols and inserted history all rolled back.';
});
runLive('withdrawal invalidates approved and pending protocols atomically',function () use ($actor,$governance,$repository,$sources,$pdo) {
    $id=completeProtocol(); [$rev,$sub]=submitLive($id); $actor->actor=3; $refCount=(int)$pdo->query('SELECT COUNT(*) FROM treatment_protocol_sources')->fetchColumn(); $governance->withdrawSource(1,1,'Synthetic verified withdrawal during review');
    ensure($sources->find(1)['status']==='withdrawn','Source not withdrawn');
    $p=$repository->find($id); ensure($p['review_state']==='draft' && (int)$p['content_revision']===$rev+1 && $p['approved_review_id']===null,'Pending source invalidation incorrect');
    ensure((int)$pdo->query("SELECT COUNT(*) FROM treatment_protocols WHERE approved_review_id IS NOT NULL")->fetchColumn()===0,'Invalid approval survived withdrawal');
    ensure((int)$pdo->query('SELECT COUNT(*) FROM treatment_protocol_sources')->fetchColumn()===$refCount,'Source history relationship deleted'); denied(fn()=>approveLive($id,$rev,$sub));
    $actor->actor=2; denied(fn()=>$governance->verifySource(1,1)); return 'verified -> withdrawn; all affected approvals invalidated; fresh revisions/history retained; stale review and restore rejected.';
});
$results['status']='LIVE_WORKFLOW_PASSED'; $results['sql_mode_after']=$pdo->query('SELECT @@sql_mode AS session_mode,@@global.sql_mode AS global_mode')->fetch(); persistResults(); echo count($results['tests'])." live checks passed.\n";
