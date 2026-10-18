<?php

declare(strict_types=1);

namespace App\Repositories;

use PDOException;
use RuntimeException;
use Throwable;

/** Internal read model only. Public callers receive the service's allowlist. */
class TreatmentPublicationRepository extends BaseRepository
{
    public function candidates(int $diseaseId, ?int $protocolId = null): array
    {
        if ($this->db->inTransaction()) { throw new RuntimeException('Nested treatment reads are not supported.'); }
        try {
            // All six data queries share one InnoDB snapshot; never lock or mutate content.
            if ($this->fetch('SELECT @@tx_isolation AS isolation_level')['isolation_level'] !== 'REPEATABLE-READ') {
                throw new RuntimeException('A consistent treatment read is unavailable.');
            }
            $this->query('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
            $params = ['disease_id' => $diseaseId];
            if ($protocolId !== null) { $params['protocol_id'] = $protocolId; }
            $protocols = $this->fetchAll("SELECT p.*, d.id AS existing_disease_id,
                s.id AS existing_system_id, s.is_active AS system_active,
                s.name_en AS system_name_en, s.name_hi AS system_name_hi, s.slug AS system_slug,
                EXISTS (SELECT 1 FROM treatment_protocol_events e WHERE e.protocol_id=p.id
                    AND e.id>p.approved_review_id AND e.event_type IN
                    ('content_changed','submitted','changes_required','review_reset','withdrawn','source_invalidated','risk_assessed')) AS unresolved_invalidation
                FROM treatment_protocols p LEFT JOIN diseases d ON d.id=p.disease_id
                LEFT JOIN treatment_systems s ON s.id=p.treatment_system_id
                WHERE p.disease_id=:disease_id AND p.review_state='approved' AND p.publication_status='published'"
                . ($protocolId !== null ? ' AND p.id=:protocol_id' : '')
                . ' ORDER BY p.treatment_system_id,p.id', $params);
            $ids = array_column($protocols, 'id');
            $items = $this->rows('treatment_protocol_items', 'protocol_id', $ids);
            $protocolRefs = $this->rows('treatment_protocol_sources', 'protocol_id', $ids);
            $itemRefs = $this->rows('treatment_item_sources', 'item_id', array_column($items, 'id'));
            $sources = $this->rows('treatment_sources', 'id', array_merge(array_column($protocolRefs, 'source_id'), array_column($itemRefs, 'source_id')));
            $approvals = array_column($this->rows('treatment_protocol_events', 'id', array_column($protocols, 'approved_review_id')), null, 'id');
            $itemsByProtocol = $this->group($items, 'protocol_id');
            $refsByProtocol = $this->group($protocolRefs, 'protocol_id');
            $refsByItem = $this->group($itemRefs, 'item_id');
            $sourcesById = array_column($sources, null, 'id');
            $result = [];
            foreach ($protocols as $p) {
                $protocolItems = $itemsByProtocol[$p['id']] ?? [];
                $pRefs = $refsByProtocol[$p['id']] ?? [];
                $iRefs = [];
                foreach ($protocolItems as $item) { array_push($iRefs, ...($refsByItem[$item['id']] ?? [])); }
                $sourceIds = array_unique(array_merge(array_column($pRefs, 'source_id'), array_column($iRefs, 'source_id')));
                $protocolSources = [];
                foreach ($sourceIds as $id) { if (isset($sourcesById[$id])) { $protocolSources[] = $sourcesById[$id]; } }
                $result[] = [
                    'protocol' => $p, 'items' => $protocolItems, 'protocol_sources' => $pRefs,
                    'item_sources' => $iRefs, 'sources' => $protocolSources,
                    'disease' => $p['existing_disease_id'] === null ? null : ['id' => $p['existing_disease_id']],
                    'system' => $p['existing_system_id'] === null ? null : [
                        'id' => $p['existing_system_id'], 'is_active' => $p['system_active'],
                        'name_en' => $p['system_name_en'], 'name_hi' => $p['system_name_hi'], 'slug' => $p['system_slug'],
                    ],
                    'approval' => $approvals[$p['approved_review_id']] ?? null,
                    'unresolved_invalidation' => $p['unresolved_invalidation'],
                ];
            }
            $this->commit();
            return $result;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) { $this->rollback(); }
            if ($error instanceof PDOException) { throw new RuntimeException('Unable to read public treatments. Please retry.', 0, $error); }
            throw $error;
        }
    }

    /** Identifiers are private fixed call sites; all row IDs are bound. */
    private function rows(string $table, string $parent, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn($id) => $id !== null)));
        if ($ids === []) { return []; }
        $params = []; $placeholders = [];
        foreach ($ids as $index => $id) { $params['id'.$index] = $id; $placeholders[] = ':id'.$index; }
        return $this->fetchAll("SELECT * FROM $table WHERE $parent IN (".implode(',', $placeholders).') ORDER BY id', $params);
    }

    private function group(array $rows, string $key): array
    {
        $result = [];
        foreach ($rows as $row) { $result[$row[$key]][] = $row; }
        return $result;
    }
}
