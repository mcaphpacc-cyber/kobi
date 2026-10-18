<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/vendor/autoload.php';
$db=new App\Core\Database();$pdo=$db->connection();
$target='kobi_public_api_v1_test_20261014';
if ($pdo->query('SELECT DATABASE()')->fetchColumn()!=='medical1') { throw new RuntimeException('Unexpected source database'); }
$mode=$pdo->query('SELECT @@session.sql_mode,@@global.sql_mode')->fetch();
$exists=$pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');$exists->execute([$target]);
if ($exists->fetchColumn()) { throw new RuntimeException('Disposable target exists; refusing reuse'); }
$pdo->exec('CREATE DATABASE `'.$target.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$checks=0;$check=function(bool $ok,string $name)use(&$checks):void { if (!$ok) { throw new LogicException($name); }++$checks; };
try {
    $pdo->exec('USE `'.$target.'`');
    if ($pdo->query('SELECT DATABASE()')->fetchColumn()!==$target) { throw new RuntimeException('Isolation guard failed'); }
    $pdo->exec('CREATE TABLE body_parts(id INT PRIMARY KEY,name_en VARCHAR(255))');
    $pdo->exec('CREATE TABLE diseases(id INT PRIMARY KEY,body_part_id INT,disease_en VARCHAR(255),disease_hi VARCHAR(255),slug VARCHAR(255),gender VARCHAR(10)) COLLATE utf8mb4_general_ci');
    $pdo->exec('CREATE TABLE disease_content(disease_id INT PRIMARY KEY,overview_en TEXT,causes_en TEXT,risk_factors_en TEXT,diagnosis_en TEXT,prevention_en TEXT,urgency_note_en TEXT)');
    $pdo->exec('CREATE TABLE causes(id INT PRIMARY KEY,cause_en VARCHAR(255),cause_hi VARCHAR(255))');
    $pdo->exec('CREATE TABLE disease_causes(disease_id INT,cause_id INT)');
    $pdo->exec('CREATE TABLE kobi_substances(substance_id BIGINT UNSIGNED PRIMARY KEY,canonical_name VARCHAR(255),canonical_name_normalized VARCHAR(255),is_active TINYINT,is_searchable TINYINT) COLLATE utf8mb4_unicode_ci');
    $pdo->exec("INSERT INTO body_parts VALUES(1,'Public body label')");
    $di=$pdo->prepare('INSERT INTO diseases VALUES(?,1,?,?,?,?)');
    $si=$pdo->prepare('INSERT INTO kobi_substances VALUES(?,?,?,?,?)');
    foreach ([[1,'Para'],[2,'Paracetamol'],[3,'A Para'],[4,'Paracetamol']] as [$id,$name]) {
        $di->execute([$id,$name,'नाम','disease-'.$id,'both']);$si->execute([$id,$name,strtolower($name),1,1]);
    }
    foreach(range(10,69) as $id) { $di->execute([$id,'A Para '.$id,'नाम','disease-'.$id,'both']);$si->execute([$id,'A Para '.$id,'a para '.$id,1,1]); }
    $di->execute([90,'Hidden','नाम',null,'both']);$di->execute([91,'Hidden','नाम','','both']);
    $si->execute([90,'Para hidden','para hidden',0,1]);$si->execute([91,'Para hidden','para hidden',1,0]);
    $si->execute(['18446744073709551615','Big Identity','big identity',1,1]);
    $ci=$pdo->prepare('INSERT INTO disease_content VALUES(?,?,?,?,?,?,?)');
    $ci->execute([1,'TEST FIXTURE ONLY: "quotes" & Unicode नाम',null,'','TEST FIXTURE ONLY',null,'TEST FIXTURE ONLY']);
    $pdo->exec("INSERT INTO causes VALUES(1,'Fixture Z',''),(2,'Fixture A','')");
    $pdo->exec('INSERT INTO disease_causes VALUES(1,1),(1,2)');
    $d=new App\Repositories\DiseaseRepository($db);$s=new App\Repositories\SubstanceRepository($db);
    $api=new App\Services\PublicApiService($d,$s,new App\Services\DiseaseReferenceService());
    $r=$api->listDiseases([]);$check(count($r['data'])===20 && $r['meta']['page_size']===20 && $r['meta']['total']===64,'default paging/public count');
    $check($r===$api->listDiseases([]),'deterministic page');
    $r=$api->listDiseases(['page_size'=>'50']);$check(count($r['data'])===50 && $r['meta']['has_more'],'max page');
    $r=$api->listDiseases(['page'=>'2','page_size'=>'50']);$check(count($r['data'])===14 && !$r['meta']['has_more'],'second page');
    $check($api->listDiseases(['page'=>'10000'])['data']===[],'beyond last page');
    foreach (['searchDiseases','searchSubstances'] as $method) {
        $r=$api->$method(['q'=>'  pArA  ']);
        $check(array_column(array_slice($r['data'],0,4),'id')===['1','2','4','3'],'native exact/prefix/contains before limit and ID ties');
        $check(count($r['data'])===10 && $r['meta']['has_more'],'cap/extra row');
        $check($r===$api->$method(['q'=>'pArA']),'stable/case/whitespace');
        foreach(["' OR '1'='1",'1 OR 1=1','UNION SELECT','<script>alert(1)</script>','<svg onload=alert(1)>','P%','P_','P='] as $payload) {
            $check($api->$method(['q'=>$payload])['data']===[],'literal hostile query');
        }
    }
    $r=$api->detail('disease-1',[]);
    $check($r['data']['id']==='1' && $r['data']['content_format']==='plain_text','native detail identity');
    $check($r['data']['sections']['overview']==='TEST FIXTURE ONLY: "quotes" & Unicode नाम' && $r['data']['sections']['causes']===null,'text preserved/null');
    $check($r['data']['causes_items']===['Fixture A','Fixture Z'],'shared ordered cause read');
    $check($r['data']['references']['overview']===[] && count($r['data']['references']['causes'])>0,'public reference service');
    $r=$api->detail('disease-2',[]);$check($r['data']['sections']['overview']===null && $r['data']['causes_items']===[],'missing optional content');
    try {$api->detail('missing',[]);throw new LogicException('Missing detail accepted');} catch(App\Services\PublicApiException $e){$check($e->status===404,'missing404');}
    $r=$api->searchSubstances(['q'=>'Big Identity']);$check($r['data'][0]['id']==='18446744073709551615','native unsigned bigint no precision loss');
    $check(array_keys($r['data'][0])===['id','canonical_name','registry_url','information_type'],'substance allowlist');
    $legacy=$s->search('Para');$check(array_column($legacy,'substance_id')===[1,2,4] && array_keys($legacy[0])===['substance_id','canonical_name'],'legacy prefix/contract intact');
    $check($mode===$pdo->query('SELECT @@session.sql_mode,@@global.sql_mode')->fetch(),'SQL modes unchanged');
} finally {
    if ($pdo->query('SELECT DATABASE()')->fetchColumn()===$target) { $pdo->exec('DROP DATABASE `'.$target.'`'); }
}
$exists->execute([$target]);$check((int)$exists->fetchColumn()===0,'disposable database removed');
echo "Public API V1 MariaDB: $checks checks passed; disposable database removed.\n";
