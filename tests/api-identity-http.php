<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/api-auth-fixture.php';
$checks=0;
function ih(bool $ok,string $name):void {global $checks;if(!$ok)throw new LogicException($name);++$checks;}
function identityHttp(string $path,?string $token=null,int $expected=200,string $method='GET',?array $body=null,array $extra=[]):array {
    global $pem;
    $headers=['Connection: close'];if($token!==null)$headers[]='Authorization: Bearer '.$token;
    if($body!==null)$headers[]='Content-Type: application/json';
    $c=curl_init('https://localhost:18866/api/v1/'.$path);
    $options=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>array_merge($headers,$extra),CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_CAINFO=>$pem,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>10];
    if($method==='HEAD')$options[CURLOPT_NOBODY]=true;
    if($body!==null)$options[CURLOPT_POSTFIELDS]=json_encode($body===[]?(object)[]:$body,JSON_THROW_ON_ERROR);
    curl_setopt_array($c,$options);$raw=curl_exec($c);$status=curl_getinfo($c,CURLINFO_HTTP_CODE);$n=curl_getinfo($c,CURLINFO_HEADER_SIZE);
    ih(curl_errno($c)===0&&curl_getinfo($c,CURLINFO_SSL_VERIFYRESULT)===0&&is_string($raw),'Verified PHP/cURL HTTPS');curl_close($c);
    ih($status===$expected,'HTTP status '.$path.' expected '.$expected.' observed '.$status);
    $head=substr($raw,0,$n);$text=substr($raw,$n);
    ih(preg_match('/^Content-Type: application\/json; charset=UTF-8\s*$/im',$head)===1,'JSON content type');
    ih(preg_match('/^Cache-Control: no-store\s*$/im',$head)===1,'No-store');
    ih(preg_match('/^X-Content-Type-Options: nosniff\s*$/im',$head)===1,'Nosniff');
    ih(!preg_match('/^(?:Set-Cookie|Access-Control-Allow-Origin|Access-Control-Allow-Credentials):/im',$head),'No cookies/CORS');
    if($expected===405)ih(preg_match('/^Allow: GET\s*$/im',$head)===1,'Allow GET');
    if($method==='HEAD'){ih($text==='','HEAD empty');return [];}
    $json=json_decode($text,true,16,JSON_THROW_ON_ERROR);
    ih(($json['success']??null)===($expected===200)&&$json['meta']===['api_version'=>'v1'],'Standard envelope');
    ih(!str_contains($text,'SQLSTATE')&&!str_contains($text,'Stack trace')&&!str_contains($text,'password_hash'),'Safe JSON');
    if($expected===401){ih($json['error']===['code'=>'UNAUTHENTICATED','message'=>'Authentication is required. Please sign in again.'],'Generic denial');ih(preg_match('/^WWW-Authenticate: Bearer\s*$/im',$head)===1,'Bearer challenge');}
    if($expected===429)ih(preg_match('/^Retry-After: [1-9][0-9]*\s*$/im',$head)===1,'Retry-After');
    return $json['data']??$json;
}
function identityExpected(array $data,int $id):void {ih($data===['id'=>(string)$id,'email'=>'fixture'.$id.'@example.invalid','status'=>'active','email_verified'=>true],'Exact four-field identity/privacy/types');}
// Reuse only the verified request/assertion helpers in other focused API suites.
if(defined('API_IDENTITY_HTTP_LIBRARY')){return;}
$source=apiFixtureSource();apiFixtureCreate($source);$db=apiFixtureConnect();$p=$db->connection();$password=bin2hex(random_bytes(18));
$storage=dirname(__DIR__).'/storage/cache/api-identity-http-'.bin2hex(random_bytes(8));mkdir($storage,0700);$pem=$storage.'/tls.pem';$processes=[];$families=[];
try {
    apiFixtureUser($p,$password);apiFixtureUser($p,$password,100002);
    $p->exec("UPDATE users SET mobile_number='+12025550123' WHERE id=100001");
    $sslConfig='D:/xampp/apache/conf/openssl.cnf';$key=openssl_pkey_new(['private_key_bits'=>2048,'config'=>$sslConfig]);
    $csr=openssl_csr_new(['commonName'=>'localhost'],$key,['config'=>$sslConfig]);$cert=openssl_csr_sign($csr,null,$key,1,['config'=>$sslConfig]);
    openssl_x509_export($cert,$public);openssl_pkey_export($key,$private,null,['config'=>$sslConfig]);file_put_contents($pem,$public.$private);unset($private,$key);
    $rateKey=bin2hex(random_bytes(32));$environment=getenv();$environment['KOBI_API_TEST_RATE_KEY']=$rateKey;$environment['KOBI_API_TEST_STORAGE']=$storage;
    $server=proc_open([PHP_BINARY,'-S','127.0.0.1:18865',__DIR__.'/api-identity-http-router.php'],[0=>['pipe','r'],1=>['file','NUL','w'],2=>['file','NUL','w']],$pipes,dirname(__DIR__),$environment);$processes[]=[$server,$pipes];fclose($pipes[0]);
    // Reuse the established TLS harness; clients still verify its dedicated test certificate.
    $proxy=proc_open([PHP_BINARY,__DIR__.'/api-auth-http.php','--tls-proxy'],[0=>['pipe','r'],1=>['file','NUL','w'],2=>['file','NUL','w']],$pipes);$processes[]=[$proxy,$pipes];fwrite($pipes[0],json_encode(['pem'=>$pem]));fclose($pipes[0]);
    $ready=false;for($i=0;$i<30;$i++){usleep(100000);$socket=@stream_socket_client('tcp://127.0.0.1:18865',$errno,$error,0.1);if($socket){fclose($socket);$ready=true;break;}}ih($ready,'Isolated upstream ready');usleep(300000);
    foreach([null,'malformed','ka1_'.rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=')] as $token)identityHttp('me',$token,401);
    foreach(['POST','PUT','PATCH','DELETE','OPTIONS','HEAD'] as $method)identityHttp('me',null,405,$method);
    foreach(['me/100002','user','account'] as $path)identityHttp($path,null,404);
    foreach([100001,100001,100002] as $id){$families[]=identityHttp('auth/login',null,200,'POST',['email'=>'fixture'.$id.'@example.invalid','password'=>$password,'device_label'=>'Identity fixture only']);}
    [$a,$device,$b]=$families;
    $before=$p->query('SELECT * FROM users ORDER BY id')->fetchAll();$beforeSessions=$p->query('SELECT * FROM user_api_sessions ORDER BY id')->fetchAll();$beforeRefresh=$p->query('SELECT * FROM user_api_refresh_tokens ORDER BY id')->fetchAll();
    identityExpected(identityHttp('me',$a['access_token']),100001);identityExpected(identityHttp('me',$device['access_token']),100001);identityExpected(identityHttp('me',$b['access_token']),100002);
    identityExpected(identityHttp('me',$a['access_token'],200,'GET',['user_id'=>'100002'],['Cookie: PHPSESSID=not-an-api-identity','X-User-ID: 100002']),100001);
    ih($p->query('SELECT * FROM users ORDER BY id')->fetchAll()===$before&&$p->query('SELECT * FROM user_api_sessions ORDER BY id')->fetchAll()===$beforeSessions&&$p->query('SELECT * FROM user_api_refresh_tokens ORDER BY id')->fetchAll()===$beforeRefresh,'GET leaves all account/token rows unchanged');
    identityHttp('me?user_id=100002',$a['access_token'],400);identityHttp('me?user_id=100002',null,401);identityHttp('me',$a['refresh_token'],401);
    $a2=identityHttp('auth/refresh',null,200,'POST',['refresh_token'=>$a['refresh_token']]);identityHttp('me',$a['access_token'],401);identityExpected(identityHttp('me',$a2['access_token']),100001);
    identityHttp('auth/logout',$a2['access_token'],200,'POST',[]);identityHttp('me',$a2['access_token'],401);identityExpected(identityHttp('me',$device['access_token']),100001);
    identityHttp('auth/logout-all',$device['access_token'],200,'POST',['current_password'=>$password]);identityHttp('me',$device['access_token'],401);identityExpected(identityHttp('me',$b['access_token']),100002);
    $s=$p->prepare('SELECT id FROM user_api_sessions WHERE public_id=?');$s->execute([$b['session_id']]);$family=(string)$s->fetchColumn();
    $file=$storage.'/cache/api-auth/counters.json';$counters=json_decode(file_get_contents($file),true,16,JSON_THROW_ON_ERROR);$bucket=hash_hmac('sha256',"access_family\0".$family,$rateKey);$count=$counters[$bucket]['count'];
    identityExpected(identityHttp('me',$b['access_token']),100002);$counters=json_decode(file_get_contents($file),true,16,JSON_THROW_ON_ERROR);ih($counters[$bucket]['count']===$count+1,'Exactly one existing family budget admission per GET');
    $counters[$bucket]['count']=120;file_put_contents($file,json_encode($counters));identityHttp('me',$b['access_token'],429);$counters[$bucket]['count']=0;file_put_contents($file,json_encode($counters));
    // Expiry fixture is confined to the explicitly guarded disposable database.
    $s=$p->prepare('UPDATE user_api_sessions SET created_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1200 SECOND),last_refreshed_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1200 SECOND),access_expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 600 SECOND) WHERE public_id=?');$s->execute([$b['session_id']]);identityHttp('me',$b['access_token'],401);
    $b2=identityHttp('auth/refresh',null,200,'POST',['refresh_token'=>$b['refresh_token']]);identityExpected(identityHttp('me',$b2['access_token']),100002);
    identityHttp('auth/refresh',null,401,'POST',['refresh_token'=>$b['refresh_token']]);identityHttp('me',$b2['access_token'],401);
    ih((int)$p->query('SELECT COUNT(*) FROM user_api_sessions WHERE revoked_at IS NULL')->fetchColumn()===0,'Zero active fixture families via normal lifecycle');
} finally {
    foreach(array_reverse($processes) as [$process,$pipes]){if(is_resource($process)){proc_terminate($process);foreach($pipes as $pipe){if(is_resource($pipe))fclose($pipe);}proc_close($process);}}
    apiFixtureDrop($source);
    if(is_file($storage.'/cache/api-auth/counters.json')){unlink($storage.'/cache/api-auth/counters.json');rmdir($storage.'/cache/api-auth');rmdir($storage.'/cache');}
    if(is_file($pem))unlink($pem);rmdir($storage);
}
echo "API identity PHP/cURL HTTPS: $checks checks passed; TLS verification 0; disposable database/server/files removed.\n";
