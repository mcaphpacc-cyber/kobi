<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\UserRepository;

final class ApiIdentityService
{
    public function __construct(private UserRepository $users) {}

    public function forPrincipal(ApiAuthPrincipal $principal): array
    {
        $user=$this->users->findById($principal->userId);
        if (!$user || (int)$user['id']!==$principal->userId || $user['status']!=='active' || $user['email_verified_at']===null) {
            throw ApiAuthException::denied();
        }
        // Never return the repository row: contact, profile and internal fields stay private.
        return ['id'=>(string)$user['id'],'email'=>$user['email'],'status'=>$user['status'],'email_verified'=>$user['email_verified_at']!==null];
    }
}
