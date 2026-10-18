<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TreatmentPublicationRepository;
use JsonException;
use RuntimeException;

/** Side-effect-free canonical eligibility and deliberate public projection. */
class TreatmentPublicationService
{
    public function __construct(
        private TreatmentPublicationRepository $repository,
        private TreatmentGovernanceRules $rules,
        private TreatmentGovernanceSnapshot $snapshots,
        private TreatmentGovernancePolicy $policy
    ) {}

    /** @return array{status: 'DENY'|'CANDIDATE'} Internal assessment; never authorizes exposure. */
    public function assessProtocolForPublicRead(int $protocolId, int $diseaseId, string $locale = 'en'): array
    {
        if ($protocolId < 1 || $diseaseId < 1 || !in_array($locale, ['en','hi'], true)) { return ['status' => 'DENY']; }
        // Publication mode is intentionally absent: the same substantive predicate
        // governs assessment and exposure, but only exposure requires authorization.
        foreach ($this->repository->candidates($diseaseId, $protocolId) as $aggregate) {
            if ($this->eligible($aggregate, $diseaseId, $protocolId, $locale)) { return ['status' => 'CANDIDATE']; }
        }
        return ['status' => 'DENY'];
    }

    public function getPublicTreatmentProjection(int $protocolId, int $diseaseId, string $locale = 'en'): ?array
    {
        if ($protocolId < 1) { return null; }
        return $this->read($diseaseId, $locale, $protocolId)[0] ?? null;
    }

    public function getPublicTreatmentsForDisease(int $diseaseId, string $locale = 'en'): array
    {
        return $this->read($diseaseId, $locale, null);
    }

    private function read(int $diseaseId, string $locale, ?int $protocolId): array
    {
        if ($this->policy->mode() !== 'governed' || $diseaseId < 1 || !in_array($locale, ['en','hi'], true)) { return []; }
        // Database failures are outside data validation; they must propagate safely.
        $aggregates = $this->repository->candidates($diseaseId, $protocolId);
        $result = [];
        foreach ($aggregates as $aggregate) {
            if ($this->eligible($aggregate, $diseaseId, $protocolId, $locale)) { $result[] = $this->project($aggregate, $locale); }
        }
        usort($result, static fn($a, $b) => [$a['treatment_system_id'], $a['protocol_id']] <=> [$b['treatment_system_id'], $b['protocol_id']]);
        return $result;
    }

    /** Only persisted-data validation exceptions are converted to a denied read. */
    private function eligible(array $a, int $diseaseId, ?int $protocolId, string $locale): bool
    {
        try {
            $p = $a['protocol'] ?? null; $system = $a['system'] ?? null; $approval = $a['approval'] ?? null;
            if (!is_array($p) || !is_array($system) || !is_array($approval) || !is_array($a['disease'] ?? null)) { return false; }
            $id = $this->rules->id($p['id'] ?? null);
            if (($protocolId !== null && $id !== $protocolId)
                || $this->rules->id($p['disease_id'] ?? null) !== $diseaseId
                || $this->rules->id($a['disease']['id'] ?? null) !== $diseaseId
                || $this->rules->id($system['id'] ?? null) !== $this->rules->id($p['treatment_system_id'] ?? null)
                || $this->rules->flag($system['is_active'] ?? null) !== 1
                || $this->rules->flag($a['unresolved_invalidation'] ?? null) !== 0) { return false; }
            $this->rules->protocol($p);
            if ($p['review_state'] !== 'approved' || $p['publication_status'] !== 'published'
                || !$this->policy->evidenceAllowed($p['evidence_classification']) || !$this->policy->riskAllowed($p['risk_eligibility'])) { return false; }
            $this->rules->value($p['content_purpose'] ?? null, TreatmentGovernanceRules::PURPOSE);
            $this->rules->text($system['name_'.$locale] ?? null, 65535, true);
            $this->rules->text($system['slug'] ?? null, 65535, true);
            $this->rules->event($approval);
            $occurred = $this->rules->timestamp($approval['occurred_at'] ?? '');
            $now = $this->rules->now();
            if ($this->rules->id($approval['id'] ?? null) !== $this->rules->id($p['approved_review_id'] ?? null)
                || $approval['event_type'] !== 'approved'
                || $this->rules->id($approval['protocol_id']) !== $id
                || $this->rules->id($approval['content_revision']) !== $this->rules->id($p['content_revision'])
                || $occurred > $now
                || $this->rules->timestamp($approval['review_due_at']) <= $now) { return false; }
            $locales = $this->rules->storedLocales($approval['locales']);
            $published = $this->rules->storedLocales($p['published_locales']);
            if (!in_array($locale, $locales, true) || !in_array($locale, $published, true) || array_diff($published, $locales)) { return false; }
            foreach (['title','overview','applicability','limitations'] as $field) { $this->rules->text($p[$field.'_'.$locale] ?? null, 65535, true); }
            $items = []; $orders = []; $included = 0;
            foreach ($a['items'] ?? [] as $item) {
                $itemId = $this->rules->id($item['id'] ?? null);
                if (isset($items[$itemId]) || $this->rules->id($item['protocol_id'] ?? null) !== $id) { return false; }
                $items[$itemId] = $item;
                if ($this->rules->flag($item['is_included'] ?? null) !== 1) { continue; }
                ++$included;
                $this->rules->value($item['item_type'] ?? null, TreatmentGovernanceRules::ITEM_TYPES);
                $evidence = $this->rules->value($item['evidence_classification'] ?? null, TreatmentGovernanceRules::EVIDENCE);
                if (!$this->policy->evidenceAllowed($evidence)) { return false; }
                $this->rules->value($item['content_purpose'] ?? null, TreatmentGovernanceRules::PURPOSE);
                $order = $this->rules->id($item['sort_order'] ?? null);
                if (isset($orders[$order])) { return false; } $orders[$order] = true;
                foreach (['title','instructions','limitations'] as $field) { $this->rules->text($item[$field.'_'.$locale] ?? null, 65535, true); }
            }
            if (!$included) { return false; }
            $sources = [];
            foreach ($a['sources'] ?? [] as $source) {
                $sourceId = $this->rules->id($source['id'] ?? null);
                if (isset($sources[$sourceId]) || ($source['status'] ?? null) !== 'verified') { return false; }
                $this->rules->id($source['verified_by'] ?? null);
                $this->rules->timestamp($source['verified_at'] ?? '');
                $this->rules->value($source['source_type'] ?? null, TreatmentGovernanceRules::SOURCE_TYPES);
                $this->rules->text($source['title'] ?? null, 2000, true);
                $this->rules->text($source['citation_text'] ?? null, 65535, true);
                $url = $source['url'] ?? null;
                if ($url !== null && (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)
                    || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http','https'], true))) { return false; }
                $sources[$sourceId] = $source;
            }
            $protocolRefs = $a['protocol_sources'] ?? [];
            if (!$protocolRefs) { return false; }
            $supportedItems = [];
            foreach (['protocol_sources' => 'protocol_id', 'item_sources' => 'item_id'] as $key => $parent) {
                foreach ($a[$key] ?? [] as $ref) {
                    $this->rules->id($ref['id'] ?? null);
                    $parentId = $this->rules->id($ref[$parent] ?? null);
                    if (($parent === 'protocol_id' && $parentId !== $id) || ($parent === 'item_id' && !isset($items[$parentId]))
                        || !isset($sources[$this->rules->id($ref['source_id'] ?? null)])) { return false; }
                    $this->rules->text($ref['claim_scope'] ?? null, 65535, true);
                    $this->rules->text($ref['locator'] ?? null, 2000);
                    if ($parent === 'item_id') { $supportedItems[$parentId] = true; }
                }
            }
            foreach ($items as $itemId => $item) { if ($this->rules->flag($item['is_included']) === 1 && !isset($supportedItems[$itemId])) { return false; } }
            // Revalidate BOTH the stored JSON/hash pair and the full current canonical content.
            $stored = json_decode($approval['content_snapshot'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($stored)) { return false; }
            $current = $this->snapshots->capture($a, $locales);
            return $approval['content_snapshot'] === $current['content_snapshot']
                && hash_equals($approval['content_hash'], $current['content_hash']);
        } catch (RuntimeException | JsonException $invalidData) {
            return false;
        }
    }

    private function project(array $a, string $locale): array
    {
        $p = $a['protocol']; $s = $a['system']; $sources = array_column($a['sources'], null, 'id');
        $items = [];
        foreach ($a['items'] as $item) {
            if ($this->rules->flag($item['is_included']) !== 1) { continue; }
            $items[] = ['item_id' => (int)$item['id'], 'item_type' => $item['item_type'],
                'title' => $item['title_'.$locale], 'instructions' => $item['instructions_'.$locale],
                'limitations' => $item['limitations_'.$locale],
                'references' => $this->references(array_values(array_filter($a['item_sources'], static fn($r) => (int)$r['item_id'] === (int)$item['id'])), $sources),
                '_order' => (int)$item['sort_order']];
        }
        usort($items, static fn($a, $b) => [$a['_order'], $a['item_id']] <=> [$b['_order'], $b['item_id']]);
        foreach ($items as &$item) { unset($item['_order']); } unset($item);
        return ['protocol_id' => (int)$p['id'], 'disease_id' => (int)$p['disease_id'],
            'treatment_system_id' => (int)$p['treatment_system_id'], 'treatment_system_name' => $s['name_'.$locale],
            'treatment_system_slug' => $s['slug'], 'locale' => $locale, 'title' => $p['title_'.$locale],
            'overview' => $p['overview_'.$locale], 'content_purpose' => $p['content_purpose'],
            'evidence_classification' => $p['evidence_classification'], 'evidence_summary' => $p['evidence_summary_'.$locale] ?? null,
            'applicability' => $p['applicability_'.$locale], 'limitations' => $p['limitations_'.$locale],
            'included_items' => $items, 'references' => $this->references($a['protocol_sources'], $sources)];
    }

    private function references(array $refs, array $sources): array
    {
        usort($refs, static fn($a, $b) => [(int)$a['id'], (int)$a['source_id']] <=> [(int)$b['id'], (int)$b['source_id']]);
        $result = [];
        foreach ($refs as $ref) {
            $source = $sources[$ref['source_id']];
            $result[] = ['reference_id' => (int)$ref['id'], 'source_id' => (int)$source['id'],
                'claim_scope' => $ref['claim_scope'], 'locator' => $ref['locator'],
                'source_type' => $source['source_type'], 'title' => $source['title'],
                'author_or_organization' => $source['author_or_organization'] ?? null,
                'publication' => $source['publication'] ?? null, 'citation_text' => $source['citation_text'],
                'url' => $source['url'] ?? null, 'identifier' => $source['identifier'] ?? null,
                'publication_date' => $source['publication_date'] ?? null, 'edition_or_version' => $source['edition_or_version'] ?? null,
                'accessed_on' => $source['accessed_on'] ?? null];
        }
        return $result;
    }
}
