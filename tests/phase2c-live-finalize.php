<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php'; env('DB_HOST');
$name='kobi_phase2c_validation_20261008';
$pdo=new PDO('mysql:host='.env('DB_HOST','localhost').';port='.env('DB_PORT','3306').';dbname='.$name.';charset=utf8mb4',env('DB_USERNAME'),env('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
if ($pdo->query('SELECT DATABASE()')->fetchColumn()!==$name) { throw new RuntimeException('Wrong database'); }
$file=dirname(__DIR__).'/docs/TREATMENT_PHASE_2C_FINAL_DATABASE_RESULTS.json';
if (($argv[1]??null)==='cleanup') {
    $report=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
    if ($report['status']!=='PASS' || $report['database']!==$name) { throw new RuntimeException('Final validation not passed; refusing cleanup.'); }
    $pdo->exec('DROP DATABASE `kobi_phase2c_validation_20261008`');
    $q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?'); $q->execute([$name]);
    $report['cleanup']=['sql'=>'DROP DATABASE `kobi_phase2c_validation_20261008`','removed'=>$q->fetchColumn()==0];
    $report['mode_after_cleanup']=$pdo->query('SELECT @@sql_mode AS session_mode,@@global.sql_mode AS global_mode')->fetch();
    file_put_contents($file,json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n"); echo 'Isolated database removed; SQL modes unchanged.'.PHP_EOL; exit;
}
$report=['database'=>$name,'status'=>'STARTED','checks'=>[]];
function finalCheck(bool $ok,string $name,array $detail=[]): void {
    global $report,$file;
    $report['checks'][]=['test'=>$name,'status'=>$ok ? 'PASS' : 'FAIL','detail'=>$detail];
    if (!$ok) { $report['status']='FAIL'; file_put_contents($file,json_encode($report,JSON_PRETTY_PRINT)."\n"); throw new RuntimeException('STOP: '.$name); }
}
$q=$pdo->query("SELECT p.id,p.content_revision,p.review_state,p.publication_status,p.published_locales,p.approved_review_id,e.protocol_id AS event_protocol,e.content_revision AS event_revision,e.event_type,e.actor_id,e.notes,e.locales,e.review_due_at,e.independence_confirmed,e.content_snapshot,e.content_hash FROM treatment_protocols p JOIN treatment_protocol_events e ON e.id=p.approved_review_id WHERE p.id IN(901,902) ORDER BY p.id"); $rows=$q->fetchAll();
finalCheck(count($rows)===2,'browser approval rows persisted');
foreach ($rows as $row) {
    $id=(int)$row['id']; $rev=$id===901 ? 4 : 5;
    finalCheck($row['review_state']==='approved' && $row['publication_status']==='unpublished' && $row['published_locales']==='' && (int)$row['content_revision']===$rev && (int)$row['event_protocol']===$id && (int)$row['event_revision']===$rev && $row['event_type']==='approved' && (int)$row['actor_id']===14 && (int)$row['independence_confirmed']===1 && $row['review_due_at']==='2027-10-08 12:00:00.000000' && $row['content_hash']===hash('sha256',$row['content_snapshot']),'browser approval state and event '.$id,['revision'=>$rev,'event'=>$row['approved_review_id'],'actor'=>$row['actor_id'],'due'=>$row['review_due_at'],'locales'=>$row['locales'],'rationale'=>$row['notes']]);
}
finalCheck((int)$pdo->query('SELECT COUNT(*) FROM treatment_sources WHERE id=100')->fetchColumn()===0,'self insert trigger left no row');
$pdo->exec("UPDATE treatment_protocols SET publication_status='published',published_locales='en' WHERE id=2300");
// A synthetic historic publication fixture only; no publish route/action added or used.
$db=new class($pdo) extends App\Core\Database { public function __construct(private PDO $pdo) {} public function connection(): PDO { return $this->pdo; } };
$actor=new class extends App\Services\AuthService { public function __construct() {} public function id(): ?int { return 3; } };
$repo=new App\Repositories\TreatmentGovernanceRepository($db); $sources=new App\Repositories\TreatmentSourceRepository($db); $permissions=new App\Repositories\TreatmentEditorialPermissionRepository($db); $rules=new App\Services\TreatmentGovernanceRules(); $policy=new App\Services\TreatmentGovernancePolicy(); $auth=new App\Services\TreatmentEditorialAuthorizationService($actor,$permissions,$rules,$policy);
$service=new App\Services\TreatmentGovernanceService($repo,$sources,$permissions,$auth,$rules,new App\Services\TreatmentGovernanceSnapshot($rules),$policy);
$service->withdrawSource(1,900,'Synthetic historic publication source withdrawal');
$p=$repo->find(2300);
finalCheck($p['publication_status']==='withdrawn' && $p['review_state']==='draft' && $p['approved_review_id']===null && $p['published_locales']==='' && (int)$p['content_revision']===2,'published fixture withdrawn by source invalidation',['fixture_sql'=>"UPDATE treatment_protocols SET publication_status='published',published_locales='en' WHERE id=2300",'after'=>array_intersect_key($p,array_flip(['id','publication_status','review_state','approved_review_id','published_locales','content_revision']))]);
finalCheck($repo->find(901)['approved_review_id']===null && $repo->find(902)['approved_review_id']===null,'browser approved fixtures also invalidated on source withdrawal');
$report['mode_after']=$pdo->query('SELECT @@sql_mode AS session_mode,@@global.sql_mode AS global_mode')->fetch();
finalCheck(count(array_unique(array_values($report['mode_after'])))===1 && $report['mode_after']['session_mode']==='NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION','SQL mode preserved');
finalCheck(hash_file('sha256',dirname(__DIR__).'/database/structure.sql')==='ec84a58e3b5a107604108cf25e9d9663b8f1a06fc3e3a5989fff556f964d7cff','authoritative schema unchanged');
$report['status']='PASS'; file_put_contents($file,json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n"); echo count($report['checks']).' final persistence/source invalidation/safety checks passed.'.PHP_EOL;
