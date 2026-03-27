<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RepairExclusiveRolesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'koordinator', 'pimpinan', 'pic', 'analis', 'responder'] as $roleName) {
            Role::create(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    public function test_command_dry_run_does_not_apply_changes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->assignRole('pic');

        $this->artisan('rbac:repair-exclusive-roles')
            ->expectsOutputToContain('Dry-run mode')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->hasRole('admin'));
        $this->assertTrue($user->fresh()->hasRole('pic'));
    }

    public function test_command_apply_repairs_role_violation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->assignRole('responder');

        $this->artisan('rbac:repair-exclusive-roles --apply')
            ->expectsOutputToContain('Apply mode enabled')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->hasRole('admin'));
        $this->assertFalse($user->fresh()->hasRole('responder'));
        $this->assertCount(1, $user->fresh()->roles);
    }
}
