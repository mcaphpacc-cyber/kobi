<?php
declare(strict_types=1);
// Optional operator-run check: requires explicit permission for one normal API family.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
ini_set('zend.exception_ignore_args','1');ini_set('display_errors','0');
define('SSC_RECOVERY_LIBRARY',true);require __DIR__.'/saved-symptom-checks-recovery.php';
const IDENTITY_SMOKE_RESULT='D:/xampp/kobi-backups/step50b-20261016/step51b-identity.json';
$checks=0;$issued=null;$p=null;$baseline=null;$password='';$result=['status'=>'NOT_STARTED'];
function ipCheck(bool $ok,string $name):void {global $checks;if(!$ok)throw new LogicException($name);++$checks;}
function ipRequest(string $path,?string $access=null,?array $data=null,int $expected=200):array {
    $headers=[];if($access!==null)$headers[]='Authorization: Bearer '.$access;if($data!==null)$headers[]='Content-Type: application/json';
    $c=curl_init('https://localhost/kobi/public/api/v1/'.$path);
    $options=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15,CURLOPT_CERTINFO=>true];
    if($data!==null){$options[CURLOPT_POST]=true;$options[CURLOPT_POSTFIELDS]=json_encode($data===[]?(object)[]:$data,JSON_THROW_ON_ERROR);}
    curl_setopt_array($c,$options);$raw=curl_exec($c);$info=curl_getinfo($c,CURLINFO_CERTINFO);$n=curl_getinfo($c,CURLINFO_HEADER_SIZE);
    ipCheck(curl_errno($c)===0&&curl_getinfo($c,CURLINFO_SSL_VERIFYRESULT)===0&&is_string($raw),'Certificate-verified HTTPS');
    ipCheck(isset($info[0]['Cert'])&&openssl_x509_fingerprint($info[0]['Cert'],'sha256')==='83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087','Expected localhost certificate');
    $status=curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);ipCheck($status===$expected,'Expected HTTP status for '.$path);
    $head=substr($raw,0,$n);$j=json_decode(substr($raw,$n),true,16,JSON_THROW_ON_ERROR);
    ipCheck(preg_match('/^Cache-Control: no-store\s*$/im',$head)===1,'No-store');
    ipCheck(preg_match('/^Content-Type: application\/json; charset=UTF-8\s*$/im',$head)===1,'JSON content type');
    ipCheck(!preg_match('/^(?:Set-Cookie|Access-Control-Allow-Origin|Access-Control-Allow-Credentials|X-Kobi-Diag-[^:]*):/im',$head),'No cookies/CORS/probe');
    ipCheck(($j['success']??null)===($expected===200)&&$j['meta']===['api_version'=>'v1'],'Standard envelope');
    if($expected===401)ipCheck($j['error']===['code'=>'UNAUTHENTICATED','message'=>'Authentication is required. Please sign in again.'],'Generic denied access');
    return $j['data']??[];
}
try {
    ipCheck(!is_file(IDENTITY_SMOKE_RESULT),'Existing smoke evidence; do not rerun automatically');
    $input=json_decode(stream_get_contents(STDIN),true,8,JSON_THROW_ON_ERROR);$password=(string)($input['password']??'');unset($input);ipCheck($password!=='','Local hidden password required');
    $p=(new App\Core\Database())->connection();ipCheck($p->query('SELECT DATABASE()')->fetchColumn()==='medical1','Normal database guard');
    ipCheck((int)$p->query('SELECT COUNT(*) FROM user_api_sessions WHERE revoked_at IS NULL')->fetchColumn()===0,'No preexisting active families');
    $baseline=sscCapture($p);$sessions=$baseline['tables']['user_api_sessions']['rows'];$refresh=$baseline['tables']['user_api_refresh_tokens']['rows'];unset($baseline['tables']['user_api_sessions'],$baseline['tables']['user_api_refresh_tokens']);
    $q=$p->prepare('SELECT id,email,status,email_verified_at FROM users WHERE id=?');$q->execute([1]);$user=$q->fetch();ipCheck($user&&$user['status']==='active'&&$user['email_verified_at']!==null,'Designated Account 1 eligible');
    $issued=ipRequest('auth/login',null,['email'=>$user['email'],'password'=>$password,'device_label'=>'Step51B temporary identity validation']);
    $dto=ipRequest('me',$issued['access_token']);
    ipCheck($dto===['id'=>'1','email'=>$user['email'],'status'=>'active','email_verified'=>true],'Exact own-account identity; no mobile/health/secret fields');
    $result=['status'=>'PASS','normal_apache_identity_http_200'=>true,'authorization_forwarding'=>true,'exact_identity_projection'=>true,'no_browser_session'=>true,'tls_verify_result'=>0];
} catch(Throwable $e) {$result=['status'=>'FAILED','failure'=>$e instanceof LogicException?$e->getMessage():'Smoke runtime failure; no credential output'];}
finally {
    $cleanup=$issued===null;
    if($issued!==null){try{ipRequest('auth/logout',$issued['access_token'],[]);ipRequest('me',$issued['access_token'],null,401);$cleanup=true;}catch(Throwable){$cleanup=false;}}
    $result['normal_logout_cleanup']=$cleanup;
    if(!$cleanup)$result['status']='FAILED';
    if($p!==null&&$baseline!==null){
        $current=sscCapture($p);$afterSessions=$current['tables']['user_api_sessions']['rows'];$afterRefresh=$current['tables']['user_api_refresh_tokens']['rows'];unset($current['tables']['user_api_sessions'],$current['tables']['user_api_refresh_tokens']);
        $sids=array_column($sessions,'id');$rids=array_column($refresh,'id');
        $integrity=['unrelated_state_identical'=>sscDigest($baseline,'medical1')===sscDigest($current,'medical1'),'existing_families_unchanged'=>array_values(array_filter($afterSessions,fn($row)=>in_array($row['id'],$sids,true)))===$sessions,'existing_refresh_history_unchanged'=>array_values(array_filter($afterRefresh,fn($row)=>in_array($row['id'],$rids,true)))===$refresh,'new_family_count'=>count($afterSessions)-count($sessions),'new_refresh_history_count'=>count($afterRefresh)-count($refresh),'active_api_families'=>(int)$p->query('SELECT COUNT(*) FROM user_api_sessions WHERE revoked_at IS NULL')->fetchColumn(),'sql_mode'=>$current['mode']];
        $result['integrity']=$integrity;
        if(!$integrity['unrelated_state_identical']||!$integrity['existing_families_unchanged']||!$integrity['existing_refresh_history_unchanged']||$integrity['active_api_families']!==0||($issued!==null&&($integrity['new_family_count']!==1||$integrity['new_refresh_history_count']!==1)))$result['status']='FAILED';
    }
    $password='';$issued=null;$result['checks']=$checks;$result['finished_utc']=gmdate('c');
    // Safe metadata only; never overwrite previous evidence, including on accidental rerun.
    if(!is_file(IDENTITY_SMOKE_RESULT))file_put_contents(IDENTITY_SMOKE_RESULT,json_encode($result,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
}
echo json_encode($result,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR),PHP_EOL;
if($result['status']!=='PASS')exit(1);
