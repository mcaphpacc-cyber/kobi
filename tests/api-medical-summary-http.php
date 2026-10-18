<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('API_IDENTITY_HTTP_LIBRARY',true);require __DIR__.'/api-identity-http.php';
function medicationExpected(int $id,string $status='current'):array{return ['id'=>(string)$id,'brand_name'=>'Recorded '.$id,'generic_name'=>null,'strength'=>'10 mg','dose'=>'recorded dose','frequency'=>'recorded frequency','route'=>'recorded route','status'=>$status,'started_on'=>'2026-01-01','stopped_on'=>$status==='stopped'?'2026-02-01':null];}
$source=apiFixtureSource();apiFixtureCreate($source);$db=apiFixtureConnect();$p=$db->connection();$storage=dirname(__DIR__).'/storage/cache/api-medical-summary-http-'.bin2hex(random_bytes(8));mkdir($storage,0700);$pem=$storage.'/tls.pem';$processes=[];
try {
    $schema=file_get_contents(dirname(__DIR__).'/database/structure.sql');foreach(['health_profiles','health_profile_members','health_medicines','health_conditions','health_allergies','health_procedures','medical_summary_shares','medical_summary_share_access_logs'] as $table){if(!preg_match('/CREATE TABLE `'.$table.'` \([\s\S]*?ENGINE=InnoDB[^;]*;/',$schema,$match))throw new LogicException('Fixture schema missing');$p->exec($match[0]);}
    $password=bin2hex(random_bytes(18));foreach([100001,100002] as $id)apiFixtureUser($p,$password,$id);
    $insert=$p->prepare('INSERT INTO health_profiles(id,full_name,profile_type,created_by,status) VALUES (?,?,?,?,?)');foreach([[1,'Owner A','self',100001,'active'],[2,'Owner B','self',100002,'active'],[3,'Shared','family',100002,'active'],[4,'Archived','self',100001,'archived'],[5,'Revoked','family',100001,'active'],[6,'Empty','family',100001,'active'],[7,'Single','dependent',100002,'active']] as $r)$insert->execute($r);
    $insert=$p->prepare('INSERT INTO health_profile_members(health_profile_id,user_id,role,status) VALUES (?,?,?,?)');foreach([[1,100001,'owner','active'],[2,100002,'owner','active'],[3,100001,'viewer','active'],[3,100002,'owner','active'],[4,100001,'owner','active'],[5,100001,'editor','revoked'],[6,100001,'owner','active'],[7,100001,'editor','active']] as $r)$insert->execute($r);
    $insert=$p->prepare('INSERT INTO health_medicines(id,health_profile_id,brand_name,generic_name,strength,dose,frequency,route,status,started_on,stopped_on,prescribed_by,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach([[1,1,'current'],[2,1,'current'],[3,1,'stopped'],[4,1,'historical'],[5,2,'current'],[6,3,'current'],[7,7,'current']] as [$id,$profile,$status]){$dto=medicationExpected($id,$status);$insert->execute([$id,$profile,$dto['brand_name'],$dto['generic_name'],$dto['strength'],$dto['dose'],$dto['frequency'],$dto['route'],$status,$dto['started_on'],$dto['stopped_on'],'PRIVATE PRESCRIBER','PRIVATE NOTES']);}
    $ssl='D:/xampp/apache/conf/openssl.cnf';$key=openssl_pkey_new(['private_key_bits'=>2048,'config'=>$ssl]);$csr=openssl_csr_new(['commonName'=>'localhost'],$key,['config'=>$ssl]);$cert=openssl_csr_sign($csr,null,$key,1,['config'=>$ssl]);openssl_x509_export($cert,$public);openssl_pkey_export($key,$private,null,['config'=>$ssl]);file_put_contents($pem,$public.$private);unset($private,$key);
    $rateKey=bin2hex(random_bytes(32));$environment=getenv();$environment['KOBI_API_TEST_RATE_KEY']=$rateKey;$environment['KOBI_API_TEST_STORAGE']=$storage;
    $server=proc_open([PHP_BINARY,'-S','127.0.0.1:18865',__DIR__.'/api-identity-http-router.php'],[0=>['pipe','r'],1=>['file','NUL','w'],2=>['file','NUL','w']],$pipes,dirname(__DIR__),$environment);$processes[]=[$server,$pipes];fclose($pipes[0]);
    $proxy=proc_open([PHP_BINARY,__DIR__.'/api-auth-http.php','--tls-proxy'],[0=>['pipe','r'],1=>['file','NUL','w'],2=>['file','NUL','w']],$pipes);$processes[]=[$proxy,$pipes];fwrite($pipes[0],json_encode(['pem'=>$pem]));fclose($pipes[0]);
    $ready=false;for($i=0;$i<30;$i++){usleep(100000);$s=@stream_socket_client('tcp://127.0.0.1:18865',$errno,$error,0.1);if($s){fclose($s);$ready=true;break;}}ih($ready,'Fixture upstream ready');usleep(300000);
    $families=[];foreach([100001,100001,100002] as $id)$families[]=identityHttp('auth/login',null,200,'POST',['email'=>'fixture'.$id.'@example.invalid','password'=>$password]);[$a,$device,$b]=$families;
    $insert=$p->prepare('INSERT INTO health_conditions(health_profile_id,condition_name,status,notes) VALUES (?,?,?,?)');
    foreach([[1,'active'],[1,'chronic'],[1,'resolved'],[1,'historical'],[2,'active'],[3,'active'],[7,'chronic']] as [$profile,$status])$insert->execute([$profile,'PRIVATE CONDITION',$status,'PRIVATE NOTES']);
    $insert=$p->prepare('INSERT INTO health_allergies(health_profile_id,allergen,notes) VALUES (?,?,?)');foreach([1,1,2] as $id)$insert->execute([$id,'PRIVATE ALLERGEN','PRIVATE NOTES']);
    $insert=$p->prepare('INSERT INTO health_procedures(health_profile_id,procedure_name,procedure_type,notes) VALUES (?,?,?,?)');foreach(['surgery','procedure','hospitalization'] as $type)$insert->execute([1,'PRIVATE PROCEDURE',$type,'PRIVATE NOTES']);
    $insert=$p->prepare("INSERT INTO medical_summary_shares(health_profile_id,created_by,token_hash,snapshot,expires_at) VALUES (1,100001,?,'{\"private\":\"snapshot\"}',DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 DAY))");$insert->execute([hash('sha256',random_bytes(32))]);
    $share=(int)$p->lastInsertId();$p->prepare('INSERT INTO medical_summary_share_access_logs(share_id) VALUES (?)')->execute([$share]);
    // Full-row comparisons are strictly synthetic, isolated fixtures; never normal KOBI rows.
    $snapshot=[];foreach(['users','health_profiles','health_profile_members','health_conditions','health_medicines','health_allergies','health_procedures','medical_summary_shares','medical_summary_share_access_logs','user_api_sessions','user_api_refresh_tokens'] as $table)$snapshot[$table]=$p->query('SELECT * FROM '.$table.' ORDER BY id')->fetchAll();
    $route='health-profiles/1/medical-summary';
    $expected=['profile'=>['id'=>'1','full_name'=>'Owner A','profile_type'=>'self'],'counts'=>['conditions'=>2,'historical_conditions'=>2,'current_medications'=>2,'allergies'=>2,'procedures'=>3]];
    ih(identityHttp($route,$a['access_token'])===$expected,'Exact approved profile/counts projection and definitions');
    ih(identityHttp($route,$a['access_token'])===$expected,'Deterministic unchanged live aggregate');
    ih(identityHttp($route,$device['access_token'])===$expected,'Same account separate device');
    ih(identityHttp($route,$a['access_token'],200,'GET',['user_id'=>'100002','profile_id'=>'2'],['Cookie: PHPSESSID=not-api-identity','X-User-ID: 100002'])===$expected,'Body cookie and headers cannot override bearer actor');
    $empty=identityHttp('health-profiles/6/medical-summary',$a['access_token']);ih($empty===['profile'=>['id'=>'6','full_name'=>'Empty','profile_type'=>'family'],'counts'=>array_fill_keys(array_keys($expected['counts']),0)],'Integer zero profile');
    $viewer=identityHttp('health-profiles/3/medical-summary',$a['access_token']);ih($viewer['counts']===['conditions'=>1,'historical_conditions'=>0,'current_medications'=>1,'allergies'=>0,'procedures'=>0],'Viewer profile read');
    ih(identityHttp('health-profiles/7/medical-summary',$a['access_token'])['counts']['conditions']===1,'Editor profile read');
    ih(identityHttp('health-profiles/2/medical-summary',$b['access_token'])['counts']['conditions']===1,'Other principal own profile');
    foreach($snapshot as $table=>$rows)ih($p->query('SELECT * FROM '.$table.' ORDER BY id')->fetchAll()===$rows,'Read-only GET leaves synthetic rows unchanged: '.$table);
    foreach([null,'malformed','ka1_'.rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=')] as $token)identityHttp($route,$token,401);
    identityHttp($route,$a['refresh_token'],401);identityHttp($route.'?user_id=100002',null,401);
    foreach(['user_id=100002','profile_id=2','page=1'] as $query)identityHttp($route.'?'.$query,$a['access_token'],400);
    foreach(['POST','PUT','PATCH','DELETE','OPTIONS','HEAD'] as $method)identityHttp($route,null,405,$method);
    $missing=null;foreach(['2','4','5','999999'] as $id){$error=identityHttp('health-profiles/'.$id.'/medical-summary',$a['access_token'],404);$missing??=$error;ih($missing===$error,'Inaccessible archived revoked and missing parity');}
    identityHttp($route,$b['access_token'],404);
    foreach(['0','01','-1','abc','2147483648'] as $id)identityHttp('health-profiles/'.$id.'/medical-summary',$a['access_token'],422);
    foreach(['health-profiles/1/summary','health-profiles/1/medical-summary/1','medical-summary','health-profiles/1/records','health-profiles/1/schedules'] as $path)identityHttp($path,$a['access_token'],404);
    identityExpected(identityHttp('me',$a['access_token']),100001);
    ih(identityHttp('health-profiles/1',$a['access_token'])===$expected['profile'],'Existing profile detail');identityHttp('health-profiles',$a['access_token']);
    ih(identityHttp('health-profiles/1/medications/1',$a['access_token'])===medicationExpected(1),'Existing medication detail');ih(count(identityHttp('health-profiles/1/medications',$a['access_token']))===4,'Existing medication collection includes non-current records');
    $sid=$p->prepare('SELECT id FROM user_api_sessions WHERE public_id=?');$sid->execute([$a['session_id']]);$bucket=hash_hmac('sha256',"access_family\0".$sid->fetchColumn(),$rateKey);$file=$storage.'/cache/api-auth/counters.json';$counters=json_decode(file_get_contents($file),true);$count=$counters[$bucket]['count'];identityHttp($route,$a['access_token']);$counters=json_decode(file_get_contents($file),true);ih($counters[$bucket]['count']===$count+1,'One existing family budget admission');$counters[$bucket]['count']=120;file_put_contents($file,json_encode($counters));identityHttp($route,$a['access_token'],429);$counters[$bucket]['count']=0;file_put_contents($file,json_encode($counters));
    $p->exec("UPDATE health_conditions SET status='historical' WHERE health_profile_id=1 AND status='active'");$changed=identityHttp($route,$a['access_token']);ih($changed['counts']['conditions']===1&&$changed['counts']['historical_conditions']===3,'Live source change reflected without snapshot creation');
    ih($p->query('SELECT * FROM medical_summary_shares ORDER BY id')->fetchAll()===$snapshot['medical_summary_shares']&&$p->query('SELECT * FROM medical_summary_share_access_logs ORDER BY id')->fetchAll()===$snapshot['medical_summary_share_access_logs'],'Existing snapshot share and access logs unchanged after all reads');
    $p->exec("UPDATE health_profiles SET profile_type='' WHERE id=1");identityHttp($route,$a['access_token'],500);$p->exec("UPDATE health_profiles SET profile_type='self' WHERE id=1");
    $p->exec("UPDATE health_profile_members SET status='revoked' WHERE health_profile_id=3 AND user_id=100001");identityHttp('health-profiles/3/medical-summary',$a['access_token'],404);
    $s=$p->prepare('UPDATE user_api_sessions SET created_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1200 SECOND),last_refreshed_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1200 SECOND),access_expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 600 SECOND) WHERE public_id=?');$s->execute([$a['session_id']]);identityHttp($route,$a['access_token'],401);
    identityHttp('auth/logout',$device['access_token'],200,'POST',[]);identityHttp($route,$device['access_token'],401);
    foreach([$a,$b] as $issued){$next=identityHttp('auth/refresh',null,200,'POST',['refresh_token'=>$issued['refresh_token']]);identityHttp('auth/logout',$next['access_token'],200,'POST',[]);}
    ih((int)$p->query('SELECT COUNT(*) FROM user_api_sessions WHERE revoked_at IS NULL')->fetchColumn()===0,'All synthetic families revoked normally');
} finally {
    foreach(array_reverse($processes) as [$process,$pipes]){if(is_resource($process)){proc_terminate($process);foreach($pipes as $pipe){if(is_resource($pipe))fclose($pipe);}proc_close($process);}}
    apiFixtureDrop($source);
    if(is_file($storage.'/cache/api-auth/counters.json')){unlink($storage.'/cache/api-auth/counters.json');rmdir($storage.'/cache/api-auth');rmdir($storage.'/cache');}
    if(is_file($pem))unlink($pem);rmdir($storage);
}
echo "Medical Summary API verified PHP/cURL HTTPS: $checks checks passed; disposable database/server/files removed.\n";
