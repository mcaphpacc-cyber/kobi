<?php
declare(strict_types=1);
namespace App\Core {
    final class Logger {
        public static array $messages = [];
        public static function error(string $message): void { self::$messages[] = $message; }
    }
}
namespace {
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
spl_autoload_register(function(string $class): void {
    if (str_starts_with($class, 'App\\')) { require dirname(__DIR__).'/app/'.str_replace('\\','/',substr($class,4)).'.php'; }
});
function config(?string $key=null): mixed { return $key==='base_path'?'/kobi/public':null; }
function url(string $path=''): string { return 'http://localhost/kobi/public/'.ltrim($path,'/'); }
class ApiDiseases extends App\Repositories\DiseaseRepository {
    public array $rows=[];
    public array $calls=[];
    public ?array $detailRow=null;
    public function __construct() {}
    public function countPublicApiDiseases(): int { return 63; }
    public function listPublicApiDiseases(int $limit,int $offset): array { $this->calls[]=[$limit,$offset];return array_slice($this->rows,$offset,$limit); }
    public function searchPublicNames(string $query,string $pattern): array { $this->calls[]=[$query,$pattern];return $this->rows; }
    public function findPublicApiDisease(string $slug): ?array { $this->calls[]=$slug;return $this->detailRow; }
    public function publicApiCauseNames(int $id): array { return ['Cause text']; }
}
class ApiSubstances extends App\Repositories\SubstanceRepository {
    public array $rows=[];
    public array $calls=[];
    public function __construct() {}
    public function searchPublicNames(string $query,string $pattern): array { $this->calls[]=[$query,$pattern];return $this->rows; }
}
$checks=0;
function apiCheck(bool $ok,string $name): void { global $checks; if (!$ok) { throw new LogicException($name); } ++$checks; }
function apiError(callable $action,int $status,string $code): void {
    try { $action();throw new LogicException('Expected API error'); }
    catch (App\Services\PublicApiException $e) { apiCheck($e->status===$status && $e->errorCode===$code,'typed safe validation'); }
}
$d=new ApiDiseases();$s=new ApiSubstances();$refs=new App\Services\DiseaseReferenceService();
$api=new App\Services\PublicApiService($d,$s,$refs);
$row=['id'=>1,'slug'=>'migraine','disease_en'=>'Migraine','disease_hi'=>'माइग्रेन','gender'=>'both','body_name'=>'Head & Brain',
    'overview_en'=>'Text & "quotes"','causes_en'=>'','risk_factors_en'=>null,'diagnosis_en'=>'Diagnosis','prevention_en'=>'Prevention','urgency_note_en'=>'Warning',
    'password'=>'DO NOT EXPOSE','reviewer_id'=>9,'treatments'=>['secret'],'symptoms'=>['private']];
foreach(range(1,63) as $id) {$copy=$row;$copy['id']=$id;$d->rows[]=$copy;}
$s->rows=[['substance_id'=>'18446744073709551615','canonical_name'=>'Name & "quote"','identity_status'=>'verified','evidence'=>'DO NOT EXPOSE']];
$r=$api->listDiseases([]);apiCheck(count($r['data'])===20 && $r['meta']===['page'=>1,'page_size'=>20,'total'=>63,'has_more'=>true],'default pagination');
$r=$api->listDiseases(['page'=>'2','page_size'=>'50']);apiCheck(count($r['data'])===13 && !$r['meta']['has_more'] && $d->calls[1]===[50,50],'custom max pagination');
apiCheck($api->listDiseases(['page'=>'10000'])['data']===[],'out-of-range page empty');
foreach([['page'=>'0'],['page'=>'-1'],['page'=>'1.2'],['page'=>'10001'],['page_size'=>'51'],['page_size'=>'0'],['page_size'=>'9999999999999'],['unknown'=>'x']] as $bad){apiError(fn()=>$api->listDiseases($bad),422,'VALIDATION_ERROR');}
apiError(fn()=>$api->listDiseases(['page'=>[]]),400,'BAD_REQUEST');
foreach([[],['q'=>''],['q'=>'a'],['q'=>str_repeat('x',101)],['q'=>'Name','user_id'=>'1']] as $bad){apiError(fn()=>$api->searchDiseases($bad),422,'VALIDATION_ERROR');}
foreach([['q'=>[]],['q'=>"\xff"]] as $bad){apiError(fn()=>$api->searchSubstances($bad),400,'BAD_REQUEST');}
$r=$api->searchDiseases(['q'=>" \tGenital\u{00a0}  Warts\n"]);
apiCheck($r['meta']===['query'=>'Genital Warts','limit'=>10,'has_more'=>true] && count($r['data'])===10,'normalization and cap');
apiCheck($r['data'][0]['id']==='1' && $r['data'][1]['id']==='2','same names distinct string identities');
apiCheck(array_keys($r['data'][0])===['id','slug','name_en','name_hi','public_url','body_system','gender'],'summary exact allowlist');
apiCheck($r['data'][0]['public_url']==='http://localhost/kobi/public/disease/migraine','canonical public URL');
$r=$api->searchSubstances(['q'=>"P%_=' OR 1=1 --"]);
apiCheck($s->calls[0][1]==="P=%=_==' OR 1==1 --",'shared literal LIKE escaping');
apiCheck($r['data'][0]['id']==='18446744073709551615','bigint preserved as string');
apiCheck(array_keys($r['data'][0])===['id','canonical_name','registry_url','information_type'] && $r['data'][0]['information_type']==='legacy_registry_name','legacy allowlist');
$d->detailRow=$row;$r=$api->detail('migraine',[]);
apiCheck(count($r['data']['sections'])===5 && $r['data']['sections']['causes']===null && $r['data']['sections']['overview']==='Text & "quotes"','plain actual text/nulls');
apiCheck($r['data']['references']['overview']===[] && count($r['data']['references']['causes'])>0,'real public references and omitted overview mapping');
apiCheck(array_keys($r['data']['references']['causes'][0])===['organization','title','url','date','date_type'],'reference allowlist');
apiCheck($r['data']['locale']==='en' && $r['data']['content_format']==='plain_text' && $r['data']['causes_items']===['Cause text'],'detail subset');
apiCheck(!str_contains(json_encode($r),'DO NOT EXPOSE') && !isset($r['data']['treatments'],$r['data']['symptoms'],$r['data']['reviewer_id']),'excluded data');
foreach(['a/b','%00','../migraine',str_repeat('a',256)] as $slug){apiError(fn()=>$api->detail($slug,[]),422,'VALIDATION_ERROR');}
apiError(fn()=>$api->detail('%ff',[]),400,'BAD_REQUEST');
apiError(fn()=>$api->detail('migraine',['include_private'=>'1']),422,'VALIDATION_ERROR');
$d->detailRow=null;apiError(fn()=>$api->detail('missing',[]),404,'NOT_FOUND');
foreach(['/kobi/public/api/v1','/kobi/public/api/v1/diseases?q=a','/api/v1/nope'] as $uri){apiCheck(App\Core\Request::isPublicApiUri($uri),'API path detection');}
foreach(['/kobi/public/api/v10/diseases','/kobi/public/api/substances/search','/other/kobi/public/api/v1'] as $uri){apiCheck(!App\Core\Request::isPublicApiUri($uri),'web boundary intact');}
$_SERVER['REQUEST_METHOD']='GET';$_SESSION=['sentinel'=>'unchanged'];
ob_start();App\Core\PublicApiResponse::success(['text'=>'माइग्रेन <tag> & "quote"']);$json=ob_get_clean();
apiCheck(json_decode($json,true,512,JSON_THROW_ON_ERROR)['data']['text']==='माइग्रेन <tag> & "quote"','JSON not HTML encoded');
apiCheck($_SESSION===['sentinel'=>'unchanged'] && session_status()===PHP_SESSION_NONE,'no session access/start');
foreach([['bad'=>"\xff"],['large'=>str_repeat('x',262145)]] as $bad){ob_start();App\Core\PublicApiResponse::success($bad);$json=ob_get_clean();apiCheck(http_response_code()===500 && json_decode($json,true)['error']['code']==='INTERNAL_ERROR','serialization/size safe');}
$_SERVER['REQUEST_METHOD']='HEAD';ob_start();App\Core\PublicApiResponse::error(405,'METHOD_NOT_ALLOWED','Use GET for this endpoint.');apiCheck(ob_get_clean()==='' && http_response_code()===405,'HEAD suppresses body');
$_SERVER['REQUEST_METHOD']='GET';
$router=new App\Core\Router();$router->get('/api/v1/diseases',App\Controllers\PublicDiseaseApiController::class,'index');
foreach(['POST','PUT','PATCH','DELETE','OPTIONS'] as $method){ob_start();$router->dispatch($method,'/api/v1/diseases',new App\Core\Container());$j=json_decode(ob_get_clean(),true);apiCheck(http_response_code()===405 && $j['error']['code']==='METHOD_NOT_ALLOWED','method rejected before DI');}
ob_start();$router->dispatch('GET','/api/v1/nope',new App\Core\Container());$j=json_decode(ob_get_clean(),true);apiCheck(http_response_code()===404 && $j['error']['code']==='NOT_FOUND','JSON unknown route');
ob_start();$router->dispatch('GET','/ordinary-missing',new App\Core\Container());$html=ob_get_clean();apiCheck(str_contains($html,'<h1>404'),'web404 unchanged');
echo "Public API V1: $checks database-free checks passed.\n";
}
