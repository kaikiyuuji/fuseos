<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AUTH-01 — Login com credenciais válidas retorna token Sanctum + usuário.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'ti@fuseos.test',
            'password' => Hash::make('senha-secreta'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'ti@fuseos.test',
            'password' => 'senha-secreta',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'ti@fuseos.test');

        // O token deve ser uma string não vazia e persistido no banco.
        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    /**
     * AUTH-01 — Senha incorreta é rejeitada e nenhum token é emitido.
     */
    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'ti@fuseos.test',
            'password' => Hash::make('senha-secreta'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'ti@fuseos.test',
            'password' => 'senha-errada',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * AUTH-01 — Email inexistente é rejeitado sem vazar qual campo falhou.
     */
    public function test_login_fails_with_unknown_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'ninguem@fuseos.test',
            'password' => 'qualquer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /**
     * AUTH-01 — Email e senha são obrigatórios.
     */
    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
