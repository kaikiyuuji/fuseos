<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AUTH-05 — Logout revoga o token atual do usuário.
     */
    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Reseta o guard cacheado para simular uma requisição HTTP independente.
        $this->app['auth']->forgetGuards();

        // O token revogado não autentica mais.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user')
            ->assertStatus(401);
    }

    /**
     * AUTH-05 — Logout exige autenticação.
     */
    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')->assertStatus(401);
    }

    /**
     * AUTH-05 — Refresh rotaciona o token: emite um novo e revoga o antigo.
     */
    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $oldToken = $user->createToken('auth')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->postJson('/api/auth/refresh');

        $response->assertOk()->assertJsonStructure(['token']);

        $newToken = $response->json('token');
        $this->assertNotSame($oldToken, $newToken);

        // Continua existindo exatamente um token (o novo).
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Reseta o guard cacheado para simular requisições HTTP independentes.
        $this->app['auth']->forgetGuards();

        // Token antigo revogado, novo token válido.
        $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/user')->assertStatus(401);

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$newToken}")
            ->getJson('/api/user')->assertOk();
    }

    /**
     * AUTH-05 — Refresh exige autenticação.
     */
    public function test_refresh_requires_authentication(): void
    {
        $this->postJson('/api/auth/refresh')->assertStatus(401);
    }
}
