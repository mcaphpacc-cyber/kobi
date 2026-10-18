<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/vendor/autoload.php';
$db=new App\Core\Database();$pdo=$db->connection();
$target='kobi_public_search_test_20261014';
if ($pdo->query('SELECT DATABASE()')->fetchColumn()!=='medical1') { throw new RuntimeException('Unexpected source database'); }
$mode=$pdo->query('SELECT @@session.sql_mode,@@global.sql_mode')->fetch();
$q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');$q->execute([$target]);
if ($q->fetchColumn()) { throw new RuntimeException('Disposable target already exists; refusing reuse'); }
$pdo->exec('CREATE DATABASE `'.$target.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$checks=0;$check=function(bool $ok,string $name)use(&$checks):void { if (!$ok) { throw new LogicException($name); } ++$checks; };
try {
    $pdo->exec('USE `'.$target.'`');
    if ($pdo->query('SELECT DATABASE()')->fetchColumn()!==$target) { throw new RuntimeException('Isolation guard failed'); }
    // Only disposable synthetic name tables, never production structure.sql or user tables.
    $pdo->exec('CREATE TABLE body_parts(id INT PRIMARY KEY,name_en VARCHAR(255))');
    $pdo->exec('CREATE TABLE diseases(id INT PRIMARY KEY,body_part_id INT,disease_en VARCHAR(255),disease_hi VARCHAR(255),slug VARCHAR(255),gender VARCHAR(10)) COLLATE utf8mb4_general_ci');
    $pdo->exec('CREATE TABLE kobi_substances(substance_id BIGINT UNSIGNED PRIMARY KEY,canonical_name VARCHAR(255),canonical_name_normalized VARCHAR(255),is_active TINYINT,is_searchable TINYINT) COLLATE utf8mb4_unicode_ci');
    $pdo->exec("INSERT INTO body_parts VALUES(1,'Body')");
    $di=$pdo->prepare('INSERT INTO diseases VALUES(?,1,?,?,?,?)');
    $si=$pdo->prepare('INSERT INTO kobi_substances VALUES(?,?,?,?,?)');
    foreach ([[1,'Para'],[2,'Paracetamol'],[3,'A Para'],[4,'Paracetamol']] as [$id,$name]) {
        $di->execute([$id,$name,'नाम','disease-'.$id,'both']);$si->execute([$id,$name,strtolower($name),1,1]);
    }
    $di->execute([5,'Para','नाम',null,'both']);
    $si->execute([5,'Para hidden','para hidden',0,1]);$si->execute([6,'Para hidden','para hidden',1,0]);
    foreach (range(10,23) as $id) { $di->execute([$id,'A Para '.$id,'नाम','disease-'.$id,'both']);$si->execute([$id,'A Para '.$id,'a para '.$id,1,1]); }
    $di->execute([30,'P% name','प्रतिशत','literal','both']);$si->execute([30,'P% name','p% name',1,1]);
    $d=new App\Repositories\DiseaseRepository($db);$s=new App\Repositories\SubstanceRepository($db);
    foreach ([$d,$s] as $repo) {
        $id=$repo===$d?'id':'substance_id';
        $rows=$repo->searchPublicNames('pArA','pArA');
        $check(array_column(array_slice($rows,0,4),$id)===[1,2,4,3],'exact before prefix before contains; numeric duplicate tie');
        $check(count($rows)===11,'ranked extra-row bound');
        $check(!in_array(5,array_column($rows,$id),true) && !in_array(6,array_column($rows,$id),true),'hidden or missing destination excluded');
        $check(array_column($repo->searchPublicNames('P%','P=%'),$id)===[30],'literal wildcard');
        $check($repo->searchPublicNames("' OR 1=1 --","' OR 1==1 --")===[],'bound injection text');
        $check($repo->searchPublicNames('<script>','<script>')===[],'HTML treated as name');
    }
    $check(count($d->searchPublicNames('नाम','नाम'))===11,'Hindi canonical name searched');
    $legacy=$s->search('Para');
    $check(array_column($legacy,'substance_id')===[1,2,4],'legacy prefix order unchanged');
    $check(array_keys($legacy[0])===['substance_id','canonical_name'],'legacy API row contract unchanged');
    $check($s->search('A',100)!==[] && count($s->search('A',100))===10,'legacy maximum10 unchanged');
    $check($mode===$pdo->query('SELECT @@session.sql_mode,@@global.sql_mode')->fetch(),'SQL modes unchanged');
} finally {
    if ($pdo->query('SELECT DATABASE()')->fetchColumn()===$target) { $pdo->exec('DROP DATABASE `'.$target.'`'); }
}
$q->execute([$target]);$check((int)$q->fetchColumn()===0,'disposable database removed');
echo "Public search MariaDB: $checks checks passed; disposable database removed.\n";
