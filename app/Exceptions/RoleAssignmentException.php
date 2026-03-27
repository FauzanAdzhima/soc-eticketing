<?php

namespace App\Exceptions;

use RuntimeException;

class RoleAssignmentException extends RuntimeException
{
    public static function cannotAssignWhenExclusiveRoleExists(string $exclusiveRole): self
    {
        return new self("Cannot assign additional role because user already has exclusive role [{$exclusiveRole}].");
    }

    public static function cannotMixExclusiveRole(string $role): self
    {
        return new self("Cannot mix exclusive role [{$role}] with other roles.");
    }
}
