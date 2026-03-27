<?php

namespace Tests\Unit;

use App\Exceptions\RoleAssignmentException;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RoleAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'koordinator', 'pimpinan', 'pic', 'analis', 'responder'] as $roleName) {
            Role::create(['name' => $roleName, 'guard_name' => 'web']);
        }

        $this->service = new RoleAssignmentService();
    }

    public function test_assigning_exclusive_role_syncs_to_single_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('pic');

        $this->service->assignRole($user, 'admin');

        $this->assertTrue($user->fresh()->hasRole('admin'));
        $this->assertCount(1, $user->fresh()->roles);
    }

    public function test_assigning_non_exclusive_role_when_exclusive_exists_throws(): void
    {
        $this->expectException(RoleAssignmentException::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->service->assignRole($user, 'responder');
    }

    public function test_sync_roles_cannot_mix_exclusive_and_non_exclusive(): void
    {
        $this->expectException(RoleAssignmentException::class);

        $user = User::factory()->create();
        $this->service->syncRoles($user, ['admin', 'pic']);
    }

    public function test_sync_roles_allows_multi_operational_roles(): void
    {
        $user = User::factory()->create();

        $this->service->syncRoles($user, ['pic', 'analis', 'responder']);

        $fresh = $user->fresh();
        $this->assertTrue($fresh->hasRole('pic'));
        $this->assertTrue($fresh->hasRole('analis'));
        $this->assertTrue($fresh->hasRole('responder'));
    }
}
