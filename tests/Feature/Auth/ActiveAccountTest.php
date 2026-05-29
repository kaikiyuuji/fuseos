<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActiveAccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AUTH-06 — Usuário ativo acessa rotas protegidas normalmente.
     */
    public function test_active_user_can_access_protected_routes(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);

        $this->getJson('/api/profile')->assertOk();
    }

    /**
     * AUTH-06 — Usuário inativo é bloqueado mesmo com token válido.
     */
    public function test_inactive_user_is_blocked_with_403(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        Sanctum::actingAs($user);

        $this->getJson('/api/profile')->assertStatus(403);
        $this->getJson('/api/user')->assertStatus(403);
    }

    /**
     * AUTH-06 — Por padrão um usuário recém-criado é ativo.
     */
    public function test_users_are_active_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->fresh()->is_active);
        $this->assertTrue($user->isActive());
    }
}
