<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/api-auth-fixture.php';
if(($argv[1]??'')==='--tls-proxy'){
    $options=json_decode(stream_get_contents(STDIN),true,8,JSON_THROW_ON_ERROR);
    $context=stream_context_create(['ssl'=>['local_cert'=>$options['pem'],'allow_self_signed'=>true,'verify_peer'=>false]]);
    $server=stream_socket_server('tls://127.0.0.1:18866',$errno,$error,STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,$context);
    if(!$server){throw new RuntimeException('TLS harness unavailable');}
    while($client=@stream_socket_accept($server,5)){
        stream_set_timeout($client,5);$request='';$length=0;
        while(($line=fgets($client,8194))!==false){$request.=$line;if(stripos($line,'Content-Length:')===0){$length=(int)trim(substr($line,15));}if($line==="\r\n"){break;}if(strlen($request)>20000){break;}}
        if($length>0 && $length<=20000){$body='';while(strlen($body)<$length && !feof($client)){$part=fread($client,$length-strlen($body));if($part===''){break;}$body.=$part;}$request.=$body;}
        $upstream=stream_socket_client('tcp://127.0.0.1:18865',$errno,$error,5);
        if($upstream){fwrite($upstream,$request);while(!feof($upstream)){$chunk=fread($upstream,8192);if($chunk!==false && $chunk!==''){fwrite($client,$chunk);}}fclose($upstream);}
        fclose($client);
    }
    exit;
}
$checks=0;
function ha(bool $ok,string $name):void{global $checks;if(!$ok){throw new LogicException($name);}++$checks;}
function authHttp(string $path,string $body='{}',?string $bearer=null,int $status=200,string $method='POST',array $extra=[]):array{
    global $pem;
    $headers=['Content-Type: application/json','Connection: close'];if($bearer!==null){$headers[]='Authorization: Bearer '.$bearer;}$headers=array_merge($headers,$extra);
    $ctx=stream_context_create(['http'=>['method'=>$method,'ignore_errors'=>true,'timeout'=>8,'header'=>implode("\r\n",$headers),'content'=>$body],'ssl'=>['cafile'=>$pem,'verify_peer'=>true,'verify_peer_name'=>true,'peer_name'=>'localhost']]);
    $raw=file_get_contents('https://localhost:18866/api/v1/auth/'.$path,false,$ctx);$head=$http_response_header??[];
    preg_match('/\s(\d{3})\s/',$head[0]??'',$m);ha((int)($m[1]??0)===$status,'HTTP status '.$path);
    $map=[];foreach($head as $h){$parts=explode(':',$h,2);if(count($parts)===2){$map[strtolower(trim($parts[0]))]=trim($parts[1]);}}
    ha(str_contains($map['content-type']??'','application/json'),'JSON content type');ha(($map['cache-control']??'')==='no-store','No-store');
    foreach(['set-cookie','access-control-allow-origin','access-control-allow-credentials'] as $header){ha(!isset($map[$header]),'No cookies/CORS');}
    if($method==='HEAD'){ha($raw==='','HEAD empty');return [];}
    $data=json_decode($raw,true,16,JSON_THROW_ON_ERROR);ha($data['success']===($status===200) && $data['meta']['api_version']==='v1','V1 envelope');
    if($status!==200){ha(!str_contains($raw,'SQLSTATE') && !str_contains($raw,'Stack trace') && !str_contains($raw,'password_hash'),'Safe JSON error');}
    if($status===401){ha(($map['www-authenticate']??'')==='Bearer','Bearer challenge');}
    return $data;
}
$source=apiFixtureSource();apiFixtureCreate($source);$db=apiFixtureConnect();$p=$db->connection();$password=bin2hex(random_bytes(18));apiFixtureUser($p,$password);
$storage=dirname(__DIR__).'/storage/cache/api-auth-http-'.bin2hex(random_bytes(8));mkdir($storage,0700);$pem=$storage.'/tls.pem';$processes=[];
try{
    $sslConfig='D:/xampp/apache/conf/openssl.cnf';$key=openssl_pkey_new(['private_key_bits'=>2048,'config'=>$sslConfig]);
    $csr=openssl_csr_new(['commonName'=>'localhost'],$key,['config'=>$sslConfig]);$cert=openssl_csr_sign($csr,null,$key,1,['config'=>$sslConfig]);
    openssl_x509_export($cert,$public);openssl_pkey_export($key,$private,null,['config'=>$sslConfig]);file_put_contents($pem,$public.$private);unset($private,$key);
    $environment=getenv();$environment['KOBI_API_TEST_RATE_KEY']=bin2hex(random_bytes(32));$environment['KOBI_API_TEST_STORAGE']=$storage;
    // Windows anonymous stderr pipes are small; discard server request diagnostics rather than block the server or persist request logs.
    $server=proc_open([PHP_BINARY,'-S','127.0.0.1:18865',__DIR__.'/api-auth-http-router.php'],[0=>['pipe','r'],1=>['file','NUL','w'],2=>['file','NUL','w']],$serverPipes,dirname(__DIR__),$environment);$processes[]=[$server,$serverPipes];fclose($serverPipes[0]);
    $proxy=proc_open([PHP_BINARY,__FILE__,'--tls-proxy'],[0=>['pipe','r'],1=>['file','NUL','w'],2=>['file','NUL','w']],$proxyPipes);$processes[]=[$proxy,$proxyPipes];fwrite($proxyPipes[0],json_encode(['pem'=>$pem]));fclose($proxyPipes[0]);
    // Bounded startup wait; sends no credentials.
    $ready=false;for($i=0;$i<30;$i++){usleep(100000);$socket=@stream_socket_client('tcp://127.0.0.1:18865',$errno,$error,0.1);if($socket){fclose($socket);$ready=true;break;}}ha($ready,'Isolated upstream started');usleep(300000);
    $login=json_encode(['email'=>'fixture100001@example.invalid','password'=>$password,'device_label'=>'Fixture नाम']);
    $a=authHttp('login',$login)['data'];ha($a['expires_in']===600 && count($a)===7,'Exact token DTO');
    $b=authHttp('refresh',json_encode(['refresh_token'=>$a['refresh_token']]))['data'];ha($a['access_token']!==$b['access_token'],'Access replaced');
    authHttp('logout','{}',$a['access_token'],401);authHttp('refresh',json_encode(['refresh_token'=>$a['refresh_token']]),null,401);authHttp('logout','{}',$b['access_token'],401);
    $a=authHttp('login',$login)['data'];$b=authHttp('login',$login)['data'];
    authHttp('logout','{}',$a['access_token']);authHttp('logout','{}',$a['access_token'],401);
    authHttp('logout-all',json_encode(['current_password'=>'wrong']),$b['access_token'],401);
    authHttp('logout-all',json_encode(['current_password'=>$password]),$b['access_token']);authHttp('logout','{}',$b['access_token'],401);
    $a=authHttp('login',$login)['data'];
    $s=$p->prepare("UPDATE user_api_sessions SET created_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1200 SECOND),last_refreshed_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1200 SECOND),access_expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 600 SECOND) WHERE public_id=?");$s->execute([$a['session_id']]);
    authHttp('logout','{}',$a['access_token'],401);$b=authHttp('refresh',json_encode(['refresh_token'=>$a['refresh_token']]))['data'];authHttp('logout','{}',$b['access_token']);
    foreach(['{}','{"email":"fixture100001@example.invalid"}','{"email":"bad","password":"x"}','{"email":"x@example.invalid","password":"x","user_id":"1"}'] as $body){authHttp('login',$body,null,422);}
    foreach(['[]','{"email":[]}','{"email":"x","email":"y"}',"{\"x\":\"\xff\"}"] as $body){authHttp('login',$body,null,400);}
    authHttp('login',str_repeat('x',8193),null,413);
    authHttp('refresh','{"refresh_token":"random"}',null,401);
    authHttp('logout','{}','not-a-token',401);authHttp('logout','{}',null,401);
    foreach(['GET','HEAD','OPTIONS','PUT'] as $method){authHttp('login','{}',null,405,$method);}
    authHttp('missing','{}',null,404);
    authHttp('login?access_token=not-a-token',$login,null,400);
    // All account-state outcomes share precisely the same failure JSON.
    $failure=authHttp('login',json_encode(['email'=>'missing@example.invalid','password'=>$password]),null,401);
    ha($failure===authHttp('login',json_encode(['email'=>'fixture100001@example.invalid','password'=>'wrong']),null,401),'Unknown/wrong parity');
    foreach(["status='inactive'","email_verified_at=NULL","password_set_at=NULL"] as $change){$p->exec('UPDATE users SET '.$change.' WHERE id=100001');ha($failure===authHttp('login',$login,null,401),'Account state parity');$p->exec("UPDATE users SET status='active',email_verified_at=UTC_TIMESTAMP(),password_set_at=UTC_TIMESTAMP() WHERE id=100001");}
    ha(!str_contains(serialize($p->query('SELECT * FROM user_api_sessions')->fetchAll()),$a['access_token']),'No raw access in SQL');
    ha(!str_contains(serialize($p->query('SELECT * FROM user_api_refresh_tokens')->fetchAll()),$a['refresh_token']),'No raw refresh in SQL');
}finally{
    foreach(array_reverse($processes) as [$process,$pipes]){if(is_resource($process)){proc_terminate($process);foreach($pipes as $pipe){if(is_resource($pipe)){fclose($pipe);}}proc_close($process);}}
    apiFixtureDrop($source);
    // Remove only files/directories created by this invocation, no recursive broad cleanup.
    if(is_file($storage.'/cache/api-auth/counters.json')){unlink($storage.'/cache/api-auth/counters.json');rmdir($storage.'/cache/api-auth');rmdir($storage.'/cache');}
    if(is_file($pem)){unlink($pem);}rmdir($storage);
}
echo "API auth HTTPS: $checks checks passed; verified local test certificate; isolated database/server/files removed.\n";
