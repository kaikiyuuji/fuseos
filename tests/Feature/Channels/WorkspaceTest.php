<?php

namespace Tests\Feature\Channels;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CHAN-01 — Usuário autenticado cria um workspace (empresa).
     */
    public function test_authenticated_user_can_create_workspace(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/workspaces', [
            'name' => 'Acme Corp',
            'description' => 'Workspace da Acme',
        ]);

        $response->assertCreated()
            ->assertJsonPath('workspace.name', 'Acme Corp')
            ->assertJsonStructure(['workspace' => ['id', 'name', 'slug', 'owner_id']]);

        $this->assertDatabaseHas('workspaces', [
            'name' => 'Acme Corp',
            'owner_id' => $user->id,
        ]);
    }

    /**
     * CHAN-01 — O criador vira dono e também membro do workspace.
     */
    public function test_creator_becomes_owner_and_member(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/workspaces', ['name' => 'Acme Corp'])->assertCreated();

        $workspace = Workspace::firstWhere('name', 'Acme Corp');

        $this->assertSame($user->id, $workspace->owner_id);
        $this->assertTrue($workspace->members()->where('users.id', $user->id)->exists());
    }

    /**
     * CHAN-01 — Criação de workspace exige autenticação.
     */
    public function test_workspace_creation_requires_authentication(): void
    {
        $this->postJson('/api/workspaces', ['name' => 'Acme'])->assertStatus(401);
    }

    /**
     * CHAN-01 — Nome é obrigatório.
     */
    public function test_workspace_requires_name(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/workspaces', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    /**
     * CHAN-01 — Slugs são gerados automaticamente e únicos.
     */
    public function test_workspace_slug_is_generated_and_unique(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $first = $this->postJson('/api/workspaces', ['name' => 'Acme Corp'])->json('workspace.slug');
        $second = $this->postJson('/api/workspaces', ['name' => 'Acme Corp'])->json('workspace.slug');

        $this->assertSame('acme-corp', $first);
        $this->assertNotSame($first, $second);
    }
}
