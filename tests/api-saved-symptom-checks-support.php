<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

/** Synthetic frozen history, deliberately different from current matcher output. */
function savedApiSnapshot(): array
{
    $result = ['id' => 90, 'label' => 'Historical नाम <match>', 'ranking_score' => 12.25,
        'user_match_score' => 75.5, 'coverage' => 40.25, 'display_percent' => 13, 'match_level' => 'Weak Match',
        'matched' => [['id' => 2, 'label' => 'Historical cough']], 'missing_preview' => [], 'missing_count' => 0,
        'reason' => ['Original reason two', 'Original reason one'], 'explanation' => ['Original explanation'],
        'internal_secret' => 'PRIVATE'];
    return ['version' => 1, 'checked_at' => '2026-01-01 01:02:03',
        'selected' => [['id' => 2, 'label' => 'Historical cough', 'owner_id' => 'PRIVATE'], ['id' => 1, 'label' => 'Historical fever']],
        'summary' => ['total_matches' => 9, 'displayed_matches' => 2, 'internal' => 'PRIVATE'],
        'results' => [$result, array_replace($result, ['id' => 3, 'label' => 'Earlier ID, second result', 'display_percent' => 99])],
        'disclaimer' => App\Services\SavedSymptomCheckService::DISCLAIMER, 'unknown' => 'PRIVATE'];
}

function savedApiRow(int $id = 1, int $owner = 1, ?array $snapshot = null): array
{
    $snapshot ??= savedApiSnapshot();
    return ['id' => $id, 'user_id' => $owner, 'check_key' => str_pad((string) $id, 64, '0', STR_PAD_LEFT),
        'checked_at' => $snapshot['checked_at'], 'saved_at' => '2026-01-02 04:05:06',
        'selected_symptom_count' => count($snapshot['selected']), 'displayed_result_count' => count($snapshot['results']),
        'snapshot_json' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)];
}

function savedApiExpected(int $id = 1): array
{
    return ['id' => (string) $id, 'checked_at' => '2026-01-01T01:02:03Z', 'saved_at' => '2026-01-02T04:05:06Z',
        'selected_symptom_count' => 2, 'displayed_result_count' => 2, 'snapshot' => [
            'version' => 1, 'checked_at' => '2026-01-01T01:02:03Z',
            'selected' => [['id' => '2', 'label' => 'Historical cough'], ['id' => '1', 'label' => 'Historical fever']],
            'summary' => ['total_matches' => 9, 'displayed_matches' => 2],
            'results' => [
                ['id' => '90', 'label' => 'Historical नाम <match>', 'display_percent' => 13, 'match_level' => 'Weak Match',
                    'user_match_score' => 75.5, 'coverage' => 40.25, 'reason' => ['Original reason two', 'Original reason one'], 'explanation' => ['Original explanation']],
                ['id' => '3', 'label' => 'Earlier ID, second result', 'display_percent' => 99, 'match_level' => 'Weak Match',
                    'user_match_score' => 75.5, 'coverage' => 40.25, 'reason' => ['Original reason two', 'Original reason one'], 'explanation' => ['Original explanation']],
            ], 'disclaimer' => App\Services\SavedSymptomCheckService::DISCLAIMER,
        ]];
}

class SavedApiUnavailableChecker extends App\Services\SymptomCheckerService
{
    public function __construct() {}
    public function match(array $ids): array { throw new LogicException('Historical read called matcher'); }
    public function recordSearch(array $ids): void { throw new LogicException('Historical read changed counters'); }
    public function getSymptomsByIds(array $ids): array { throw new LogicException('Historical read consulted current labels'); }
}
