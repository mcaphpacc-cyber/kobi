<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/api-auth-unit-support.php';
$directory=dirname(__DIR__).'/storage/cache/api-auth-unit-'.bin2hex(random_bytes(8));
$apiConfig=['storage_path'=>$directory,'api_auth'=>['rate_key'=>bin2hex(random_bytes(32))]];
function config(?string $key=null):mixed{global $apiConfig;return $key===null?$apiConfig:($apiConfig[$key]??null);}
$checks=0;
function ra(bool $ok,string $name):void{global $checks;if(!$ok){throw new LogicException($name);}++$checks;}
function rd(callable $call,int $code):void{try{$call();throw new LogicException('Expected limiter denial');}catch(App\Services\ApiAuthException $e){ra($e->status===$code,'Limiter status');if($code===429){ra($e->retryAfter>0,'Retry-After');}}}
$clock=new ApiTestClock();$limiter=new App\Services\ApiAuthRateLimiter($clock);$file=$directory.'/cache/api-auth/counters.json';
try{
    $email='limiter-fixture@example.invalid';
    foreach(range(1,5) as $_){$limiter->assertLoginBudget($email);$limiter->loginFailure($email);}
    rd(fn()=>$limiter->assertLoginBudget($email),429);
    $limiter->assertLoginBudget('other@example.invalid');ra(true,'Account isolation');
    ra(!str_contains(file_get_contents($file),$email),'Pseudonymized keys');
    $clock->time+=900;$limiter->assertLoginBudget($email);$limiter->loginFailure($email);ra(true,'Window expiry');
    $limiter->hit('refresh_ip','127.0.0.1',2,60);$limiter->hit('refresh_ip','127.0.0.1',2,60);rd(fn()=>$limiter->hit('refresh_ip','127.0.0.1',2,60),429);
    ra(!str_contains(file_get_contents($file),'127.0.0.1'),'No raw IP');
    $apiConfig['api_auth']['rate_key']='';rd(fn()=>$limiter->hit('x','x',1,60),503);$apiConfig['api_auth']['rate_key']=bin2hex(random_bytes(32));
    file_put_contents($file,'broken');rd(fn()=>$limiter->hit('x','x',1,60),503);
    file_put_contents($file,str_repeat('x',2097153));rd(fn()=>$limiter->hit('x','x',1,60),503);
    $full=[];foreach(range(1,10000) as $id){$full['fixture'.$id]=['count'=>1,'until'=>$clock->time+100];}file_put_contents($file,json_encode($full));rd(fn()=>$limiter->hit('x','new',1,60),503);
    $clock->time+=100;$limiter->hit('x','new',1,60);ra(count(json_decode(file_get_contents($file),true))===1,'Expired counters pruned, capacity recovers');
}finally{if(is_file($file)){unlink($file);}if(is_dir(dirname($file))){rmdir(dirname($file));rmdir($directory.'/cache');rmdir($directory);}}
echo "API auth limiter: $checks checks passed; private disposable files removed.\n";
