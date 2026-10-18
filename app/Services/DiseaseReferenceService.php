<?php

declare(strict_types=1);

namespace App\Services;

use UnexpectedValueException;

class DiseaseReferenceService
{
    public const SECTIONS = ['overview', 'causes', 'risk_factors', 'diagnosis', 'prevention'];
    private const DISEASE_IDS = [1, 120, 200, 364, 398, 358, 217, 201];
    private const DATE_TYPES = [
        'Published', 'Updated', 'Reviewed', 'Posted (publication)',
        'Reviewed (month precision)', 'Published; Updated; Reviewed',
        'Displayed page date; type not explicitly labelled in retrieved page text',
        'Displayed page date; not explicitly labelled publication/update/review',
        'Displayed article date; not explicitly labelled publication/update/review',
    ];

    private ?array $references = null;

    public function getForSection(int $diseaseId, string $section): array
    {
        if (!in_array($diseaseId, self::DISEASE_IDS, true)
            || !in_array($section, self::SECTIONS, true)) {
            return [];
        }

        $this->references ??= $this->validatedReferences();
        return $this->references[$diseaseId][$section] ?? [];
    }

    protected function loadManifest(): array
    {
        return require dirname(__DIR__, 2) . '/resources/disease-references.php';
    }

    private function validatedReferences(): array
    {
        $references = [];
        foreach ($this->loadManifest() as $mapping) {
            if (!is_array($mapping)) {
                throw new UnexpectedValueException('Invalid disease reference mapping.');
            }
            $id = $mapping['disease_id'] ?? null;
            $section = $mapping['section'] ?? null;
            if (!is_int($id) || $id <= 0 || !in_array($id, self::DISEASE_IDS, true)
                || !in_array($section, self::SECTIONS, true)
                || (in_array($id, [1, 201], true) && $section === 'overview')
                || isset($references[$id][$section])
                || !is_array($mapping['sources'] ?? null) || $mapping['sources'] === []) {
                throw new UnexpectedValueException('Invalid disease reference mapping.');
            }

            $references[$id][$section] = [];
            foreach ($mapping['sources'] as $source) {
                if (!is_array($source)) {
                    throw new UnexpectedValueException('Invalid disease reference metadata.');
                }
                foreach (['organization', 'title', 'url'] as $field) {
                    if (!is_string($source[$field] ?? null) || trim($source[$field]) === '') {
                        throw new UnexpectedValueException('Invalid disease reference metadata.');
                    }
                }
                if (filter_var($source['url'], FILTER_VALIDATE_URL) === false
                    || !in_array(strtolower((string) parse_url($source['url'], PHP_URL_SCHEME)), ['http', 'https'], true)) {
                    throw new UnexpectedValueException('Invalid disease reference URL.');
                }
                $date = $source['date'] ?? null;
                $dateType = $source['date_type'] ?? null;
                if (($date !== null && (!is_string($date) || trim($date) === ''))
                    || ($dateType !== null && !in_array($dateType, self::DATE_TYPES, true))) {
                    throw new UnexpectedValueException('Invalid disease reference date metadata.');
                }

                $public = array_intersect_key($source, array_flip(['organization', 'title', 'url']));
                // A displayed date with unspecified semantics is not a public review date.
                if ($date !== null && $dateType !== null && !str_starts_with($dateType, 'Displayed')) {
                    $public['date'] = $date;
                    $public['date_type'] = $dateType;
                }
                $references[$id][$section][] = $public;
            }
        }
        return $references;
    }
}
