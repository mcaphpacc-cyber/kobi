<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/** Database-independent rules. No medical assessment is inferred here. */
class TreatmentGovernanceRules
{
    public const EVIDENCE = ['evidence_supported', 'insufficient_evidence', 'evidence_does_not_support', 'not_assessed'];
    public const RISK_ELIGIBILITY = ['eligible', 'not_eligible', 'not_assessed'];
    public const PURPOSE = ['clinical_management', 'traditional_practice', 'supportive_care', 'wellness_self_care', 'mixed'];
    public const REVIEW = ['draft', 'pending_review', 'changes_required', 'approved'];
    public const PUBLICATION = ['unpublished', 'published', 'withdrawn'];
    public const SOURCE_TYPES = ['guideline', 'systematic_review', 'research_study', 'official_monograph', 'professional_guidance', 'traditional_text', 'other'];
    public const SOURCE_STATES = ['unverified', 'verified', 'withdrawn'];
    public const ITEM_TYPES = ['medicine', 'herb', 'exercise', 'acupoint', 'diet', 'therapy', 'lifestyle'];
    public const EVENTS = ['legacy_initialized', 'content_changed', 'submitted', 'approved', 'changes_required', 'review_reset', 'published', 'withdrawn', 'publication_locales_changed', 'source_invalidated', 'risk_assessed'];
    public const PROTOCOL_CONTENT = ['disease_id', 'treatment_system_id', 'title_en', 'title_hi', 'overview_en', 'overview_hi', 'evidence_classification', 'content_purpose', 'evidence_summary_en', 'evidence_summary_hi', 'applicability_en', 'applicability_hi', 'limitations_en', 'limitations_hi'];
    public const ITEM_CONTENT = ['item_type', 'title_en', 'title_hi', 'instructions_en', 'instructions_hi', 'sort_order', 'is_included', 'evidence_classification', 'content_purpose', 'limitations_en', 'limitations_hi'];
    public const SOURCE_CONTENT = ['source_type', 'title', 'author_or_organization', 'publication', 'citation_text', 'url', 'identifier', 'publication_date', 'edition_or_version', 'accessed_on', 'replaces_source_id'];

    public function value(mixed $value, array $allowed): string
    {
        if (!is_string($value) || !in_array($value, $allowed, true)) {
            throw new RuntimeException('Invalid treatment governance value.');
        }
        return $value;
    }

    public function locales(mixed $value, bool $allowEmpty = true): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new RuntimeException('Invalid treatment language selection.');
        }
        foreach ($value as $locale) { $this->value($locale, ['en', 'hi']); }
        if (count($value) !== count(array_unique($value)) || (!$allowEmpty && $value === [])) {
            throw new RuntimeException('Invalid treatment language selection.');
        }
        return array_values(array_intersect(['en', 'hi'], $value));
    }

    public function storedLocales(string $value): array
    {
        return $this->locales($value === '' ? [] : explode(',', $value));
    }

    public function id(mixed $value): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1 || (string) (int) $value !== (string) $value) {
            throw new RuntimeException('Invalid treatment identifier.');
        }
        return (int) $value;
    }

    public function flag(mixed $value): int
    {
        if (!in_array($value, [0, 1, '0', '1', false, true], true)) {
            throw new RuntimeException('Invalid treatment governance flag.');
        }
        return (int) $value;
    }

    public function text(mixed $value, int $max = 65535, bool $required = false): ?string
    {
        if ($value === null && !$required) { return null; }
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || strlen($value) > $max || ($required && trim($value) === '')) {
            throw new RuntimeException('Invalid treatment content.');
        }
        return $value;
    }

    public function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
    }

    public function timestamp(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s.u', $value, new DateTimeZone('UTC'));
        if (!$date || $date->format('Y-m-d H:i:s.u') !== $value) {
            throw new RuntimeException('Invalid treatment review date.');
        }
        return $value;
    }

    public function legacyProtocol(array $existing): array
    {
        return array_replace($existing, ['review_state' => 'draft', 'publication_status' => 'unpublished', 'content_revision' => 1, 'approved_review_id' => null, 'published_locales' => '', 'risk_eligibility' => 'not_assessed', 'evidence_classification' => 'not_assessed', 'content_purpose' => null, 'evidence_summary_en' => null, 'evidence_summary_hi' => null, 'applicability_en' => null, 'applicability_hi' => null, 'limitations_en' => null, 'limitations_hi' => null, 'updated_at' => null, 'updated_by' => null]);
    }

    public function legacyItem(array $existing): array
    {
        return array_replace($existing, ['is_included' => 0, 'evidence_classification' => 'not_assessed', 'content_purpose' => null, 'limitations_en' => null, 'limitations_hi' => null]);
    }

    public function protocol(array $row): void
    {
        $review = $this->value($row['review_state'] ?? null, self::REVIEW);
        $publication = $this->value($row['publication_status'] ?? null, self::PUBLICATION);
        $this->id($row['content_revision'] ?? null);
        $this->value($row['evidence_classification'] ?? null, self::EVIDENCE);
        $this->value($row['risk_eligibility'] ?? null, self::RISK_ELIGIBILITY);
        if (($row['content_purpose'] ?? null) !== null) { $this->value($row['content_purpose'], self::PURPOSE); }
        $locales = $this->storedLocales($row['published_locales'] ?? '');
        if (($review === 'approved') !== (($row['approved_review_id'] ?? null) !== null)
            || ($publication === 'published' && ($review !== 'approved' || $locales === []))
            || ($publication !== 'published' && $locales !== [])) {
            throw new RuntimeException('Inconsistent treatment governance state.');
        }
        if (($row['approved_review_id'] ?? null) !== null) { $this->id($row['approved_review_id']); }
    }

    public function grant(array $grant): void
    {
        $this->id($grant['user_id'] ?? null);
        $this->id($grant['treatment_system_id'] ?? null);
        $edit = $this->flag($grant['can_edit'] ?? null);
        $review = $this->flag($grant['can_review'] ?? null);
        $publish = $this->flag($grant['can_publish'] ?? null);
        if (!$edit && !$review && !$publish) { throw new RuntimeException('An editorial capability is required.'); }
        $this->text($grant['qualification_scope'] ?? null, 65535, $review === 1);
        $revoked = [$grant['revoked_by'] ?? null, $grant['revoked_at'] ?? null, $grant['revocation_reason'] ?? null];
        if (count(array_filter($revoked, static fn($v) => $v !== null)) !== 0) {
            $this->id($revoked[0]); $this->timestamp($revoked[1] ?? ''); $this->text($revoked[2], 65535, true);
        }
    }

    public function sourceTransition(string $from, string $to): void
    {
        $this->value($from, self::SOURCE_STATES); $this->value($to, self::SOURCE_STATES);
        if (!in_array([$from, $to], [['unverified', 'verified'], ['unverified', 'withdrawn'], ['verified', 'withdrawn']], true)) {
            throw new RuntimeException('Invalid treatment source transition.');
        }
    }

    /** Resolver must return the stored predecessor; missing/cyclic chains fail closed. */
    public function replacement(?int $sourceId, ?int $predecessor, callable $resolve): void
    {
        $seen = [];
        if ($sourceId !== null) { $seen[$this->id($sourceId)] = true; }
        while ($predecessor !== null) {
            $id = $this->id($predecessor);
            if (isset($seen[$id])) { throw new RuntimeException('Invalid treatment source replacement chain.'); }
            $seen[$id] = true;
            $row = $resolve($id);
            if (!$row || $this->id($row['id'] ?? null) !== $id) { throw new RuntimeException('Invalid treatment source replacement chain.'); }
            $predecessor = ($row['replaces_source_id'] ?? null) === null ? null : $this->id($row['replaces_source_id']);
        }
    }

    public function event(array $event): void
    {
        $type = $this->value($event['event_type'] ?? null, self::EVENTS);
        $this->id($event['protocol_id'] ?? null); $this->id($event['content_revision'] ?? null);
        if ($type !== 'legacy_initialized' || ($event['actor_id'] ?? null) !== null) { $this->id($event['actor_id'] ?? null); }
        $this->storedLocales($event['locales'] ?? '');
        $this->flag($event['independence_confirmed'] ?? 0);
        if (in_array($type, ['approved','changes_required','withdrawn','review_reset','source_invalidated','risk_assessed'], true)) { $this->text($event['notes'] ?? null, 65535, true); }
        if ($type==='risk_assessed' && (($event['content_snapshot']??null)===null || $this->flag($event['independence_confirmed']??0)!==1)) { throw new RuntimeException('An independent risk decision snapshot is required.'); }
        $snapshot = $event['content_snapshot'] ?? null; $hash = $event['content_hash'] ?? null;
        if (in_array($type, ['submitted','approved'], true) && ($snapshot === null || $hash === null || ($event['locales'] ?? '') === '')) {
            throw new RuntimeException('A treatment review snapshot is required.');
        }
        if ($snapshot !== null) {
            json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR);
            if (!is_string($hash) || !preg_match('/^[0-9a-f]{64}$/D', $hash) || !hash_equals(hash('sha256', $snapshot), $hash)) { throw new RuntimeException('Invalid treatment snapshot hash.'); }
        } elseif ($hash !== null) { throw new RuntimeException('Invalid treatment snapshot hash.'); }
        if ($type === 'approved') {
            $due = $this->timestamp($event['review_due_at'] ?? '');
            if ($this->flag($event['independence_confirmed'] ?? 0) !== 1 || $due <= ($event['occurred_at'] ?? $this->now())) { throw new RuntimeException('Invalid treatment approval.'); }
        }
    }

    public function independence(int $actor, array $contributors, bool $attested): void
    {
        if (!$attested || in_array($actor, array_map('intval', $contributors), true)) { throw new RuntimeException('Independent treatment review is required.'); }
    }

    public function invalidate(array $row, bool $source = false): array
    {
        $this->protocol($row);
        if (!$source && $row['publication_status'] === 'published') { throw new RuntimeException('Withdraw published treatment content before editing.'); }
        if ((int) $row['content_revision'] >= 4294967295) { throw new RuntimeException('Treatment revision limit reached.'); }
        $row['content_revision'] = (int) $row['content_revision'] + 1;
        $row['approved_review_id'] = null; $row['published_locales'] = '';
        $row['review_state'] = !$source && $row['review_state'] === 'changes_required' ? 'changes_required' : 'draft';
        if ($row['publication_status'] === 'published') { $row['publication_status'] = 'withdrawn'; }
        return $row;
    }
}
