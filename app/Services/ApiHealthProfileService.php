<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\HealthProfileRepository;

final class ApiHealthProfileService
{
    public function __construct(private HealthProfileRepository $profiles) {}

    public function accessible(ApiAuthPrincipal $principal): array
    {
        return array_map($this->project(...),$this->profiles->findAccessibleByUser($principal->userId));
    }

    public function detail(ApiAuthPrincipal $principal,string $profileId): array
    {
        return $this->project($this->requireAccessible($principal,$profileId));
    }

    /** Internal authorization record; controllers must return an explicit projection. */
    public function requireAccessible(ApiAuthPrincipal $principal,string $profileId): array
    {
        if (!preg_match('/\A[1-9][0-9]{0,9}\z/D',$profileId) || (strlen($profileId)===10 && strcmp($profileId,'2147483647')>0)) {
            throw new ApiAuthException(422,'VALIDATION_ERROR','Use a valid profile ID.');
        }
        $profile=$this->profiles->findAccessible((int)$profileId,$principal->userId);
        if ($profile===null) { throw new ApiAuthException(404,'NOT_FOUND','Health profile not found.'); }
        if (!in_array($profile['profile_type'],['self','family','dependent'],true)) {
            throw new \UnexpectedValueException('Invalid stored profile type');
        }
        return $profile;
    }

    private function project(array $profile): array
    {
        if (!in_array($profile['profile_type'],['self','family','dependent'],true)) {
            throw new \UnexpectedValueException('Invalid stored profile type');
        }
        // Deliberately omit demographics, account IDs, memberships and medical data.
        return ['id'=>(string)$profile['id'],'full_name'=>$profile['full_name'],'profile_type'=>$profile['profile_type']];
    }
}
