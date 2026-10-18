<?php
declare(strict_types=1);
namespace App\Core {
    final class Logger {
        public static array $messages=[];
        public static function info(string $message): void { self::$messages[]=$message; }
        public static function warning(string $message): void { self::$messages[]=$message; }
        public static function error(string $message): void { self::$messages[]=$message; }
    }
}
namespace {
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
ini_set('zend.exception_ignore_args','1');
spl_autoload_register(function(string $class):void { if(str_starts_with($class,'App\\')) { require dirname(__DIR__).'/app/'.str_replace('\\','/',substr($class,4)).'.php'; } });
class ApiTestClock extends App\Services\ApiAuthClock { public int $time;public function __construct(){ $this->time=time()-1200; }public function now():int{return $this->time;} }
class ApiNoLimit extends App\Services\ApiAuthRateLimiter {
    public function __construct(){}
    public function hit(string $category,string $identity,int $limit,int $window):void{}
    public function check(string $category,string $identity,int $limit,int $window):void{}
    public function assertLoginBudget(string $email):void{}
    public function loginFailure(string $email):void{}
}
class ApiMemoryRepository extends App\Repositories\ApiAuthRepository {
    public array $users=[],$sessions=[],$tokens=[];public bool $failCommit=false;private array $snapshot=[];
    public function __construct(){}
    public function begin():void{$this->snapshot=[$this->users,$this->sessions,$this->tokens];}
    public function commit():bool{return !$this->failCommit;}
    public function rollback():bool{[$this->users,$this->sessions,$this->tokens]=$this->snapshot;return true;}
    public function userByEmail(string $email):?array{foreach($this->users as $u){if($u['email']===$email){return ['id'=>$u['id']];}}return null;}
    public function lockUser(int $id):?array{return $this->users[$id]??null;}
    public function candidate(string $digest,bool $refresh):?array{if($refresh){foreach($this->tokens as $t){if($t['token_hash']===$digest){$s=$this->sessions[$t['api_session_id']];return ['id'=>$s['id'],'user_id'=>$s['user_id']];}}}else{foreach($this->sessions as $s){if($s['access_hash']===$digest){return ['id'=>$s['id'],'user_id'=>$s['user_id']];}}}return null;}
    public function lockSession(int $id):?array{return $this->sessions[$id]??null;}
    public function lockRefresh(string $digest):?array{foreach($this->tokens as $t){if($t['token_hash']===$digest){return $t;}}return null;}
    public function activeSessions(int $userId,string $now):array{return array_values(array_filter($this->sessions,fn($s)=>$s['user_id']===$userId && $s['revoked_at']===null && $s['absolute_expires_at']>$now));}
    public function createSession(array $r):int{$id=count($this->sessions)+1;$this->sessions[$id]=['id'=>$id,'user_id'=>$r['user'],'public_id'=>$r['public'],'device_label'=>$r['label'],'created_at'=>$r['created'],'last_refreshed_at'=>$r['refreshed'],'absolute_expires_at'=>$r['absolute'],'credential_stamp'=>$r['stamp'],'access_hash'=>$r['hash'],'access_expires_at'=>$r['access'],'refresh_generation'=>1,'revoked_at'=>null];return $id;}
    public function createRefresh(int $session,int $generation,string $hash,string $issued,string $expires):void{$id=count($this->tokens)+1;$this->tokens[$id]=['id'=>$id,'api_session_id'=>$session,'generation'=>$generation,'token_hash'=>$hash,'issued_at'=>$issued,'expires_at'=>$expires,'consumed_at'=>null];}
    public function consume(int $id,string $now):bool{if($this->tokens[$id]['consumed_at']!==null){return false;}$this->tokens[$id]['consumed_at']=$now;return true;}
    public function rotate(int $id,int $generation,string $hash,string $access,string $now):void{$this->sessions[$id]['refresh_generation']=$generation;$this->sessions[$id]['access_hash']=$hash;$this->sessions[$id]['access_expires_at']=$access;$this->sessions[$id]['last_refreshed_at']=$now;}
    public function revoke(int $id,string $now,string $reason):void{$this->sessions[$id]['revoked_at']=$now;}
    public function revokeAll(int $user,string $now):void{foreach($this->sessions as $s){if($s['user_id']===$user){$this->revoke($s['id'],$now,'logout_all');}}}
}
}
