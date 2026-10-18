<?php
declare(strict_types=1);
namespace App\Services;

use App\Repositories\SavedSymptomCheckRepository;
use DateTimeImmutable;
use DateTimeZone;
use UnexpectedValueException;

final class ApiSavedSymptomCheckService
{
    public function __construct(private SavedSymptomCheckRepository $repository, private SavedSymptomCheckService $savedChecks) {}

    public function collection(ApiAuthPrincipal $principal, array $input): array
    {
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
        $total = $this->repository->countApiForUser($principal->userId);
        $rows = $this->repository->listApiForUser($principal->userId, $size, ($page - 1) * $size);
        return ['data' => array_map($this->metadata(...), $rows),
            'meta' => ['page' => $page, 'page_size' => $size, 'total' => $total, 'has_more' => $page * $size < $total]];
    }

    public function detail(ApiAuthPrincipal $principal, string $savedCheckId, array $input): array
    {
        if ($input !== []) { throw new ApiAuthException(400, 'BAD_REQUEST', 'Query parameters are not accepted.'); }
        if (!preg_match('/\A[1-9][0-9]{0,9}\z/D', $savedCheckId) || (strlen($savedCheckId) === 10 && strcmp($savedCheckId, '2147483647') > 0)) {
            throw new ApiAuthException(422, 'VALIDATION_ERROR', 'Use a valid saved symptom check ID.');
        }
        $row = $this->savedChecks->getForPrincipal($principal, (int) $savedCheckId);
        if ($row === null) { throw new ApiAuthException(404, 'NOT_FOUND', 'Saved symptom check not found.'); }
        $snapshot = $row['snapshot']; // Existing bounded decoder/validator; no live lookups or matching.
        $metadata = $this->metadata($row);
        if ($row['checked_at'] !== $snapshot['checked_at'] || $metadata['selected_symptom_count'] !== count($snapshot['selected'])
            || $metadata['displayed_result_count'] !== count($snapshot['results'])) {
            throw new UnexpectedValueException('Invalid stored snapshot metadata');
        }
        return $metadata + ['snapshot' => [
            'version' => $snapshot['version'], 'checked_at' => $this->timestamp($snapshot['checked_at']),
            'selected' => array_map(fn (array $item): array => ['id' => (string) $item['id'], 'label' => $item['label']], $snapshot['selected']),
            'summary' => ['total_matches' => $snapshot['summary']['total_matches'], 'displayed_matches' => $snapshot['summary']['displayed_matches']],
            'results' => array_map(fn (array $item): array => [
                'id' => (string) $item['id'], 'label' => $item['label'], 'display_percent' => $item['display_percent'],
                'match_level' => $item['match_level'], 'user_match_score' => $item['user_match_score'], 'coverage' => $item['coverage'],
                'reason' => $item['reason'], 'explanation' => $item['explanation'],
            ], $snapshot['results']),
            'disclaimer' => $snapshot['disclaimer'],
        ]];
    }

    private function positiveInteger(array $input, string $key, int $default, int $max): int
    {
        if (!array_key_exists($key, $input)) { return $default; }
        if (!preg_match('/\A[1-9][0-9]{0,4}\z/D', $input[$key]) || (int) $input[$key] > $max) {
            throw new ApiAuthException(422, 'VALIDATION_ERROR', 'Check the request parameters.');
        }
        return (int) $input[$key];
    }

    private function metadata(array $row): array
    {
        $selected = filter_var($row['selected_symptom_count'], FILTER_VALIDATE_INT);
        $displayed = filter_var($row['displayed_result_count'], FILTER_VALIDATE_INT);
        if ($selected === false || $selected < 1 || $selected > 50 || $displayed === false || $displayed < 0 || $displayed > 10) {
            throw new UnexpectedValueException('Invalid stored snapshot counts');
        }
        return ['id' => (string) $row['id'], 'checked_at' => $this->timestamp($row['checked_at']), 'saved_at' => $this->timestamp($row['saved_at']),
            'selected_symptom_count' => $selected, 'displayed_result_count' => $displayed];
    }

    private function timestamp(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC'));
        if (!$date || $date->format('Y-m-d H:i:s') !== $value) { throw new UnexpectedValueException('Invalid stored snapshot timestamp'); }
        return $date->format('Y-m-d\TH:i:s\Z');
    }
}
