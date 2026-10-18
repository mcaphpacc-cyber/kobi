<?php
declare(strict_types=1);
namespace App\Services;

class ApiAuthGuard
{
    public function __construct(private ApiAuthService $service, private ApiAuthRateLimiter $limiter) {}
    public function token(?string $header): string
    {
        if ($header===null || strlen($header)!==54 || !preg_match('/\ABearer (ka1_[A-Za-z0-9_-]{43})\z/iD',$header,$match) || !ApiAuthService::validToken($match[1],'access')) {
            $this->limiter->hit('access_failure',ApiAuthService::peer(),60,60);
            throw ApiAuthException::denied();
        }
        return $match[1];
    }
    public function resolve(string $token, bool $logout = false): ApiAuthPrincipal
    { return $this->service->authenticate($token,$logout); }
}
