<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
ini_set('zend.exception_ignore_args','1');ini_set('display_errors','0');
require dirname(__DIR__).'/vendor/autoload.php';
const P50_EVIDENCE='D:/xampp/kobi-backups/step50b-20261016';
$checks=0;$secrets=[];$password='';$p=null;
function p50(bool $ok,string $name):void{global $checks;if(!$ok){throw new RuntimeException($name);}++$checks;}
function p50Evidence(array $data):void{file_put_contents(P50_EVIDENCE.'/production-smoke.json',json_encode($data,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));}
function p50Request(string $route,array $data=[],?string $access=null,int $expected=200):array{
    global $checks;
    $headers=['Content-Type: application/json'];if($access!==null){$headers[]='Authorization: Bearer '.$access;}
    $c=curl_init('https://localhost/kobi/public/api/v1/'.$route);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($data===[]?(object)[]:$data,JSON_THROW_ON_ERROR),CURLOPT_HTTPHEADER=>$headers,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15,CURLOPT_CERTINFO=>true,CURLOPT_HEADER=>true]);
    $response=curl_exec($c);$status=curl_getinfo($c,CURLINFO_HTTP_CODE);$verified=curl_getinfo($c,CURLINFO_SSL_VERIFYRESULT);$errno=curl_errno($c);$info=curl_getinfo($c,CURLINFO_CERTINFO);$size=curl_getinfo($c,CURLINFO_HEADER_SIZE);curl_close($c);
    p50($errno===0&&$verified===0&&is_string($response),'Verified HTTPS transport');
    p50(isset($info[0]['Cert'])&&openssl_x509_fingerprint($info[0]['Cert'],'sha256')==='83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087','Intended live certificate');
    p50($status===$expected,'HTTP status for '.$route.' expected '.$expected.' observed '.$status);
    $head=substr($response,0,$size);$body=substr($response,$size);$j=json_decode($body,true,16,JSON_THROW_ON_ERROR);
    p50(preg_match('/^Cache-Control:\s*no-store\s*$/im',$head)===1,'No-store');
    p50(!preg_match('/^(Set-Cookie|Access-Control-Allow-Origin|Access-Control-Allow-Credentials):/im',$head),'No cookies/CORS');
    p50(($j['success']??null)===($expected===200),'Response envelope');
    p50(!str_contains($body,'SQLSTATE')&&!str_contains($body,'Stack trace')&&!str_contains($body,'password_hash'),'Safe response');
    if($expected===429){p50(preg_match('/^Retry-After:\s*[1-9][0-9]*\s*$/im',$head)===1,'Rate-limit retry header');}
    return $j['data']??[];
}
function p50Login(string $email,string $password,string $label):array{
    global $secrets;
    $d=p50Request('auth/login',compact('email','password')+['device_label'=>$label]);
    p50(($d['expires_in']??null)===600 && count($d)===7,'Exact ten-minute token DTO');
    p50(App\Services\ApiAuthService::validToken($d['access_token'],'access')&&App\Services\ApiAuthService::validToken($d['refresh_token'],'refresh'),'Opaque token formats');
    $secrets[]=$d['access_token'];$secrets[]=$d['refresh_token'];return $d;
}
function p50Refresh(array $old):array{
    global $secrets;
    $d=p50Request('auth/refresh',['refresh_token'=>$old['refresh_token']]);
    p50($d['access_token']!==$old['access_token']&&$d['refresh_token']!==$old['refresh_token'],'Rotation changes both credentials');
    $secrets[]=$d['access_token'];$secrets[]=$d['refresh_token'];return $d;
}
try{
    p50(is_file(P50_EVIDENCE.'/deployed.json')&&is_file(P50_EVIDENCE.'/limiter-provisioned.json'),'Deployment evidence required');
    p50(!is_file(P50_EVIDENCE.'/production-smoke.json'),'Smoke already started; do not rerun automatically');
    $input=json_decode(stream_get_contents(STDIN),true,8,JSON_THROW_ON_ERROR);$password=(string)($input['password']??'');unset($input);
    p50($password!=='','Local password required');$secrets[]=$password;
    $p=(new App\Core\Database())->connection();p50($p->query('SELECT DATABASE()')->fetchColumn()==='medical1','Normal database guard');
    $u=$p->query('SELECT id,email,status,email_verified_at,password_set_at FROM users WHERE id=1')->fetch();
    p50($u&&$u['status']==='active'&&$u['email_verified_at']!==null&&$u['password_set_at']!==null,'Designated account eligibility');
    p50((int)$p->query('SELECT COUNT(*) FROM user_api_sessions')->fetchColumn()===0,'No pre-existing API families; smoke will not revoke unrelated credentials');
    $logStarts=[];foreach([dirname(__DIR__).'/storage/logs','D:/xampp/apache/logs','D:/xampp/php/logs'] as $directory){if(is_dir($directory)){foreach(glob($directory.'/*') as $file){if(is_file($file)){$logStarts[$file]=filesize($file);}}}}
    $a=p50Login($u['email'],$password,'Step50B expiry validation');$expiryStarted=time();
    p50Request('auth/login',['email'=>$u['email'],'password'=>bin2hex(random_bytes(24))],null,401);
    $b=p50Login($u['email'],$password,'Step50B rotation validation');$b2=p50Refresh($b);$b3=p50Refresh($b2);
    p50Request('auth/logout',[],$b['access_token'],401);
    p50Request('auth/refresh',['refresh_token'=>$b['refresh_token']],null,401);
    p50Request('auth/logout',[],$b3['access_token'],401);
    $s=$p->prepare('SELECT revocation_reason FROM user_api_sessions WHERE public_id=?');$s->execute([$b['session_id']]);p50($s->fetchColumn()==='refresh_reuse','Reuse commits family revocation');
    $c=p50Login($u['email'],$password,'Step50B single logout');$d=p50Login($u['email'],$password,'Step50B independent device');
    p50Request('auth/logout',[],'malformed',401);
    p50Request('auth/logout',[],'ka1_'.rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'='),401);
    $c2=p50Refresh($c);p50Request('auth/logout',[],$c2['access_token']);
    p50Request('auth/logout',[],$c2['access_token'],401);
    p50Request('auth/refresh',['refresh_token'=>$c2['refresh_token']],null,401);
    $d2=p50Refresh($d);p50(true,'Other device remains usable after single logout');
    $unknown='step50b-rate-'.bin2hex(random_bytes(5)).'@example.invalid';
    for($i=0;$i<5;$i++){p50Request('auth/login',['email'=>$unknown,'password'=>bin2hex(random_bytes(16))],null,401);}
    p50Request('auth/login',['email'=>$unknown,'password'=>bin2hex(random_bytes(16))],null,429);
    $until=$expiryStarted+605;
    p50Evidence(['status'=>'WAITING_FOR_REAL_ACCESS_EXPIRY','checks'=>$checks,'account_id'=>1,'expiry_check_after_utc'=>gmdate('c',$until),'no_password_or_token_persisted'=>true]);
    echo "PASS: login, rotation, reuse, single logout/device isolation and rate limiting. Waiting for real 10-minute access expiry; do not rerun.\n";flush();
    while(time()<$until){sleep(min(15,$until-time()));}
    p50Request('auth/logout',[],$a['access_token'],401);
    $a2=p50Refresh($a);p50(true,'Refresh succeeds after genuine access expiry');
    p50Request('auth/logout-all',['current_password'=>$password],$a2['access_token']);
    foreach([$a2,$d2] as $issued){p50Request('auth/logout',[],$issued['access_token'],401);p50Request('auth/refresh',['refresh_token'=>$issued['refresh_token']],null,401);}
    p50((int)$p->query('SELECT COUNT(*) FROM user_api_sessions WHERE revoked_at IS NULL')->fetchColumn()===0,'All issued families revoked');
    $stored=serialize($p->query('SELECT * FROM user_api_sessions')->fetchAll()).serialize($p->query('SELECT * FROM user_api_refresh_tokens')->fetchAll());
    foreach($secrets as $secret){p50(!str_contains($stored,$secret),'No raw credential in API tables');}
    foreach([dirname(__DIR__).'/storage/logs','D:/xampp/apache/logs','D:/xampp/php/logs'] as $directory){if(!is_dir($directory)){continue;}foreach(glob($directory.'/*') as $file){if(!is_file($file)){continue;}$h=fopen($file,'rb');$offset=$logStarts[$file]??0;if(filesize($file)>=$offset){fseek($h,$offset);}$new=stream_get_contents($h);fclose($h);foreach($secrets as $secret){p50(!str_contains($new,$secret),'No raw credential in appended logs');}}}
    $report=['status'=>'PASS','checks'=>$checks,'account_id'=>1,'login'=>true,'real_access_expiry'=>true,'refresh_rotation'=>true,'reuse_family_revocation'=>true,'single_logout_device_isolation'=>true,'logout_all'=>true,'rate_limit_429'=>true,'digest_only_persistence'=>true,'appended_logs_no_raw_credentials'=>true,'tls_verify_result'=>0,'tls_peer_sha256'=>'83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087','finished_utc'=>gmdate('c'),'all_test_api_families_revoked'=>true,'no_user_account_or_password_changes'=>true];
    p50Evidence($report);echo 'PASS: normal HTTPS API smoke complete; '.$checks." checks; all test API families revoked; no raw credentials retained.\n";
}catch(Throwable $e){
    // Attempt only the already-authorized normal logout-all UI/API operation on failure; never manually edit account data.
    if(isset($a2,$password)&&$password!==''){try{p50Request('auth/logout-all',['current_password'=>$password],$a2['access_token']);}catch(Throwable){}}
    p50Evidence(['status'=>'FAILED','checks'=>$checks,'failure'=>$e instanceof RuntimeException?$e->getMessage():'Smoke runtime failure; no secret output','finished_utc'=>gmdate('c')]);
    fwrite(STDERR,"STOP: production smoke failed; inspect protected result metadata, do not rerun automatically.\n");exit(1);
}finally{$password='';$secrets=[];}
