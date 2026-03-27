<?php

namespace App\Services;

use App\Exceptions\RoleAssignmentException;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class RoleAssignmentService
{
    private const EXCLUSIVE_ROLES = ['admin', 'koordinator', 'pimpinan'];

    public function assignRole(User $user, string $roleName): User
    {
        $this->assertRoleExists($roleName);

        if ($this->isExclusiveRole($roleName)) {
            $user->syncRoles([$roleName]);

            return $user->refresh();
        }

        $existingExclusive = $this->getExistingExclusiveRole($user);
        if ($existingExclusive !== null) {
            throw RoleAssignmentException::cannotAssignWhenExclusiveRoleExists($existingExclusive);
        }

        $user->assignRole($roleName);

        return $user->refresh();
    }

    public function syncRoles(User $user, array $roleNames): User
    {
        $roleNames = array_values(array_unique($roleNames));

        foreach ($roleNames as $roleName) {
            $this->assertRoleExists($roleName);
        }

        $exclusiveRoles = array_values(array_intersect($roleNames, self::EXCLUSIVE_ROLES));
        if (count($exclusiveRoles) > 1) {
            throw RoleAssignmentException::cannotMixExclusiveRole(implode(', ', $exclusiveRoles));
        }

        if (count($exclusiveRoles) === 1 && count($roleNames) > 1) {
            throw RoleAssignmentException::cannotMixExclusiveRole($exclusiveRoles[0]);
        }

        $user->syncRoles($roleNames);

        return $user->refresh();
    }

    public function removeRole(User $user, string $roleName): User
    {
        $this->assertRoleExists($roleName);
        $user->removeRole($roleName);

        return $user->refresh();
    }

    public function getViolations(User $user): array
    {
        $roleNames = $user->roles->pluck('name')->values()->all();
        $exclusiveRoles = array_values(array_intersect($roleNames, self::EXCLUSIVE_ROLES));

        return [
            'has_mixed_exclusive_and_non_exclusive' => count($exclusiveRoles) >= 1 && count($roleNames) > 1,
            'has_multiple_exclusive_roles' => count($exclusiveRoles) > 1,
            'exclusive_roles' => $exclusiveRoles,
            'all_roles' => $roleNames,
        ];
    }

    public function repairExclusiveRoleViolations(User $user): array
    {
        $violations = $this->getViolations($user);
        if (!$violations['has_mixed_exclusive_and_non_exclusive'] && !$violations['has_multiple_exclusive_roles']) {
            return [
                'changed' => false,
                'kept_role' => null,
                'before' => $violations['all_roles'],
                'after' => $violations['all_roles'],
            ];
        }

        $keptRole = $violations['exclusive_roles'][0] ?? null;
        if ($keptRole === null) {
            return [
                'changed' => false,
                'kept_role' => null,
                'before' => $violations['all_roles'],
                'after' => $violations['all_roles'],
            ];
        }

        $before = $violations['all_roles'];
        $user->syncRoles([$keptRole]);

        return [
            'changed' => true,
            'kept_role' => $keptRole,
            'before' => $before,
            'after' => [$keptRole],
        ];
    }

    public function exclusiveRoles(): Collection
    {
        return collect(self::EXCLUSIVE_ROLES);
    }

    private function isExclusiveRole(string $roleName): bool
    {
        return in_array($roleName, self::EXCLUSIVE_ROLES, true);
    }

    private function getExistingExclusiveRole(User $user): ?string
    {
        return $user->roles
            ->pluck('name')
            ->first(fn (string $roleName) => $this->isExclusiveRole($roleName));
    }

    private function assertRoleExists(string $roleName): void
    {
        Role::findByName($roleName, 'web');
    }
}
