<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
env('DB_HOST');
$pdo=new PDO('mysql:host='.env('DB_HOST','localhost').';port='.env('DB_PORT','3306').';dbname=kobi_phase2c_validation_20261008;charset=utf8mb4',env('DB_USERNAME'),env('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if ($pdo->query('SELECT DATABASE()')->fetchColumn()!=='kobi_phase2c_validation_20261008') { throw new RuntimeException('Wrong database'); }
foreach (['browser-editor@example.test'=>[1,1,0],'browser-reviewer@example.test'=>[1,0,1],'browser-wrong@example.test'=>[2,0,1]] as $email=>[$system,$edit,$review]) {
    $q=$pdo->prepare('SELECT id FROM users WHERE email=?'); $q->execute([$email]); $id=(int)$q->fetchColumn(); if (!$id) { throw new RuntimeException('Missing synthetic registered account'); }
    $pdo->prepare('UPDATE users SET email_verified_at=NOW() WHERE id=?')->execute([$id]);
    $pdo->prepare('INSERT INTO treatment_editorial_permissions(user_id,treatment_system_id,can_edit,can_review,qualification_scope,authorized_by) VALUES(?,?,?,?,?,4)')->execute([$id,$system,$edit,$review,$review ? 'Synthetic browser review scope' : null]);
}
foreach ([901=>'Browser validation protocol',902=>'Browser changes protocol'] as $id=>$title) {
    $pdo->prepare("INSERT INTO treatment_protocols(id,disease_id,treatment_system_id,title_en,title_hi,overview_en,overview_hi,evidence_classification,content_purpose,applicability_en,applicability_hi,limitations_en,limitations_hi) VALUES(?,1,1,?,?,'Synthetic overview','Synthetic overview','evidence_supported','supportive_care','Synthetic scope','Synthetic scope','Educational limitation','Educational limitation')")->execute([$id,$title,$title]);
    $pdo->exec("INSERT INTO treatment_protocol_items(id,protocol_id,item_type,title_en,title_hi,instructions_en,instructions_hi,sort_order,evidence_classification,content_purpose,limitations_en,limitations_hi) VALUES($id,$id,'lifestyle','Browser synthetic item','Browser synthetic item','Synthetic instruction','Synthetic instruction',1,'evidence_supported','supportive_care','Educational limitation','Educational limitation')");
}
echo "Browser fixture protocols 901/902 and registered isolated account grants prepared. No application configuration changed.\n";
