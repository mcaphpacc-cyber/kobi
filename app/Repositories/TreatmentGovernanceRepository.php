<?php

declare(strict_types=1);

namespace App\Repositories;

use PDOException;
use RuntimeException;
use Throwable;

class TreatmentGovernanceRepository extends BaseRepository
{
    /** Exact initial-content repeat protection; the schema permits multiple protocols per pair. */
    public function matchingProtocol(array $row): bool
    {
        return $this->fetch('SELECT id FROM treatment_protocols WHERE disease_id=:disease_id AND treatment_system_id=:treatment_system_id AND title_en <=> :title_en AND title_hi <=> :title_hi AND overview_en <=> :overview_en AND overview_hi <=> :overview_hi LIMIT 1', array_intersect_key($row,array_flip(['disease_id','treatment_system_id','title_en','title_hi','overview_en','overview_hi'])))!==null;
    }

    public function insertProtocol(array $row): int
    {
        $fields=array_merge(\App\Services\TreatmentGovernanceRules::PROTOCOL_CONTENT,['review_state','publication_status','content_revision','approved_review_id','published_locales','risk_eligibility','updated_at','updated_by']);
        return $this->insertContent('treatment_protocols',$row,$fields);
    }

    public function insertItem(array $row): int
    {
        return $this->insertContent('treatment_protocol_items',$row,array_merge(['protocol_id'],\App\Services\TreatmentGovernanceRules::ITEM_CONTENT));
    }

    private function insertContent(string $table,array $row,array $fields): int
    {
        if (array_diff(array_keys($row),$fields) || array_diff($fields,array_keys($row))) { throw new RuntimeException('Invalid treatment field.'); }
        $this->execute('INSERT INTO '.$table.' ('.implode(',',$fields).') VALUES (:'.implode(',:',$fields).')',$row);
        return (int)$this->lastInsertId();
    }

    public function reviewList(int $actor,array $filters,int $offset,string $now): array
    {
        $where=["g.user_id=:actor","g.revoked_at IS NULL","g.can_review=1","u.status='active'"];
        $params=['actor'=>$actor];
        foreach (['treatment_system_id','disease_id','review_state','evidence_classification','content_purpose','content_revision'] as $field) {
            if (isset($filters[$field])) { $where[]="p.`$field`=:$field"; $params[$field]=$filters[$field]; }
        }
        if (isset($filters['due_status'])) {
            $where[]=$filters['due_status']==='expired' ? 'a.review_due_at<=:now' : 'a.review_due_at>:now'; $params['now']=$now;
        }
        return $this->fetchAll('SELECT p.*,d.disease_en,s.name_en AS system_name,a.review_due_at,(SELECT MAX(e.id) FROM treatment_protocol_events e WHERE e.protocol_id=p.id AND e.content_revision=p.content_revision AND e.event_type=\'submitted\') AS submission_id FROM treatment_protocols p JOIN diseases d ON d.id=p.disease_id JOIN treatment_systems s ON s.id=p.treatment_system_id JOIN treatment_editorial_permissions g ON g.treatment_system_id=p.treatment_system_id JOIN users u ON u.id=g.user_id LEFT JOIN treatment_protocol_events a ON a.id=p.approved_review_id AND a.protocol_id=p.id AND a.content_revision=p.content_revision WHERE '.implode(' AND ',$where).' ORDER BY p.id LIMIT 51 OFFSET '.max(0,$offset),$params);
    }

    public function operationsList(int $actor,array $filters,int $offset): array
    {
        $where=["g.user_id=:actor","g.revoked_at IS NULL","(g.can_edit=1 OR g.can_review=1 OR g.can_publish=1)","u.status='active'"];
        $params=['actor'=>$actor];
        foreach (['treatment_system_id','disease_id','review_state','publication_status'] as $field) {
            if (isset($filters[$field])) { $where[]="p.`$field`=:$field"; $params[$field]=$filters[$field]; }
        }
        return $this->fetchAll('SELECT p.*,d.disease_en,s.name_en AS system_name FROM treatment_protocols p JOIN diseases d ON d.id=p.disease_id JOIN treatment_systems s ON s.id=p.treatment_system_id JOIN treatment_editorial_permissions g ON g.treatment_system_id=p.treatment_system_id JOIN users u ON u.id=g.user_id WHERE '.implode(' AND ',$where).' ORDER BY p.id LIMIT 51 OFFSET '.max(0,$offset),$params);
    }

    public function history(int $id,int $beforeId=0): array
    {
        // Never project snapshot JSON into the history table/view.
        return $this->fetchAll('SELECT id,content_revision,event_type,actor_id,occurred_at,locales,notes,review_due_at,independence_confirmed FROM treatment_protocol_events WHERE protocol_id=:id'.($beforeId>0 ? ' AND id<:before_id' : '').' ORDER BY id DESC LIMIT 51',$beforeId>0 ? ['id'=>$id,'before_id'=>$beforeId] : ['id'=>$id]);
    }

    public function editorialList(int $actor, array $filters, int $offset): array
    {
        $where=["g.user_id=:actor", "g.revoked_at IS NULL", "g.can_edit=1", "u.status='active'"];
        $params=['actor'=>$actor];
        foreach (['treatment_system_id','disease_id','review_state','publication_status','evidence_classification'] as $field) {
            if (isset($filters[$field])) { $where[]="p.`$field`=:$field"; $params[$field]=$filters[$field]; }
        }
        if (($filters['completeness']??null)==='unassessed') { $where[]="(p.evidence_classification='not_assessed' OR p.content_purpose IS NULL)"; }
        if (($filters['completeness']??null)==='missing_sources') { $where[]='NOT EXISTS (SELECT 1 FROM treatment_protocol_sources r WHERE r.protocol_id=p.id)'; }
        // Explicit bounded pagination. IDs/controlled values are bound, never interpolated.
        return $this->fetchAll('SELECT p.*,d.disease_en,d.disease_hi,s.name_en AS system_name,(SELECT COUNT(*) FROM treatment_protocol_sources r WHERE r.protocol_id=p.id) AS source_count FROM treatment_protocols p JOIN diseases d ON d.id=p.disease_id JOIN treatment_systems s ON s.id=p.treatment_system_id JOIN treatment_editorial_permissions g ON g.treatment_system_id=p.treatment_system_id JOIN users u ON u.id=g.user_id WHERE '.implode(' AND ',$where).' ORDER BY p.id LIMIT 51 OFFSET '.max(0,$offset),$params);
    }

    public function editorialIdentity(int $id): ?array
    {
        return $this->fetch('SELECT d.disease_en,d.disease_hi,s.name_en AS system_name FROM treatment_protocols p JOIN diseases d ON d.id=p.disease_id JOIN treatment_systems s ON s.id=p.treatment_system_id WHERE p.id=:id',['id'=>$id]);
    }

    public function transaction(callable $action): mixed
    {
        if ($this->db->inTransaction()) { throw new RuntimeException('Nested treatment transactions are not supported.'); }
        for ($attempt = 0; ; ++$attempt) {
            $this->beginTransaction();
            try { $result = $action(); $this->commit(); return $result; }
            catch (Throwable $error) {
                if ($this->db->inTransaction()) { $this->rollback(); }
                // Roll back assertion Errors as well, but always rethrow them.
                if (!$error instanceof PDOException) { throw $error; }
                if ((int) ($error->errorInfo[1] ?? 0) === 1213 && $attempt < 2) { continue; }
                throw new RuntimeException('Unable to save treatment governance. Please retry.', 0, $error);
            }
        }
    }

    public function find(int $id, bool $lock = false): ?array
    {
        return $this->fetch('SELECT * FROM treatment_protocols WHERE id=:id'.($lock ? ' FOR UPDATE' : ''), ['id'=>$id]);
    }

    public function system(int $id, bool $lock = false): ?array
    {
        return $this->fetch('SELECT id,is_active FROM treatment_systems WHERE id=:id'.($lock ? ' FOR UPDATE' : ''), ['id'=>$id]);
    }

    public function diseaseExists(int $id): bool
    {
        return $this->fetch('SELECT id FROM diseases WHERE id=:id', ['id'=>$id]) !== null;
    }

    public function aggregate(int $id, bool $lockChildren = false): array
    {
        $items = $this->fetchAll('SELECT * FROM treatment_protocol_items WHERE protocol_id=:id ORDER BY id'.($lockChildren ? ' FOR UPDATE' : ''), ['id'=>$id]);
        $protocolRefs = $this->fetchAll('SELECT * FROM treatment_protocol_sources WHERE protocol_id=:id ORDER BY id'.($lockChildren ? ' FOR UPDATE' : ''), ['id'=>$id]);
        $itemRefs = $this->fetchAll('SELECT r.* FROM treatment_item_sources r JOIN treatment_protocol_items i ON i.id=r.item_id WHERE i.protocol_id=:id ORDER BY r.id'.($lockChildren ? ' FOR UPDATE' : ''), ['id'=>$id]);
        $sources = $this->fetchAll('SELECT s.* FROM treatment_sources s WHERE EXISTS (SELECT 1 FROM treatment_protocol_sources r WHERE r.source_id=s.id AND r.protocol_id=:protocol_id) OR EXISTS (SELECT 1 FROM treatment_item_sources r JOIN treatment_protocol_items i ON i.id=r.item_id WHERE r.source_id=s.id AND i.protocol_id=:item_protocol_id) ORDER BY s.id', ['protocol_id'=>$id,'item_protocol_id'=>$id]);
        return ['protocol'=>$this->find($id), 'items'=>$items,'protocol_sources'=>$protocolRefs,'item_sources'=>$itemRefs,'sources'=>$sources];
    }

    public function updateProtocol(int $id, array $changes): void
    {
        $allowed = array_merge(\App\Services\TreatmentGovernanceRules::PROTOCOL_CONTENT, ['review_state','publication_status','content_revision','approved_review_id','published_locales','risk_eligibility','updated_at','updated_by']);
        $this->updateFields('treatment_protocols', $id, $changes, $allowed);
    }

    public function updateItem(int $id, array $changes): void
    {
        $this->updateFields('treatment_protocol_items', $id, $changes, \App\Services\TreatmentGovernanceRules::ITEM_CONTENT);
    }

    private function updateFields(string $table, int $id, array $changes, array $allowed): void
    {
        if (array_diff(array_keys($changes), $allowed)) { throw new RuntimeException('Invalid treatment field.'); }
        if ($changes === []) { return; }
        $sets=[]; $params=['row_id'=>$id];
        foreach ($changes as $key=>$value) { $sets[]="`$key`=:$key"; $params[$key]=$value; }
        $this->execute('UPDATE '.$table.' SET '.implode(',', $sets).' WHERE id=:row_id', $params);
    }

    public function link(int $protocolId, ?int $itemId, int $sourceId, string $scope, ?string $locator): void
    {
        $table = $itemId === null ? 'treatment_protocol_sources' : 'treatment_item_sources';
        $parent = $itemId === null ? 'protocol_id' : 'item_id';
        $this->execute("INSERT INTO $table ($parent,source_id,claim_scope,locator) VALUES (:parent,:source,:scope,:locator) ON DUPLICATE KEY UPDATE claim_scope=VALUES(claim_scope),locator=VALUES(locator)", ['parent'=>$itemId ?? $protocolId,'source'=>$sourceId,'scope'=>$scope,'locator'=>$locator]);
    }

    public function unlink(int $protocolId, ?int $itemId, int $sourceId): void
    {
        $table = $itemId === null ? 'treatment_protocol_sources' : 'treatment_item_sources';
        $parent = $itemId === null ? 'protocol_id' : 'item_id';
        $this->execute("DELETE FROM $table WHERE $parent=:parent AND source_id=:source", ['parent'=>$itemId ?? $protocolId,'source'=>$sourceId]);
    }

    public function appendEvent(array $event): int
    {
        $fields=['protocol_id','content_revision','event_type','actor_id','occurred_at','locales','notes','review_due_at','independence_confirmed','content_snapshot','content_hash'];
        $params=[]; foreach ($fields as $field) { $params[$field]=$event[$field] ?? ($field==='independence_confirmed' ? 0 : ($field==='locales' ? '' : null)); }
        $this->execute('INSERT INTO treatment_protocol_events ('.implode(',', $fields).') VALUES (:'.implode(',:', $fields).')', $params);
        return (int) $this->lastInsertId();
    }

    public function event(int $id): ?array
    {
        return $this->fetch('SELECT * FROM treatment_protocol_events WHERE id=:id', ['id'=>$id]);
    }

    public function latestSubmission(int $id, int $revision): ?array
    {
        // Commands already hold the protocol lock. Use a current read so a reset
        // committed after their initial REPEATABLE READ snapshot replaces the
        // old submission. Presentation reads remain non-locking.
        return $this->fetch("SELECT * FROM treatment_protocol_events WHERE protocol_id=:id AND content_revision=:revision AND event_type='submitted' ORDER BY id DESC LIMIT 1".($this->db->inTransaction() ? ' FOR UPDATE' : ''), ['id'=>$id,'revision'=>$revision]);
    }

    public function latestChangesRequired(int $id): ?array
    {
        return $this->fetch("SELECT id,content_revision FROM treatment_protocol_events WHERE protocol_id=:id AND event_type='changes_required' ORDER BY id DESC LIMIT 1",['id'=>$id]);
    }

    public function contributors(int $id): array
    {
        return array_map('intval', array_column($this->fetchAll("SELECT DISTINCT actor_id FROM treatment_protocol_events WHERE protocol_id=:id AND event_type='content_changed' AND actor_id IS NOT NULL", ['id'=>$id]), 'actor_id'));
    }

    public function hasEvents(int $id): bool
    {
        return $this->fetch('SELECT id FROM treatment_protocol_events WHERE protocol_id=:id LIMIT 1', ['id'=>$id]) !== null;
    }
}
