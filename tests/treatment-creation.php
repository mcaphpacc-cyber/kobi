<?php
declare(strict_types=1);

// Memory-only creation commands and HTTP boundaries; no application bootstrap.
ob_start();
require __DIR__.'/treatment-admin-hardening.php';
$creationBaseline=$passed;

final class CreationRepository extends App\Repositories\TreatmentGovernanceRepository
{
    public ?Throwable $failure=null;
    public string $stage='event';
    public function __construct(private GovernanceMemory $memory,private GovernanceMemoryRepository $inner) {}
    private function fail(string $stage): void { if ($this->failure && $this->stage===$stage) { throw $this->failure; } }
    public function transaction(callable $action): mixed { return $this->inner->transaction($action); }
    public function find(int $id,bool $lock=false): ?array { return $this->inner->find($id,$lock); }
    public function system(int $id,bool $lock=false): ?array { return $this->inner->system($id,$lock); }
    public function diseaseExists(int $id): bool { return $this->inner->diseaseExists($id); }
    public function aggregate(int $id,bool $lockChildren=false): array { return $this->inner->aggregate($id,$lockChildren); }
    public function editorialIdentity(int $id): ?array { return $this->inner->editorialIdentity($id); }
    public function editorialList(int $actor,array $filters,int $offset): array { return $this->inner->editorialList($actor,$filters,$offset); }
    public function updateProtocol(int $id,array $changes): void { $this->inner->updateProtocol($id,$changes); }
    public function appendEvent(array $event): int { $this->fail('event'); return $this->inner->appendEvent($event); }
    public function contributors(int $id): array { return $this->inner->contributors($id); }
    public function matchingProtocol(array $row): bool {
        $this->fail('duplicate');
        foreach ($this->memory->protocols as $p) { $same=true; foreach (['disease_id','treatment_system_id','title_en','title_hi','overview_en','overview_hi'] as $key) { if (($p[$key]??null)!==($row[$key]??null)) { $same=false; } } if ($same) { return true; } }
        return false;
    }
    public function insertProtocol(array $row): int { $this->fail('protocol'); $id=max(array_keys($this->memory->protocols)?:[0])+1; $this->memory->protocols[$id]=$row+['id'=>$id]; return $id; }
    public function insertItem(array $row): int { $this->fail('item'); $id=max(array_keys($this->memory->items)?:[0])+1; $this->memory->items[$id]=$row+['id'=>$id]; return $id; }
}
function creationFixture(): array {
    [$f]=editorialFixture();
    $repo=new CreationRepository($f[0],$f[5]); $permissions=new HardeningPermissions($f[7]);
    $auth=new App\Services\TreatmentEditorialAuthorizationService(new GovernanceMemoryAuth($f[0]),$permissions,$f[1],$f[8]);
    $governance=new App\Services\TreatmentGovernanceService($repo,$f[6],$permissions,$auth,$f[1],$f[2],$f[8]);
    $editor=new App\Services\TreatmentEditorialService($repo,$f[6],$permissions,$auth,$governance,$f[1]);
    $controller=new EditorialController($editor,new GovernanceMemoryAuth($f[0]),new EditorialMemorySession(),$f[1]);
    return [$f,$repo,$permissions,$governance,$editor,$controller];
}
function newDraftInput(array $changes=[]): array { return array_replace(['_csrf_token'=>'fixture-csrf','disease_id'=>'1','treatment_system_id'=>'1','protocol'=>['title_en'=>'New synthetic draft']],$changes); }
function newItemInput(array $changes=[]): array { return array_replace(['_csrf_token'=>'fixture-csrf','expected_revision'=>'1','expected_state'=>'draft','item'=>['item_type'=>'lifestyle','title_en'=>'New synthetic item','sort_order'=>'0']],$changes); }
function unchangedRows(GovernanceMemory $m): string { return serialize([$m->protocols,$m->items,$m->events,$m->protocolRefs,$m->itemRefs,$m->grants]); }

test('4B2 new draft canonical defaults and contributor event',function () {
    [$f,$repo,,,$e]=creationFixture(); $id=$e->createDraft(newDraftInput()); $p=$f[0]->protocols[$id];
    check($p['review_state']==='draft' && $p['publication_status']==='unpublished' && $p['content_revision']===1,'Initial state');
    check($p['approved_review_id']===null && $p['published_locales']==='' && $p['evidence_classification']==='not_assessed' && $p['risk_eligibility']==='not_assessed' && $p['content_purpose']===null,'Unsafe defaults');
    foreach (['evidence_summary_en','evidence_summary_hi','applicability_en','applicability_hi','limitations_en','limitations_hi','title_hi','overview_en','overview_hi'] as $key) { check($p[$key]===null,'Unexpected initial content '.$key); }
    check($p['updated_by']===1 && $p['updated_at']!==null && $repo->contributors($id)===[1],'Creator attribution');
    $events=array_values($f[0]->events); check(count($events)===1 && $events[0]['event_type']==='content_changed' && $events[0]['content_revision']===1 && $events[0]['actor_id']===1 && $events[0]['notes']==='New treatment protocol draft created.','False creation history');
    check(count($f[0]->items)===1 && in_array('system:1',$f[0]->locks,true),'Unrequested item or missing system lock');
});
test('4B2 optional first item is atomic revision 1 with excluded defaults',function () {
    [$f,,,, $e]=creationFixture(); $id=$e->createDraft(newDraftInput(['include_first_item'=>'1','first_item'=>['item_type'=>'herb','title_hi'=>'प्रारंभिक मद','sort_order'=>'0']]));
    $a=$f[5]->aggregate($id); $i=$a['items'][0]; check(count($a['items'])===1 && $a['protocol']['content_revision']===1 && count($f[0]->events)===1,'Extra initial revision');
    check($i['protocol_id']===$id && $i['is_included']===0 && $i['evidence_classification']==='not_assessed' && $i['content_purpose']===null && $i['limitations_en']===null && $i['limitations_hi']===null && $i['title_en']===null && $i['instructions_en']===null,'Item defaults');
});
test('4B2 first later item uses approved clarified revision rule',function () { [$f,,,,$e]=creationFixture(); $id=$e->createDraft(newDraftInput()); $item=$e->addItem($id,newItemInput()); check($f[0]->protocols[$id]['content_revision']===2 && $f[0]->items[$item]['protocol_id']===$id && count($f[0]->events)===2,'Later item revision bypass'); });
test('4B2 subsequent item addition increments exactly once',function () { [$f,,,,$e]=creationFixture(); $e->addItem(1,newItemInput()); $e->addItem(1,newItemInput(['expected_revision'=>'2'])); check($f[0]->protocols[1]['content_revision']===3 && count($f[0]->items)===3 && count($f[0]->events)===2,'Item revision count'); });
test('4B2 new draft appears in scoped listing and detail',function () { [$f,,,,$e]=creationFixture(); $id=$e->createDraft(newDraftInput()); check(in_array($id,array_column($e->listing([])['protocols'],'id'),true) && $e->detail($id)['protocol']['publication_status']==='unpublished','New draft unreachable'); });
test('4B2 exact repeat rejects without creating another draft or history',function () { [$f,,,,$e]=creationFixture(); $e->createDraft(newDraftInput()); $before=unchangedRows($f[0]); rejects(fn()=>$e->createDraft(newDraftInput())); check(unchangedRows($f[0])===$before,'Repeated creation wrote'); });
test('4B2 distinct titles under same disease and system remain allowed',function () { [$f,,,,$e]=creationFixture(); $first=$e->createDraft(newDraftInput()); $second=$e->createDraft(newDraftInput(['protocol'=>['title_en'=>'Distinct synthetic draft']])); check($first!==$second && count($f[0]->protocols)===3,'Invented pair uniqueness'); });
test('4B2 Hindi-only protocol and empty omitted locale fields accepted',function () { [$f,,,,$e]=creationFixture(); $id=$e->createDraft(newDraftInput(['protocol'=>['title_en'=>'','title_hi'=>'मसौदा','overview_en'=>'']])); check($f[0]->protocols[$id]['title_en']===null && $f[0]->protocols[$id]['overview_en']===null && $f[0]->protocols[$id]['title_hi']==='मसौदा','Locale fabrication'); });
test('4B2 blank browser optional item fields do not create an item',function () { [$f,,,,$e]=creationFixture(); $id=$e->createDraft(newDraftInput(['first_item'=>['item_type'=>'','title_en'=>'','title_hi'=>'','instructions_en'=>'','instructions_hi'=>'','sort_order'=>'']])); check($f[5]->aggregate($id)['items']===[],'Empty form item created'); });

foreach (['draft','item'] as $kind) {
    foreach (['anonymous','reviewer','no_grant','revoked','inactive_user','wrong_scope'] as $case) {
        test('4B2 '.$kind.' authorization rejects '.$case,function () use ($kind,$case) {
            [$f,,,,$e]=creationFixture(); $m=$f[0];
            if ($case==='anonymous') { $m->actor=0; } elseif ($case==='reviewer') { $m->actor=2; } elseif ($case==='no_grant') { $m->actor=4; } elseif ($case==='revoked') { $m->grants[1]['revoked_at']='2026-01-01 00:00:00.000000'; } elseif ($case==='inactive_user') { $m->users[1]['status']='inactive'; } else { $m->protocols[1]['treatment_system_id']=2; }
            $before=unchangedRows($m);
            rejects(fn()=>$kind==='draft' ? $e->createDraft(newDraftInput($case==='wrong_scope' ? ['treatment_system_id'=>'2'] : [])) : $e->addItem(1,newItemInput()));
            check(unchangedRows($m)===$before,'Denied write');
        });
    }
}
foreach (['inactive','missing'] as $case) {
    foreach (['draft','item'] as $kind) {
        test('4B2 '.$kind.' rejects '.$case.' treatment system',function () use ($case,$kind) { [$f,,,,$e]=creationFixture(); if ($case==='inactive') { $f[0]->systems[1]['is_active']=0; } else { unset($f[0]->systems[1]); } $before=unchangedRows($f[0]); rejects(fn()=>$kind==='draft' ? $e->createDraft(newDraftInput()) : $e->addItem(1,newItemInput())); check(unchangedRows($f[0])===$before,'Invalid system write'); });
    }
}
foreach ([null,'0','01','999','1e0',[], '-1'] as $bad) {
    test('4B2 invalid or nonexistent disease '.json_encode($bad),function () use ($bad) { [$f,,,,$e]=creationFixture(); $before=unchangedRows($f[0]); rejects(fn()=>$e->createDraft(newDraftInput(['disease_id'=>$bad]))); check(unchangedRows($f[0])===$before,'Invalid disease wrote'); });
}
foreach ([[],['title_en'=>' '],['title_en'=>['nested']],['title_en'=>str_repeat('x',256)],['title_hi'=>str_repeat('ह',256)],['title_en'=>"\xFF"],['title_en'=>'Valid','overview_en'=>str_repeat('x',65536)]] as $content) {
    test('4B2 malformed protocol content case '.count($content).' '.strlen(serialize($content)),function () use ($content) { [$f,,,,$e]=creationFixture(); $before=unchangedRows($f[0]); rejects(fn()=>$e->createDraft(newDraftInput(['protocol'=>$content]))); check(unchangedRows($f[0])===$before,'Bad content wrote'); });
}
foreach (['user_id','actor_id','content_revision','review_state','publication_status','approved_review_id','published_locales','risk_eligibility'] as $key) {
    test('4B2 creation rejects top-level mass assignment '.$key,function () use ($key) { [$f,,,,$e]=creationFixture(); $before=unchangedRows($f[0]); rejects(fn()=>$e->createDraft(newDraftInput([$key=>'1']))); rejects(fn()=>$e->addItem(1,newItemInput([$key=>'1']))); check(unchangedRows($f[0])===$before,'Mass assignment'); });
}
test('4B2 governance itself rejects privileged protocol/item content',function () { [$f,,,$g,$e]=creationFixture(); $before=unchangedRows($f[0]); rejects(fn()=>$g->createDraft(1,1,['title_en'=>'Valid','evidence_classification'=>'evidence_supported'])); rejects(fn()=>$g->addItem(1,1,'draft',['title_en'=>'Valid','item_type'=>'herb','is_included'=>1])); rejects(fn()=>$e->createDraft(newDraftInput(['include_first_item'=>'1','first_item'=>['item_type'=>'herb','title_en'=>'Valid','protocol_id'=>'2']]))); check(unchangedRows($f[0])===$before,'Governance shortcut'); });
foreach ([null,'UNKNOWN','Herb',[], ''] as $type) {
    test('4B2 item type rejection '.json_encode($type),function () use ($type) { [$f,,,,$e]=creationFixture(); $before=unchangedRows($f[0]); rejects(fn()=>$e->addItem(1,newItemInput(['item'=>['item_type'=>$type,'title_en'=>'Valid']]))); check(unchangedRows($f[0])===$before,'Unknown type wrote'); });
}
test('4B2 canonical item type allowlist and Hindi-only items',function () { [$f,,,,$e]=creationFixture(); foreach (App\Services\TreatmentGovernanceRules::ITEM_TYPES as $type) { $id=$e->addItem(1,newItemInput(['expected_revision'=>(string)$f[0]->protocols[1]['content_revision'],'item'=>['item_type'=>$type,'title_hi'=>'मद']])); check($f[0]->items[$id]['item_type']===$type && $f[0]->items[$id]['title_en']===null && $f[0]->items[$id]['is_included']===0,'Allowlist or locale changed'); } });
foreach (['-1','1.5','01','1e2','2147483648',[]] as $order) {
    test('4B2 malformed sort order '.json_encode($order),function () use ($order) { [$f,,,,$e]=creationFixture(); $before=unchangedRows($f[0]); rejects(fn()=>$e->addItem(1,newItemInput(['item'=>['item_type'=>'diet','title_en'=>'Valid','sort_order'=>$order]]))); check(unchangedRows($f[0])===$before,'Invalid order wrote'); });
}
test('4B2 excluded item accepts schema maximum sort order',function () { [$f,,,,$e]=creationFixture(); $id=$e->addItem(1,newItemInput(['item'=>['item_type'=>'diet','title_en'=>'Valid','sort_order'=>'2147483647']])); check($f[0]->items[$id]['sort_order']===2147483647,'Valid order rejected'); });
test('4B2 item requires meaningful title and bounded instructions',function () { [$f,,,,$e]=creationFixture(); foreach ([['item_type'=>'herb','title_hi'=>' '],['item_type'=>'herb','title_en'=>str_repeat('x',256)],['item_type'=>'herb','title_en'=>'Valid','instructions_en'=>str_repeat('x',65536)]] as $content) { rejects(fn()=>$e->addItem(1,newItemInput(['item'=>$content]))); } check(count($f[0]->items)===1 && !$f[0]->events,'Bad item wrote'); });
test('4B2 malformed nested inputs and unchecked populated first item rejected',function () { [$f,,,,$e]=creationFixture(); foreach ([['protocol'=>'bad'],['first_item'=>'bad'],['include_first_item'=>['1']],['first_item'=>['title_en'=>'Unselected']]] as $change) { rejects(fn()=>$e->createDraft(newDraftInput($change))); } rejects(fn()=>$e->addItem(1,newItemInput(['item'=>'bad']))); check(count($f[0]->protocols)===1 && !$f[0]->events,'Malformed request wrote'); });

foreach (['draft','pending_review','changes_required','approved','withdrawn'] as $state) {
    test('4B2 item governed invalidation from '.$state,function () use ($state) {
        [$f,,,,$e]=creationFixture(); $p=&$f[0]->protocols[1]; $p['review_state']=$state==='withdrawn' ? 'draft' : $state;
        if ($state==='approved') { $p['approved_review_id']=99; } if ($state==='withdrawn') { $p['publication_status']='withdrawn'; }
        $e->addItem(1,newItemInput(['expected_state'=>$p['review_state']]));
        check($p['content_revision']===2 && $p['review_state']===($state==='changes_required' ? 'changes_required' : 'draft') && $p['approved_review_id']===null && $p['publication_status']==='unpublished' && $p['published_locales']==='' && $p['updated_by']===1,'Invalidation bypass');
        check(array_values($f[0]->events)[0]['event_type']==='content_changed','False approval event');
    });
}
test('4B2 published item addition and form rejected unchanged',function () { [$f,,,,$e]=creationFixture(); $p=&$f[0]->protocols[1]; $p['review_state']='approved'; $p['approved_review_id']=99; $p['publication_status']='published'; $p['published_locales']='en'; $before=unchangedRows($f[0]); rejects(fn()=>$e->addItem(1,newItemInput(['expected_state'=>'approved']))); rejects(fn()=>$e->itemForm(1)); check(unchangedRows($f[0])===$before,'Published mutation'); });
test('4B2 missing protocol and stale revision or state fail unchanged',function () { [$f,,,,$e]=creationFixture(); $before=unchangedRows($f[0]); rejects(fn()=>$e->addItem(999,newItemInput())); rejects(fn()=>$e->addItem(1,newItemInput(['expected_revision'=>'2']))); rejects(fn()=>$e->addItem(1,newItemInput(['expected_state'=>'approved']))); check(unchangedRows($f[0])===$before,'Stale mutation'); });
test('4B2 repeated POST with old revision cannot add twice',function () { [$f,,,,$e]=creationFixture(); $e->addItem(1,newItemInput()); $before=unchangedRows($f[0]); rejects(fn()=>$e->addItem(1,newItemInput())); check(unchangedRows($f[0])===$before && count($f[0]->items)===2,'Repeat added twice'); });
test('4B2 revision overflow rolls back item creation',function () { [$f,,,,$e]=creationFixture(); $f[0]->protocols[1]['content_revision']=4294967295; $before=unchangedRows($f[0]); rejects(fn()=>$e->addItem(1,newItemInput(['expected_revision'=>'4294967295']))); check(unchangedRows($f[0])===$before,'Overflow wrote'); });
test('4B2 creation contributor cannot independently review own content',function () {
    [$f,$repo,,$g,$e]=creationFixture(); $id=$e->createDraft(newDraftInput());
    $f[0]->grants[1]['can_review']=1; $f[0]->grants[1]['qualification_scope']='Synthetic dual-capability reviewer';
    $before=unchangedRows($f[0]); rejects(fn()=>$g->recordRiskEligibility($id,1,'draft','eligible','Self assessment',true)); check(unchangedRows($f[0])===$before,'Creator reviewed own draft');
});
test('4B2 unprepared new draft cannot submit or publish',function () { [$f,,,$g,$e]=creationFixture(); $id=$e->createDraft(newDraftInput()); $before=unchangedRows($f[0]); rejects(fn()=>$g->submit($id,1,'draft',['en'])); $f[0]->actor=3; rejects(fn()=>$g->publish($id,1,['en'])); check(unchangedRows($f[0])===$before,'Fresh draft gained approval/publication'); });
test('4B2 publisher-only permission does not grant creation',function () { [$f,,,,$e]=creationFixture(); $f[0]->actor=3; $f[0]->grants[3]['can_edit']=0; $before=unchangedRows($f[0]); rejects(fn()=>$e->createDraft(newDraftInput())); rejects(fn()=>$e->addItem(1,newItemInput())); check(unchangedRows($f[0])===$before,'Publisher implicitly edits'); });
test('4B2 invalid optional first item leaves no draft',function () { [$f,,,,$e]=creationFixture(); $before=unchangedRows($f[0]); rejects(fn()=>$e->createDraft(newDraftInput(['include_first_item'=>'1','first_item'=>['item_type'=>'unknown','title_en'=>'First']]))); check(unchangedRows($f[0])===$before,'Invalid first item left parent'); });
test('4B2 schema-length draft boundaries accepted',function () { [$f,,,,$e]=creationFixture(); $id=$e->createDraft(newDraftInput(['protocol'=>['title_hi'=>str_repeat('ह',255),'overview_en'=>str_repeat('x',65535)]])); check(mb_strlen($f[0]->protocols[$id]['title_hi'])===255 && strlen($f[0]->protocols[$id]['overview_en'])===65535,'Valid schema boundary rejected'); });
test('4B2 permission system-list outage is safe infrastructure response',function () {
    [$f]=creationFixture();
    $permissions=new class extends App\Repositories\TreatmentEditorialPermissionRepository { public function __construct() {} public function systems(int $userId): array { throw new RuntimeException('PRIVATE_SQL_SECRET'); } };
    $e=new App\Services\TreatmentEditorialService($f[5],$f[6],$permissions,$f[3],$f[4],$f[1]);
    $c=new EditorialController($e,new GovernanceMemoryAuth($f[0]),new EditorialMemorySession(),$f[1]); $c->create();
    check(http_response_code()===503 && !str_contains(serialize($c->rendered),'PRIVATE_SQL') && count($f[0]->protocols)===1,'System-list failure leaked or denied');
});
foreach (['protocol','item','event'] as $stage) {
    test('4B2 atomic initial draft rollback on '.$stage.' failure',function () use ($stage) { [$f,$repo,,,$e]=creationFixture(); $repo->stage=$stage; $repo->failure=new PDOException('PRIVATE_SQL_SECRET'); $before=unchangedRows($f[0]); $error=hardeningFailure(fn()=>$e->createDraft(newDraftInput(['include_first_item'=>'1','first_item'=>['item_type'=>'diet','title_en'=>'First']]))); check($error===$repo->failure && unchangedRows($f[0])===$before,'Initial transaction leaked rows'); });
}
foreach (['item','event'] as $stage) {
    test('4B2 item revision and event rollback on '.$stage.' failure',function () use ($stage) { [$f,$repo,,,$e]=creationFixture(); $repo->stage=$stage; $repo->failure=new PDOException('PRIVATE_SQL_SECRET'); $before=unchangedRows($f[0]); $error=hardeningFailure(fn()=>$e->addItem(1,newItemInput())); check($error===$repo->failure && unchangedRows($f[0])===$before,'Item transaction leaked'); });
}

foreach (['store','storeItem'] as $method) {
    foreach ([null,'wrong',['fixture-csrf']] as $token) {
        test('4B2 '.$method.' rejects CSRF '.json_encode($token),function () use ($method,$token) { [$f,,,,,$c]=creationFixture(); $input=$method==='store' ? newDraftInput() : newItemInput(); if ($token===null) { unset($input['_csrf_token']); } else { $input['_csrf_token']=$token; } $before=unchangedRows($f[0]); check(controllerPost($c,$method,$input,$method==='store' ? [] : ['1'])===null && http_response_code()===403,'Bad CSRF accepted'); check(unchangedRows($f[0])===$before,'CSRF wrote'); });
    }
    test('4B2 '.$method.' rejects unsupported method',function () use ($method) { [$f,,,,,$c]=creationFixture(); $_POST=$method==='store' ? newDraftInput() : newItemInput(); $_SERVER['REQUEST_METHOD']='GET'; $method==='store' ? $c->store() : $c->storeItem('1'); check(http_response_code()===422 && count($f[0]->protocols)===1 && count($f[0]->items)===1,'GET wrote'); });
    foreach ([PDOException::class,UnexpectedValueException::class] as $class) {
        test('4B2 '.$method.' safe storage failure '.$class,function () use ($method,$class) { [$f,$repo,,,,$c]=creationFixture(); $repo->stage='event'; $repo->failure=new $class('PRIVATE_SQL_SECRET'); $before=unchangedRows($f[0]); controllerPost($c,$method,$method==='store' ? newDraftInput() : newItemInput(),$method==='store' ? [] : ['1']); check(http_response_code()===($class===PDOException::class ? 503 : 500) && !str_contains(serialize($c->rendered),'PRIVATE_SQL') && unchangedRows($f[0])===$before,'Unsafe failure mapping'); });
    }
    foreach (['user','lockUsers','active'] as $stage) {
        test('4B2 '.$method.' permission outage '.$stage.' safe 503',function () use ($method,$stage) { [$f,,$permissions,,,$c]=creationFixture(); $permissions->stage=$stage; $permissions->failure=new RuntimeException('PRIVATE_SQL_SECRET'); $before=unchangedRows($f[0]); // Commands use locked actor lookup; exercise user lookup through GET too.
            if ($stage==='user') { $method==='store' ? $c->create() : $c->createItem('1'); } else { controllerPost($c,$method,$method==='store' ? newDraftInput() : newItemInput(),$method==='store' ? [] : ['1']); }
            check(http_response_code()===503 && !str_contains(serialize($c->rendered),'PRIVATE_SQL') && unchangedRows($f[0])===$before,'Permission fault denied or leaked');
        });
    }
}
test('4B2 creation HTTP validation 422 and item stale 409',function () { [,,,,,$c]=creationFixture(); controllerPost($c,'store',newDraftInput(['disease_id'=>'999']),[]); check(http_response_code()===422,'Invalid disease status'); [,,,,,$c]=creationFixture(); controllerPost($c,'storeItem',newItemInput(['expected_revision'=>'2'])); check(http_response_code()===409,'Stale item status'); });
test('4B2 cross-scope creation HTTP returns 403',function () { [,,,,,$c]=creationFixture(); controllerPost($c,'store',newDraftInput(['treatment_system_id'=>'2']),[]); check(http_response_code()===403,'Scope status'); });
test('4B2 successful draft and item POST redirect to editor',function () { [$f,,,,,$c]=creationFixture(); $target=controllerPost($c,'store',newDraftInput(),[]); check($target==='/admin/treatments/2/edit?created=1','Draft PRG'); $target=controllerPost($c,'storeItem',newItemInput(),['2']); check($target==='/admin/treatments/2/edit?saved=1' && $f[0]->protocols[2]['content_revision']===2,'Item PRG'); });
test('4B2 all four creation endpoints redirect anonymous actors',function () { foreach (['create'=>[],'store'=>[],'createItem'=>['1'],'storeItem'=>['1']] as $method=>$ids) { [$f,,,,,$c]=creationFixture(); $f[0]->actor=0; $error=hardeningFailure(fn()=>$c->$method(...$ids)); check($error instanceof EditorialRedirect && $error->target==='/login' && !$c->rendered,'Anonymous route'); } });
test('4B2 forms scoped systems and actual parent identity',function () { [$f,,,,$e,$c]=creationFixture(); $c->create(); check(array_column($c->rendered['data']['systems'],'id')===[1],'Unauthorized system offered'); $c->createItem('1'); check($c->rendered['data']['protocol']['id']===1,'Wrong item parent'); $f[0]->systems[1]['is_active']=0; rejects(fn()=>$e->draftForm()); });
test('4B2 new forms are escaped labelled CSRF forms without inline scripting',function () {
    [$f,,,,$e]=creationFixture(); $data=$e->draftForm(); $data['systems'][0]['name_en']='<script>secret</script>';
    foreach (['create'=>$data,'item-create'=>$e->itemForm(1)] as $name=>$data) {
        $html=renderEditorial($name,$data); check(!str_contains($html,'<script>'),'Unescaped creation content');
        $doc=new DOMDocument(); $old=libxml_use_internal_errors(true); $doc->loadHTML($html); libxml_clear_errors(); libxml_use_internal_errors($old); $x=new DOMXPath($doc); $ids=[];
        foreach ($x->query('//*[@id]') as $node) { check(!isset($ids[$node->getAttribute('id')]),'Duplicate ID'); $ids[$node->getAttribute('id')]=true; }
        foreach ($x->query('//label[@for]') as $node) { check(isset($ids[$node->getAttribute('for')]),'Unlabelled control'); }
        check($x->query('//form[@method="post"]')->length===1 && $x->query('//input[@name="_csrf_token"]')->length===1,'Missing POST CSRF');
        if ($name==='item-create') { check($x->query('//input[@name="expected_revision"]')->length===1 && $x->query('//input[@name="expected_state"]')->length===1,'Missing stale controls'); }
        check(!preg_match('/<script\b|\bon\w+\s*=|javascript:/i',file_get_contents(dirname(__DIR__).'/app/Views/admin/treatments/'.$name.'.php')),'Inline scripting');
    }
});
test('4B2 actual creation routes register correct HTTP verbs only',function () {
    global $testConfiguration; $testConfiguration['base_path']='';
    $loader=new class { public App\Core\Router $r,$router; public function __construct() { $this->r=$this->router=new App\Core\Router(); } public function router(): App\Core\Router { return $this->r; } public function load(): array { require dirname(__DIR__).'/routes/web.php'; return (new ReflectionProperty(App\Core\Router::class,'routes'))->getValue($this->r); } };
    $found=[]; foreach ($loader->load() as $verb=>$entries) { foreach ($entries as $route) { if ($route['controller']===App\Controllers\TreatmentAdminController::class && in_array($route['method'],['create','store','createItem','storeItem'],true)) { $found[$route['method']]=[$verb,$route['uri']]; } } }
    check($found===['create'=>['GET','/admin/treatments/create'],'createItem'=>['GET','/admin/treatments/{protocolId}/items/create'],'store'=>['POST','/admin/treatments/create'],'storeItem'=>['POST','/admin/treatments/{protocolId}/items/create']],'Incorrect creation routes');
});
test('4B2 canonical public read denies created drafts in hold and governed modes',function () {
    global $testConfiguration; [$f,$repo,,,$e]=creationFixture(); $id=$e->createDraft(newDraftInput(['include_first_item'=>'1','first_item'=>['item_type'=>'herb','title_en'=>'First']]));
    $publicRepo=new class($repo,$id) extends App\Repositories\TreatmentPublicationRepository { public function __construct(private CreationRepository $r,private int $id) {} public function candidates(int $diseaseId,?int $protocolId=null): array { return [$this->r->aggregate($this->id)+['system'=>['id'=>1,'is_active'=>1],'disease'=>['id'=>1],'approval'=>null,'unresolved_invalidation'=>0]]; } };
    $reader=new App\Services\TreatmentPublicationService($publicRepo,$f[1],$f[2],$f[8]);
    foreach (['hold','governed'] as $mode) { $testConfiguration['treatment_governance']['publication_mode']=$mode; check($reader->assessProtocolForPublicRead($id,1)['status']==='DENY' && $reader->getPublicTreatmentProjection($id,1)===null && $reader->getPublicTreatmentsForDisease(1)===[],'Draft exposed'); }
    $testConfiguration['treatment_governance']['publication_mode']='hold';
});
test('4B2 real insert repository uses BaseRepository parameterized writes and field allowlists',function () {
    $r=new class extends App\Repositories\TreatmentGovernanceRepository { public array $writes=[]; public function __construct() {} protected function execute(string $sql,array $params=[]): bool { $this->writes[]=[$sql,$params]; return true; } protected function lastInsertId(): string { return '7'; } };
    $rules=new App\Services\TreatmentGovernanceRules(); $p=$rules->legacyProtocol(['disease_id'=>1,'treatment_system_id'=>1,'title_en'=>'Bound value','title_hi'=>null,'overview_en'=>null,'overview_hi'=>null]);
    $i=$rules->legacyItem(['protocol_id'=>7,'item_type'=>'herb','title_en'=>'Bound item','title_hi'=>null,'instructions_en'=>null,'instructions_hi'=>null,'sort_order'=>0]);
    check($r->insertProtocol($p)===7 && $r->insertItem($i)===7 && count($r->writes)===2,'Insert persistence');
    foreach ($r->writes as [$sql,$params]) { check(!str_contains($sql,'Bound') && str_contains($sql,':title_en') && isset($params['title_en']),'Unparameterized insert'); }
    rejects(fn()=>$r->insertProtocol($p+['id'=>99])); rejects(fn()=>$r->insertItem($i+['actor_id'=>99])); check(count($r->writes)===2,'Repository allowlist bypass');
});

$creationCount=$passed-$creationBaseline;
echo "Treatment creation: $creationCount focused tests passed; $creationBaseline embedded regression tests also passed; no database/session/log writes.\n";
ob_end_flush();
