<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
ini_set('zend.exception_ignore_args','1');ini_set('display_errors','0');
define('SSC_RECOVERY_LIBRARY',true);
require __DIR__.'/saved-symptom-checks-recovery.php';
const F50_RESULT='D:/xampp/kobi-backups/step50b-20261016/final-acceptance.json';
$checks=0;$secrets=[];$families=[];$password='';$p=null;$baseline=null;$result=['status'=>'STARTING'];
function f50(bool $ok,string $name):void{global $checks;if(!$ok){throw new LogicException($name);}++$checks;}
function f50Save(array $data):void{file_put_contents(F50_RESULT,json_encode($data,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));}
function f50Request(string $route,array $data=[],?string $access=null,int $expected=200):array{
    $headers=['Content-Type: application/json'];if($access!==null){$headers[]='Authorization: Bearer '.$access;}
    $c=curl_init('https://localhost/kobi/public/api/v1/auth/'.$route);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($data===[]?(object)[]:$data,JSON_THROW_ON_ERROR),CURLOPT_HTTPHEADER=>$headers,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15,CURLOPT_CERTINFO=>true,CURLOPT_HEADER=>true]);
    $raw=curl_exec($c);$status=curl_getinfo($c,CURLINFO_HTTP_CODE);$verified=curl_getinfo($c,CURLINFO_SSL_VERIFYRESULT);$err=curl_errno($c);$info=curl_getinfo($c,CURLINFO_CERTINFO);$n=curl_getinfo($c,CURLINFO_HEADER_SIZE);curl_close($c);
    f50($err===0&&$verified===0&&is_string($raw),'Verified HTTPS transport');
    f50(isset($info[0]['Cert'])&&openssl_x509_fingerprint($info[0]['Cert'],'sha256')==='83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087','Intended live certificate');
    f50($status===$expected,'HTTP status for '.$route.' expected '.$expected.' observed '.$status);
    $head=substr($raw,0,$n);$body=substr($raw,$n);$j=json_decode($body,true,16,JSON_THROW_ON_ERROR);
    f50(preg_match('/^Cache-Control:\s*no-store\s*$/im',$head)===1,'No-store');
    f50(!preg_match('/^(?:Set-Cookie|Access-Control-Allow-Origin|Access-Control-Allow-Credentials):|^X-Kobi-Diag-/im',$head),'No cookies/CORS/diagnostic headers');
    f50(($j['success']??null)===($expected===200),'Response envelope');
    f50(!str_contains($body,'SQLSTATE')&&!str_contains($body,'Stack trace')&&!str_contains($body,'password_hash'),'Safe JSON');
    if($expected===429){f50(preg_match('/^Retry-After:\s*[1-9][0-9]*\s*$/im',$head)===1,'Rate-limit retry header');}
    return $j['data']??[];
}
function f50Login(string $email,string $password,string $label):array{
    global $secrets,$families;
    $d=f50Request('login',compact('email','password')+['device_label'=>$label]);
    f50(($d['expires_in']??null)===600&&count($d)===7,'Exact ten-minute token DTO');
    f50(App\Services\ApiAuthService::validToken($d['access_token'],'access')&&App\Services\ApiAuthService::validToken($d['refresh_token'],'refresh'),'Opaque formats');
    $secrets[]=$d['access_token'];$secrets[]=$d['refresh_token'];$families[$d['session_id']]=$d;return $d;
}
function f50Refresh(array $old):array{
    global $secrets;
    $d=f50Request('refresh',['refresh_token'=>$old['refresh_token']]);
    f50($d['access_token']!==$old['access_token']&&$d['refresh_token']!==$old['refresh_token'],'Both credentials rotated');
    $secrets[]=$d['access_token'];$secrets[]=$d['refresh_token'];return $d;
}
try{
    f50(!is_file(F50_RESULT),'Final acceptance already started; preserve evidence and do not rerun');
    $input=json_decode(stream_get_contents(STDIN),true,8,JSON_THROW_ON_ERROR);$password=(string)($input['password']??'');unset($input);f50($password!=='','Local test password required');$secrets[]=$password;
    $p=(new App\Core\Database())->connection();f50($p->query('SELECT DATABASE()')->fetchColumn()==='medical1','Normal database guard');
    $baseline=sscCapture($p);$existingSessions=$baseline['tables']['user_api_sessions']['rows'];$existingRefresh=$baseline['tables']['user_api_refresh_tokens']['rows'];
    unset($baseline['tables']['user_api_sessions'],$baseline['tables']['user_api_refresh_tokens']);
    f50(count($existingSessions)===5&&count($existingRefresh)===9,'Expected retained prior lifecycle history');
    f50((int)$p->query('SELECT COUNT(*) FROM user_api_sessions WHERE revoked_at IS NULL')->fetchColumn()===0,'No pre-existing active API credentials');
    $u=$p->query('SELECT id,email,status,email_verified_at,password_set_at FROM users WHERE id=1')->fetch();f50($u&&$u['status']==='active'&&$u['email_verified_at']!==null&&$u['password_set_at']!==null,'Designated account eligibility');
    f50(strlen((string)config('api_auth')['rate_key'])>=32,'Dedicated configured limiter key');
    $logStarts=[];foreach([dirname(__DIR__).'/storage/logs','D:/xampp/apache/logs','D:/xampp/php/logs'] as $directory){if(is_dir($directory)){foreach(glob($directory.'/*') as $file){if(is_file($file)){$logStarts[$file]=filesize($file);}}}}
    // First retry only the precise previously failed operation; stop on failure.
    $f=f50Login($u['email'],$password,'Step50B final forwarding check');$f2=f50Refresh($f);
    f50Request('logout',[],$f2['access_token']);
    $q=$p->prepare('SELECT revoked_at IS NOT NULL AS revoked,revocation_reason FROM user_api_sessions WHERE public_id=?');$q->execute([$f['session_id']]);$row=$q->fetch();f50((bool)$row['revoked']&&$row['revocation_reason']==='logout','Valid rotated access logout executes');
    $result=['status'=>'LOGOUT_CORRECTION_PASS','checks'=>$checks,'expected_authorization_variable_available_through_unchanged_controller'=>true,'normal_logout'=>true];f50Save($result);
    echo "PASS: previously failing rotated-token logout now succeeds. Continuing remaining acceptance.\n";flush();
    $a=f50Login($u['email'],$password,'Step50B final real expiry');$expiryStarted=time();
    f50Request('login',['email'=>$u['email'],'password'=>bin2hex(random_bytes(24))],null,401);
    $b=f50Login($u['email'],$password,'Step50B final rotation/reuse');$b2=f50Refresh($b);$b3=f50Refresh($b2);
    f50Request('logout',[],$b['access_token'],401);
    f50Request('refresh',['refresh_token'=>$b['refresh_token']],null,401);
    f50Request('logout',[],$b3['access_token'],401);
    $q->execute([$b['session_id']]);$row=$q->fetch();f50((bool)$row['revoked']&&$row['revocation_reason']==='refresh_reuse','Replay commits only its family revocation');
    $c=f50Login($u['email'],$password,'Step50B final single logout');$d=f50Login($u['email'],$password,'Step50B final independent device');
    f50Request('logout',[],'malformed',401);f50Request('logout',[],null,401);
    f50Request('logout',[],'ka1_'.rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'='),401);
    $c2=f50Refresh($c);f50Request('logout',[],$c2['access_token']);f50Request('logout',[],$c2['access_token'],401);
    f50Request('refresh',['refresh_token'=>$c2['refresh_token']],null,401);
    $q->execute([$d['session_id']]);$row=$q->fetch();f50(!(bool)$row['revoked'],'Other device not revoked by single logout');$d2=f50Refresh($d);
    $unknown='step50b-final-rate-'.bin2hex(random_bytes(5)).'@example.invalid';
    for($i=0;$i<5;$i++){f50Request('login',['email'=>$unknown,'password'=>bin2hex(random_bytes(16))],null,401);}
    f50Request('login',['email'=>$unknown,'password'=>bin2hex(random_bytes(16))],null,429);
    $counterPath=rtrim((string)config('storage_path'),'/\\').'/cache/api-auth/counters.json';$counters=json_decode(file_get_contents($counterPath),true,16,JSON_THROW_ON_ERROR);
    $counter=hash_hmac('sha256',"login_failure\0".$unknown,(string)config('api_auth')['rate_key']);f50(($counters[$counter]['count']??null)===5,'Actual configured HMAC key used by normal limiter');unset($counter,$counters);
    $until=$expiryStarted+605;$result=['status'=>'WAITING_FOR_REAL_ACCESS_EXPIRY','checks'=>$checks,'normal_logout'=>true,'rotation_reuse_device_isolation'=>true,'rate_limiting_429'=>true,'configured_limiter_key_used'=>true,'expiry_check_after_utc'=>gmdate('c',$until)];f50Save($result);
    echo "PASS: rotation, replay, device isolation and normal 429 enforcement. Waiting for genuine 10-minute access expiry; leave this window open.\n";flush();
    while(time()<$until){sleep(min(15,$until-time()));}
    f50Request('logout',[],$a['access_token'],401);$a2=f50Refresh($a);
    f50Request('logout-all',['current_password'=>'incorrect-test-password'], $a2['access_token'],401);
    f50Request('logout-all',['current_password'=>$password],$a2['access_token']);
    foreach([$a2,$d2] as $issued){f50Request('logout',[],$issued['access_token'],401);f50Request('refresh',['refresh_token'=>$issued['refresh_token']],null,401);}
    f50((int)$p->query('SELECT COUNT(*) FROM user_api_sessions WHERE revoked_at IS NULL')->fetchColumn()===0,'All fresh families revoked by normal operations');
    $stored=serialize($p->query('SELECT * FROM user_api_sessions')->fetchAll()).serialize($p->query('SELECT * FROM user_api_refresh_tokens')->fetchAll());foreach($secrets as $secret){f50(!str_contains($stored,$secret),'No raw credential in API persistence');}unset($stored);
    foreach([dirname(__DIR__).'/storage/logs','D:/xampp/apache/logs','D:/xampp/php/logs'] as $directory){if(!is_dir($directory)){continue;}foreach(glob($directory.'/*') as $file){if(!is_file($file)){continue;}$h=fopen($file,'rb');$offset=$logStarts[$file]??0;if(filesize($file)>=$offset){fseek($h,$offset);}$new=stream_get_contents($h);fclose($h);foreach($secrets as $secret){f50(!str_contains($new,$secret),'No exact raw credential in appended logs');}}}
    $result=['status'=>'PASS','checks'=>$checks,'account_id'=>1,'login'=>true,'valid_access_and_post_rotation_logout'=>true,'expected_authorization_variable_available_through_unchanged_controller'=>true,'malformed_invalid_expired_access_rejected'=>true,'real_access_expiry'=>true,'refresh_rotation'=>true,'reuse_family_revocation'=>true,'single_logout_device_isolation'=>true,'logout_all'=>true,'rate_limit_429'=>true,'configured_limiter_key_used'=>true,'digest_only_persistence'=>true,'appended_logs_no_raw_credentials'=>true,'tls_verify_result'=>0,'tls_peer_sha256'=>'83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087'];
}catch(Throwable $e){$result=['status'=>'FAILED','checks'=>$checks,'failure'=>$e instanceof LogicException?$e->getMessage():'Acceptance runtime failure; no credential output'];}
finally{
    // Cleanup only this run's families via existing refresh/replay lifecycle; no manual row edits.
    $cleanup=true;
    if($p!==null){foreach($families as $public=>$issued){try{$s=$p->prepare('SELECT revoked_at FROM user_api_sessions WHERE public_id=?');$s->execute([$public]);if($s->fetchColumn()===null){$t=$p->prepare('SELECT consumed_at IS NOT NULL FROM user_api_refresh_tokens WHERE token_hash=?');$t->execute([App\Services\ApiAuthService::digest($issued['refresh_token'],'refresh')]);if(!(bool)$t->fetchColumn()){$r=f50Request('refresh',['refresh_token'=>$issued['refresh_token']],null,200);$secrets[]=$r['access_token'];$secrets[]=$r['refresh_token'];}}f50Request('refresh',['refresh_token'=>$issued['refresh_token']],null,401);$s=$p->prepare('SELECT revoked_at IS NOT NULL FROM user_api_sessions WHERE public_id=?');$s->execute([$public]);$cleanup=$cleanup&&(bool)$s->fetchColumn();}catch(Throwable){$cleanup=false;}}}
    $result['normal_lifecycle_cleanup_pass']=$cleanup;
    if($p!==null&&$baseline!==null){$current=sscCapture($p);$afterSessions=$current['tables']['user_api_sessions']['rows'];$afterRefresh=$current['tables']['user_api_refresh_tokens']['rows'];unset($current['tables']['user_api_sessions'],$current['tables']['user_api_refresh_tokens']);$ids=array_column($existingSessions,'id');$rids=array_column($existingRefresh,'id');$result['integrity']=['unrelated_state_identical'=>sscDigest($current,'medical1')===sscDigest($baseline,'medical1'),'existing_api_families_unchanged'=>array_values(array_filter($afterSessions,fn($r)=>in_array($r['id'],$ids,true)))===$existingSessions,'existing_refresh_history_unchanged'=>array_values(array_filter($afterRefresh,fn($r)=>in_array($r['id'],$rids,true)))===$existingRefresh,'new_family_count'=>count($afterSessions)-count($existingSessions),'new_refresh_history_count'=>count($afterRefresh)-count($existingRefresh),'active_api_families'=>(int)$p->query('SELECT COUNT(*) FROM user_api_sessions WHERE revoked_at IS NULL')->fetchColumn(),'sql_mode'=>$current['mode']];if(in_array(false,$result['integrity'],true)||$result['integrity']['active_api_families']!==0){$result['status']='FAILED';$result['failure']='Final integrity/cleanup mismatch';}}
    if(!$cleanup){$result['status']='FAILED';$result['failure']='Normal lifecycle cleanup failed; no manual row edits attempted';}
    $result['finished_utc']=gmdate('c');$password='';$secrets=[];$families=[];f50Save($result);
}
echo json_encode($result,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR),"\n";if($result['status']!=='PASS'){exit(1);}
