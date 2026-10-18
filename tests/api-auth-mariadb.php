<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/api-auth-fixture.php';
if(($argv[1]??'')==='--refresh-child'){
    $data=json_decode(stream_get_contents(STDIN),true,16,JSON_THROW_ON_ERROR);
    $db=apiFixtureConnect();$service=apiFixtureService($db);
    usleep(max(0,(int)(($data['at']-microtime(true))*1000000)));
    try{$service->refresh($data['refresh']);echo '200';}catch(App\Services\ApiAuthException $e){echo (string)$e->status;}
    exit;
}
$checks=0;
function ma(bool $ok,string $name):void{global $checks;if(!$ok){throw new LogicException($name);}++$checks;}
function md(callable $action):void{try{$action();throw new LogicException('Expected authentication denial');}catch(App\Services\ApiAuthException $e){ma($e->status===401,'Safe denial');}}
function constraint(callable $action):void{try{$action();throw new LogicException('Expected database constraint');}catch(PDOException $e){ma(in_array((int)$e->errorInfo[1],[1062,1452,4025],true),'Expected integrity error; actual SQLSTATE '.$e->errorInfo[0].' / MariaDB '.$e->errorInfo[1]);}}
$source=apiFixtureSource();$sp=$source->connection();$modes=$sp->query('SELECT @@session.sql_mode,@@global.sql_mode')->fetch();
$before=$sp->query('SELECT COUNT(*) FROM users')->fetchColumn();
apiFixtureCreate($source);
try{
    $db=apiFixtureConnect();$p=$db->connection();$clock=new ApiFixtureClock();$svc=apiFixtureService($db,$clock);$repo=new App\Repositories\ApiAuthRepository($db);
    ma(str_starts_with($p->query('SELECT VERSION()')->fetchColumn(),'10.4.27'),'Actual MariaDB 10.4.27');
    ma($modes===$p->query('SELECT @@session.sql_mode,@@global.sql_mode')->fetch(),'Modes preserved');
    ma((int)$p->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME='trg_users_api_revoke'")->fetchColumn()===1,'One actual trigger');
    ma((int)$p->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'user_api_%'")->fetchColumn()===2,'Two actual tables');
    ma((int)$p->query("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'user_api_%'")->fetchColumn()===2,'Two cascading foreign keys');
    ma((int)$p->query("SELECT COUNT(*) FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'user_api_%'")->fetchColumn()===9,'Nine check constraints');
    $password=bin2hex(random_bytes(18));apiFixtureUser($p,$password);apiFixtureUser($p,$password,100002);
    $email='fixture100001@example.invalid';
    foreach([['missing@example.invalid',$password],[$email,'wrong']] as [$em,$pw]){md(fn()=>$svc->login($em,$pw,null));}
    foreach(["status='inactive'","email_verified_at=NULL","password_set_at=NULL"] as $change){$p->exec('UPDATE users SET '.$change.' WHERE id=100001');md(fn()=>$svc->login($email,$password,null));$p->exec("UPDATE users SET status='active',email_verified_at=UTC_TIMESTAMP(),password_set_at=UTC_TIMESTAMP() WHERE id=100001");}
    $a=$svc->login($email,$password,'Fixture only');ma($a['expires_in']===600,'600 second access');
    ma($svc->authenticate($a['access_token'])->userId===100001,'Real digest lookup');
    $row=$p->query('SELECT * FROM user_api_sessions ORDER BY id DESC LIMIT 1')->fetch();$sid=(int)$row['id'];
    ma(strlen($row['access_hash'])===32 && strlen($row['credential_stamp'])===32,'Binary digest lengths');
    ma(!str_contains(serialize($row),$a['access_token']),'No raw bearer persisted');
    foreach(["access_hash=X'00'","refresh_generation=0","revoked_at=UTC_TIMESTAMP(),revocation_reason=NULL","revoked_at=UTC_TIMESTAMP(),revocation_reason='invalid'","access_expires_at=created_at","public_id='bad'","device_label='".str_repeat('x',81)."'","user_id=999999"] as $assignment){constraint(fn()=>$p->exec('UPDATE user_api_sessions SET '.$assignment.' WHERE id='.$sid));}
    $duplicate=$p->prepare('INSERT INTO user_api_refresh_tokens(api_session_id,generation,token_hash,issued_at,expires_at) SELECT api_session_id,generation+1,:hash,issued_at,expires_at FROM user_api_refresh_tokens LIMIT 1');
    constraint(fn()=>$duplicate->execute(['hash'=>random_bytes(32)]));
    constraint(fn()=>$p->exec("UPDATE user_api_refresh_tokens SET api_session_id=999999 WHERE api_session_id=$sid"));
    constraint(fn()=>$p->exec("UPDATE user_api_refresh_tokens SET generation=0 WHERE api_session_id=$sid"));
    constraint(fn()=>$p->exec("UPDATE user_api_refresh_tokens SET expires_at=issued_at WHERE api_session_id=$sid"));
    $clock->time+=600;md(fn()=>$svc->authenticate($a['access_token']));
    $b=$svc->refresh($a['refresh_token']);ma($b['expires_in']===600,'Refresh after expired access');md(fn()=>$svc->authenticate($a['access_token']));
    ma($svc->authenticate($b['access_token'])->userId===100001,'Rotated access works');
    md(fn()=>$svc->refresh($a['refresh_token']));md(fn()=>$svc->authenticate($b['access_token']));
    ma($p->query("SELECT revocation_reason FROM user_api_sessions WHERE id=$sid")->fetchColumn()==='refresh_reuse','Reuse revocation committed');
    $a=$svc->login($email,$password,null);$other=$svc->login('fixture100002@example.invalid',$password,null);$b=$svc->login($email,$password,null);
    $svc->logout($a['access_token']);md(fn()=>$svc->logout($a['access_token']));md(fn()=>$svc->refresh($a['refresh_token']));ma($svc->authenticate($b['access_token'])->userId===100001,'Single device isolation');
    md(fn()=>$svc->logoutAll($b['access_token'],'wrong'));$svc->logoutAll($b['access_token'],$password);md(fn()=>$svc->authenticate($b['access_token']));ma($svc->authenticate($other['access_token'])->userId===100002,'Other account isolation');
    $a=$svc->login($email,$password,null);foreach(range(1,5) as $_){$svc->login($email,$password,null);}md(fn()=>$svc->authenticate($a['access_token']));ma(count($repo->activeSessions(100001,gmdate('Y-m-d H:i:s',$clock->time)))===5,'Five live families');
    foreach(["status='inactive'","email_verified_at=NULL"] as $change){$a=$svc->login($email,$password,null);$p->exec('UPDATE users SET '.$change.' WHERE id=100001');md(fn()=>$svc->authenticate($a['access_token']));$p->exec("UPDATE users SET status='active',email_verified_at=UTC_TIMESTAMP() WHERE id=100001");md(fn()=>$svc->refresh($a['refresh_token']));}
    $a=$svc->login($email,$password,null);$update=$p->prepare('UPDATE users SET password_hash=? WHERE id=100001');$update->execute([password_hash($password,PASSWORD_DEFAULT)]);md(fn()=>$svc->authenticate($a['access_token']));
    $tokens=new App\Services\UserAuthTokenService(new App\Repositories\UserAuthTokenRepository($db));$session=new ApiFixtureSession();$auth=new App\Services\AuthService(new App\Repositories\UserRepository($db),$session,$tokens,$db);
    $a=$svc->login($email,$password,null);$auth->changePassword($password,$password,$password);md(fn()=>$svc->authenticate($a['access_token']));ma($session->get('auth_user_id')===100001,'Browser password change keeps its session');
    $a=$svc->login($email,$password,null);$auth->resetPassword(100001,$password,$password);md(fn()=>$svc->refresh($a['refresh_token']));
    $a=$svc->login($email,$password,null);$p->beginTransaction();$p->exec("UPDATE users SET status='inactive' WHERE id=100001");$p->rollBack();ma($svc->authenticate($a['access_token'])->userId===100001,'Account trigger rollback atomic');
    $clock->time+=14*86400;md(fn()=>$svc->refresh($a['refresh_token']));$clock->time=time()-1200;
    // Force a partial rotation failure; consumption must roll back with successor insertion.
    $a=$svc->login($email,$password,null);$repoRow=$repo->candidate(App\Services\ApiAuthService::digest($a['refresh_token'],'refresh'),true);$sid=(int)$repoRow['id'];
    $p->exec("UPDATE user_api_refresh_tokens SET generation=4294967295 WHERE api_session_id=$sid");$p->exec("UPDATE user_api_sessions SET refresh_generation=4294967295 WHERE id=$sid");
    try{$svc->refresh($a['refresh_token']);throw new LogicException('Expected failed generation overflow');}catch(PDOException){ma($p->query("SELECT consumed_at FROM user_api_refresh_tokens WHERE api_session_id=$sid")->fetchColumn()===null,'Failed rotation rolled consumption back');}
    $p->exec("UPDATE user_api_refresh_tokens SET generation=1 WHERE api_session_id=$sid");$p->exec("UPDATE user_api_sessions SET refresh_generation=1 WHERE id=$sid");
    // Genuine simultaneous refresh attempts, separate PHP processes/connections, no credential output.
    $at=microtime(true)+2;$processes=[];
    foreach([1,2] as $_){$proc=proc_open([PHP_BINARY,__FILE__,'--refresh-child'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fwrite($pipes[0],json_encode(['refresh'=>$a['refresh_token'],'at'=>$at]));fclose($pipes[0]);$processes[]=[$proc,$pipes];}
    $statuses=[];foreach($processes as [$proc,$pipes]){$statuses[]=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($proc);ma($err==='' && $exit===0,'Concurrent child clean');}sort($statuses);ma($statuses===['200','401'],'At most one concurrent rotation');
    ma($p->query("SELECT revocation_reason FROM user_api_sessions WHERE id=$sid")->fetchColumn()==='refresh_reuse','Losing concurrent refresh revoked winning family');
    ma((int)$p->query("SELECT COUNT(*) FROM user_api_refresh_tokens WHERE api_session_id=$sid AND consumed_at IS NULL")->fetchColumn()===1,'One successor lineage');
    $a=$svc->login('fixture100002@example.invalid',$password,null);$p->exec('DELETE FROM users WHERE id=100002');md(fn()=>$svc->refresh($a['refresh_token']));ma((int)$p->query('SELECT COUNT(*) FROM user_api_sessions WHERE user_id=100002')->fetchColumn()===0,'Deletion cascades');
    ma($repo->cleanup(gmdate('Y-m-d H:i:s',time()-7*86400))===0,'Cleanup preserves young/live replay history');
    ma(session_status()===PHP_SESSION_NONE,'No browser session');
    ma($modes===$p->query('SELECT @@session.sql_mode,@@global.sql_mode')->fetch(),'SQL modes unchanged at end');
}finally{apiFixtureDrop($source);}
$exists=$sp->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');$exists->execute([API_TEST_DATABASE]);ma((int)$exists->fetchColumn()===0,'Disposable database removed');
ma($before===$sp->query('SELECT COUNT(*) FROM users')->fetchColumn(),'Normal account count unchanged');
echo "API auth MariaDB: $checks checks passed; isolated target removed; normal KOBI unchanged.\n";
