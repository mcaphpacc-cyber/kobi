<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\MedicationScheduleRepository;

final class ApiMedicationScheduleService
{
    public function __construct(private ApiHealthProfileService $profiles, private MedicationScheduleRepository $repository,
        private MedicationScheduleService $schedules) {}

    public function collection(ApiAuthPrincipal $principal, string $profileId, array $input, bool $dueToday = false): array
    {
        $profile=$this->profiles->requireAccessible($principal,$profileId);
        if (array_diff(array_keys($input),['page','page_size'])) { throw new ApiAuthException(422,'VALIDATION_ERROR','Unsupported request parameter.'); }
        foreach ($input as $value) {
            if (!is_string($value) || !mb_check_encoding($value,'UTF-8')) { throw new ApiAuthException(400,'BAD_REQUEST','Check the request parameters.'); }
        }
        $page=$this->integer($input,'page',1,10000); $size=$this->integer($input,'page_size',20,50); $offset=($page-1)*$size;
        if ($dueToday) {
            // Recheck principal/profile access in the canonical service; never page candidates before eligibility/sort.
            $all=$this->schedules->dueTodayForPrincipal($principal,$profileId);
            $total=count($all); $rows=array_slice($all,$offset,$size);
        } else {
            $total=$this->repository->countApiSlots((int)$profile['id']);
            $rows=$this->repository->listApiSlots((int)$profile['id'],$size,$offset);
        }
        $meta=['page'=>$page,'page_size'=>$size,'total'=>$total,'has_more'=>$page*$size<$total];
        if ($dueToday) { $meta['can_record_checkoffs']=in_array($profile['role']??null,['owner','editor'],true); }
        return ['data'=>array_map(fn(array $row):array=>$this->project($row,$dueToday),$rows),'meta'=>$meta];
    }

    public function checkoff(ApiAuthPrincipal $principal, string $profileId, string $scheduleId, array $input): array
    {
        // Authorize the profile before looking up any child resource.
        $this->profiles->requireAccessible($principal,$profileId);
        if (!preg_match('/\A[1-9][0-9]{0,9}\z/D',$scheduleId)
            || (strlen($scheduleId)===10 && strcmp($scheduleId,'2147483647')>0)) {
            throw new ApiAuthException(422,'VALIDATION_ERROR','Use a valid schedule ID.');
        }
        if (array_keys($input)!==['state'] || !in_array($input['state'],['taken','skipped'],true)) {
            throw new ApiAuthException(422,'VALIDATION_ERROR','Choose Taken or Skipped.');
        }
        return $this->schedules->markTodayForPrincipal($principal,$profileId,(int)$scheduleId,$input['state'],
            fn(array $row):array=>$this->project($row,true));
    }

    private function integer(array $input, string $key, int $default, int $max): int
    {
        if (!array_key_exists($key,$input)) { return $default; }
        if (!preg_match('/\A[1-9][0-9]{0,4}\z/D',$input[$key]) || (int)$input[$key]>$max) {
            throw new ApiAuthException(422,'VALIDATION_ERROR','Check the request parameters.');
        }
        return (int)$input[$key];
    }

    private function project(array $row, bool $dueToday): array
    {
        if (!preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9]\z/D',$row['local_time'])
            || !in_array($row['timezone'],\DateTimeZone::listIdentifiers(),true)) {
            throw new \UnexpectedValueException('Invalid stored schedule time or timezone');
        }
        $item=['id'=>(string)$row['id'],'health_medicine_id'=>(string)$row['health_medicine_id'],
            'local_time'=>$row['local_time'],'timezone'=>$row['timezone']];
        if ($dueToday) {
            if (!in_array($row['state'],[null,'taken','skipped'],true)) { throw new \UnexpectedValueException('Invalid stored checkoff state'); }
            return $item+['scheduled_on'=>$this->date($row['scheduled_on']),'state'=>$row['state']];
        }
        if (!in_array($row['is_active'],[0,1,'0','1'],true)) { throw new \UnexpectedValueException('Invalid stored schedule flag'); }
        return $item+['starts_on'=>$this->date($row['starts_on']),
            'ends_on'=>$row['ends_on']===null?null:$this->date($row['ends_on']),'is_active'=>(int)$row['is_active']===1];
    }

    private function date(string $value): string
    {
        if (!preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/D',$value)
            || !checkdate((int)substr($value,5,2),(int)substr($value,8,2),(int)substr($value,0,4))) {
            throw new \UnexpectedValueException('Invalid stored schedule date');
        }
        return $value;
    }
}
