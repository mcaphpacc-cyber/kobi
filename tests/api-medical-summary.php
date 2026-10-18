<?php
declare(strict_types=1);
// Reuse the canonical browser/Dashboard fixtures and their unchanged assertions.
require __DIR__.'/health-dashboard.php';
$checks=0;
$profiles->rows=[2=>$profile(2),3=>$profile(3,'family')];
$auth->userId=11;$auth->active=true;
$api=new App\Services\ApiMedicalSummaryService($summary);
$actor=new App\Services\ApiAuthPrincipal(11,1);
$expected=['profile'=>['id'=>'2','full_name'=>'<script>private-name</script>','profile_type'=>'self'],
    'counts'=>['conditions'=>2,'historical_conditions'=>1,'current_medications'=>1,'allergies'=>1,'procedures'=>2]];
$check($api->detail($actor,'2')===$expected,'Exact whitelist excludes all synthetic private fields and arrays');
$check($api->detail($actor,'2')===$expected,'Deterministic live projection');
foreach($expected['counts'] as $value)$check(is_int($value),'Integer count');
$check(is_string($api->detail($actor,'2')['profile']['id']),'Decimal-string profile ID');
$zero=$api->detail($actor,'3');
$check($zero['counts']===array_fill_keys(array_keys($expected['counts']),0),'Empty profile returns integer zeros');
$before=[$conditions->reads,$medicines->reads,$allergies->reads,$procedures->reads];
foreach(['0','01','-1','abc','2147483648','999'] as $id){
    try{$api->detail($actor,$id);throw new LogicException('Expected denial');}
    catch(App\Services\ApiAuthException $e){$check($e->status===($id==='999'?404:422),'Existing safe profile errors');}
}
try{$api->detail(new App\Services\ApiAuthPrincipal(99,2),'2');throw new LogicException('Expected denial');}
catch(App\Services\ApiAuthException $e){$check($e->status===404,'Cross-user denial');}
$check([$conditions->reads,$medicines->reads,$allergies->reads,$procedures->reads]===$before,'No domain reads before authorization');
$conditions->rows[2][]=['id'=>4,'status'=>'historical'];
$check($api->detail($actor,'2')['counts']['historical_conditions']===2,'Resolved and historical use canonical historical group');
$conditions->rows[2][]=['id'=>5,'status'=>'chronic'];
$check($api->detail($actor,'2')['counts']['conditions']===3,'Active plus chronic use canonical active group');
$medicines->rows[2][]=['id'=>4,'status'=>'stopped'];
$check($api->detail($actor,'2')['counts']['current_medications']===1,'Stopped and historical medicines excluded');
$browser=$summary->getSummary(2);
$check(count($browser['conditions']['active'])===3&&count($browser['conditions']['historical'])===2,'Browser uses same live grouping');
$check($browser['profile']===$profiles->rows[2]&&$browser['timeline_available']===true,'Browser full summary retains original shape');
$auth->userId=null;
$check($api->detail($actor,'2')['counts']['conditions']===3,'Bearer summary independent of browser login');
try{$summary->getSummary(2);throw new LogicException('Expected browser guard');}
catch(RuntimeException $e){$check($e->getMessage()==='Authentication required.','Existing browser authentication remains required');}
$auth->userId=11;

class SummaryShares extends App\Repositories\MedicalSummaryShareRepository {
    public array $rows=[];public int $logs=0;
    public function __construct(){}
    public function create(array $data):int{$id=count($this->rows)+1;$this->rows[$id]=['id'=>$id]+$data;return $id;}
    public function findById(int $id):?array{return $this->rows[$id]??null;}
    public function findByTokenHash(string $tokenHash):?array{foreach($this->rows as $r)if($r['token_hash']===$tokenHash)return $r;return null;}
    public function findByProfileId(int $profileId):array{return array_values(array_filter($this->rows,fn($r)=>$r['health_profile_id']===$profileId));}
    public function revoke(int $id,int $revokedBy):bool{$this->rows[$id]['status']='revoked';return true;}
    public function logAccess(int $shareId,?string $ipAddress,?string $userAgent):int{return ++$this->logs;}
}
$shareRows=new SummaryShares();
$shares=new App\Services\MedicalSummaryShareService($shareRows,$summary,$profileService,$auth);
$issued=$shares->createShare(2,1);
$snapshot=$shareRows->rows;
$api->detail($actor,'2');
$check($shareRows->rows===$snapshot&&$shareRows->logs===0,'Aggregate does not create/update shares snapshots or access logs');
$public=$shares->getPublicShare($issued['token']);
$check($shareRows->logs===1&&isset($public['snapshot']['profile']),'Existing public sharing still reads snapshot and logs access');
$conditions->rows[2][]=['id'=>6,'status'=>'active'];
$check($shareRows->rows===$snapshot&&$api->detail($actor,'2')['counts']['conditions']===4,'Live changes do not regenerate stored snapshot');
$shares->revokeShare($issued['id']);
try{$shares->getPublicShare($issued['token']);throw new LogicException('Expected revoked share rejection');}
catch(RuntimeException){$check($shareRows->logs===1,'Revoked share denied without another log');}
$check(session_status()===PHP_SESSION_NONE,'No PHP session or database connection');
echo "Medical Summary API unit: $checks checks passed; browser and sharing regressions included.\n";
