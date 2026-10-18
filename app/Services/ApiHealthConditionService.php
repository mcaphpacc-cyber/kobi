<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\HealthConditionRepository;

final class ApiHealthConditionService
{
    public function __construct(private ApiHealthProfileService $profiles, private HealthConditionRepository $conditions) {}

    public function collection(ApiAuthPrincipal $principal, string $profileId, array $input): array
    {
        $profile = $this->profiles->detail($principal, $profileId);
        if (array_diff(array_keys($input), ['page', 'page_size'])) {
            throw new ApiAuthException(422, 'VALIDATION_ERROR', 'Unsupported request parameter.');
        }
        foreach ($input as $value) {
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
                throw new ApiAuthException(400, 'BAD_REQUEST', 'Check the request parameters.');
            }
        }
        $page = $this->positiveInteger($input, 'page', 1, 10000);
        $size = $this->positiveInteger($input, 'page_size', 20, 50);
        $id = (int) $profile['id'];
        $total = $this->conditions->countApiByProfile($id);
        $rows = $this->conditions->listApiByProfile($id, $size, ($page - 1) * $size);
        return ['data' => array_map($this->project(...), $rows),
            'meta' => ['page' => $page, 'page_size' => $size, 'total' => $total, 'has_more' => $page * $size < $total]];
    }

    public function detail(ApiAuthPrincipal $principal, string $profileId, string $conditionId, array $input): array
    {
        $profile = $this->profiles->detail($principal, $profileId);
        if ($input !== []) { throw new ApiAuthException(400, 'BAD_REQUEST', 'Query parameters are not accepted.'); }
        if (!preg_match('/\A[1-9][0-9]{0,9}\z/D', $conditionId) || (strlen($conditionId) === 10 && strcmp($conditionId, '2147483647') > 0)) {
            throw new ApiAuthException(422, 'VALIDATION_ERROR', 'Use a valid condition ID.');
        }
        $condition = $this->conditions->findApiByIdInProfile((int) $conditionId, (int) $profile['id']);
        if ($condition === null) { throw new ApiAuthException(404, 'NOT_FOUND', 'Medical condition not found.'); }
        return $this->project($condition);
    }

    private function positiveInteger(array $input, string $key, int $default, int $max): int
    {
        if (!array_key_exists($key, $input)) { return $default; }
        if (!preg_match('/\A[1-9][0-9]{0,4}\z/D', $input[$key]) || (int) $input[$key] > $max) {
            throw new ApiAuthException(422, 'VALIDATION_ERROR', 'Check the request parameters.');
        }
        return (int) $input[$key];
    }

    private function project(array $condition): array
    {
        if (!in_array($condition['status'], ['active', 'resolved', 'chronic', 'historical'], true)) {
            throw new \UnexpectedValueException('Invalid stored condition status');
        }
        return ['id' => (string) $condition['id'], 'condition_name' => $condition['condition_name'],
            'status' => $condition['status'], 'diagnosed_on' => $this->date($condition['diagnosed_on']),
            'resolved_on' => $this->date($condition['resolved_on'])];
    }

    private function date(?string $date): ?string
    {
        if ($date !== null && (!preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/D', $date)
            || !checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)))) {
            throw new \UnexpectedValueException('Invalid stored condition date');
        }
        return $date;
    }
}
