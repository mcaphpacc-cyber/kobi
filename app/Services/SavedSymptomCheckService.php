<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Session;
use App\Repositories\SavedSymptomCheckRepository;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

class SavedSymptomCheckService
{
    public const DISCLAIMER = 'KOBI Symptom Checker is an educational tool. It does not provide a medical diagnosis.';
    private const PENDING = 'pending_symptom_check';

    public function __construct(
        private SavedSymptomCheckRepository $repository,
        private AuthService $authService,
        private Session $session,
        private SymptomCheckerService $checker
    ) {}

    public function capture(array $response): ?array
    {
        $this->session->remove(self::PENDING);
        if (!$this->authService->check() || $this->authService->user() === null) {
            return null;
        }
        try {
            if (!isset($response['summary']['selectedSymptoms'], $response['summary']['generatedAt'], $response['summary']['totalMatches'], $response['summary']['displayedMatches']) || !is_array($response['results'] ?? null)) {
                throw new RuntimeException('Invalid snapshot.');
            }
            $ids = $response['summary']['selectedSymptoms'];
            if (!is_array($ids) || count($ids) < 1 || count($ids) > 50 || count(array_unique($ids, SORT_REGULAR)) !== count($ids)) {
                throw new RuntimeException('Invalid selected symptoms.');
            }
            foreach ($ids as $id) { $this->identifier($id); }
            $labels = array_column($this->checker->getSymptomsByIds($ids), 'symptom_en', 'id');
            $selected = [];
            foreach ($ids as $id) { $selected[] = ['id' => $id, 'label' => $this->label($labels[$id] ?? null)]; }
            $snapshot = [
                'version' => 1,
                'checked_at' => (new DateTimeImmutable($response['summary']['generatedAt']))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                'selected' => $selected,
                'summary' => ['total_matches' => $response['summary']['totalMatches'], 'displayed_matches' => $response['summary']['displayedMatches']],
                'results' => [], 'disclaimer' => self::DISCLAIMER,
            ];
            foreach ($response['results'] as $item) {
                if (!is_array($item) || !isset($item['disease']['id'], $item['disease']['disease_en'], $item['rankingScore'], $item['userMatchScore'], $item['coverage'])
                    || !is_array($item['matchedSymptoms'] ?? null) || !is_array($item['missingSymptoms'] ?? null)) {
                    throw new RuntimeException('Invalid snapshot.');
                }
                foreach (array_merge($item['matchedSymptoms'], $item['missingSymptoms']) as $symptom) {
                    if (!is_array($symptom) || !isset($symptom['id'], $symptom['symptom_en'])) { throw new RuntimeException('Invalid snapshot.'); }
                }
                $matched = array_map(fn ($s) => ['id' => $s['id'], 'label' => $this->label($s['symptom_en'])], $item['matchedSymptoms']);
                $missing = array_map(fn ($s) => ['id' => $s['id'], 'label' => $this->label($s['symptom_en'])], array_slice($item['missingSymptoms'], 0, 3));
                $count = count($matched); $missingCount = count($item['missingSymptoms']);
                $score = $item['rankingScore'];
                // Freeze the existing frontend presentation; never rerun it on historical reads.
                $level = $score >= 90 ? 'Very High Match' : ($score >= 75 ? 'High Match' : ($score >= 60 ? 'Moderate Match' : ($score >= 40 ? 'Low Match' : 'Weak Match')));
                $snapshot['results'][] = [
                    'id' => $item['disease']['id'], 'label' => $item['disease']['disease_en'],
                    'ranking_score' => $score, 'user_match_score' => $item['userMatchScore'], 'coverage' => $item['coverage'],
                    'display_percent' => (int) floor($score + 0.5), 'match_level' => $level,
                    'matched' => $matched, 'missing_preview' => $missing, 'missing_count' => $missingCount,
                    'reason' => [
                        $count . ' selected symptom' . ($count !== 1 ? 's' : '') . ' matched.',
                        'Disease coverage is ' . $item['coverage'] . '%.',
                        $missingCount === 0 ? 'All major symptoms are present.' : $missingCount . ' common symptom' . ($missingCount !== 1 ? 's are' : ' is') . ' not present.',
                    ],
                    'explanation' => array_merge(array_map(fn ($s) => '✓ ' . $s['label'] . ' matched', $matched), array_map(fn ($s) => '• ' . $s['label'] . ' is commonly associated', array_slice($missing, 0, 2))),
                ];
            }
            $this->validate($snapshot);
            $key = bin2hex(random_bytes(32));
            $this->session->put(self::PENDING, ['owner' => $this->owner(), 'key' => $key, 'expires' => time() + 3600, 'snapshot' => $snapshot]);
            return ['key' => $key];
        } catch (Throwable $exception) {
            // Matching remains usable if capture is unavailable; no partial snapshot can be saved.
            if (!$exception instanceof RuntimeException) {
                \App\Core\Logger::error('Symptom check capture failed (' . get_class($exception) . ').');
            }
            return ['unavailable' => true];
        }
    }

    public function save(string $key): int
    {
        $owner = $this->owner();
        $pending = $this->session->get(self::PENDING);
        if (!preg_match('/^[a-f0-9]{64}$/D', $key) || !is_array($pending) || $pending['owner'] !== $owner || !hash_equals($pending['key'], $key) || $pending['expires'] < time()) {
            throw new RuntimeException('Run a new check before saving.');
        }
        $this->validate($pending['snapshot']);
        $existing = $this->repository->findKeyForUser($key, $owner);
        if ($existing !== null) { return (int) $existing['id']; }
        try {
            return $this->repository->createForUser($owner, $key, $pending['snapshot'], json_encode($pending['snapshot'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
        } catch (\PDOException $exception) {
            if ($exception->getCode() === '23000' && ($existing = $this->repository->findKeyForUser($key, $owner)) !== null) { return (int) $existing['id']; }
            throw $exception;
        }
    }

    public function page(int $page): array
    {
        $page = max(1, min(100000, $page));
        $rows = $this->repository->pageForUser($this->owner(), $page);
        return ['rows' => array_slice($rows, 0, 20), 'page' => $page, 'has_next' => count($rows) > 20];
    }

    public function get(int $id): array
    {
        $row = $this->repository->findForUser($id, $this->owner());
        if ($row === null) { throw new RuntimeException('Saved symptom check not found.'); }
        return $this->decodeStored($row);
    }

    /** Historical read only: actor comes from the admitted bearer, never browser state. */
    public function getForPrincipal(ApiAuthPrincipal $principal, int $id): ?array
    {
        $row = $this->repository->findForUser($id, $principal->userId);
        return $row === null ? null : $this->decodeStored($row);
    }

    private function decodeStored(array $row): array
    {
        try {
            if (strlen($row['snapshot_json']) > 262144) { throw new RuntimeException('Invalid snapshot.'); }
            $snapshot = json_decode($row['snapshot_json'], true, 32, JSON_THROW_ON_ERROR);
            $this->validate($snapshot);
            $row['snapshot'] = $snapshot;
        } catch (Throwable $exception) {
            throw new RuntimeException('This saved symptom check is unavailable. You can delete it from your saved list.');
        }
        unset($row['snapshot_json'], $row['check_key'], $row['user_id']);
        return $row;
    }

    public function delete(int $id): void
    {
        $owner = $this->owner();
        $row = $this->repository->findForUser($id, $owner);
        if ($row === null || !$this->repository->deleteForUser($id, $owner)) { throw new RuntimeException('Saved symptom check not found.'); }
        $pending = $this->session->get(self::PENDING);
        if (is_array($pending) && $pending['owner'] === $owner && $pending['key'] === $row['check_key']) { $this->session->remove(self::PENDING); }
    }

    private function owner(): int
    {
        if (!$this->authService->check() || $this->authService->user() === null || ($id = $this->authService->id()) === null) { throw new RuntimeException('Authentication required.'); }
        return $id;
    }

    private function identifier(mixed $id): void
    {
        if (!is_int($id) || $id < 1) { throw new RuntimeException('Invalid snapshot.'); }
    }

    private function label(mixed $label): string
    {
        if (!is_string($label) || trim($label) === '' || strlen($label) > 2048) { throw new RuntimeException('Invalid snapshot.'); }
        return $label;
    }

    private function validate(mixed $snapshot): void
    {
        if (!is_array($snapshot) || ($snapshot['version'] ?? null) !== 1 || ($snapshot['disclaimer'] ?? null) !== self::DISCLAIMER || !is_string($snapshot['checked_at'] ?? null)
            || !is_array($snapshot['selected'] ?? null) || !array_is_list($snapshot['selected']) || count($snapshot['selected']) < 1 || count($snapshot['selected']) > 50
            || !is_array($snapshot['results'] ?? null) || !array_is_list($snapshot['results']) || count($snapshot['results']) > 10) { throw new RuntimeException('Invalid snapshot.'); }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $snapshot['checked_at'], new DateTimeZone('UTC'));
        if (!$date || $date->format('Y-m-d H:i:s') !== $snapshot['checked_at']) { throw new RuntimeException('Invalid snapshot.'); }
        foreach ($snapshot['selected'] as $symptom) { $this->identifier($symptom['id'] ?? null); $this->label($symptom['label'] ?? null); }
        if (count(array_unique(array_column($snapshot['selected'], 'id'))) !== count($snapshot['selected'])
            || ($snapshot['summary']['displayed_matches'] ?? null) !== count($snapshot['results']) || !is_int($snapshot['summary']['total_matches'] ?? null)
            || $snapshot['summary']['total_matches'] < count($snapshot['results'])) { throw new RuntimeException('Invalid snapshot.'); }
        foreach ($snapshot['results'] as $item) {
            if (!is_array($item)) { throw new RuntimeException('Invalid snapshot.'); }
            $this->identifier($item['id'] ?? null); $this->label($item['label'] ?? null); $this->label($item['match_level'] ?? null);
            foreach (['ranking_score','user_match_score','coverage','display_percent'] as $field) {
                $number = $item[$field] ?? null;
                if ((!is_int($number) && !is_float($number)) || !is_finite((float) $number) || $number < 0 || $number > 100) { throw new RuntimeException('Invalid snapshot.'); }
            }
            foreach (['reason','explanation'] as $field) {
                if (!is_array($item[$field] ?? null) || !array_is_list($item[$field]) || $item[$field] === [] || count($item[$field]) > ($field === 'reason' ? 3 : 52)) { throw new RuntimeException('Invalid snapshot.'); }
                foreach ($item[$field] as $text) { $this->label($text); }
            }
            foreach (['matched','missing_preview'] as $field) {
                if (!is_array($item[$field] ?? null) || !array_is_list($item[$field]) || count($item[$field]) > ($field === 'matched' ? 50 : 3)) { throw new RuntimeException('Invalid snapshot.'); }
                foreach ($item[$field] as $symptom) { $this->identifier($symptom['id'] ?? null); $this->label($symptom['label'] ?? null); }
            }
            if (!is_int($item['missing_count'] ?? null) || $item['missing_count'] < count($item['missing_preview']) || $item['matched'] === []) { throw new RuntimeException('Invalid snapshot.'); }
            foreach ($item['matched'] as $symptom) {
                if (!in_array($symptom['id'], array_column($snapshot['selected'], 'id'), true)) { throw new RuntimeException('Invalid snapshot.'); }
            }
            if (!is_int($item['display_percent']) || !in_array($item['match_level'], ['Very High Match','High Match','Moderate Match','Low Match','Weak Match'], true)) { throw new RuntimeException('Invalid snapshot.'); }
        }
        if (strlen(json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)) > 262144) { throw new RuntimeException('Invalid snapshot.'); }
    }
}
