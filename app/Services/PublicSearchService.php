<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\DiseaseRepository;
use App\Repositories\SubstanceRepository;
use InvalidArgumentException;

class PublicSearchService
{
    public const LIMIT = 10;

    public function __construct(
        private DiseaseRepository $diseases,
        private SubstanceRepository $substances
    ) {}

    public static function normalize(mixed $input): string
    {
        if (!is_string($input) || strlen($input) > 4096 || !mb_check_encoding($input, 'UTF-8')) {
            throw new InvalidArgumentException('Enter a valid search of up to 100 characters.');
        }
        $query = trim(preg_replace('/[\s\p{Z}]+/u', ' ', $input));
        if (mb_strlen($query, 'UTF-8') > 100) {
            throw new InvalidArgumentException('Search using no more than 100 characters.');
        }
        return $query;
    }

    public function search(mixed $input): array
    {
        $query = self::normalize($input);
        $result = ['query' => $query, 'searched' => mb_strlen($query, 'UTF-8') >= 2,
            'diseases' => [], 'substances' => [], 'diseases_more' => false, 'substances_more' => false];
        if (!$result['searched']) { return $result; }
        $pattern = self::likePattern($query);
        $diseases = $this->diseases->searchPublicNames($query, $pattern);
        $substances = $this->substances->searchPublicNames($query, $pattern);
        $result['diseases_more'] = count($diseases) > self::LIMIT;
        $result['substances_more'] = count($substances) > self::LIMIT;
        foreach (array_slice($diseases, 0, self::LIMIT) as $row) {
            $context = array_filter([$row['body_name'] ?? '', match ($row['gender'] ?? '') {
                'male' => 'Male', 'female' => 'Female', default => ''
            }]);
            $result['diseases'][] = ['id' => (int)$row['id'], 'label' => $row['disease_en'],
                'context' => implode(' · ', $context),
                'url' => url('/disease/' . rawurlencode($row['slug']))];
        }
        foreach (array_slice($substances, 0, self::LIMIT) as $row) {
            $result['substances'][] = ['id' => (int)$row['substance_id'], 'label' => $row['canonical_name'],
                'url' => url('/medicines') . '?' . http_build_query(['q' => $row['canonical_name']], '', '&', PHP_QUERY_RFC3986)];
        }
        return $result;
    }

    public static function likePattern(string $query): string
    {
        return strtr($query, ['=' => '==', '%' => '=%', '_' => '=_']);
    }
}
