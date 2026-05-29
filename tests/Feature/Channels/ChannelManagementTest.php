<?php

namespace Tests\Feature\Channels;

use App\Models\Channel;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChannelManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Cria um usuário com papel, dono e membro de um novo workspace.
     *
     * @return array{0: User, 1: Workspace}
     */
    private function userInWorkspace(string $role = 'member'): array
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $workspace->members()->attach($user->id, ['role' => 'admin']);

        return [$user, $workspace];
    }

    /**
     * CHAN-02 — Membro do workspace cria canal público.
     */
    public function test_member_can_create_public_channel(): void
    {
        [$user, $workspace] = $this->userInWorkspace('member');
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/workspaces/{$workspace->id}/channels", [
            'name' => 'Geral',
            'is_private' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('channel.name', 'Geral')
            ->assertJsonPath('channel.is_private', false);

        $channel = Channel::firstWhere('name', 'Geral');
        $this->assertSame($workspace->id, $channel->workspace_id);
        $this->assertSame($user->id, $channel->created_by_id);
        // CHAN-02 — o criador entra automaticamente no canal.
        $this->assertTrue($channel->members()->where('users.id', $user->id)->exists());
    }

    /**
     * CHAN-02 — Canal privado pode ser criado.
     */
    public function test_member_can_create_private_channel(): void
    {
        [$user, $workspace] = $this->userInWorkspace('member');
        Sanctum::actingAs($user);

        $this->postJson("/api/workspaces/{$workspace->id}/channels", [
            'name' => 'Diretoria',
            'is_private' => true,
        ])->assertCreated()->assertJsonPath('channel.is_private', true);
    }

    /**
     * CHAN-02 / RBAC-03 — guest (sem channel.create) não cria canal.
     */
    public function test_guest_cannot_create_channel(): void
    {
        [$user, $workspace] = $this->userInWorkspace('guest');
        Sanctum::actingAs($user);

        $this->postJson("/api/workspaces/{$workspace->id}/channels", [
            'name' => 'Proibido',
        ])->assertStatus(403);
    }

    /**
     * CHAN-02 — Criar canal exige autenticação.
     */
    public function test_channel_creation_requires_authentication(): void
    {
        $workspace = Workspace::factory()->create();

        $this->postJson("/api/workspaces/{$workspace->id}/channels", [
            'name' => 'X',
        ])->assertStatus(401);
    }

    /**
     * CHAN-02 — Usuário que não pertence ao workspace não cria canais nele.
     */
    public function test_non_member_cannot_create_channel_in_workspace(): void
    {
        [, $workspace] = $this->userInWorkspace('member');

        $outsider = User::factory()->create();
        $outsider->assignRole('member');
        Sanctum::actingAs($outsider);

        $this->postJson("/api/workspaces/{$workspace->id}/channels", [
            'name' => 'Intruso',
        ])->assertStatus(403);
    }

    /**
     * CHAN-02 — Nome é obrigatório.
     */
    public function test_channel_creation_validates_name(): void
    {
        [$user, $workspace] = $this->userInWorkspace('member');
        Sanctum::actingAs($user);

        $this->postJson("/api/workspaces/{$workspace->id}/channels", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    /**
     * CHAN-02 — Slug é único por workspace, mas pode repetir entre workspaces.
     */
    public function test_channel_slug_is_unique_per_workspace(): void
    {
        [$user, $workspace] = $this->userInWorkspace('member');
        Sanctum::actingAs($user);

        $first = $this->postJson("/api/workspaces/{$workspace->id}/channels", ['name' => 'Geral'])
            ->json('channel.slug');
        $second = $this->postJson("/api/workspaces/{$workspace->id}/channels", ['name' => 'Geral'])
            ->json('channel.slug');

        $this->assertSame('geral', $first);
        $this->assertNotSame($first, $second);
    }

    /**
     * CHAN-03 — Lista canais do workspace.
     */
    public function test_lists_channels_in_workspace(): void
    {
        [$user, $workspace] = $this->userInWorkspace('member');
        Sanctum::actingAs($user);

        Channel::factory()->count(3)->create(['workspace_id' => $workspace->id]);

        $this->getJson("/api/workspaces/{$workspace->id}/channels")
            ->assertOk()
            ->assertJsonCount(3, 'channels');
    }

    /**
     * CHAN-03 — Busca canais por nome.
     */
    public function test_searches_channels_by_name(): void
    {
        [$user, $workspace] = $this->userInWorkspace('member');
        Sanctum::actingAs($user);

        Channel::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Engenharia']);
        Channel::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Marketing']);

        $response = $this->getJson("/api/workspaces/{$workspace->id}/channels?search=eng")
            ->assertOk()
            ->assertJsonCount(1, 'channels');

        $this->assertSame('Engenharia', $response->json('channels.0.name'));
    }

    /**
     * CHAN-03 — Canais privados ficam ocultos para não-membros.
     */
    public function test_private_channels_are_hidden_from_non_members(): void
    {
        [$user, $workspace] = $this->userInWorkspace('member');
        Sanctum::actingAs($user);

        Channel::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Publico', 'is_private' => false]);
        Channel::factory()->private()->create(['workspace_id' => $workspace->id, 'name' => 'Secreto']);

        // O usuário não é membro do canal privado → vê apenas o público.
        $this->getJson("/api/workspaces/{$workspace->id}/channels")
            ->assertOk()
            ->assertJsonCount(1, 'channels')
            ->assertJsonPath('channels.0.name', 'Publico');
    }
}
