<?php

declare(strict_types=1);

namespace App\Repositories;

class TreatmentSourceRepository extends BaseRepository
{
    public function catalog(?string $status, int $offset = 0): array
    {
        return $this->fetchAll('SELECT * FROM treatment_sources'.($status===null ? '' : ' WHERE status=:status').' ORDER BY id DESC LIMIT 51 OFFSET '.max(0,$offset),$status===null ? [] : ['status'=>$status]);
    }

    public function find(int $id, bool $lock = false): ?array
    {
        return $this->fetch('SELECT * FROM treatment_sources WHERE id=:id'.($lock ? ' FOR UPDATE' : ''), ['id'=>$id]);
    }

    public function insert(array $row): int
    {
        $fields = ['source_type','title','author_or_organization','publication','citation_text','url','identifier','publication_date','edition_or_version','accessed_on','replaces_source_id','created_by','created_at'];
        $params = []; foreach ($fields as $field) { $params[$field] = $row[$field] ?? null; }
        $this->execute('INSERT INTO treatment_sources ('.implode(',', $fields).') VALUES (:'.implode(',:', $fields).')', $params);
        return (int) $this->lastInsertId();
    }

    public function verify(int $id, int $actor, string $now): void
    {
        $this->execute("UPDATE treatment_sources SET status='verified',verified_by=:actor,verified_at=:now WHERE id=:id AND status='unverified'", ['actor'=>$actor,'now'=>$now,'id'=>$id]);
    }

    public function withdraw(int $id, int $actor, string $now, string $reason): void
    {
        $this->execute("UPDATE treatment_sources SET status='withdrawn',withdrawn_by=:actor,withdrawn_at=:now,withdrawal_reason=:reason WHERE id=:id AND status IN ('unverified','verified')", ['actor'=>$actor,'now'=>$now,'reason'=>$reason,'id'=>$id]);
    }

    public function affectedProtocols(int $sourceId, bool $lock = false): array
    {
        return $this->fetchAll('SELECT p.id,p.treatment_system_id FROM treatment_protocols p WHERE EXISTS (SELECT 1 FROM treatment_protocol_sources r WHERE r.protocol_id=p.id AND r.source_id=:protocol_source) OR EXISTS (SELECT 1 FROM treatment_protocol_items i JOIN treatment_item_sources r ON r.item_id=i.id WHERE i.protocol_id=p.id AND r.source_id=:item_source) ORDER BY p.id'.($lock ? ' FOR UPDATE' : ''), ['protocol_source'=>$sourceId,'item_source'=>$sourceId]);
    }
}
