<?php

declare(strict_types=1);

namespace App\Services;

/** Deployment policy, never a source of medical approval. */
class TreatmentGovernancePolicy
{
    /** Initial release: evidence permission only; all other governance gates still apply. */
    public const INITIAL_PUBLICATION_EVIDENCE = [
        'evidence_supported' => true,
        'insufficient_evidence' => false,
        'evidence_does_not_support' => false,
        'not_assessed' => false,
    ];

    /**
     * Fixed requirements for the future publication reader/workflow, not an
     * eligibility predicate. Phase 2C-4 must enforce these through one canonical
     * service shared by public pages and any future API. Risk eligibility is an
     * explicit reviewer decision; it never implies a risk taxonomy or effectiveness.
     */
    public const INITIAL_PUBLICATION_RULES = [
        'wellness_evidence_exception' => false,
        'higher_risk_publication' => false,
        'require_current_review' => true,
        'require_system_scoped_reviewer_qualification' => true,
        'public_reviewer_identity' => false,
        'public_review_rationale' => false,
        'require_explicit_publication' => true,
        'require_withdrawal_before_edit' => true,
        'automatic_legacy_publication' => false,
        'require_shared_public_api_eligibility' => true,
    ];

    public function mode(mixed $settings = null): string
    {
        $settings ??= config('treatment_governance');
        $mode = is_array($settings) ? ($settings['publication_mode'] ?? null) : null;
        return in_array($mode, ['hold', 'governed'], true) ? $mode : 'hold';
    }

    public function administrators(): array
    {
        $settings = config('treatment_governance');
        $ids = is_array($settings) ? ($settings['permission_admin_user_ids'] ?? []) : [];
        if (!is_array($ids) || !array_is_list($ids)) { return []; }
        foreach ($ids as $id) { if (!is_int($id) || $id < 1) { return []; } }
        return array_values(array_unique($ids));
    }

    public function evidenceAllowed(string $classification): bool
    {
        // Unknown values fail closed; purpose (including wellness) creates no exception.
        return self::INITIAL_PUBLICATION_EVIDENCE[$classification] ?? false;
    }

    public function riskAllowed(mixed $decision): bool
    {
        return $decision === 'eligible';
    }

    public function publicTreatments(?callable $governedReader = null): array
    {
        // Stage 2C-1 supplies no public reader. Never fall back to legacy rows,
        // even if deployment configuration is prematurely set to governed.
        return $this->mode() === 'governed' && $governedReader !== null ? $governedReader() : [];
    }
}
