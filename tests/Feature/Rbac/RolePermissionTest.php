<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * RBAC-01 — Os quatro papéis base existem.
     */
    public function test_base_roles_exist(): void
    {
        foreach (['super_admin', 'admin', 'member', 'guest'] as $role) {
            $this->assertTrue(Role::where('name', $role)->exists(), "Papel ausente: {$role}");
        }
    }

    /**
     * RBAC-02 — Permissões granulares por recurso existem.
     */
    public function test_granular_permissions_exist(): void
    {
        $expected = [
            'user.create', 'user.deactivate',
            'channel.create', 'channel.archive', 'channel.manage-members',
            'message.create', 'message.edit-own', 'message.delete-any',
            'audit.view', 'admin.access',
        ];

        foreach ($expected as $permission) {
            $this->assertTrue(
                Permission::where('name', $permission)->exists(),
                "Permissão ausente: {$permission}"
            );
        }
    }

    /**
     * RBAC-02 — super_admin detém todas as permissões.
     */
    public function test_super_admin_has_every_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->assertEqualsCanonicalizing(
            Permission::pluck('name')->all(),
            $user->getAllPermissions()->pluck('name')->all()
        );
    }

    /**
     * RBAC-02 — admin pode criar usuários; member não.
     */
    public function test_admin_can_manage_users_but_member_cannot(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->assertTrue($admin->can('user.create'));
        $this->assertTrue($admin->can('user.deactivate'));

        $this->assertFalse($member->can('user.create'));
        $this->assertFalse($member->can('admin.access'));
    }

    /**
     * RBAC-02 — guest é somente leitura.
     */
    public function test_guest_is_read_only(): void
    {
        $guest = User::factory()->create();
        $guest->assignRole('guest');

        $this->assertTrue($guest->can('channel.view'));
        $this->assertTrue($guest->can('message.view'));
        $this->assertFalse($guest->can('message.create'));
        $this->assertFalse($guest->can('channel.create'));
    }
}
