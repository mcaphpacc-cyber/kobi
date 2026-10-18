<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TreatmentEditorialPermissionRepository;
use RuntimeException;

class TreatmentEditorialAuthorizationService
{
    private const CAPABILITIES = ['view'=>'any','edit'=>'can_edit','submit'=>'can_edit','create_source'=>'can_edit','manage_sources'=>'can_edit','review'=>'can_review','approve'=>'can_review','request_changes'=>'can_review','verify_source'=>'can_review','publish'=>'can_publish','withdraw'=>'can_publish'];

    public function __construct(private AuthService $auth, private TreatmentEditorialPermissionRepository $permissions, private TreatmentGovernanceRules $rules, private TreatmentGovernancePolicy $policy) {}

    /** Repository failures are not deliberate authorization denials. */
    private function lookup(callable $query): mixed
    {
        try { return $query(); }
        catch (RuntimeException $error) {
            throw new RuntimeException('Treatment authorization data is unavailable.', 0, $error);
        }
    }

    public function actor(bool $lock = false): int
    {
        $id = $this->auth->id();
        if (!$id || $id < 1) { throw new RuntimeException('Treatment editorial access denied.'); }
        $user = $this->lookup(fn() => $lock ? ($this->permissions->lockUsers([$id])[$id] ?? null) : $this->permissions->user($id));
        if (!$user || ($user['status'] ?? null) !== 'active') { throw new RuntimeException('Treatment editorial access denied.'); }
        return $id;
    }

    public function administrator(bool $lock = false): int
    {
        $actor=$this->actor($lock);
        if (!in_array($actor, $this->policy->administrators(), true)) { throw new RuntimeException('Treatment editorial access denied.'); }
        return $actor;
    }

    public function require(string $action, int $systemId, bool $lock = false): int
    {
        $this->rules->id($systemId);
        $actor=$this->actor($lock);
        $capability=self::CAPABILITIES[$action] ?? null;
        if ($capability === null) { throw new RuntimeException('Treatment editorial access denied.'); }
        $grant=$this->lookup(fn() => $this->permissions->active($actor, $systemId, $lock));
        if (!$grant || ($grant['revoked_at'] ?? null) !== null) { throw new RuntimeException('Treatment editorial access denied.'); }
        $this->rules->grant($grant);
        if ((int)$grant['user_id'] !== $actor || (int)$grant['treatment_system_id'] !== $systemId
            || ($capability !== 'any' && (int)$grant[$capability] !== 1)) { throw new RuntimeException('Treatment editorial access denied.'); }
        return $actor;
    }

    public function can(string $action, int $systemId): bool
    {
        try { $this->require($action, $systemId); return true; }
        catch (RuntimeException $error) {
            if ($error->getPrevious() !== null) { throw $error; }
            return false;
        }
    }
}
