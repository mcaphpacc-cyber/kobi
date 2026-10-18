<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Logger;
use App\Repositories\ApiAuthRepository;

class ApiAuthService
{
    public function __construct(private ApiAuthRepository $repository, private AuthService $passwords, private ApiAuthClock $clock, private ApiAuthRateLimiter $limiter) {}

    public static function digest(string $token, string $purpose): string
    { return hash('sha256',"kobi-api-v1\0".$purpose."\0".$token,true); }
    public static function validToken(string $token, string $purpose): bool
    { return preg_match('/\A'.($purpose==='access'?'ka1_':'kr1_').'[A-Za-z0-9_-]{42}[AEIMQUYcgkosw048]\z/D',$token)===1; }
    public static function peer(): string { return (string)($_SERVER['REMOTE_ADDR']??'unknown'); }
    private function token(string $purpose): string
    { return ($purpose==='access'?'ka1_':'kr1_').rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'='); }
    private function date(int $time): string { return gmdate('Y-m-d H:i:s',$time); }
    private function timestamp(string $date): int { return (new \DateTimeImmutable($date,new \DateTimeZone('UTC')))->getTimestamp(); }
    private function stamp(array $user): string { return hash('sha256',$user['password_hash'],true); }

    private function transaction(callable $operation): mixed
    {
        $this->repository->begin();
        try {
            $result=$operation();
            if (!$this->repository->commit()) { throw new \RuntimeException('Unable to commit API transaction'); }
        }
        catch (\Throwable $e) { $this->repository->rollback(); throw $e; }
        // A replay denial is deliberately returned by the operation to commit revocation first.
        if ($result instanceof ApiAuthException) { throw $result; }
        return $result;
    }
    private function checkSession(?array $user, ?array $session): void
    {
        if (!$user || !$session || (int)$session['user_id']!==(int)$user['id'] || $user['status']!=='active' || $user['email_verified_at']===null || $user['password_set_at']===null || $session['revoked_at']!==null || $this->timestamp($session['absolute_expires_at'])<=$this->clock->now() || !hash_equals($session['credential_stamp'],$this->stamp($user))) {
            throw ApiAuthException::denied();
        }
    }
    private function verify(?array $user, string $password): array
    {
        try { return $this->passwords->verifyPasswordRecord($user,$password,true); }
        catch (\RuntimeException $e) {
            if (get_class($e)!==\RuntimeException::class) { throw $e; }
            throw new ApiAuthException(401,'AUTHENTICATION_FAILED','Unable to sign in with these credentials.');
        }
    }
    public function login(string $email, string $password, ?string $label): array
    {
        $email=strtolower(trim($email));
        $this->limiter->hit('login_ip',self::peer(),30,900);
        $this->limiter->assertLoginBudget($email);
        $candidate=$this->repository->userByEmail($email);
        try {
            $result=$this->transaction(function()use($candidate,$email,$password,$label):array {
                $user=$candidate ? $this->repository->lockUser((int)$candidate['id']) : null;
                if ($user && strtolower(trim($user['email']))!==$email) { $user=null; }
                $user=$this->verify($user,$password);
                $now=$this->clock->now(); $date=$this->date($now);
                $sessions=$this->repository->activeSessions((int)$user['id'],$date);
                while (count($sessions)>=5) { $old=array_shift($sessions);$this->repository->revoke((int)$old['id'],$date,'device_limit'); }
                $access=$this->token('access');$refresh=$this->token('refresh');
                $public=bin2hex(random_bytes(16)); $absolute=$now+30*86400;
                $id=$this->repository->createSession(['public'=>$public,'user'=>(int)$user['id'],'label'=>$label,'created'=>$date,'refreshed'=>$date,'absolute'=>$this->date($absolute),'stamp'=>$this->stamp($user),'hash'=>self::digest($access,'access'),'access'=>$this->date($now+600)]);
                $this->repository->createRefresh($id,1,self::digest($refresh,'refresh'),$date,$this->date($now+14*86400));
                return $this->response($access,$refresh,$now+600,$now+14*86400,$public,$absolute);
            });
        } catch (ApiAuthException $e) {
            if ($e->errorCode==='AUTHENTICATION_FAILED') { $this->limiter->loginFailure($email);Logger::warning('API auth login_failed'); }
            throw $e;
        }
        Logger::info('API auth login_succeeded');
        return $result;
    }
    private function response(string $access,string $refresh,int $expiry,int $refreshExpiry,string $public,int $absolute): array
    {
        return ['token_type'=>'Bearer','access_token'=>$access,'expires_in'=>$expiry-$this->clock->now(),'refresh_token'=>$refresh,'refresh_expires_at'=>gmdate('Y-m-d\TH:i:s\Z',$refreshExpiry),'session_id'=>$public,'session_expires_at'=>gmdate('Y-m-d\TH:i:s\Z',$absolute)];
    }
    public function refresh(string $token): array
    {
        $this->limiter->hit('refresh_ip',self::peer(),60,60);
        if (!self::validToken($token,'refresh')) { throw ApiAuthException::denied(); }
        $hash=self::digest($token,'refresh'); $candidate=$this->repository->candidate($hash,true);
        if (!$candidate) { throw ApiAuthException::denied(); }
        $this->limiter->hit('refresh_family',(string)$candidate['id'],30,60);
        $result=$this->transaction(function()use($hash,$candidate):array|ApiAuthException {
            $user=$this->repository->lockUser((int)$candidate['user_id']);
            $session=$this->repository->lockSession((int)$candidate['id']);
            $row=$this->repository->lockRefresh($hash);
            $this->checkSession($user,$session);
            if (!$row || (int)$row['api_session_id']!==(int)$session['id']) { throw ApiAuthException::denied(); }
            $now=$this->clock->now();$date=$this->date($now);
            // Retained consumed hashes detect replay even when that old token's individual TTL has passed.
            if ($row['consumed_at']!==null || (int)$row['generation']!==(int)$session['refresh_generation']) {
                $this->repository->revoke((int)$session['id'],$date,'refresh_reuse');
                Logger::warning('API auth refresh_reuse');
                return ApiAuthException::denied();
            }
            if ($this->timestamp($row['expires_at'])<=$now) { throw ApiAuthException::denied(); }
            if (!$this->repository->consume((int)$row['id'],$date)) { throw ApiAuthException::denied(); }
            $generation=(int)$row['generation']+1; $absolute=$this->timestamp($session['absolute_expires_at']);
            $accessExpiry=min($now+600,$absolute); $refreshExpiry=min($now+14*86400,$absolute);
            $access=$this->token('access');$refresh=$this->token('refresh');
            $this->repository->createRefresh((int)$session['id'],$generation,self::digest($refresh,'refresh'),$date,$this->date($refreshExpiry));
            $this->repository->rotate((int)$session['id'],$generation,self::digest($access,'access'),$this->date($accessExpiry),$date);
            return $this->response($access,$refresh,$accessExpiry,$refreshExpiry,$session['public_id'],$absolute);
        });
        Logger::info('API auth refresh_rotated');return $result;
    }
    private function accessOperation(string $token, callable $operation): mixed
    {
        if (!self::validToken($token,'access')) { throw ApiAuthException::denied(); }
        $hash=self::digest($token,'access');$candidate=$this->repository->candidate($hash,false);
        if (!$candidate) { throw ApiAuthException::denied(); }
        return $this->transaction(function()use($candidate,$hash,$operation):mixed {
            $user=$this->repository->lockUser((int)$candidate['user_id']);$session=$this->repository->lockSession((int)$candidate['id']);
            $this->checkSession($user,$session);
            if (!hash_equals($session['access_hash'],$hash) || $this->timestamp($session['access_expires_at'])<=$this->clock->now()) { throw ApiAuthException::denied(); }
            return $operation($user,$session);
        });
    }
    public function authenticate(string $token, bool $logout = false): ApiAuthPrincipal
    {
        $this->limiter->check('access_failure',self::peer(),60,60);
        try {
            $principal=$this->accessOperation($token,fn(array $user,array $session)=>new ApiAuthPrincipal((int)$user['id'],(int)$session['id']));
        } catch (ApiAuthException $e) { $this->limiter->hit('access_failure',self::peer(),60,60);throw $e; }
        if (!$logout) { $this->limiter->hit('access_family',(string)$principal->sessionId,120,60); }
        return $principal;
    }
    public function logout(string $token): void
    {
        $this->accessOperation($token,function(array $user,array $session):void { $this->repository->revoke((int)$session['id'],$this->date($this->clock->now()),'logout'); });
        Logger::info('API auth logout');
    }
    public function logoutAll(string $token, string $password): void
    {
        $this->accessOperation($token,function(array $user)use($password):void {
            $this->limiter->assertLoginBudget(strtolower($user['email']));
            try { $this->verify($user,$password); }
            catch (ApiAuthException $e) { $this->limiter->loginFailure(strtolower($user['email']));throw $e; }
            $this->repository->revokeAll((int)$user['id'],$this->date($this->clock->now()));
        });
        Logger::info('API auth logout_all');
    }
}
