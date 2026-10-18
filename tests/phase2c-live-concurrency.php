<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
env('DB_HOST'); $_ENV['DB_DATABASE']='kobi_phase2c_validation_20261008'; $_ENV['TREATMENT_PUBLICATION_MODE']='hold';
$pdo=new PDO('mysql:host='.env('DB_HOST','localhost').';port='.env('DB_PORT','3306').';dbname='.$_ENV['DB_DATABASE'].';charset=utf8mb4',env('DB_USERNAME'),env('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
if ($pdo->query('SELECT DATABASE()')->fetchColumn()!==$_ENV['DB_DATABASE']) { throw new RuntimeException('Wrong database'); }
$db=new class($pdo) extends App\Core\Database { public function __construct(private PDO $pdo) {} public function connection(): PDO { return $this->pdo; } };
$actor=new class extends App\Services\AuthService { public int $actor=1; public function __construct() {} public function id(): ?int { return $this->actor; } };
$repo=new App\Repositories\TreatmentGovernanceRepository($db); $sources=new App\Repositories\TreatmentSourceRepository($db); $grants=new App\Repositories\TreatmentEditorialPermissionRepository($db); $rules=new App\Services\TreatmentGovernanceRules(); $policy=new App\Services\TreatmentGovernancePolicy(); $snapshot=new App\Services\TreatmentGovernanceSnapshot($rules); $auth=new App\Services\TreatmentEditorialAuthorizationService($actor,$grants,$rules,$policy); $service=new App\Services\TreatmentGovernanceService($repo,$sources,$grants,$auth,$rules,$snapshot,$policy);
if (($argv[1]??null)==='worker') {
    $actor->actor=(int)$argv[2]; $id=(int)$argv[3]; $submission=(int)$argv[4]; $start=(float)$argv[5];
    while (microtime(true)<$start) { usleep(10000); }
    $began=microtime(true);
    try { $event=$service->approve($id,1,$submission,['en'],'Concurrent synthetic review','2099-01-01 00:00:00.000000',true); echo json_encode(['actor'=>$actor->actor,'status'=>'APPROVED','event'=>$event,'began'=>$began,'finished'=>microtime(true)]); }
    catch (RuntimeException $error) { echo json_encode(['actor'=>$actor->actor,'status'=>'REJECTED','message'=>$error->getMessage(),'began'=>$began,'finished'=>microtime(true)]); }
    exit;
}
$pdo->exec("INSERT INTO treatment_sources(id,source_type,title,citation_text,created_by,status,verified_by,verified_at) VALUES(900,'other','Concurrent synthetic source','Synthetic citation',1,'verified',2,NOW(6))");
$pdo->exec("INSERT INTO treatment_protocols(disease_id,treatment_system_id,title_en,overview_en,evidence_classification,content_purpose,applicability_en,limitations_en) VALUES(1,1,'Concurrent synthetic protocol','Synthetic overview','evidence_supported','supportive_care','Synthetic scope','Educational limitation')"); $id=(int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO treatment_protocol_items(protocol_id,item_type,title_en,instructions_en,sort_order,is_included,evidence_classification,content_purpose,limitations_en) VALUES($id,'lifestyle','Synthetic item','Synthetic instruction',1,1,'evidence_supported','supportive_care','Educational limitation')"); $item=(int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO treatment_protocol_sources(protocol_id,source_id,claim_scope) VALUES($id,900,'Synthetic protocol claim')"); $pdo->exec("INSERT INTO treatment_item_sources(item_id,source_id,claim_scope) VALUES($item,900,'Synthetic item claim')"); $submission=$service->submit($id,1,'draft',['en']);
$jobs=[]; $start=microtime(true)+2;
foreach ([2,5] as $reviewer) {
    $pipes=[]; $process=proc_open([PHP_BINARY,__FILE__,'worker',(string)$reviewer,(string)$id,(string)$submission,(string)$start],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__));
    if (!is_resource($process)) { throw new RuntimeException('Could not start concurrent worker.'); }
    fclose($pipes[0]); $jobs[]=[$process,$pipes];
}
$out=[];
foreach ($jobs as [$process,$pipes]) { $stdout=stream_get_contents($pipes[1]); $stderr=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $exit=proc_close($process); $out[]=['exit'=>$exit,'result'=>json_decode($stdout,true),'stderr'=>$stderr]; }
$p=$repo->find($id); $q=$pdo->prepare("SELECT COUNT(*) FROM treatment_protocol_events WHERE protocol_id=? AND event_type='approved'"); $q->execute([$id]); $count=(int)$q->fetchColumn();
$statuses=array_column(array_column($out,'result'),'status');
$passed=count(array_filter($statuses,fn($status)=>$status==='APPROVED'))===1 && count(array_filter($statuses,fn($status)=>$status==='REJECTED'))===1 && $count===1 && $p['publication_status']==='unpublished' && $p['review_state']==='approved';
$report=['status'=>$passed ? 'PASS' : 'FAIL','protocol'=>$id,'submission'=>$submission,'workers'=>$out,'approved_event_count'=>$count,'approved_review_id'=>$p['approved_review_id'],'publication_status'=>$p['publication_status'],'sql_mode'=>$pdo->query('SELECT @@sql_mode')->fetchColumn()];
file_put_contents(dirname(__DIR__).'/docs/TREATMENT_PHASE_2C_CONCURRENCY_RESULTS.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n"); echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
exit($passed ? 0 : 2);
