<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('API_IDENTITY_HTTP_LIBRARY',true);require __DIR__.'/api-identity-http.php';
function profileExpected(int $id,string $name,string $type):array{return ['id'=>(string)$id,'full_name'=>$name,'profile_type'=>$type];}
$source=apiFixtureSource();apiFixtureCreate($source);$db=apiFixtureConnect();$p=$db->connection();
$storage=dirname(__DIR__).'/storage/cache/api-profiles-http-'.bin2hex(random_bytes(8));mkdir($storage,0700);$pem=$storage.'/tls.pem';$processes=[];
try {
    $schema=file_get_contents(dirname(__DIR__).'/database/structure.sql');
    foreach(['health_profiles','health_profile_members'] as $table){if(!preg_match('/CREATE TABLE `'.$table.'` \([\s\S]*?ENGINE=InnoDB[^;]*;/',$schema,$match))throw new LogicException('Profile fixture schema');$p->exec($match[0]);}
    $password=bin2hex(random_bytes(18));foreach([100001,100002,100003] as $id)apiFixtureUser($p,$password,$id);
    $insert=$p->prepare('INSERT INTO health_profiles(id,profile_type,full_name,date_of_birth,gender,created_by,status) VALUES (?,?,?,?,?,?,?)');
    foreach([[1,'self','A owner','2000-01-01','male',100001,'active'],[2,'family','B only',null,'unspecified',100002,'active'],[3,'dependent','C shared',null,'other',100002,'active'],[4,'family','D revoked',null,'unspecified',100001,'active'],[5,'self','E archived',null,'unspecified',100001,'archived'],[6,'family','F creator without access',null,'unspecified',100001,'active'],[7,'family','G editor',null,'unspecified',100002,'active']] as $row)$insert->execute($row);
    $insert=$p->prepare('INSERT INTO health_profile_members(health_profile_id,user_id,role,status) VALUES (?,?,?,?)');
    foreach([[1,100001,'owner','active'],[2,100002,'owner','active'],[3,100001,'viewer','active'],[3,100002,'owner','active'],[4,100001,'editor','revoked'],[5,100001,'owner','active'],[6,100002,'owner','active'],[7,100001,'editor','active']] as $row)$insert->execute($row);
    $ssl='D:/xampp/apache/conf/openssl.cnf';$key=openssl_pkey_new(['private_key_bits'=>2048,'config'=>$ssl]);$csr=openssl_csr_new(['commonName'=>'localhost'],$key,['config'=>$ssl]);$cert=openssl_csr_sign($csr,null,$key,1,['config'=>$ssl]);openssl_x509_export($cert,$public);openssl_pkey_export($key,$private,null,['config'=>$ssl]);file_put_contents($pem,$public.$private);unset($private,$key);
    $rateKey=bin2hex(random_bytes(32));$environment=getenv();$environment['KOBI_API_TEST_RATE_KEY']=$rateKey;$environment['KOBI_API_TEST_STORAGE']=$storage;
    $server=proc_open([PHP_BINARY,'-S','127.0.0.1:18865',__DIR__.'/api-identity-http-router.php'],[0=>['pipe','r'],1=>['file','NUL','w'],2=>['file','NUL','w']],$pipes,dirname(__DIR__),$environment);$processes[]=[$server,$pipes];fclose($pipes[0]);
    $proxy=proc_open([PHP_BINARY,__DIR__.'/api-auth-http.php','--tls-proxy'],[0=>['pipe','r'],1=>['file','NUL','w'],2=>['file','NUL','w']],$pipes);$processes[]=[$proxy,$pipes];fwrite($pipes[0],json_encode(['pem'=>$pem]));fclose($pipes[0]);
    $ready=false;for($i=0;$i<30;$i++){usleep(100000);$s=@stream_socket_client('tcp://127.0.0.1:18865',$errno,$error,0.1);if($s){fclose($s);$ready=true;break;}}ih($ready,'Upstream ready');usleep(300000);
    $families=[];foreach([100001,100001,100002,100003] as $id)$families[]=identityHttp('auth/login',null,200,'POST',['email'=>'fixture'.$id.'@example.invalid','password'=>$password]);
    [$a,$device,$b,$empty]=$families;
    $snapshot=[];foreach(['users','health_profiles','health_profile_members','user_api_sessions','user_api_refresh_tokens'] as $table)$snapshot[$table]=$p->query('SELECT * FROM '.$table.' ORDER BY id')->fetchAll();
    $expected=[profileExpected(1,'A owner','self'),profileExpected(3,'C shared','dependent'),profileExpected(7,'G editor','family')];
    ih(identityHttp('health-profiles',$a['access_token'])===$expected,'Only active accessible profiles and exact private projection');
    ih(identityHttp('health-profiles',$device['access_token'])===$expected,'Independent device uses same principal');
    ih(identityHttp('health-profiles',$empty['access_token'])===[],'No profiles returns [] without creation');
    ih(identityHttp('health-profiles/3',$a['access_token'])===$expected[1],'Viewer shared profile detail permitted');
    ih(identityHttp('health-profiles/7',$a['access_token'])===$expected[2],'Editor detail permitted');
    ih(identityHttp('health-profiles/2',$b['access_token'])===profileExpected(2,'B only','family'),'Other account own profile');
    ih(identityHttp('health-profiles/1',$a['access_token'],200,'GET',['user_id'=>'100002'],['Cookie: PHPSESSID=not-an-api-identity','X-User-ID: 100002'])===$expected[0],'Body cookie and custom header cannot override principal');
    foreach($snapshot as $table=>$rows)ih($p->query('SELECT * FROM '.$table.' ORDER BY id')->fetchAll()===$rows,'Read has no row side effects '.$table);
    foreach(['health-profiles','health-profiles/1'] as $route){
        foreach([null,'malformed','ka1_'.rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=')] as $token)identityHttp($route,$token,401);
        identityHttp($route.'?user_id=100002',$a['access_token'],400);identityHttp($route.'?page=1',$a['access_token'],400);
        foreach(['POST','HEAD','OPTIONS'] as $method)identityHttp($route,null,405,$method);
    }
    $notFound=null;foreach(['2','4','5','6','999999'] as $id){$error=identityHttp('health-profiles/'.$id,$a['access_token'],404);$notFound??=$error;ih($error===$notFound,'Missing revoked archived other and creator-only same error');}
    identityHttp('health-profiles/1',$b['access_token'],404);
    foreach(['0','01','-1','abc','2147483648','99999999999999999999'] as $id)identityHttp('health-profiles/'.$id,$a['access_token'],422);
    identityHttp('health-profiles/1/medicines',$a['access_token'],404);
    identityExpected(identityHttp('me',$a['access_token']),100001);
    $sid=$p->prepare('SELECT id FROM user_api_sessions WHERE public_id=?');$sid->execute([$a['session_id']]);$bucket=hash_hmac('sha256',"access_family\0".$sid->fetchColumn(),$rateKey);$file=$storage.'/cache/api-auth/counters.json';$counters=json_decode(file_get_contents($file),true);$count=$counters[$bucket]['count'];identityHttp('health-profiles',$a['access_token']);$counters=json_decode(file_get_contents($file),true);ih($counters[$bucket]['count']===$count+1,'One existing family budget hit');$counters[$bucket]['count']=120;file_put_contents($file,json_encode($counters));identityHttp('health-profiles',$a['access_token'],429);$counters[$bucket]['count']=0;file_put_contents($file,json_encode($counters));
    $p->exec("UPDATE health_profile_members SET status='revoked' WHERE health_profile_id=3 AND user_id=100001");identityHttp('health-profiles/3',$a['access_token'],404);
    $p->exec("UPDATE health_profiles SET profile_type='' WHERE id=1");identityHttp('health-profiles/1',$a['access_token'],500);$p->exec("UPDATE health_profiles SET profile_type='self' WHERE id=1");
    $s=$p->prepare('UPDATE user_api_sessions SET created_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1200 SECOND),last_refreshed_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1200 SECOND),access_expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 600 SECOND) WHERE public_id=?');$s->execute([$a['session_id']]);identityHttp('health-profiles',$a['access_token'],401);identityHttp('health-profiles/1',$a['access_token'],401);
    identityHttp('auth/logout',$device['access_token'],200,'POST',[]);identityHttp('health-profiles',$device['access_token'],401);identityHttp('health-profiles/1',$device['access_token'],401);
    foreach([$a,$device,$b,$empty] as $issued){$next=null;if($issued!==$device){$next=identityHttp('auth/refresh',null,200,'POST',['refresh_token'=>$issued['refresh_token']]);identityHttp('auth/logout',$next['access_token'],200,'POST',[]);}}
    ih((int)$p->query('SELECT COUNT(*) FROM user_api_sessions WHERE revoked_at IS NULL')->fetchColumn()===0,'All synthetic families revoked normally');
} finally {
    foreach(array_reverse($processes) as [$process,$pipes]){if(is_resource($process)){proc_terminate($process);foreach($pipes as $pipe){if(is_resource($pipe))fclose($pipe);}proc_close($process);}}
    apiFixtureDrop($source);
    if(is_file($storage.'/cache/api-auth/counters.json')){unlink($storage.'/cache/api-auth/counters.json');rmdir($storage.'/cache/api-auth');rmdir($storage.'/cache');}
    if(is_file($pem))unlink($pem);rmdir($storage);
}
echo "Health Profiles API verified PHP/cURL HTTPS: $checks checks passed; isolated database/server/files removed.\n";
