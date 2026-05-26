<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Garante que o modelo User pode ser criado via factory.
     */
    public function test_user_can_be_created_with_factory(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->id);
        $this->assertNotNull($user->email);
    }

    /**
     * Garante que o campo password é ocultado na serialização.
     */
    public function test_password_is_hidden_from_serialization(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    /**
     * Garante que o modelo User tem os campos corretos preenchíveis.
     */
    public function test_user_has_correct_fillable_attributes(): void
    {
        $user = new User;
        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
    }

    /**
     * Garante que o User pode ter papéis (Spatie Permission).
     */
    public function test_user_can_be_assigned_a_role(): void
    {
        // Criar permissão e papel manualmente para o teste (sem depender de seeder)
        Role::create(['name' => 'member']);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->assertTrue($user->hasRole('member'));
    }
}
