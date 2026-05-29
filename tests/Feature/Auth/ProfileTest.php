<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AUTH-04 — Usuário autenticado vê o próprio perfil.
     */
    public function test_user_can_view_their_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria TI',
            'avatar' => 'avatars/maria.png',
            'status' => 'Em reunião',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('name', 'Maria TI')
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('avatar', 'avatars/maria.png')
            ->assertJsonPath('status', 'Em reunião');
    }

    /**
     * AUTH-04 — Perfil exige autenticação.
     */
    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/profile')->assertStatus(401);
        $this->patchJson('/api/profile', ['name' => 'X'])->assertStatus(401);
    }

    /**
     * AUTH-04 — Usuário atualiza nome, avatar e status.
     */
    public function test_user_can_update_their_profile(): void
    {
        $user = User::factory()->create(['name' => 'Antigo']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/profile', [
            'name' => 'Novo Nome',
            'avatar' => 'avatars/novo.png',
            'status' => 'Disponível',
        ])->assertOk()
            ->assertJsonPath('name', 'Novo Nome')
            ->assertJsonPath('status', 'Disponível');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Novo Nome',
            'avatar' => 'avatars/novo.png',
            'status' => 'Disponível',
        ]);
    }

    /**
     * AUTH-04 — Atualização parcial: enviar só o status não apaga o nome.
     */
    public function test_profile_update_is_partial(): void
    {
        $user = User::factory()->create(['name' => 'Preservado']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/profile', ['status' => 'Ausente'])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Preservado',
            'status' => 'Ausente',
        ]);
    }

    /**
     * AUTH-04 — Email e senha não são alteráveis pelo endpoint de perfil.
     */
    public function test_profile_update_cannot_change_email_or_password(): void
    {
        $user = User::factory()->create(['email' => 'fixo@fuseos.test']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/profile', [
            'name' => 'Mudou',
            'email' => 'hacker@fuseos.test',
            'password' => 'nova-senha',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'fixo@fuseos.test',
        ]);
    }

    /**
     * AUTH-04 — Validação: nome não pode ser vazio nem exceder o limite.
     */
    public function test_profile_update_validates_input(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/profile', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->patchJson('/api/profile', ['name' => str_repeat('a', 256)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }
}
