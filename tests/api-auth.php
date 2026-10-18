<?php
declare(strict_types=1);
require __DIR__.'/api-auth-unit-support.php';
$checks=0;
function apiAssert(bool $ok,string $message):void{global $checks;if(!$ok){throw new LogicException($message);}++$checks;}
function apiDenied(callable $action,int $status=401):void{try{$action();throw new LogicException('Expected denial');}catch(App\Services\ApiAuthException $e){apiAssert($e->status===$status,'Expected safe status');apiAssert(!str_contains($e->getMessage(),'SQLSTATE'),'Safe failure');}}
$clock=new ApiTestClock();$repo=new ApiMemoryRepository();$limit=new ApiNoLimit();
$passwords=(new ReflectionClass(App\Services\AuthService::class))->newInstanceWithoutConstructor();
$password=bin2hex(random_bytes(18));
$repo->users[1]=['id'=>1,'email'=>'fixture@example.invalid','password_hash'=>password_hash($password,PASSWORD_DEFAULT),'password_set_at'=>'2026-01-01','status'=>'active','email_verified_at'=>'2026-01-01'];
$service=new App\Services\ApiAuthService($repo,$passwords,$clock,$limit);
$guard=new App\Services\ApiAuthGuard($service,$limit);
foreach(['{}','{"email":"fixture@example.invalid","password":"a b नाम"}','{"device_label":null}'] as $json){apiAssert(is_array(App\Core\Request::decodeAuthJson($json)),'Valid flat JSON');}
foreach(['','[]','null','{"x":1}','{"x":[]}','{"x":{}}','{"x":"a","x":"b"}','{"x":"a","\\u0078":"b"}',"{\"x\":\"\xff\"}",'{"x":"a",}'] as $json){apiDenied(fn()=>App\Core\Request::decodeAuthJson($json),400);}
apiDenied(fn()=>App\Core\Request::decodeAuthJson(str_repeat('x',8193)),413);
$issued=$service->login(' FIXTURE@example.invalid ',$password,'Fixture only');
apiAssert($issued['expires_in']===600 && count($issued)===7,'Exact issuance DTO and TTL');
apiAssert(App\Services\ApiAuthService::validToken($issued['access_token'],'access'),'Opaque access format');
apiAssert(App\Services\ApiAuthService::validToken($issued['refresh_token'],'refresh'),'Opaque refresh format');
apiAssert($guard->resolve($guard->token('Bearer '.$issued['access_token']))->userId===1,'Guard identity');
apiAssert(!str_contains(serialize([$repo->sessions,$repo->tokens]),$issued['access_token']) && !str_contains(serialize([$repo->sessions,$repo->tokens]),$issued['refresh_token']),'Digest only');
foreach([null,'','Basic x','Bearer ','Bearer x','Bearer '.str_repeat('x',10000),'Bearer '.$issued['refresh_token'],'Bearer '.$issued['access_token'].',other'] as $header){apiDenied(fn()=>$guard->token($header));}
foreach([['unknown@example.invalid',$password],['fixture@example.invalid','wrong'],["' OR 1=1--",$password],['<script>@example.invalid',$password]] as [$email,$pw]){apiDenied(fn()=>$service->login($email,$pw,null));}
foreach(['inactive','unverified','no_password'] as $case){$original=$repo->users[1];if($case==='inactive'){$repo->users[1]['status']='inactive';}elseif($case==='unverified'){$repo->users[1]['email_verified_at']=null;}else{$repo->users[1]['password_set_at']=null;}apiDenied(fn()=>$service->login('fixture@example.invalid',$password,null));$repo->users[1]=$original;}
$next=$service->refresh($issued['refresh_token']);apiAssert($issued['access_token']!==$next['access_token'] && $issued['refresh_token']!==$next['refresh_token'],'Both rotate');
apiDenied(fn()=>$service->authenticate($issued['access_token']));
apiAssert($service->authenticate($next['access_token'])->userId===1,'New access works');
apiDenied(fn()=>$service->refresh($issued['refresh_token']));
apiDenied(fn()=>$service->authenticate($next['access_token']));
apiAssert($repo->sessions[1]['revoked_at']!==null,'Reuse revocation committed');
$a=$service->login('fixture@example.invalid',$password,null);$b=$service->login('fixture@example.invalid',$password,null);
$service->logout($a['access_token']);apiDenied(fn()=>$service->logout($a['access_token']));apiDenied(fn()=>$service->refresh($a['refresh_token']));
apiAssert($service->authenticate($b['access_token'])->userId===1,'Other device intact');
apiDenied(fn()=>$service->logoutAll($b['access_token'],'wrong'));
$service->logoutAll($b['access_token'],$password);apiDenied(fn()=>$service->authenticate($b['access_token']));
$a=$service->login('fixture@example.invalid',$password,null);$clock->time+=600;apiDenied(fn()=>$service->authenticate($a['access_token']));
$next=$service->refresh($a['refresh_token']);apiAssert($next['expires_in']===600,'Refresh accepts expired access');
$clock->time+=14*86400;apiDenied(fn()=>$service->refresh($next['refresh_token']));
$clock->time=time()-1200;
$first=$service->login('fixture@example.invalid',$password,null);foreach(range(1,5) as $_){$service->login('fixture@example.invalid',$password,null);}apiDenied(fn()=>$service->authenticate($first['access_token']));apiAssert(count($repo->activeSessions(1,gmdate('Y-m-d H:i:s',$clock->time)))===5,'Five device cap');
$a=$service->login('fixture@example.invalid',$password,null);$repo->users[1]['password_hash']=password_hash($password,PASSWORD_DEFAULT);apiDenied(fn()=>$service->authenticate($a['access_token']));
apiAssert(!str_contains(implode('|',App\Core\Logger::$messages),$password) && !str_contains(implode('|',App\Core\Logger::$messages),$a['access_token']),'No secrets logged');
apiAssert(session_status()===PHP_SESSION_NONE,'No PHP session');
apiDenied(fn()=>$service->refresh('not-a-refresh-token'));
$unknown='ka1_'.rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=');apiDenied(fn()=>$service->authenticate($unknown));
apiAssert(App\Services\ApiAuthService::digest($unknown,'access')!==App\Services\ApiAuthService::digest($unknown,'refresh'),'Digest purpose separation');
$clock->time=time()-1200;$absolute=$service->login('fixture@example.invalid',$password,null);
$clock->time+=13*86400;$absolute=$service->refresh($absolute['refresh_token']);
$clock->time+=13*86400;$absolute=$service->refresh($absolute['refresh_token']);
$clock->time+=4*86400-30;$absolute=$service->refresh($absolute['refresh_token']);
apiAssert($absolute['expires_in']===30,'Access capped by absolute family deadline');
apiAssert($absolute['refresh_expires_at']===$absolute['session_expires_at'],'Refresh capped by absolute deadline');
$clock->time+=30;apiDenied(fn()=>$service->authenticate($absolute['access_token']));apiDenied(fn()=>$service->refresh($absolute['refresh_token']));
$clock->time=time()-1200;
$before=count($repo->sessions);$repo->failCommit=true;
try{$service->login('fixture@example.invalid',$password,null);throw new LogicException('Uncommitted credentials returned');}catch(RuntimeException $e){apiAssert(get_class($e)===RuntimeException::class,'Commit failure propagated safely');}
apiAssert(count($repo->sessions)===$before,'Failed commit rolled back issuance');
echo "API auth unit: $checks checks passed; no database/session/log writes.\n";
