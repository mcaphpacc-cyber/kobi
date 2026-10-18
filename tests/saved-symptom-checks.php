<?php
declare(strict_types=1);
namespace App\Core { class Logger { public static function error(string $message): void {} } }
namespace {
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
spl_autoload_register(function ($name) { if (str_starts_with($name, 'App\\')) require dirname(__DIR__) . '/app/' . str_replace('\\','/',substr($name,4)) . '.php'; });
function e(?string $text): string { return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8'); }
function url(string $path): string { return '/kobi/public' . $path; }
function csrfField(): string { return '<input name="_csrf_token" type="hidden" value="valid">'; }
class SavedRedirect extends \Exception {}
function redirect(string $path): never { throw new SavedRedirect($path); }
class SavedAuth extends \App\Services\AuthService {
    public ?int $uid = 1; public function __construct() {}
    public function check(): bool { return $this->uid !== null; }
    public function id(): ?int { return $this->uid; }
    public function user(): ?array { return $this->check() ? ['id'=>$this->uid] : null; }
}
class SavedSession extends \App\Core\Session {
    public array $values = [];
    public function get(string $key,mixed $default=null): mixed { return $this->values[$key]??$default; }
    public function put(string $key,mixed $value): void { $this->values[$key]=$value; }
    public function remove(string $key): void { unset($this->values[$key]); }
    public function verifyCsrfToken(?string $token): bool { return $token === 'valid'; }
}
class SavedChecker extends \App\Services\SymptomCheckerService {
    public array $labels = [['id'=>1,'symptom_en'=>'Fever'],['id'=>2,'symptom_en'=>'Cough']];
    public function __construct() {}
    public function getSymptomsByIds(array $ids): array { return $this->labels; }
    public function match(array $ids): array { throw new \RuntimeException('Must not recompute'); }
    public function recordSearch(array $ids): void { throw new \RuntimeException('Must not increment'); }
}
class SavedMemory extends \App\Repositories\SavedSymptomCheckRepository {
    public array $rows=[]; public int $writes=0; private int $nextId=1; public function __construct() {}
    public function createForUser(int $userId,string $key,array $snapshot,string $json): int {
        $id=$this->nextId++; $this->writes++;
        $this->rows[$id]=['id'=>$id,'user_id'=>$userId,'check_key'=>$key,'checked_at'=>$snapshot['checked_at'],'saved_at'=>gmdate('Y-m-d H:i:s'),'selected_symptom_count'=>count($snapshot['selected']),'displayed_result_count'=>count($snapshot['results']),'snapshot_json'=>$json]; return $id;
    }
    public function findForUser(int $id,int $userId): ?array { return isset($this->rows[$id])&&$this->rows[$id]['user_id']===$userId?$this->rows[$id]:null; }
    public function findKeyForUser(string $key,int $userId): ?array { foreach($this->rows as $r) if($r['user_id']===$userId&&$r['check_key']===$key)return ['id'=>$r['id']]; return null; }
    public function pageForUser(int $userId,int $page): array { $rows=array_values(array_filter($this->rows,fn($r)=>$r['user_id']===$userId)); usort($rows,fn($a,$b)=>[$b['checked_at'],$b['id']]<=>[$a['checked_at'],$a['id']]); return array_slice($rows,($page-1)*20,21); }
    public function deleteForUser(int $id,int $userId): bool { if(!$this->findForUser($id,$userId))return false; unset($this->rows[$id]);$this->writes++;return true; }
}
class SavedController extends \App\Controllers\SavedSymptomCheckController {
    public array $rendered=[];
    protected function view(string $view,array $data=[]): void { $this->rendered=[$view,$data]; }
}
$checks=0;
function verify(bool $pass,string $label): void { global $checks; if(!$pass)throw new \RuntimeException('FAIL '.$label);$checks++; }
function denied(callable $action,string $expected): void { try{$action();throw new \LogicException('not denied');}catch(\RuntimeException $e){verify($e->getMessage()===$expected,'safe denial '.$expected);} }
$auth=new SavedAuth();$session=new SavedSession();$repo=new SavedMemory();$checker=new SavedChecker();
$service=new \App\Services\SavedSymptomCheckService($repo,$auth,$session,$checker);
$response=(new \App\Algorithms\SymptomMatcher())->match([1,2],[['id'=>9,'disease_en'=>'Original <Name>','symptoms'=>[['id'=>1,'symptom_en'=>'Fever'],['id'=>3,'symptom_en'=>'Fatigue']]]]);
$capture=$service->capture($response);verify(isset($capture['key']),'trusted completed capture');verify($repo->writes===0,'no automatic persistence');
$id=$service->save($capture['key']);verify($repo->rows[$id]['user_id']===1,'Auth owner');verify($service->save($capture['key'])===$id&&$repo->writes===1,'idempotent retry');
$original=$service->get($id)['snapshot'];verify($original['results'][0]['ranking_score']===$response['results'][0]['rankingScore'],'original score');
verify($original['results'][0]['display_percent']===(int)floor($response['results'][0]['rankingScore']+0.5),'same percent formatter');
verify($original['results'][0]['reason']===['1 selected symptom matched.','Disease coverage is 50%.','1 common symptom is not present.'],'exact frontend reasons');
verify($original['results'][0]['explanation']===['✓ Fever matched','• Fatigue is commonly associated'],'exact original explanation');
$checker->labels[0]['symptom_en']='CHANGED';$response['results'][0]['rankingScore']=99;$response['results'][0]['disease']['disease_en']='CHANGED';
verify($service->get($id)['snapshot']===$original,'history unchanged after master/result change; no matcher calls');
$check=$service->get($id);ob_start();require dirname(__DIR__).'/app/Views/account/saved-symptom-check.php';$html=ob_get_clean();
verify(str_contains($html,'Original &lt;Name&gt;')&&!str_contains($html,'CHANGED'),'historical label escaped');
verify(str_contains($html,\App\Services\SavedSymptomCheckService::DISCLAIMER),'disclaimer');verify(str_contains($html,'Symptom Match Reason'),'stored reasons rendered');
verify(!str_contains($html,'Clinical Confidence'),'neutral terminology');
$auth->uid=2;denied(fn()=>$service->get($id),'Saved symptom check not found.');denied(fn()=>$service->delete($id),'Saved symptom check not found.');denied(fn()=>$service->save($capture['key']),'Run a new check before saving.');verify($service->page(1)['rows']===[],'foreign list excluded');
$auth->uid=null;denied(fn()=>$service->save($capture['key']),'Authentication required.');denied(fn()=>$service->get($id),'Authentication required.');verify($service->capture(['arbitrary'=>'payload'])===null,'anonymous no capture');
$auth->uid=1;denied(fn()=>$service->save(str_repeat('a',64)),'Run a new check before saving.');
foreach([[],['summary'=>['selectedSymptoms'=>[999],'generatedAt'=>date('c'),'totalMatches'=>0,'displayedMatches'=>0],'results'=>[]]] as $bad){verify(($service->capture($bad)['unavailable']??false)===true,'invalid/unknown snapshot unavailable');}
$zero=['summary'=>['selectedSymptoms'=>[1,2],'generatedAt'=>date('c'),'totalMatches'=>0,'displayedMatches'=>0],'results'=>[]];
$key=$service->capture($zero)['key'];$zeroId=$service->save($key);verify($service->get($zeroId)['snapshot']['results']===[],'valid zero matches');
$session->values['pending_symptom_check']['expires']=time()-1;denied(fn()=>$service->save($key),'Run a new check before saving.');
$key=$service->capture($zero)['key'];$other=$service->capture($zero)['key'];denied(fn()=>$service->save($key),'Run a new check before saving.');
$key=$other;$session->values['pending_symptom_check']['snapshot']['version']=3;denied(fn()=>$service->save($key),'Invalid snapshot.');
for($i=0;$i<25;$i++){$key=$service->capture($zero)['key'];$service->save($key);}
$page=$service->page(1);verify(count($page['rows'])===20&&$page['has_next'],'pagination first page');verify(count($service->page(2)['rows'])===7,'pagination second page');verify($page['rows'][0]['id']>$page['rows'][1]['id'],'newest tie ordering');
$repo->rows[$id]['snapshot_json']='malformed';denied(fn()=>$service->get($id),'This saved symptom check is unavailable. You can delete it from your saved list.');
$service->delete($id);verify(!isset($repo->rows[$id]),'owner deletes malformed history');
$key=$service->capture($zero)['key'];$deleteId=$service->save($key);$service->delete($deleteId);denied(fn()=>$service->save($key),'Run a new check before saving.');
$controller=new SavedController($auth,$session,$service);$before=$repo->writes;
$_POST=['check_key'=>str_repeat('a',64)];$controller->store();verify(str_contains($controller->rendered[1]['message'],'Invalid security token'),'save CSRF');
$_POST=['confirm_delete'=>'1'];$controller->delete($zeroId);verify(str_contains($controller->rendered[1]['message'],'Invalid security token'),'delete CSRF');
verify($repo->writes===$before,'CSRF causes no writes');
$_POST=['_csrf_token'=>'valid'];$controller->delete($zeroId);verify($controller->rendered[1]['message']==='Confirm deletion before continuing.','explicit deletion confirmation');
$key=$service->capture($zero)['key'];$_POST=['_csrf_token'=>'valid','check_key'=>$key,'user_id'=>99,'profile_id'=>999,'results'=>['tampered']];
try{$controller->store();}catch(SavedRedirect $e){verify(str_contains($e->getMessage(),'?saved=1'),'save confirmation redirect');}
$owned=$repo->findKeyForUser($key,1);verify($owned!==null&&$service->get($owned['id'])['snapshot']['results']===[],'browser owner/results ignored');
$auth->uid=null;
foreach(['index','store','show','delete'] as $method){try{$method==='show'||$method==='delete'?$controller->$method(1):$controller->$method();throw new \LogicException('anonymous render');}catch(SavedRedirect $e){verify($e->getMessage()==='/login','anonymous Account '.$method);}}
$ddl=file_get_contents(dirname(__DIR__).'/database/saved-symptom-checks.sql');verify(substr_count($ddl,'CREATE TABLE')===1&&!preg_match('/\b(?:ALTER|DROP|INSERT|UPDATE|SET SQL_MODE)\b/i',$ddl),'one additive table only');
verify(str_contains($ddl,'FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)')&&!str_contains($ddl,'health_profile_id'),'user owned schema');verify(str_contains($ddl,'JSON_VALID')&&str_contains($ddl,'uq_saved_check_user_key')&&str_contains($ddl,'idx_saved_check_user_time'),'JSON idempotency/list constraints');
verify(str_contains(file_get_contents(dirname(__DIR__).'/database/structure.sql'),trim($ddl)),'authoritative schema contains exact artifact');
$auth->uid=1;
foreach ([-1,101,INF,NAN,'50'] as $badScore) {
    $bad=$response;$bad['results'][0]['rankingScore']=$badScore;
    verify(($service->capture($bad)['unavailable']??false)===true,'invalid score capture rejected');
}
$duplicate=$zero;$duplicate['summary']['selectedSymptoms']=[1,1];verify(($service->capture($duplicate)['unavailable']??false)===true,'duplicate selected IDs rejected');
$oversize=$zero;$oversize['summary']['selectedSymptoms']=range(1,51);verify(($service->capture($oversize)['unavailable']??false)===true,'bounded selection');
verify($service->page(0)['page']===1,'page clamped');
class SavedSQLSpy extends \App\Repositories\SavedSymptomCheckRepository {
    public array $queries=[];public function __construct(){}
    protected function fetch(string $sql,array $params=[]): ?array {$this->queries[]=[$sql,$params];return null;}
    protected function fetchAll(string $sql,array $params=[]): array {$this->queries[]=[$sql,$params];return [];}
}
$sqlSpy=new SavedSQLSpy();$sqlSpy->findForUser(7,1);$sqlSpy->findKeyForUser(str_repeat('a',64),1);$sqlSpy->pageForUser(1,2);
foreach($sqlSpy->queries as [$sql,$params]) { verify(str_contains($sql,'user_id=:user')&&$params['user']===1,'production reads owner constrained'); }
verify(!str_contains($sqlSpy->queries[2][0],'snapshot_json')&&str_contains($sqlSpy->queries[2][0],'LIMIT 21 OFFSET 20'),'production lightweight bounded list');
$repositorySource=file_get_contents(dirname(__DIR__).'/app/Repositories/SavedSymptomCheckRepository.php');verify(str_contains($repositorySource,'DELETE FROM user_saved_symptom_checks WHERE id=:id AND user_id=:user'),'production deletion predicate');
class SavedSymptomSQLSpy extends \App\Repositories\SymptomRepository {
    public array $query=[];public function __construct(){}
    protected function fetchAll(string $sql,array $params=[]): array {$this->query=[$sql,$params];return [['id'=>2,'symptom_en'=>'Cough']];}
}
$symptomSpy=new SavedSymptomSQLSpy();verify($symptomSpy->findByIds([])===[]&&$symptomSpy->query===[],'empty batch no query');
verify($symptomSpy->findByIds([2,1])[0]['id']===2&&$symptomSpy->query[1]===[2,1]&&str_contains($symptomSpy->query[0],'IN (?,?)'),'functional bound symptom-ID query');
$layout=file_get_contents(dirname(__DIR__).'/app/Views/layouts/app.php');verify(str_contains($layout,'symptom-checker.js')&&str_contains($layout,'filemtime'),'checker asset versioned for save lifecycle');
echo "PASS: $checks saved-symptom checks (database-free).\n";
}
