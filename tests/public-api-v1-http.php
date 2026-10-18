<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Read-only HTTP requests only; no browser session, credentials or matching submissions.
$base='http://localhost/kobi/public';$checks=0;
function httpCheck(bool $ok,string $name): void {global $checks;if(!$ok){throw new LogicException($name);}++$checks;}
function requestApi(string $path,string $method='GET',array $headers=[]): array {
    global $base;
    $context=stream_context_create(['http'=>['method'=>$method,'ignore_errors'=>true,'timeout'=>10,'header'=>implode("\r\n",$headers)]]);
    $start=microtime(true);$body=file_get_contents($base.$path,false,$context);$raw=$http_response_header??[];
    if($body===false || !preg_match('/\s(\d{3})\s/',$raw[0]??'',$m)){throw new RuntimeException('HTTP read failed');}
    $map=[];foreach(array_slice($raw,1) as $line){$parts=explode(':',$line,2);if(count($parts)===2){$map[strtolower(trim($parts[0]))]=trim($parts[1]);}}
    return ['status'=>(int)$m[1],'headers'=>$map,'body'=>$body,'ms'=>round((microtime(true)-$start)*1000,2)];
}
function apiHttp(string $path,int $status=200,string $method='GET',array $headers=[]): array {
    $r=requestApi($path,$method,$headers);httpCheck($r['status']===$status,'HTTP status '.$path);
    httpCheck(str_contains($r['headers']['content-type']??'','application/json'),'JSON type');
    httpCheck(($r['headers']['cache-control']??'')==='no-store','no-store');
    httpCheck(!isset($r['headers']['set-cookie'],$r['headers']['access-control-allow-origin'],$r['headers']['access-control-allow-credentials']),'no cookie/CORS');
    // Check each independently, since isset(a,b,c) alone would require all three.
    foreach(['set-cookie','access-control-allow-origin','access-control-allow-credentials'] as $header){httpCheck(!isset($r['headers'][$header]),'absent '.$header);}
    if($method==='HEAD'){httpCheck($r['body']==='','HEAD empty');return $r;}
    $j=json_decode($r['body'],true,512,JSON_THROW_ON_ERROR);httpCheck(($j['meta']['api_version']??null)==='v1' && $j['success']===($status===200),'envelope');
    if($status!==200){httpCheck(!str_contains($r['body'],'Stack trace') && !str_contains($r['body'],'SQLSTATE') && !str_contains($r['body'],'<pre>'),'safe errors');}
    return $r+['json'=>$j];
}
$r=apiHttp('/api/v1/diseases');httpCheck(count($r['json']['data'])===20 && $r['json']['meta']['page_size']===20,'default20');
$r=apiHttp('/api/v1/diseases?page_size=50');httpCheck(count($r['json']['data'])===50,'max50');
$r=apiHttp('/api/v1/diseases?page=2&page_size=7');httpCheck(count($r['json']['data'])===7 && $r['json']['meta']['page']===2,'custom page');
$r=apiHttp('/api/v1/diseases?page=10000');httpCheck($r['json']['data']===[] && !$r['json']['meta']['has_more'],'empty later page');
foreach(['page=0','page=-1','page=abc','page_size=0','page_size=51','page=10001','unknown=x'] as $query){apiHttp('/api/v1/diseases?'.$query,422);}
apiHttp('/api/v1/diseases?page%5B%5D=1',400);
foreach(['/api/v1/diseases/search','/api/v1/substances/search'] as $path){
    apiHttp($path,422);apiHttp($path.'?q=a',422);apiHttp($path.'?q='.str_repeat('x',101),422);apiHttp($path.'?q%5B%5D=x',400);apiHttp($path.'?q=%FF',400);
    foreach(["'",'"',"' OR '1'='1",'1 OR 1=1','UNION SELECT','<script>alert(1)</script>','<svg onload=alert(1)>','P%','P_','P='] as $value){apiHttp($path.'?q='.rawurlencode($value),mb_strlen($value)<2?422:200);}
}
$r=apiHttp('/api/v1/diseases/search?q=%20mIgRaInE%20');httpCheck($r['json']['data'][0]['id']==='1' && $r['json']['meta']['query']==='mIgRaInE','normalized exact');
$r=apiHttp('/api/v1/diseases/search?q=Genital%20Warts');httpCheck(array_column($r['json']['data'],'id')===['179','199'],'duplicate names remain');
$r=apiHttp('/api/v1/diseases/search?q=Para');httpCheck($r['json']['data'][0]['name_en']==='Paranoid Personality Disorder','prefix before contains');
$r=apiHttp('/api/v1/substances/search?q=Para');httpCheck(count($r['json']['data'])===10 && $r['json']['meta']['has_more'],'substance cap');
$r=apiHttp('/api/v1/substances/search?q=Paracetamol');httpCheck($r['json']['data'][0]['id']==='1552' && $r['json']['data'][0]['information_type']==='legacy_registry_name','exact legacy substance');
$r=apiHttp('/api/v1/diseases/migraine');httpCheck(count($r['json']['data']['sections'])===5 && $r['json']['data']['references']['overview']===[] && count($r['json']['data']['references']['causes'])>0,'detail reference boundary');
httpCheck(!isset($r['json']['data']['treatments']) && !isset($r['json']['data']['symptoms']) && !isset($r['json']['data']['updated_at']),'detail projection');
apiHttp('/api/v1/diseases/not-a-real-public-disease',404);apiHttp('/api/v1/diseases/migraine?user_id=1',422);
// Apache rejects encoded NUL/slash before PHP. PHP-level decoded slug checks remain in the unit suite.
foreach(['/api/v1/diseases/%00','/api/v1/diseases/%2F'] as $path){$r=requestApi($path);httpCheck($r['status']===404,'server rejects encoded path before application');}
foreach(['/api/v1','/api/v1/private','/api/v1/substances/1','/api/v1/treatments','/api/v1/symptoms'] as $path){apiHttp($path,404);}
foreach(['POST','PUT','PATCH','DELETE','OPTIONS','HEAD'] as $method){$r=apiHttp('/api/v1/diseases',405,$method);httpCheck(($r['headers']['allow']??'')==='GET','Allow GET');}
$a=apiHttp('/api/v1/diseases/search?q=Migraine');$b=apiHttp('/api/v1/diseases/search?q=Migraine',200,'GET',['Cookie: PHPSESSID=irrelevant-public-api-test','Authorization: Bearer ignored-test-string','Origin: https://example.invalid','Accept: text/html']);httpCheck($a['body']===$b['body'],'cookies/auth/origin/Accept do not change public JSON');
$legacy=requestApi('/api/substances/search?q=Para');$j=json_decode($legacy['body'],true,512,JSON_THROW_ON_ERROR);httpCheck(array_keys($j[0])===['substance_id','canonical_name'] && count($j)<=10 && $j[0]['canonical_name']==='Para Aminobenzoic Acid','old AJAX contract');
$web=requestApi('/search?q=Para');httpCheck($web['status']===200 && str_contains($web['body'],'Paracetamol') && str_contains($web['body'],'Medicines / Substances'),'web search unchanged');
$detail=requestApi('/disease/migraine');httpCheck($detail['status']===200 && str_contains($detail['body'],'Diagnosis') && str_contains($detail['body'],'Treatment &amp; Management'),'web detail preserved');
echo "Public API V1 HTTP: $checks checks passed; read-only requests only.\n";
