<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Garante que a API está respondendo corretamente.
     * Smoke test básico para validar que o servidor está de pé e configurado.
     */
    public function test_api_responds_with_ok(): void
    {
        // A rota /api/user exige autenticação — espera 401
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    /**
     * Garante que rotas inexistentes retornam 404 no formato JSON da API.
     */
    public function test_unknown_api_route_returns_404(): void
    {
        $response = $this->getJson('/api/rota-que-nao-existe');

        $response->assertStatus(404);
    }
}
