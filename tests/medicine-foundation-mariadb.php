<?php
declare(strict_types=1);

// Explicit disposable-instance harness. Never reads application credentials/config.
define('MEDICINE_TEST_LIBRARY', true);
require __DIR__.'/medicine-foundation.php';

use App\Repositories\MedicineRepository;
use App\Services\MedicineIdentityService;
use App\Services\MedicineCanonicalizer;

const MEDICINE_VALIDATION_DB = 'kobi_medicine_phase35_validation_20261009';
const MEDICINE_VALIDATION_MODE = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION';
function medicineIsolatedConnection(): PDO {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3325;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $meta=$pdo->query('SELECT @@version version,@@port port,@@datadir datadir,@@sql_mode sql_mode')->fetch();
    $expected=str_replace('\\','/',realpath(__DIR__.'/.medicine35-validation/data')?:'MISSING');
    if(!str_starts_with($meta['version'],'10.4.27-') || (int)$meta['port']!==3325 || strcasecmp(rtrim(str_replace('\\','/',$meta['datadir']),'/'),$expected)!==0 || $meta['sql_mode']!==MEDICINE_VALIDATION_MODE){throw new RuntimeException('Disposable MariaDB isolation guard failed.');}
    return $pdo;
}
function medicineRealService(PDO $pdo,int $actor=1,?MedicineRepository $repo=null, bool $grant=true):MedicineIdentityService {
    $db=(new ReflectionClass(App\Core\Database::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(App\Core\Database::class,'pdo'))->setValue($db,$pdo);
    $policy=new App\Services\MedicineGovernancePolicy();$governance=new App\Repositories\MedicineGovernanceRepository($db);$eligibility=new App\Services\MedicineEligibilityService($governance,new MedicineCanonicalizer(),$policy);
    return new MedicineIdentityService($repo??new MedicineRepository($db),new MedicineCanonicalizer(),new MedicineTestAuth($actor),new App\Repositories\HealthProfileRepository($db),$grant?new MedicineTestAuthority([1=>true,2=>true]):new App\Services\MedicineAuthorizationService($governance,$policy,$eligibility));
}
function medicineRealDatabase(PDO $pdo): App\Core\Database {
    $db=(new ReflectionClass(App\Core\Database::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(App\Core\Database::class,'pdo'))->setValue($db,$pdo);return $db;
}
class MedicineFaultRepository extends MedicineRepository {
    public function __construct(App\Core\Database $db,private string $stage){parent::__construct($db);}
    private function fail(string $stage):void {if($this->stage===$stage){throw new PDOException('PRIVATE_SQL_PATH_SECRET');}}
    public function insertIdentity(array $row):string {$id=parent::insertIdentity($row);$this->fail('identity');return $id;}
    public function insertIngredient(array $row):string {$id=parent::insertIngredient($row);$this->fail('ingredient');return $id;}
    public function insertPresentation(array $row):string {$id=parent::insertPresentation($row);$this->fail('presentation');return $id;}
    public function insertStrength(array $row):void {parent::insertStrength($row);$this->fail('strength');}
    public function insertSelection(array $row):void {parent::insertSelection($row);$this->fail('selection');}
}
$action=$argv[1]??'';
if(!in_array($action,['run','race-worker','drop','metadata'],true)){fwrite(STDERR,"Use run, race-worker, metadata, or drop on the explicitly guarded disposable instance.\n");exit(2);}
$pdo=medicineIsolatedConnection();
if($action==='drop'){$pdo->exec('DROP DATABASE IF EXISTS '.MEDICINE_VALIDATION_DB);echo "Disposable database dropped; SQL mode unchanged.\n";exit;}
if($action==='metadata'){
    $q=$pdo->prepare("SELECT TABLE_NAME,CONSTRAINT_TYPE,COUNT(*) objects FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=? AND TABLE_NAME IN ('kobi_medicine_chemical_refs','kobi_medicine_identities','kobi_medicine_ingredients','kobi_medicine_presentations','kobi_medicine_strengths','health_medicine_identity_links','health_medicine_selected_ingredients') GROUP BY TABLE_NAME,CONSTRAINT_TYPE ORDER BY TABLE_NAME,CONSTRAINT_TYPE");$q->execute([MEDICINE_VALIDATION_DB]);
    echo json_encode($q->fetchAll(),JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);exit;
}
if($action==='race-worker'){
    $pdo->exec('USE '.MEDICINE_VALIDATION_DB);$actor=(int)($argv[2]??0);
    if(!in_array($actor,[1,2],true)){throw new RuntimeException('Invalid synthetic worker.');}
    $path=__DIR__.'/.medicine35-validation';file_put_contents($path.'/ready'.$actor,'ready');$deadline=microtime(true)+15;
    while(!is_file($path.'/go')){if(microtime(true)>$deadline){throw new RuntimeException('Race barrier timed out.');}usleep(10000);}
    $s=medicineRealService($pdo,$actor);
    $r=$s->createMedicine(['ingredients'=>[['form_id'=>'30'],['substance_id'=>'10']],'presentation'=>medicinePresentation(['52','53'])]);
    echo json_encode($r,JSON_THROW_ON_ERROR);exit;
}
if($pdo->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".MEDICINE_VALIDATION_DB."'")->fetchColumn()!=0){throw new RuntimeException('Disposable database already exists; refusing overwrite.');}
$pdo->exec('CREATE DATABASE '.MEDICINE_VALIDATION_DB.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$pdo->exec('USE '.MEDICINE_VALIDATION_DB);
$source=file_get_contents(dirname(__DIR__).'/database/structure.sql');$sourceHash=hash('sha256',$source);
$omitted=["/*!40101 SET SQL_MODE=''*/;","/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;","/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;"];
$delimiter=';';$buffer='';$executed=0;$skipped=[];
foreach(preg_split('/\r?\n/',$source) as $line){
    if(in_array(trim($line),$omitted,true)){$skipped[]=trim($line);continue;}
    if(preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i',$line,$m)){$delimiter=$m[1];continue;}
    $buffer.=$line."\n";$trim=rtrim($buffer);
    if(str_ends_with($trim,$delimiter)){
        $sql=substr($trim,0,-strlen($delimiter));
        // Any non-mode statement error is fatal: no schema repair or further tests.
        $pdo->exec($sql);$executed++;$buffer='';
    }
}
if(trim($buffer)!=='' || count($skipped)!==3){throw new RuntimeException('Schema import accounting failed.');}
$tables=['kobi_medicine_chemical_refs','kobi_medicine_identities','kobi_medicine_ingredients','kobi_medicine_presentations','kobi_medicine_strengths','health_medicine_identity_links','health_medicine_selected_ingredients'];
medicineTest('import and seven-table metadata',function()use($pdo,$tables,$source){foreach($tables as $t){$actual=$pdo->query("SHOW CREATE TABLE `$t`")->fetch()['Create Table'];preg_match('/CREATE TABLE `'.preg_quote($t,'/').'` \((.*?)\) ENGINE=/s',$source,$m);preg_match_all('/CONSTRAINT `([^`]+)`/',$m[1],$expected);preg_match_all('/CONSTRAINT `([^`]+)`/',$actual,$present);sort($expected[1]);sort($present[1]);medicineCheck($expected[1]===$present[1],'Foundation FK/CHECK metadata differs');preg_match_all('/(?:UNIQUE )?KEY `([^`]+)`/',$m[1],$expectedIndexes);preg_match_all('/(?:UNIQUE )?KEY `([^`]+)`/',$actual,$actualIndexes);sort($expectedIndexes[1]);sort($actualIndexes[1]);medicineCheck($expectedIndexes[1]===$actualIndexes[1],'Foundation index metadata differs');medicineCheck(str_contains($actual,'PRIMARY KEY'));}medicineCheck($pdo->query('SELECT @@sql_mode')->fetchColumn()===MEDICINE_VALIDATION_MODE);medicineCheck((int)$pdo->query('SELECT @@foreign_key_checks')->fetchColumn()===1);});
foreach([1,2,3,4] as $id){$q=$pdo->prepare('INSERT INTO users(id,name,email,password_hash,status) VALUES(?,?,?,?,?)');$q->execute([$id,'Synthetic actor '.$id,'synthetic'.$id.'@example.invalid','NOT_A_LOGIN_CREDENTIAL',$id===3?'inactive':'active']);}
$pdo->exec("INSERT INTO health_profiles(id,full_name,created_by) VALUES(11,'Synthetic profile',1),(12,'Synthetic other profile',2)");
$pdo->exec("INSERT INTO health_profile_members(health_profile_id,user_id,role) VALUES(11,1,'owner'),(11,2,'editor'),(11,4,'viewer'),(12,2,'owner')");
$pdo->exec("INSERT INTO health_medicines(id,health_profile_id,brand_name,generic_name,strength,dose,notes) VALUES(701,11,'Original raw wording','Ambiguous text','Original strength','Original dose','Private observation'),(702,12,'Other profile raw',NULL,NULL,NULL,NULL)");
foreach([2,3,10,20,21] as $id){$q=$pdo->prepare('INSERT INTO kobi_substances(substance_id,canonical_name,canonical_name_normalized) VALUES(?,?,?)');$q->execute([$id,'Synthetic '.$id,'synthetic '.$id]);}
$pdo->exec("INSERT INTO kobi_substance_forms(form_id,substance_id,form_name,form_name_normalized) VALUES(30,3,'Synthetic form','synthetic form')");
foreach([[51,2,null],[52,10,null],[53,null,30]] as $r){$q=$pdo->prepare("INSERT INTO kobi_medicine_chemical_refs(chemical_ref_id,substance_id,form_id,identity_status,created_by,created_at,updated_at,verified_by,verified_at,verification_reference) VALUES(?,?,?,'verified',1,NOW(6),NOW(6),2,NOW(6),'Synthetic test provenance')");$q->execute($r);}
$s=medicineRealService($pdo);$combo=null;$single=null;
medicineTest('single ingredient atomic creation',function()use($s,&$single){$single=$s->createMedicine(['ingredients'=>[['substance_id'=>'2']]]);medicineCheck($single['created']===true);});
medicineTest('combination atomic identity presentation and strengths',function()use($s,&$combo,$pdo){$combo=$s->createMedicine(['ingredients'=>[['substance_id'=>'10'],['substance_id'=>'2']],'presentation'=>medicinePresentation()]);medicineCheck($combo['created'] && $combo['presentation']['created']);medicineCheck((int)$pdo->query('SELECT COUNT(*) FROM kobi_medicine_strengths')->fetchColumn()===2);});
medicineTest('reordered duplicate identity and exact presentation reuse',function()use($s,$combo){$r=$s->createMedicine(['ingredients'=>[['substance_id'=>'2'],['substance_id'=>'10']],'presentation'=>medicinePresentation(['52','51'])]);medicineCheck(!$r['created'] && !$r['presentation']['created']);medicineCheck($r['medicine_identity_id']===$combo['medicine_identity_id']);});
medicineTest('multiple presentation variants one identity',function()use($s,$combo){$r=$s->createPresentation($combo['medicine_identity_id'],medicinePresentation(['51','52'],'2'));medicineCheck($r['created']);});
medicineTest('candidate adapter registration without substance mutation',function()use($s,$pdo){$before=$pdo->query('SELECT COUNT(*) FROM kobi_substances')->fetchColumn();$r=$s->registerChemicalReference(['substance_id'=>'20']);medicineCheck($r['status']==='candidate');medicineCheck($before===$pdo->query('SELECT COUNT(*) FROM kobi_substances')->fetchColumn());});
medicineTest('production shared authority denies profile owner',fn()=>medicineReject(fn()=>medicineRealService($pdo,1,null,false)->createMedicine(['ingredients'=>[['substance_id'=>'2']]]),403));
medicineTest('unverified adapter rejected',fn()=>medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['substance_id'=>'20']]]),409));
medicineTest('duplicate ingredient rejected before writes',fn()=>medicineReject(fn()=>$s->createMedicine(['ingredients'=>[['substance_id'=>'2'],['substance_id'=>'02']]])));
foreach(['1.5',str_repeat('9',39)] as $value){medicineTest('non-strict coefficient protection '.$value,fn()=>medicineReject(fn()=>$s->createPresentation($combo['medicine_identity_id'],medicinePresentation(['51','52'],$value))));}
foreach(['identity','ingredient','presentation','strength'] as $stage){medicineTest('real post-write rollback '.$stage,function()use($pdo,$stage){$before=[];foreach(['identities','ingredients','presentations','strengths'] as $t){$before[$t]=$pdo->query('SELECT COUNT(*) FROM kobi_medicine_'.$t)->fetchColumn();}$fault=new MedicineFaultRepository(medicineRealDatabase($pdo),$stage);$service=medicineRealService($pdo,1,$fault);medicineReject(fn()=>$service->createMedicine(['ingredients'=>[['substance_id'=>'2'],['form_id'=>'30']],'presentation'=>medicinePresentation(['51','53'])]),503);foreach($before as $t=>$n){medicineCheck($n===$pdo->query('SELECT COUNT(*) FROM kobi_medicine_'.$t)->fetchColumn());}});}
$raw=$pdo->query('SELECT * FROM health_medicines WHERE id=701')->fetch();
medicineTest('private selected IDs persisted and scoped',function()use($s,$pdo){$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'ingredients','ingredients'=>[['substance_id'=>'10'],['substance_id'=>'2']]]));medicineCheck(array_map('strval',$pdo->query('SELECT chemical_ref_id FROM health_medicine_selected_ingredients WHERE health_medicine_id=701 ORDER BY display_order')->fetchAll(PDO::FETCH_COLUMN))===['51','52']);});
medicineTest('private stale revision rejected',fn()=>medicineReject(fn()=>$s->setMedicationAssociation(11,701,['association_mode'=>'text','expected_link_revision'=>0,'expected_record_digest'=>str_repeat('0',64)]),409));
medicineTest('private cross-profile denied',fn()=>medicineReject(fn()=>$s->medicationAssociation(11,702),403));
medicineTest('viewer cannot mutate private sidecar',fn()=>medicineReject(fn()=>medicineRealService($pdo,4)->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'text'])),403));
medicineTest('inactive actor rejected',fn()=>medicineReject(fn()=>medicineRealService($pdo,3)->medicationAssociation(11,701),403));
medicineTest('candidate composition cannot be selected',fn()=>medicineReject(fn()=>$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'composition','medicine_identity_id'=>$combo['medicine_identity_id']])),409));
// Fixture-only verification, not a service verification workflow.
foreach(['identities','presentations'] as $t){$pdo->exec("UPDATE kobi_medicine_$t SET identity_status='verified',verified_by=2,verified_at=NOW(6),verification_reference='Synthetic fixture verification'");}
medicineTest('verified composition replaces private selections',function()use($s,$combo,$pdo){$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'composition','medicine_identity_id'=>$combo['medicine_identity_id']]));medicineCheck((int)$pdo->query('SELECT COUNT(*) FROM health_medicine_selected_ingredients WHERE health_medicine_id=701')->fetchColumn()===0);});
medicineTest('verified presentation association and editor permission',function()use($pdo,$combo){$editor=medicineRealService($pdo,2);$r=$editor->setMedicationAssociation(11,701,medicineAssociation($editor,['association_mode'=>'presentation','medicine_presentation_id'=>$combo['presentation']['medicine_presentation_id']]));medicineCheck($r['association_mode']==='presentation');});
medicineTest('private selection post-write rollback preserves previous link',function()use($s,$pdo){$before=$s->medicationAssociation(11,701);$fault=new MedicineFaultRepository(medicineRealDatabase($pdo),'selection');medicineReject(fn()=>medicineRealService($pdo,1,$fault)->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'ingredients','ingredients'=>[['substance_id'=>'2']]])),503);medicineCheck($before===$s->medicationAssociation(11,701));});
medicineTest('text fallback deletes sidecar and preserves every original field',function()use($s,$pdo,$raw){$s->setMedicationAssociation(11,701,medicineAssociation($s,['association_mode'=>'text']));medicineCheck($raw===$pdo->query('SELECT * FROM health_medicines WHERE id=701')->fetch());medicineCheck((int)$pdo->query('SELECT COUNT(*) FROM health_medicine_identity_links')->fetchColumn()===0);});
foreach([1062,1213] as $native){medicineTest('bounded fresh transaction retry '.$native,function()use($pdo,$native){$repo=new MedicineRepository(medicineRealDatabase($pdo));$attempt=0;$repo->transaction(function()use($pdo,$native,&$attempt){$attempt++;$pdo->exec("INSERT INTO kobi_medicine_chemical_refs(substance_id,created_by,created_at,updated_at) VALUES(21,1,NOW(6),NOW(6))");if($attempt===1){$e=new PDOException('PRIVATE_RETRY');$e->errorInfo=['40001',$native,'PRIVATE'];throw $e;}});medicineCheck($attempt===2);medicineCheck((int)$pdo->query('SELECT COUNT(*) FROM kobi_medicine_chemical_refs WHERE substance_id=21')->fetchColumn()===1);$pdo->exec('DELETE FROM kobi_medicine_chemical_refs WHERE substance_id=21');});}
medicineTest('two-process concurrent identity and presentation creation',function()use($pdo){
    $path=__DIR__.'/.medicine35-validation';$workers=[];
    foreach(['ready1','ready2','go'] as $marker){if(is_file($path.'/'.$marker)){unlink($path.'/'.$marker);}}
    foreach([1,2] as $actor){$workers[$actor]=proc_open([PHP_BINARY,__FILE__,'race-worker',(string)$actor],[0=>['pipe','r'],1=>['file',$path.'/worker'.$actor.'.json','w'],2=>['file',$path.'/worker'.$actor.'.err','w']],$pipes);if(!is_resource($workers[$actor])){throw new RuntimeException('Cannot start race worker.');}fclose($pipes[0]);}
    $deadline=microtime(true)+15;while(!is_file($path.'/ready1')||!is_file($path.'/ready2')){if(microtime(true)>$deadline){throw new RuntimeException('Race readiness timed out.');}usleep(10000);}file_put_contents($path.'/go','go');
    foreach($workers as $worker){medicineCheck(proc_close($worker)===0,'Race worker failed');}
    $a=json_decode(file_get_contents($path.'/worker1.json'),true,512,JSON_THROW_ON_ERROR);$b=json_decode(file_get_contents($path.'/worker2.json'),true,512,JSON_THROW_ON_ERROR);
    medicineCheck($a['medicine_identity_id']===$b['medicine_identity_id']);medicineCheck($a['presentation']['medicine_presentation_id']===$b['presentation']['medicine_presentation_id']);medicineCheck((int)$a['created']+(int)$b['created']===1);medicineCheck((int)$a['presentation']['created']+(int)$b['presentation']['created']===1);
});
medicineTest('SQL mode unchanged and canonical aggregates complete',function()use($pdo){medicineCheck($pdo->query('SELECT @@sql_mode')->fetchColumn()===MEDICINE_VALIDATION_MODE);medicineCheck((int)$pdo->query('SELECT COUNT(*) FROM kobi_medicine_identities m WHERE NOT EXISTS(SELECT 1 FROM kobi_medicine_ingredients i WHERE i.medicine_identity_id=m.medicine_identity_id)')->fetchColumn()===0);medicineCheck((int)$pdo->query('SELECT COUNT(*) FROM kobi_medicine_presentations p WHERE (SELECT COUNT(*) FROM kobi_medicine_strengths s WHERE s.medicine_presentation_id=p.medicine_presentation_id)<>(SELECT COUNT(*) FROM kobi_medicine_ingredients i WHERE i.medicine_identity_id=p.medicine_identity_id)')->fetchColumn()===0);});
$failures=array_values(array_filter($medicineCases,fn($r)=>$r['status']==='FAIL'));
echo json_encode(['schema_sha256'=>$sourceHash,'schema_statements_executed'=>$executed,'sql_mode_directives_omitted'=>$skipped,'server_version'=>$pdo->query('SELECT @@version')->fetchColumn(),'sql_mode'=>$pdo->query('SELECT @@sql_mode')->fetchColumn(),'cases'=>count($medicineCases),'assertions'=>$medicineAssertions,'failures'=>$failures],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
exit($failures?1:0);
