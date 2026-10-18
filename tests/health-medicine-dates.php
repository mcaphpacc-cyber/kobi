<?php

declare(strict_types=1);

// Database-free medication date regression checks.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Repositories\HealthMedicineRepository;
use App\Services\HealthMedicineService;
use App\Services\HealthProfileService;

class MedicineDateMemoryRepository extends HealthMedicineRepository
{
    public array $record = [];
    public function __construct() {}
    public function create(array $data): int
    {
        $this->record = ['id' => 1] + $data;
        return 1;
    }
    public function findById(int $id): ?array { return $this->record ?: null; }
    public function update(int $id, array $data): bool
    {
        $this->record = array_replace($this->record, $data);
        return true;
    }
}

class MedicineDateProfiles extends HealthProfileService
{
    public function __construct() {}
    public function getProfile(int $profileId): array
    {
        return ['id' => $profileId, 'role' => 'owner'];
    }
}

$today = new DateTimeImmutable('today');
$date = $today->format('Y-m-d');
$yesterday = $today->modify('-1 day')->format('Y-m-d');
$tomorrow = $today->modify('+1 day')->format('Y-m-d');
$futureError = 'Medical dates cannot be in the future.';
$cases = [
    'today start' => [['started_on' => $date], null],
    'today stop' => [['status' => 'stopped', 'started_on' => $yesterday, 'stopped_on' => $date], null],
    'same-day start/stop' => [['status' => 'stopped', 'started_on' => $date, 'stopped_on' => $date], null],
    'yesterday' => [['started_on' => $yesterday], null],
    'tomorrow start' => [['started_on' => $tomorrow], $futureError],
    'tomorrow stop' => [['status' => 'stopped', 'stopped_on' => $tomorrow], $futureError],
    'impossible date' => [['started_on' => '2026-02-30'], 'Invalid date format.'],
    'malformed date' => [['stopped_on' => 'not-a-date'], 'Invalid date format.'],
    'noncanonical date' => [['started_on' => '2026-2-01'], 'Invalid date format.'],
    'reversed dates' => [['status' => 'stopped', 'started_on' => $date, 'stopped_on' => $yesterday], 'Stopped date cannot be earlier than started date.'],
    'current with stop date' => [['stopped_on' => $date], 'A current medicine cannot have a stopped date.'],
    'stopped without stop date' => [['status' => 'stopped'], 'Stopped date is required for a stopped medicine.'],
    'optional empty dates' => [['started_on' => '', 'stopped_on' => null], null],
];

$passed = 0;
foreach (['create', 'update'] as $operation) {
    foreach ($cases as $label => [$input, $expectedError]) {
        $repository = new MedicineDateMemoryRepository();
        $service = new HealthMedicineService($repository, new MedicineDateProfiles());
        if ($operation === 'update') {
            $service->createMedicine(1, ['brand_name' => 'Synthetic medicine']);
        }
        $before = $repository->record;
        $data = ['brand_name' => 'Synthetic medicine'] + $input;
        $error = null;
        try {
            if ($operation === 'create') {
                $service->createMedicine(1, $data);
            } else {
                $service->updateMedicine(1, 1, $data);
            }
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
        $valid = $error === $expectedError;
        if ($expectedError === null) {
            $valid = $valid
                && $repository->record['started_on'] === (($input['started_on'] ?? '') ?: null)
                && $repository->record['stopped_on'] === (($input['stopped_on'] ?? '') ?: null);
        } else {
            $valid = $valid && $repository->record === $before;
        }
        if (!$valid) {
            fwrite(STDERR, "FAIL {$operation}: {$label} (" . ($error ?? 'accepted') . ")\n");
            exit(1);
        }
        ++$passed;
    }
}

echo "{$passed} database-free medication date checks passed.\n";
