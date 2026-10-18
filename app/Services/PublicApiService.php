<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\DiseaseRepository;
use App\Repositories\SubstanceRepository;
use InvalidArgumentException;

class PublicApiService
{
    public function __construct(
        private DiseaseRepository $diseases,
        private SubstanceRepository $substances,
        private DiseaseReferenceService $references
    ) {}

    private function parameters(array $input, array $allowed): void
    {
        if (array_diff(array_keys($input), $allowed)) {
            throw new PublicApiException(422, 'VALIDATION_ERROR', 'Unsupported request parameter.');
        }
        foreach ($input as $key => $value) {
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
                throw new PublicApiException(400, 'BAD_REQUEST', 'Check the request parameters.', [$key => 'Use a valid text value.']);
            }
        }
    }

    private function positiveInteger(array $input, string $key, int $default, int $max): int
    {
        if (!array_key_exists($key, $input)) { return $default; }
        $value = $input[$key];
        if (!preg_match('/\A[1-9][0-9]{0,4}\z/D', $value) || (int)$value > $max) {
            throw new PublicApiException(422, 'VALIDATION_ERROR', 'Check the request parameters.', [$key => 'Use a whole number between 1 and ' . $max . '.']);
        }
        return (int)$value;
    }

    private function query(array $input): string
    {
        $this->parameters($input, ['q']);
        try { $query = PublicSearchService::normalize($input['q'] ?? ''); }
        catch (InvalidArgumentException) { throw new PublicApiException(422, 'VALIDATION_ERROR', 'Check the request parameters.', ['q' => 'Enter 2–100 characters.']); }
        if (mb_strlen($query, 'UTF-8') < 2) {
            throw new PublicApiException(422, 'VALIDATION_ERROR', 'Check the request parameters.', ['q' => 'Enter 2–100 characters.']);
        }
        return $query;
    }

    private function text(mixed $value): ?string
    {
        return $value === null || trim((string)$value) === '' ? null : (string)$value;
    }

    private function disease(array $row): array
    {
        return ['id' => (string)$row['id'], 'slug' => (string)$row['slug'],
            'name_en' => (string)$row['disease_en'], 'name_hi' => $this->text($row['disease_hi']),
            'public_url' => url('/disease/' . rawurlencode($row['slug'])),
            'body_system' => $this->text($row['body_name']),
            'gender' => in_array($row['gender'], ['male', 'female', 'both'], true) ? $row['gender'] : null];
    }

    public function listDiseases(array $input): array
    {
        $this->parameters($input, ['page', 'page_size']);
        $page = $this->positiveInteger($input, 'page', 1, 10000);
        $size = $this->positiveInteger($input, 'page_size', 20, 50);
        $total = $this->diseases->countPublicApiDiseases();
        $rows = $this->diseases->listPublicApiDiseases($size, ($page - 1) * $size);
        return ['data' => array_map($this->disease(...), $rows),
            'meta' => ['page' => $page, 'page_size' => $size, 'total' => $total, 'has_more' => $page * $size < $total]];
    }

    public function searchDiseases(array $input): array
    {
        $query = $this->query($input);
        $rows = $this->diseases->searchPublicNames($query, PublicSearchService::likePattern($query));
        return ['data' => array_map($this->disease(...), array_slice($rows, 0, PublicSearchService::LIMIT)),
            'meta' => ['query' => $query, 'limit' => PublicSearchService::LIMIT, 'has_more' => count($rows) > PublicSearchService::LIMIT]];
    }

    public function searchSubstances(array $input): array
    {
        $query = $this->query($input);
        $rows = $this->substances->searchPublicNames($query, PublicSearchService::likePattern($query));
        $data = array_map(static fn(array $row): array => [
            'id' => (string)$row['substance_id'], 'canonical_name' => (string)$row['canonical_name'],
            'registry_url' => url('/medicines') . '?' . http_build_query(['q' => $row['canonical_name']], '', '&', PHP_QUERY_RFC3986),
            'information_type' => 'legacy_registry_name'
        ], array_slice($rows, 0, PublicSearchService::LIMIT));
        return ['data' => $data, 'meta' => ['query' => $query, 'limit' => PublicSearchService::LIMIT, 'has_more' => count($rows) > PublicSearchService::LIMIT]];
    }

    public function detail(string $slug, array $input): array
    {
        $this->parameters($input, []);
        $slug = rawurldecode($slug);
        if (!mb_check_encoding($slug, 'UTF-8')) { throw new PublicApiException(400, 'BAD_REQUEST', 'Use a valid disease slug.'); }
        if (strlen($slug) > 255 || !preg_match('/\A[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*\z/D', $slug)) {
            throw new PublicApiException(422, 'VALIDATION_ERROR', 'Use a valid disease slug.', ['slug' => 'Use the public disease slug.']);
        }
        $row = $this->diseases->findPublicApiDisease($slug);
        if (!$row) { throw new PublicApiException(404, 'NOT_FOUND', 'Public resource not found.'); }
        $data = $this->disease($row) + ['locale' => 'en', 'content_format' => 'plain_text'];
        $data['sections'] = [];
        $data['references'] = [];
        foreach (DiseaseReferenceService::SECTIONS as $section) {
            $data['sections'][$section] = $this->text($row[$section . '_en']);
            $data['references'][$section] = array_map(static fn(array $source): array => [
                'organization' => $source['organization'], 'title' => $source['title'], 'url' => $source['url'],
                'date' => $source['date'] ?? null, 'date_type' => $source['date_type'] ?? null
            ], $this->references->getForSection((int)$row['id'], $section));
        }
        $data['causes_items'] = $this->diseases->publicApiCauseNames((int)$row['id']);
        $data['urgency_note'] = $this->text($row['urgency_note_en']);
        return ['data' => $data, 'meta' => []];
    }
}
