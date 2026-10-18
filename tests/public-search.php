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
function url(string $path=''): string { return '/kobi/public'.$path; }
function e(?string $text): string { return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
class SearchDiseases extends App\Repositories\DiseaseRepository {
    public array $calls=[];
    public array $rows=[];
    public bool $fail=false;
    public function __construct() {}
    public function searchPublicNames(string $query,string $pattern): array {
        $this->calls[]=[$query,$pattern];
        if ($this->fail) { throw new RuntimeException('SQL secret path'); }
        return $this->rows;
    }
}
class SearchSubstances extends App\Repositories\SubstanceRepository {
    public array $calls=[];
    public array $rows=[];
    public function __construct() {}
    public function searchPublicNames(string $query,string $pattern): array { $this->calls[]=[$query,$pattern]; return $this->rows; }
}
class SearchControllerProbe extends App\Controllers\PublicSearchController {
    public array $data=[];
    protected function view(string $view,array $data=[]): void { $this->data=$data; }
}
$checks=0;
function checkSearch(bool $ok,string $name): void { global $checks; if (!$ok) { throw new LogicException($name); } ++$checks; }
$d=new SearchDiseases();$s=new SearchSubstances();$service=new App\Services\PublicSearchService($d,$s);
foreach (['',' ','a'] as $input) { checkSearch(!$service->search($input)['searched'],'empty/short guidance'); }
checkSearch($d->calls===[] && $s->calls===[],'no queries for short input');
foreach ([[],['q'=>'x'],str_repeat('x',101),str_repeat('x',4097),"\xff"] as $bad) {
    try { $service->search($bad); throw new LogicException('Accepted malformed query'); }
    catch (InvalidArgumentException) { checkSearch(true,'invalid input rejected'); }
}
checkSearch(App\Services\PublicSearchService::normalize(" \tGenital\u{00a0}  Warts\n")==='Genital Warts','Unicode whitespace collapsed');
checkSearch(App\Services\PublicSearchService::normalize(str_repeat('क',100))===str_repeat('क',100),'Unicode length, not bytes');
$r=$service->search("P%_=' OR 1=1 --");
checkSearch($d->calls[0][1]==="P=%=_==' OR 1==1 --",'LIKE escaped without changing query identity');
checkSearch($r['diseases']===[] && $r['substances']===[],'no results');
$d->rows=[['id'=>179,'slug'=>'genital-warts-male','disease_en'=>'Genital Warts','disease_hi'=>'नाम','body_name'=>'Male Reproductive System','gender'=>'male'],['id'=>199,'slug'=>'genital-warts-female','disease_en'=>'Genital Warts','disease_hi'=>'नाम','body_name'=>'Female Reproductive System','gender'=>'female']];
$s->rows=[['substance_id'=>54,'canonical_name'=>'A name & more']];
$r=$service->search('Genital Warts');
checkSearch(array_column($r['diseases'],'id')===[179,199],'duplicate names preserve IDs');
checkSearch($r['diseases'][0]['url']==='/kobi/public/disease/genital-warts-male','real disease destination');
checkSearch($r['diseases'][0]['context']!==$r['diseases'][1]['context'],'duplicate labels differ');
checkSearch(array_keys($r['diseases'][0])===['id','label','context','url'],'English-only unified search projection');
checkSearch($r['substances'][0]['url']==='/kobi/public/medicines?q=A%20name%20%26%20more','encoded existing registry destination');
checkSearch(array_keys($r['substances'][0])===['id','label','url'],'safe substance allowlist');
foreach (range(1,15) as $id) { $d->rows[]=$d->rows[0]+[]; $s->rows[]=['substance_id'=>$id,'canonical_name'=>'Name']; }
$r=$service->search('Name');
checkSearch(count($r['diseases'])===10 && count($r['substances'])===10 && $r['diseases_more'] && $r['substances_more'],'cap and refine marker');
$controller=new SearchControllerProbe($service);
foreach ([false,true] as $authenticated) {
    $_GET=['q'=>'Name']; $_SESSION=$authenticated?['auth_user_id'=>1]:[];
    $controller->index();checkSearch(count($controller->data['result']['substances'])===10,'same public contract with/without auth');
}
$_GET=['q'=>[]];$controller->index();checkSearch(http_response_code()===422 && $controller->data['result']===null,'malformed controller input safe');
$d->fail=true;$_GET=['q'=>'Name'];$controller->index();
checkSearch(http_response_code()===503 && $controller->data['error']==='Search is unavailable right now. Please try again.','backend failure safe');
checkSearch(!str_contains(implode('',App\Core\Logger::$messages),'secret'),'safe diagnostic logging');
$query='<script>alert("x")</script>';$result=null;$error='Invalid input';
ob_start();require dirname(__DIR__).'/app/Views/search/index.php';$html=ob_get_clean();
checkSearch(!str_contains($html,'<script>') && str_contains($html,'&lt;script&gt;'),'retained query escaped');
checkSearch(str_contains($html,'aria-invalid="true"') && str_contains($html,'role="alert"'),'accessible error');
$query='';$error=null;$result=['searched'=>false];
ob_start();require dirname(__DIR__).'/app/Views/search/index.php';$html=ob_get_clean();
checkSearch(str_contains($html,'Enter a disease or substance name'),'initial view');
$query='Name';$result=$r;
ob_start();require dirname(__DIR__).'/app/Views/search/index.php';$html=ob_get_clean();
checkSearch(str_contains($html,'Medicines / Substances') && str_contains($html,'Legacy registry name'),'neutral legacy positioning');
checkSearch(!str_contains($html,'lang="hi"') && !str_contains($html,'नाम'),'Hindi names absent from Unified Search rendering');
checkSearch(!str_contains($html,'governance') && !str_contains($html,'auth_user_id'),'private metadata absent');
checkSearch(str_contains(file_get_contents(dirname(__DIR__).'/routes/web.php'),"get('/search', PublicSearchController::class, 'index')"),'route wired');
echo "Public search: $checks database-free checks passed.\n";
}
