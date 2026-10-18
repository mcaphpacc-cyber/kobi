<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php'; env('DB_HOST'); $_ENV['TREATMENT_PUBLICATION_MODE']='hold';
$pdo=new PDO('mysql:host='.env('DB_HOST','localhost').';port='.env('DB_PORT','3306').';dbname=kobi_phase2c_validation_20261008;charset=utf8mb4',env('DB_USERNAME'),env('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
if ($pdo->query('SELECT DATABASE()')->fetchColumn()!=='kobi_phase2c_validation_20261008') { throw new RuntimeException('Wrong database'); }
$db=new class($pdo) extends App\Core\Database { public function __construct(private PDO $pdo) {} public function connection(): PDO { return $this->pdo; } };
$actor=new class extends App\Services\AuthService { public int $actor=1; public function __construct() {} public function id(): ?int { return $this->actor; } };
$repo=new App\Repositories\TreatmentGovernanceRepository($db); $sources=new App\Repositories\TreatmentSourceRepository($db); $grants=new App\Repositories\TreatmentEditorialPermissionRepository($db); $rules=new App\Services\TreatmentGovernanceRules(); $policy=new App\Services\TreatmentGovernancePolicy(); $auth=new App\Services\TreatmentEditorialAuthorizationService($actor,$grants,$rules,$policy); $service=new App\Services\TreatmentGovernanceService($repo,$sources,$grants,$auth,$rules,new App\Services\TreatmentGovernanceSnapshot($rules),$policy);
$pdo->exec("INSERT INTO treatment_sources(id,source_type,title,citation_text,status,created_by,verified_by,verified_at) VALUES(901,'other','Public hold synthetic source','Synthetic citation','verified',1,2,NOW(6))");
foreach ([903=>'Pending hold sentinel',904=>'Approved hold sentinel'] as $id=>$title) {
    $pdo->prepare("INSERT INTO treatment_protocols(id,disease_id,treatment_system_id,title_en,overview_en,evidence_classification,content_purpose,applicability_en,limitations_en) VALUES(?,1,1,?,'Synthetic overview','evidence_supported','supportive_care','Synthetic scope','Educational limitation')")->execute([$id,$title]);
    $pdo->exec("INSERT INTO treatment_protocol_items(id,protocol_id,item_type,title_en,instructions_en,sort_order,is_included,evidence_classification,content_purpose,limitations_en) VALUES($id,$id,'lifestyle','Synthetic item','Synthetic instruction',1,1,'evidence_supported','supportive_care','Educational limitation')");
    $pdo->exec("INSERT INTO treatment_protocol_sources(protocol_id,source_id,claim_scope) VALUES($id,901,'Synthetic overview claim')"); $pdo->exec("INSERT INTO treatment_item_sources(item_id,source_id,claim_scope) VALUES($id,901,'Synthetic item claim')");
    $actor->actor=1; $sub=$service->submit($id,1,'draft',['en']);
    if ($id===904) { $actor->actor=2; $service->approve($id,1,$sub,['en'],'Synthetic public hold review','2099-01-01 00:00:00.000000',true); }
}
$rows=$pdo->query('SELECT id,title_en,review_state,publication_status FROM treatment_protocols WHERE id IN(902,903,904) ORDER BY id')->fetchAll();
file_put_contents(dirname(__DIR__).'/docs/TREATMENT_PHASE_2C_PUBLIC_FIXTURES.json',json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n"); echo json_encode($rows,JSON_PRETTY_PRINT)."\n";
