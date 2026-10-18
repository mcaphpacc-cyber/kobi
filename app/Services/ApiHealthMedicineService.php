<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\HealthMedicineRepository;

final class ApiHealthMedicineService
{
    public function __construct(private ApiHealthProfileService $profiles,private HealthMedicineRepository $medicines) {}

    public function collection(ApiAuthPrincipal $principal,string $profileId): array
    {
        $profile=$this->profiles->detail($principal,$profileId);
        return array_map($this->project(...),$this->medicines->findApiByProfile((int)$profile['id']));
    }

    public function detail(ApiAuthPrincipal $principal,string $profileId,string $medicationId): array
    {
        $profile=$this->profiles->detail($principal,$profileId);
        if (!preg_match('/\A[1-9][0-9]{0,9}\z/D',$medicationId) || (strlen($medicationId)===10 && strcmp($medicationId,'2147483647')>0)) {
            throw new ApiAuthException(422,'VALIDATION_ERROR','Use a valid medication ID.');
        }
        $medicine=$this->medicines->findApiByIdInProfile((int)$medicationId,(int)$profile['id']);
        if ($medicine===null) { throw new ApiAuthException(404,'NOT_FOUND','Medication not found.'); }
        return $this->project($medicine);
    }

    private function project(array $medicine): array
    {
        if (!in_array($medicine['status'],['current','stopped','historical'],true)) {
            throw new \UnexpectedValueException('Invalid stored medication status');
        }
        return ['id'=>(string)$medicine['id'],'brand_name'=>$medicine['brand_name'],
            'generic_name'=>$medicine['generic_name'],'strength'=>$medicine['strength'],'dose'=>$medicine['dose'],
            'frequency'=>$medicine['frequency'],'route'=>$medicine['route'],'status'=>$medicine['status'],
            'started_on'=>$medicine['started_on'],'stopped_on'=>$medicine['stopped_on']];
    }
}
