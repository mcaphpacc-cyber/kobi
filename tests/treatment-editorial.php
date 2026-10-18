<?php

declare(strict_types=1);

namespace App\Core {
    // Keep diagnostics in memory, including simulated storage failures.
    final class Logger { public static array $messages=[]; public static function error(string $message): void { self::$messages[]=$message; } }
}

namespace {
    ob_start(); // Keep headers testable without sending output during the suite.
    require __DIR__.'/treatment-governance.php'; // Reuse fakes and retain all 88 foundation assertions.
    $foundationPassed=$passed;

    function e(?string $value): string { return htmlspecialchars($value??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
    function url(string $path=''): string { return '/'.ltrim($path,'/'); }
    function csrfField(): string { return '<input type="hidden" name="_csrf_token" value="'.e((new EditorialMemorySession())->csrfToken()).'">'; }
    final class EditorialRedirect extends Exception { public function __construct(public string $target) { parent::__construct('Test redirect'); } }
    function redirect(string $path): never { throw new EditorialRedirect($path); }

    final class EditorialMemorySession extends App\Core\Session { public function start(): void {} }
    final class EditorialController extends App\Controllers\TreatmentAdminController {
        public array $rendered=[];
        protected function view(string $view,array $data=[]): void { $this->rendered=['view'=>$view,'data'=>$data]; }
    }
    function editorialFixture(bool $legacy=false): array {
        $f=fixture($legacy); [$m,$rules,,$auth,$governance,$repo,$sources,$permissions]=$f;
        $editor=new App\Services\TreatmentEditorialService($repo,$sources,$permissions,$auth,$governance,$rules);
        $_GET=[]; $_POST=[]; $_SESSION=['_csrf_token'=>'fixture-csrf']; $_SERVER['REQUEST_METHOD']='GET'; http_response_code(200);
        $controller=new EditorialController($editor,new GovernanceMemoryAuth($m),new EditorialMemorySession(),$rules);
        return [$f,$editor,$controller];
    }
    function draftInput(array $protocol=[],array $items=[],int $revision=1,string $state='draft'): array {
        return ['_csrf_token'=>'fixture-csrf','expected_revision'=>(string)$revision,'expected_state'=>$state,'protocol'=>$protocol,'items'=>$items,'form_complete'=>'1'];
    }
    function referenceInput(int $sourceId=1,?int $itemId=null,string $operation='attach',int $revision=1): array {
        return ['_csrf_token'=>'fixture-csrf','expected_revision'=>(string)$revision,'expected_state'=>'draft','source_id'=>(string)$sourceId,'item_id'=>$itemId===null ? '' : (string)$itemId,'claim_scope'=>'Fixture claim','locator'=>'Page 2','operation'=>$operation];
    }
    function sourceInput(array $changes=[]): array { return array_replace(['_csrf_token'=>'fixture-csrf','source_type'=>'guideline','title'=>'Synthetic source','citation_text'=>'Synthetic citation','url'=>'https://example.test/source'],$changes); }
    function controllerPost(EditorialController $c,string $method,array $input,array $ids=['1']): ?string {
        $_SERVER['REQUEST_METHOD']='POST'; $_POST=$input;
        try { $c->$method(...$ids); return null; } catch (EditorialRedirect $redirect) { return $redirect->target; }
    }
    function renderEditorial(string $name,array $data): string {
        return (function () use ($name,$data) { extract($data,EXTR_SKIP); ob_start(); require dirname(__DIR__).'/app/Views/admin/treatments/'.$name.'.php'; return ob_get_clean(); })();
    }

    test('2C2 every editorial route redirects unauthenticated actors',function () {
        foreach (['index'=>[],'edit'=>['1'],'save'=>['1'],'submit'=>['1'],'reference'=>['1'],'sources'=>['1'],'createSource'=>['1'],'storeSource'=>['1'],'source'=>['1','1'],'verifySource'=>['1','1'],'withdrawSource'=>['1','1']] as $method=>$ids) {
            [$f,,$c]=editorialFixture(); $f[0]->actor=0;
            try { $c->$method(...$ids); throw new LogicException('Anonymous route accepted '.$method); } catch (EditorialRedirect $r) { check($r->target==='/login','Anonymous redirect'); }
            check(!$c->rendered,'Anonymous content rendered');
        }
    });
    test('2C2 authorized editor reads actual protocol scope',function () { [$f,$s,$c]=editorialFixture(); $c->edit('1'); check($c->rendered['data']['protocol']['id']===1,'Editor cannot inspect protocol'); });
    test('2C2 cross-system protocol IDOR rejected without content leakage',function () { [$f,$s,$c]=editorialFixture(); $f[0]->protocols[2]=array_replace($f[0]->protocols[1],['id'=>2,'treatment_system_id'=>2,'title_en'=>'Restricted title']); $c->edit('2'); check(http_response_code()===403 && !str_contains(serialize($c->rendered),'Restricted title'),'Cross-system leakage'); rejects(fn()=>$s->save(2,draftInput(['overview_en'=>'No']))); });
    test('2C2 reviewer-only user cannot access editor or save',function () { [$f,$s,$c]=editorialFixture(); $f[0]->actor=2; rejects(fn()=>$s->listing([])); rejects(fn()=>$s->detail(1)); rejects(fn()=>$s->save(1,draftInput(['overview_en'=>'No']))); });
    test('2C2 revoked editor cannot read or save',function () { [$f,$s]=editorialFixture(); $f[0]->grants[1]['revoked_at']='2026-01-01 00:00:00.000000'; rejects(fn()=>$s->detail(1)); rejects(fn()=>$s->save(1,draftInput(['overview_en'=>'No']))); });
    foreach (['treatment_system_id','disease_id','user_id','actor_id','reviewer_id','publisher_id'] as $field) {
        test('2C2 rejects submitted identity/scope '.$field,function () use ($field) { [$f,$s]=editorialFixture(); $before=serialize($f[0]); rejects(fn()=>$s->save(1,draftInput(['overview_en'=>'No'])+[$field=>'2'])); check(serialize($f[0])===$before,'Mass assignment wrote state'); });
    }
    foreach ([null,'wrong',['fixture-csrf']] as $token) {
        test('2C2 controller rejects missing malformed or invalid CSRF '.json_encode($token),function () use ($token) { [$f,,$c]=editorialFixture(); $input=draftInput(['overview_en'=>'Changed']); if ($token===null) { unset($input['_csrf_token']); } else { $input['_csrf_token']=$token; } $before=serialize($f[0]); check(controllerPost($c,'save',$input)===null && http_response_code()===403,'CSRF accepted'); check(serialize($f[0])===$before,'CSRF failure wrote'); });
    }
    test('2C2 valid CSRF saves and redirects',function () { [$f,,$c]=editorialFixture(); $target=controllerPost($c,'save',draftInput(['overview_en'=>'Changed'])); check($target==='/admin/treatments/1/edit?saved=1' && $f[0]->protocols[1]['overview_en']==='Changed','Valid CSRF save failed'); });
    test('2C2 all POST entry points reject invalid CSRF before writes',function () { foreach (['save'=>['1'],'submit'=>['1'],'reference'=>['1'],'storeSource'=>['1'],'verifySource'=>['1','1'],'withdrawSource'=>['1','1']] as $method=>$ids) { [$f,,$c]=editorialFixture(); $before=serialize($f[0]); controllerPost($c,$method,['_csrf_token'=>'bad'],$ids); check(http_response_code()===403 && serialize($f[0])===$before,'Unprotected POST '.$method); } });
    test('2C2 direct GET call cannot mutate via POST action',function () { [$f,,$c]=editorialFixture(); $_POST=draftInput(['overview_en'=>'No']); $c->save('1'); check($f[0]->protocols[1]['content_revision']===1 && http_response_code()===422,'GET mutated content'); });
    test('2C2 combined protocol/item save uses one revision and authenticated updater',function () { [$f,$s]=editorialFixture(); $s->save(1,draftInput(['overview_en'=>'Changed'],[1=>['instructions_en'=>'Changed']])); check($f[0]->protocols[1]['content_revision']===2 && $f[0]->protocols[1]['updated_by']===1 && count($f[0]->events)===1 && $f[0]->protocols[1]['updated_at']!==null,'Edit audit/revision failure'); });
    test('2C2 no-op form preserves revision including nullable legacy fields',function () { [$f,$s]=editorialFixture(true); $s->save(1,draftInput(['title_en'=>'Test protocol','content_purpose'=>'','overview_hi'=>'विवरण'],[1=>['is_included'=>'0','limitations_en'=>'']])); check($f[0]->protocols[1]['content_revision']===1 && !$f[0]->events,'No-op form revised'); });
    test('2C2 form truncation is rejected',function () { [$f,$s]=editorialFixture(); $input=draftInput(['overview_en'=>'No']); unset($input['form_complete']); rejects(fn()=>$s->save(1,$input)); check($f[0]->protocols[1]['content_revision']===1,'Truncated form saved'); });
    test('2C2 material edit invalidates approval and all languages',function () { [$f,$s]=editorialFixture(); reviewed($f); $f[0]->actor=1; $s->save(1,draftInput(['overview_hi'=>'नया'],[],1,'approved')); check($f[0]->protocols[1]['approved_review_id']===null && $f[0]->protocols[1]['review_state']==='draft','Approval survived Hindi edit'); });
    test('2C2 concurrent stale editor cannot overwrite newer content',function () { [$f,$s,$c]=editorialFixture(); $old=draftInput(['overview_en'=>'Editor A']); $s->save(1,draftInput(['overview_en'=>'Editor B'])); $before=serialize($f[0]); controllerPost($c,'save',$old); check(http_response_code()===409 && serialize($f[0])===$before && str_contains($c->rendered['data']['error'],'Reload'),'Stale save overwrote revision'); });
    test('2C2 published save is rejected without silent withdrawal',function () { [$f,$s]=editorialFixture(); published($f); $f[0]->actor=1; $before=serialize($f[0]); rejects(fn()=>$s->save(1,draftInput(['overview_en'=>'No'],[],1,'approved'))); check(serialize($f[0])===$before && $f[0]->protocols[1]['publication_status']==='published','Save withdrew publication'); });
    test('2C2 legacy inclusion is explicit and parent revision changes',function () { [$f,$s]=editorialFixture(true); $s->detail(1); check($f[0]->items[1]['is_included']===0,'Read included item'); $s->save(1,draftInput([],[1=>['is_included'=>'1']])); check($f[0]->items[1]['is_included']===1 && $f[0]->protocols[1]['content_revision']===2,'Explicit inclusion failed'); });
    test('2C2 invalid item type rejected',function () { [$f,$s]=editorialFixture(); rejects(fn()=>$s->save(1,draftInput([],[1=>['item_type'=>'unknown']]))); });
    foreach (['-1','1.5','01','2147483648'] as $sort) { test('2C2 invalid ordering '.$sort,function () use ($sort) { [$f,$s]=editorialFixture(); rejects(fn()=>$s->save(1,draftInput([],[1=>['sort_order'=>$sort]]))); }); }
    test('2C2 excluded legacy zero ordering is preserved while included zero rejected',function () { [$f,$s]=editorialFixture(true); $f[0]->items[1]['sort_order']=0; check(!$s->save(1,draftInput([],[1=>['sort_order'=>'0']])),'Legacy zero altered'); rejects(fn()=>$s->save(1,draftInput([],[1=>['is_included'=>'1','sort_order'=>'0']]))); });
    test('2C2 excluded nullable legacy ordering is preserved',function () { [$f,$s]=editorialFixture(true); $f[0]->items[1]['sort_order']=null; check(!$s->save(1,draftInput([],[1=>['sort_order'=>'']])),'Nullable legacy ordering changed'); });
    test('2C2 duplicate included ordering rejected transactionally',function () { [$f,$s]=editorialFixture(); $f[0]->items[2]=array_replace($f[0]->items[1],['id'=>2,'sort_order'=>2]); $before=serialize($f[0]); rejects(fn()=>$s->save(1,draftInput([],[2=>['sort_order'=>'1']]))); check(serialize($f[0])===$before,'Ordering rejection wrote'); });
    test('2C2 submission validates included fields and explicit verified references',function () { [$f,$s]=editorialFixture(); $f[0]->items[1]['limitations_en']=null; rejects(fn()=>$s->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'1','expected_state'=>'draft','locales'=>['en']])); check(!$f[0]->events,'Incomplete submission recorded'); });
    test('2C2 safe submission records pending review snapshot but no approval/publication',function () { [$f,$s]=editorialFixture(); $s->submit(1,['_csrf_token'=>'fixture-csrf','expected_revision'=>'1','expected_state'=>'draft','locales'=>['en']]); $p=$f[0]->protocols[1]; check($p['review_state']==='pending_review' && $p['content_revision']===1 && $p['approved_review_id']===null && $p['publication_status']==='unpublished' && $f[0]->events[1]['content_hash']!==null,'Submission crossed phase boundary'); });
    test('2C2 source creation stays unverified and attributes authenticated actor',function () { [$f,$s]=editorialFixture(true); $id=$s->createSource(1,sourceInput()); check($f[0]->sources[$id]['status']==='unverified' && $f[0]->sources[$id]['created_by']===1 && $f[0]->protocols[1]['evidence_classification']==='not_assessed','Source inferred effectiveness'); });
    foreach ([['source_type'=>'unknown'],['url'=>'javascript:alert(1)'],['url'=>'data:text/html,unsafe'],['url'=>'ftp://example.test/source'],['title'=>' '],['citation_text'=>' '],['publication_date'=>'2025-02-30'],['publication_date'=>'0000-01-01'],['actor_id'=>'2']] as $change) { test('2C2 source input rejection '.implode(',',array_keys($change)).' '.json_encode($change),function () use ($change) { [$f,$s]=editorialFixture(); $before=serialize($f[0]); rejects(fn()=>$s->createSource(1,sourceInput($change))); check(serialize($f[0])===$before,'Invalid source committed'); }); }
    test('2C2 ordinary editor cannot verify provenance',function () { [$f,$s]=editorialFixture(); $id=$s->createSource(1,sourceInput()); rejects(fn()=>$s->verifySource(1,$id,['_csrf_token'=>'fixture-csrf'])); });
    test('2C2 reviewer verifies provenance without changing treatment classification',function () { [$f,$s]=editorialFixture(true); $id=$s->createSource(1,sourceInput()); $f[0]->actor=2; $s->verifySource(1,$id,['_csrf_token'=>'fixture-csrf']); check($f[0]->sources[$id]['status']==='verified' && $f[0]->protocols[1]['evidence_classification']==='not_assessed','Verification inferred treatment support'); });
    test('2C2 withdrawal requires reason and capabilities and invalidates linked protocol',function () { [$f,$s]=editorialFixture(); rejects(fn()=>$s->withdrawSource(1,1,['_csrf_token'=>'fixture-csrf','reason'=>'Reason'])); $f[0]->actor=3; rejects(fn()=>$s->withdrawSource(1,1,['_csrf_token'=>'fixture-csrf','reason'=>' '])); $s->withdrawSource(1,1,['_csrf_token'=>'fixture-csrf','reason'=>'Provenance withdrawn']); check($f[0]->sources[1]['status']==='withdrawn' && $f[0]->protocols[1]['content_revision']===2 && count($f[0]->protocolRefs)===1,'Withdrawal invalidation/history'); $f[0]->actor=2; rejects(fn()=>$s->verifySource(1,1,['_csrf_token'=>'fixture-csrf'])); });
    test('2C2 replacement version preserves predecessor and relationships',function () { [$f,$s]=editorialFixture(); $before=serialize([$f[0]->sources[1],$f[0]->protocolRefs,$f[0]->itemRefs]); $id=$s->createSource(1,sourceInput(['replaces_source_id'=>'1'])); check($f[0]->sources[$id]['replaces_source_id']===1 && serialize([$f[0]->sources[1],$f[0]->protocolRefs,$f[0]->itemRefs])===$before,'Replacement rewired history'); $next=$s->createSource(1,sourceInput(['replaces_source_id'=>(string)$id])); check($f[0]->sources[$next]['replaces_source_id']===$id,'Valid chain failed'); });
    test('2C2 source replacement rejects cycles and direct self',function () { [$f,$s]=editorialFixture(); rejects(fn()=>$f[1]->replacement(1,1,fn()=>null)); $f[0]->sources[1]['replaces_source_id']=2; $f[0]->sources[2]=array_replace($f[0]->sources[1],['id'=>2,'replaces_source_id'=>1]); rejects(fn()=>$s->createSource(1,sourceInput(['replaces_source_id'=>'1']))); });
    test('2C2 protocol and item attachment explicit and independent',function () { [$f,$s]=editorialFixture(); $f[0]->protocolRefs=[]; $f[0]->itemRefs=[]; $s->reference(1,referenceInput()); check(count($f[0]->protocolRefs)===1 && !$f[0]->itemRefs && $f[0]->protocols[1]['content_revision']===2,'Protocol reference inherited'); $s->reference(1,referenceInput(1,1,'attach',2)); check(count($f[0]->itemRefs)===1 && $f[0]->protocols[1]['content_revision']===3,'Item reference missing'); });
    test('2C2 duplicate attachment rejected instead of upserting',function () { [$f,$s]=editorialFixture(); $before=serialize($f[0]); rejects(fn()=>$s->reference(1,referenceInput())); check(serialize($f[0])===$before,'Duplicate attachment changed claim'); });
    test('2C2 empty claim scope rejected',function () { [$f,$s]=editorialFixture(); $input=referenceInput(); $input['claim_scope']=' '; rejects(fn()=>$s->reference(1,$input)); });
    test('2C2 withdrawn source cannot be newly attached',function () { [$f,$s]=editorialFixture(); $f[0]->protocolRefs=[]; $f[0]->sources[1]['status']='withdrawn'; rejects(fn()=>$s->reference(1,referenceInput())); });
    test('2C2 relationship update and removal revise once and preserve source',function () { [$f,$s]=editorialFixture(); $s->reference(1,referenceInput(1,null,'update')); check($f[0]->protocols[1]['content_revision']===2 && $f[0]->protocolRefs[0]['claim_scope']==='Fixture claim','Claim update failed'); $s->reference(1,referenceInput(1,null,'remove',2)); check($f[0]->protocols[1]['content_revision']===3 && !$f[0]->protocolRefs && isset($f[0]->sources[1]) && count($f[0]->itemRefs)===1,'Relationship removal changed unrelated evidence'); });
    test('2C2 item IDOR and nonexistent relationship updates rejected',function () { [$f,$s]=editorialFixture(); $f[0]->items[2]=array_replace($f[0]->items[1],['id'=>2,'protocol_id'=>2]); rejects(fn()=>$s->save(1,draftInput([],[2=>['title_en'=>'No']]))); rejects(fn()=>$s->reference(1,referenceInput(1,2))); $f[0]->itemRefs=[]; rejects(fn()=>$s->reference(1,referenceInput(1,1,'remove'))); });
    foreach (['review_state','publication_status','approved_review_id','content_revision','published_locales','disease_id','treatment_system_id'] as $field) { test('2C2 content mass assignment rejects '.$field,function () use ($field) { [$f,$s]=editorialFixture(); rejects(fn()=>$s->save(1,draftInput([$field=>'1']))); rejects(fn()=>$s->save(1,draftInput([], [1=>[$field=>'1']]))); }); }
    test('2C2 saving legacy draft never assigns evidence includes items or publishes automatically',function () { [$f,$s]=editorialFixture(true); $s->save(1,draftInput(['overview_en'=>'Explicit editorial change'])); $p=$f[0]->protocols[1]; check($p['publication_status']==='unpublished' && $p['approved_review_id']===null && $p['published_locales']==='' && $p['evidence_classification']==='not_assessed' && $p['content_purpose']===null && $f[0]->items[1]['is_included']===0 && !$f[0]->sources,'Automatic legacy conversion'); check($f[8]->publicTreatments()===[],'Public reader leaked draft'); });
    test('2C2 protocol list filters and pagination remain system-scoped',function () { [$f,$s]=editorialFixture(); $f[0]->protocols[2]=array_replace($f[0]->protocols[1],['id'=>2,'treatment_system_id'=>2]); $list=$s->listing(['review_state'=>'draft']); check(count($list['protocols'])===1 && $list['protocols'][0]['id']===1,'List exposed system B'); rejects(fn()=>$s->listing(['treatment_system_id'=>'2'])); rejects(fn()=>$s->listing(['publication_status'=>'invalid'])); rejects(fn()=>$s->listing(['page'=>'0'])); });
    test('2C2 source catalog requires a valid scoped grant',function () { [$f,$s]=editorialFixture(); rejects(fn()=>$s->sourceListing(2,[])); $f[0]->actor=2; check($s->sourceListing(1,[])['canCreate']===false && $s->sourceDetail(1,1)['canVerify']===true,'Reviewer source capabilities'); });
    test('2C2 edit HTML escapes editorial text and claim scope',function () { [$f,$s]=editorialFixture(); $f[0]->protocols[1]['overview_en']='<script>unsafe()</script>'; $f[0]->protocolRefs[0]['claim_scope']='<img src=x onerror=unsafe()>'; $html=renderEditorial('edit',$s->detail(1)); check(str_contains($html,'&lt;script&gt;unsafe()&lt;/script&gt;') && str_contains($html,'&lt;img src=x onerror=unsafe()&gt;') && !str_contains($html,'<script>') && !str_contains($html,'<img src=x'),'Unsafe editor output'); });
    test('2C2 source HTML escapes citation and renders unsafe stored URL only as text',function () { [$f,$s]=editorialFixture(); $f[0]->sources[1]['citation_text']='<script>unsafe()</script>'; $f[0]->sources[1]['url']='javascript:unsafe()'; $html=renderEditorial('source',$s->sourceDetail(1,1)); check(str_contains($html,'&lt;script&gt;') && !str_contains($html,'href="javascript:') && !str_contains($html,'<script>'),'Unsafe citation/url output'); });
    test('2C2 published form read-only and no approval/publication UI',function () { [$f,$s]=editorialFixture(); published($f); $f[0]->actor=1; $html=renderEditorial('edit',$s->detail(1)); check(str_contains($html,'<fieldset disabled>') && !str_contains($html,'Save draft content') && !str_contains($html,'Submit saved content'),'Published editing form enabled'); });
    test('2C2 all templates render and POST form controls are complete',function () {
        [$f,$s]=editorialFixture();
        $pages=['index'=>$s->listing([]),'edit'=>$s->detail(1),'sources'=>$s->sourceListing(1,[]),'source-create'=>$s->sourceForm(1),'source'=>$s->sourceDetail(1,1),'error'=>['error'=>'Safe fixture error']];
        foreach ($pages as $name=>$data) {
            $html=renderEditorial($name,$data); $doc=new DOMDocument(); $old=libxml_use_internal_errors(true); $doc->loadHTML('<?xml encoding="UTF-8">'.$html); libxml_clear_errors(); libxml_use_internal_errors($old);
            $xpath=new DOMXPath($doc); $ids=[];
            foreach ($xpath->query('//*[@id]') as $node) { $id=$node->getAttribute('id'); check(!isset($ids[$id]),'Duplicate field ID on '.$name); $ids[$id]=true; }
            foreach ($xpath->query('//label[@for]') as $node) { check(isset($ids[$node->getAttribute('for')]),'Missing labelled control on '.$name); }
            foreach ($xpath->query('//form[@method="post"]') as $form) { check($xpath->query('.//input[@name="_csrf_token"]',$form)->length===1,'Incomplete CSRF form '.$name); check($xpath->query('.//form',$form)->length===0,'Nested form'); if (str_contains($form->getAttribute('action'),'/admin/treatments/')) { check($xpath->query('.//input[@name="expected_revision"]',$form)->length===1 && $xpath->query('.//input[@name="expected_state"]',$form)->length===1,'Missing optimistic controls'); } }
        }
    });
    test('2C2 every new POST form uses existing CSRF helper and no inline JS',function () { foreach (glob(dirname(__DIR__).'/app/Views/admin/treatments/*.php') as $file) { $text=file_get_contents($file); check(!preg_match('/<script\b|\bon\w+\s*=|javascript:|innerHTML/i',$text),'Inline scripting in '.$file); if (str_contains($text,'method="post"')) { check(str_contains($text,'csrfField()'),'Missing form CSRF'); } } });
    test('2C2 controller maps internal storage failures to safe setup message',function () { [$f,,$c]=editorialFixture(); $broken=new class($f[5],$f[6],$f[7],$f[3],$f[4],$f[1]) extends App\Services\TreatmentEditorialService { public function detail(int $id): array { throw new PDOException('Synthetic private SQL path credentials'); } }; $c=new EditorialController($broken,new GovernanceMemoryAuth($f[0]),new EditorialMemorySession(),$f[1]); $c->edit('1'); check(http_response_code()===503 && !str_contains(serialize($c->rendered),'private SQL') && count(App\Core\Logger::$messages)>0,'Internal database error leaked'); });
    test('2C2 real route registration has 19 internal routes and no publication actions',function () {
        global $testConfiguration; $testConfiguration['base_path']='';
        $loader=new class { public App\Core\Router $r,$router; public function __construct() { $this->r=$this->router=new App\Core\Router(); } public function router(): App\Core\Router { return $this->r; } public function load(): void { require dirname(__DIR__).'/routes/web.php'; } };
        $loader->load(); $routes=(new ReflectionProperty(App\Core\Router::class,'routes'))->getValue($loader->r); $found=[];
        foreach ($routes as $verb=>$entries) { foreach ($entries as $route) { if ($route['controller']===App\Controllers\TreatmentAdminController::class) { check(str_starts_with($route['uri'],'/admin/'),'Noninternal route'); check(!in_array($route['method'],['approve','publish'],true),'Approval/publication UI route'); if (in_array($route['method'],['save','submit','reference','store','storeItem','storeSource','verifySource','withdrawSource','withdraw','resetReview'],true)) { check($verb==='POST','State change registered as GET'); } $found[]=$route; } } }
        check(count($found)===19,'Unexpected editorial route count');
    });
    test('2C2 existing container resolves editorial controller with shared infrastructure',function () { $container=new App\Core\Container(); $pdo=new GovernanceTransactionPDO(); (new ReflectionProperty($container,'instances'))->setValue($container,[App\Core\Database::class=>new GovernanceContainerDatabase($pdo)]); check($container->get(App\Controllers\TreatmentAdminController::class) instanceof App\Controllers\TreatmentAdminController,'Editorial dependency resolution'); check($pdo->begins===0,'Container mutated'); });

    $editorialPassed=$passed-$foundationPassed;
    echo "Treatment editorial: $editorialPassed tests passed; $foundationPassed foundation tests also passed; no database/session/log writes.\n";
    ob_end_flush();
}
