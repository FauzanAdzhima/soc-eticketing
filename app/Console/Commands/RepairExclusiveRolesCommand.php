<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\RoleAssignmentService;
use Illuminate\Console\Command;

class RepairExclusiveRolesCommand extends Command
{
    protected $signature = 'rbac:repair-exclusive-roles {--apply : Apply fixes instead of dry-run}';

    protected $description = 'Audit and optionally repair users with exclusive-role violations.';

    public function handle(RoleAssignmentService $roleAssignmentService): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply
            ? 'Apply mode enabled. Fixing exclusive role violations...'
            : 'Dry-run mode. No changes will be written.');

        $users = User::query()->with('roles')->get();
        $violations = 0;
        $fixed = 0;

        foreach ($users as $user) {
            $result = $roleAssignmentService->getViolations($user);
            if (!$result['has_mixed_exclusive_and_non_exclusive'] && !$result['has_multiple_exclusive_roles']) {
                continue;
            }

            $violations++;
            $this->warn(sprintf(
                'User #%d (%s) violates exclusive roles: [%s]',
                $user->id,
                $user->email,
                implode(', ', $result['all_roles'])
            ));

            if (!$apply) {
                continue;
            }

            $repair = $roleAssignmentService->repairExclusiveRoleViolations($user);
            if ($repair['changed']) {
                $fixed++;
                $this->line(sprintf(
                    '  -> fixed: kept [%s], dropped [%s]',
                    $repair['kept_role'],
                    implode(', ', array_diff($repair['before'], $repair['after']))
                ));
            }
        }

        $this->newLine();
        $this->info("Violations found: {$violations}");
        $this->info("Violations fixed: {$fixed}");

        return self::SUCCESS;
    }
}
