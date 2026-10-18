<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php'; env('DB_HOST');
$role=$argv[1]??'editor'; $actor=match($role) {'editor'=>13,'reviewer'=>14,'wrong'=>15,default=>throw new RuntimeException('Unknown isolated role')};
$cookie=null; $token=null;
foreach (glob(__DIR__.'/phase2c-browser-storage/sessions/sess_*') as $file) {
    $data=file_get_contents($file);
    if (str_contains($data,'auth_user_id|i:'.$actor.';') && preg_match('/_csrf_token\|s:\d+:"([^"]+)";/',$data,$match)) { $cookie=substr(basename($file),5); $token=$match[1]; break; }
}
if ($cookie===null) { throw new RuntimeException('Expected real isolated browser login session not found; no synthetic session injected.'); }
$pdo=new PDO('mysql:host='.env('DB_HOST','localhost').';port='.env('DB_PORT','3306').';dbname=kobi_phase2c_validation_20261008;charset=utf8mb4',env('DB_USERNAME'),env('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if ($pdo->query('SELECT DATABASE()')->fetchColumn()!=='kobi_phase2c_validation_20261008') { throw new RuntimeException('Wrong database'); }
function state(PDO $pdo): string { return hash('sha256',json_encode([$pdo->query('SELECT * FROM treatment_protocols ORDER BY id')->fetchAll(),$pdo->query('SELECT * FROM treatment_protocol_events ORDER BY id')->fetchAll()])); }
$cases=[];
if ($role!=='wrong') { foreach (['save','approve','changes-required'] as $action) {
    foreach (['missing'=>[],'invalid'=>['_csrf_token'=>'invalid']] as $kind=>$fields) { $cases[]=['POST','/admin/treatments/901/'.$action,$fields,403,'CSRF '.$kind.' '.$action]; }
    $cases[]=['GET','/admin/treatments/901/'.$action,[],404,'GET mutation '.$action];
}
}
if ($role==='editor') { $cases[]=['POST','/admin/treatments/902/save',['_csrf_token'=>$token,'expected_revision'=>'1','expected_state'=>'draft','form_complete'=>'1','protocol'=>['overview_en'=>'Stale HTTP overwrite'],'items'=>[]],409,'Stale editorial HTTP save']; }
elseif ($role==='reviewer') { $cases[]=['POST','/admin/treatments/901/approve',['_csrf_token'=>$token,'expected_revision'=>'4','expected_state'=>'pending_review','submission_id'=>'42','locales'=>['en'],'rationale'=>'Stale browser HTTP decision','review_due_at'=>'2027-10-08T12:00','independence_confirmed'=>'1'],409,'Stale duplicate HTTP approval']; }
else {
    $cases[]=['GET','/admin/treatments/902/review',[],403,'Wrong-system detail'];
    $cases[]=['POST','/admin/treatments/902/approve',['_csrf_token'=>$token,'expected_revision'=>'5','expected_state'=>'pending_review','submission_id'=>'49','locales'=>['en'],'rationale'=>'Forged wrong-system decision','review_due_at'=>'2027-10-08T12:00','independence_confirmed'=>'1'],403,'Wrong-system approval with valid CSRF'];
    $cases[]=['POST','/admin/treatments/902/changes-required',['_csrf_token'=>$token,'expected_revision'=>'5','expected_state'=>'pending_review','submission_id'=>'49','rationale'=>'Forged wrong-system correction'],403,'Wrong-system changes with valid CSRF'];
}
$file=dirname(__DIR__).'/docs/TREATMENT_PHASE_2C_HTTP_RESULTS.json'; $report=is_file($file) ? json_decode(file_get_contents($file),true) : [];
foreach ($cases as [$method,$path,$fields,$expected,$name]) {
    $before=state($pdo); $curl=curl_init('http://127.0.0.1:8127'.$path);
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIE=>'KOBI_PHASE2C_VALIDATION='.$cookie,CURLOPT_TIMEOUT=>10]);
    if ($method==='POST') { curl_setopt($curl,CURLOPT_POST,true); curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($fields)); }
    $body=curl_exec($curl); $status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE); curl_close($curl);
    $ok=$status===$expected && $before===state($pdo);
    $entry=['role'=>$role,'test'=>$name,'method'=>$method,'path'=>$path,'expected'=>$expected,'actual'=>$status,'database_unchanged'=>$before===state($pdo),'status'=>$ok ? 'PASS' : 'FAIL']; $report[]=$entry;
    file_put_contents($file,json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
    echo ($ok ? 'PASS ' : 'FAIL ').$name.' HTTP '.$status."\n"; if (!$ok) { exit(2); }
}
