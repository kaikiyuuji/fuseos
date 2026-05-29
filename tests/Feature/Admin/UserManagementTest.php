<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * AUTH-02 / RBAC-03 — admin cria usuário (restrito à TI).
     */
    public function test_admin_can_create_user(): void
    {
        $this->actingAsRole('admin');

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Novo Colaborador',
            'email' => 'novo@fuseos.test',
            'password' => 'senha-forte-123',
            'role' => 'member',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'novo@fuseos.test');

        $this->assertDatabaseHas('users', [
            'email' => 'novo@fuseos.test',
            'is_active' => true,
        ]);

        $created = User::where('email', 'novo@fuseos.test')->first();
        $this->assertTrue($created->hasRole('member'));
        // A senha é hasheada, nunca armazenada em texto plano.
        $this->assertNotSame('senha-forte-123', $created->password);
    }

    /**
     * AUTH-02 / RBAC-03 — member comum não pode criar usuários.
     */
    public function test_member_cannot_create_user(): void
    {
        $this->actingAsRole('member');

        $this->postJson('/api/admin/users', [
            'name' => 'X',
            'email' => 'x@fuseos.test',
            'password' => 'senha-forte-123',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('users', ['email' => 'x@fuseos.test']);
    }

    /**
     * RBAC-03 — rota administrativa exige autenticação.
     */
    public function test_unauthenticated_cannot_create_user(): void
    {
        $this->postJson('/api/admin/users', [
            'name' => 'X',
            'email' => 'x@fuseos.test',
            'password' => 'senha-forte-123',
        ])->assertStatus(401);
    }

    /**
     * AUTH-02 — validação de entrada na criação de usuário.
     */
    public function test_create_user_validates_input(): void
    {
        $this->actingAsRole('super_admin');

        // Campos obrigatórios ausentes.
        $this->postJson('/api/admin/users', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        // Email duplicado.
        User::factory()->create(['email' => 'dup@fuseos.test']);
        $this->postJson('/api/admin/users', [
            'name' => 'Dup',
            'email' => 'dup@fuseos.test',
            'password' => 'senha-forte-123',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        // Senha curta.
        $this->postJson('/api/admin/users', [
            'name' => 'Curto',
            'email' => 'curto@fuseos.test',
            'password' => '123',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    /**
     * AUTH-03 — admin desativa usuário (não apaga o registro).
     */
    public function test_admin_can_deactivate_user(): void
    {
        $this->actingAsRole('admin');
        $target = User::factory()->create(['is_active' => true]);

        $this->deleteJson("/api/admin/users/{$target->id}")->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => false,
        ]);
    }

    /**
     * AUTH-03 — desativar um usuário revoga todos os seus tokens.
     */
    public function test_deactivating_user_revokes_their_tokens(): void
    {
        $this->actingAsRole('admin');
        $target = User::factory()->create();
        $target->createToken('auth');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->deleteJson("/api/admin/users/{$target->id}")->assertOk();

        $this->assertSame(0, $target->fresh()->tokens()->count());
    }

    /**
     * AUTH-03 / RBAC-03 — member não pode desativar usuários.
     */
    public function test_member_cannot_deactivate_user(): void
    {
        $this->actingAsRole('member');
        $target = User::factory()->create(['is_active' => true]);

        $this->deleteJson("/api/admin/users/{$target->id}")->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => true,
        ]);
    }
}
