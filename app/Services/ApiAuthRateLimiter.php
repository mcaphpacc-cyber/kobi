<?php
declare(strict_types=1);
namespace App\Services;

/** Single-host, bounded private-file counters. No credential or submitted address is persisted. */
class ApiAuthRateLimiter
{
    public function __construct(private ApiAuthClock $clock) {}

    public function hit(string $category, string $identity, int $limit, int $window): void
    {
        $key=(string)(config('api_auth')['rate_key']??'');
        $directory=rtrim((string)config('storage_path'), '/\\').'/cache/api-auth';
        if (strlen($key)<32) { throw new ApiAuthException(503,'SERVICE_UNAVAILABLE','Unable to complete the request.'); }
        $handle=null;
        try {
            if (!is_dir($directory) && !mkdir($directory,0700,true) && !is_dir($directory)) { throw new \RuntimeException(); }
            $handle=fopen($directory.'/counters.json','c+b');
            if ($handle===false || !flock($handle,LOCK_EX)) { throw new \RuntimeException(); }
            $size=fstat($handle)['size'];
            if ($size>2097152) { throw new \RuntimeException(); }
            $raw=stream_get_contents($handle);
            $state=$raw==='' ? [] : json_decode($raw,true,16,JSON_THROW_ON_ERROR);
            if (!is_array($state)) { throw new \RuntimeException(); }
            $now=$this->clock->now();
            foreach ($state as $id=>$counter) {
                if (!is_array($counter) || !isset($counter['until'],$counter['count'])) { throw new \RuntimeException(); }
                if ($counter['until']<=$now) { unset($state[$id]); }
            }
            $id=hash_hmac('sha256',$category."\0".$identity,$key);
            if (!isset($state[$id]) && count($state)>=10000) { throw new \RuntimeException(); }
            $counter=$state[$id]??['until'=>$now+$window,'count'=>0];
            if ($counter['count']>=$limit) { throw new ApiAuthException(429,'RATE_LIMITED','Too many requests. Please try again later.',max(1,$counter['until']-$now)); }
            if ($this->checkOnly) { return; }
            ++$counter['count']; $state[$id]=$counter;
            $encoded=json_encode($state,JSON_THROW_ON_ERROR);
            rewind($handle);
            if (fwrite($handle,$encoded)!==strlen($encoded) || !ftruncate($handle,strlen($encoded)) || !fflush($handle)) { throw new \RuntimeException(); }
        } catch (ApiAuthException $e) { throw $e; }
        catch (\Throwable) { throw new ApiAuthException(503,'SERVICE_UNAVAILABLE','Unable to complete the request.'); }
        finally { if (is_resource($handle)) { flock($handle,LOCK_UN); fclose($handle); } }
    }

    public function assertLoginBudget(string $email): void
    {
        // Checking with a zero-cost read must not consume successful logins' failure budget.
        $this->checkFailures($email);
    }

    public function check(string $category, string $identity, int $limit, int $window): void
    {
        $this->checkOnly = true;
        try { $this->hit($category,$identity,$limit,$window); }
        finally { $this->checkOnly = false; }
    }

    public function loginFailure(string $email): void { $this->hit('login_failure',$email,5,900); }

    private function checkFailures(string $email): void
    {
        // Same locking/persistence path, with an explicit read-only budget operation.
        $this->checkOnly = true;
        try { $this->hit('login_failure',$email,5,900); }
        finally { $this->checkOnly = false; }
    }
    private bool $checkOnly = false;
}
