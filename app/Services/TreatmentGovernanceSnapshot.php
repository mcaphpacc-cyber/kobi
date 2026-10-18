<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/** Versioned, explicit projection; arbitrary row keys never enter snapshots. */
class TreatmentGovernanceSnapshot
{
    public function __construct(private TreatmentGovernanceRules $rules) {}

    private function fields(array $row, array $names, array $integers = [], array $flags = []): array
    {
        $result = [];
        foreach ($names as $name) {
            $value = $row[$name] ?? null;
            if ($value !== null && in_array($name, $integers, true)) {
                if ($name === 'sort_order') {
                    if (!is_int($value) && !(is_string($value) && preg_match('/^-?[0-9]+$/D', $value))) { throw new RuntimeException('Invalid treatment ordering.'); }
                    $value = (int) $value;
                } else { $value = $this->rules->id($value); }
            } elseif ($value !== null && in_array($name, $flags, true)) { $value = (bool) $this->rules->flag($value); }
            elseif ($value !== null) { $value = $this->rules->text($value, 16777215); }
            $result[$name] = $value;
        }
        return $result;
    }

    public function build(array $aggregate, array $locales): array
    {
        $protocol = $aggregate['protocol'];
        $this->rules->protocol($protocol);
        $p = $this->fields($protocol, array_merge(['id','content_revision'], TreatmentGovernanceRules::PROTOCOL_CONTENT), ['id','content_revision','disease_id','treatment_system_id']);
        $p['risk_eligibility'] = $this->rules->value($protocol['risk_eligibility'] ?? null, TreatmentGovernanceRules::RISK_ELIGIBILITY);
        $items = [];
        foreach ($aggregate['items'] ?? [] as $row) {
            if (($row['item_type'] ?? null) !== null) { $this->rules->value($row['item_type'], TreatmentGovernanceRules::ITEM_TYPES); }
            $this->rules->value($row['evidence_classification'] ?? null, TreatmentGovernanceRules::EVIDENCE);
            if (($row['content_purpose'] ?? null) !== null) { $this->rules->value($row['content_purpose'], TreatmentGovernanceRules::PURPOSE); }
            $items[] = $this->fields($row, array_merge(['id','protocol_id'], TreatmentGovernanceRules::ITEM_CONTENT), ['id','protocol_id','sort_order'], ['is_included']);
        }
        usort($items, static fn($a,$b) => [$a['sort_order'], $a['id']] <=> [$b['sort_order'], $b['id']]);
        $protocolRefs = $this->references($aggregate['protocol_sources'] ?? [], 'protocol_id');
        $itemRefs = $this->references($aggregate['item_sources'] ?? [], 'item_id');
        $sources = [];
        foreach ($aggregate['sources'] ?? [] as $row) {
            $this->rules->value($row['source_type'] ?? null, TreatmentGovernanceRules::SOURCE_TYPES);
            $sources[] = $this->fields($row, array_merge(['id'], TreatmentGovernanceRules::SOURCE_CONTENT), ['id','replaces_source_id']);
        }
        usort($sources, static fn($a,$b) => $a['id'] <=> $b['id']);
        return ['format_version' => 1, 'protocol' => $p, 'items' => $items, 'protocol_sources' => $protocolRefs, 'item_sources' => $itemRefs, 'sources' => $sources, 'locales' => $this->rules->locales($locales)];
    }

    private function references(array $rows, string $parent): array
    {
        $result = [];
        foreach ($rows as $row) { $result[] = $this->fields($row, ['id',$parent,'source_id','claim_scope','locator'], ['id',$parent,'source_id']); }
        usort($result, static fn($a,$b) => [$a[$parent],$a['source_id'],$a['id']] <=> [$b[$parent],$b['source_id'],$b['id']]);
        return $result;
    }

    public function canonical(array $data): string
    {
        $sort = function (mixed $value) use (&$sort): mixed {
            if (is_array($value)) {
                if (!array_is_list($value)) { ksort($value, SORT_STRING); }
                foreach ($value as $key => $item) { $value[$key] = $sort($item); }
            } elseif (is_float($value) || is_object($value) || is_resource($value)) { throw new RuntimeException('Invalid canonical treatment content.'); }
            return $value;
        };
        return json_encode($sort($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function capture(array $aggregate, array $locales): array
    {
        $json = $this->canonical($this->build($aggregate, $locales));
        return ['content_snapshot' => $json, 'content_hash' => hash('sha256', $json)];
    }
}
