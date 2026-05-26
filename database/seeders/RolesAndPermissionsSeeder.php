<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Papéis base: super_admin (TI), admin, member, guest
     * Permissões granulares por recurso: channel, message, user, audit
     */
    public function run(): void
    {
        // Limpa o cache de permissões do Spatie antes de seed
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Permissões ──────────────────────────────────────────────
        $permissions = [
            // Usuários
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
            'user.deactivate',

            // Canais
            'channel.view',
            'channel.create',
            'channel.edit',
            'channel.delete',
            'channel.archive',
            'channel.manage-members',

            // Mensagens
            'message.view',
            'message.create',
            'message.edit-own',
            'message.edit-any',
            'message.delete-own',
            'message.delete-any',
            'message.pin',
            'message.broadcast',

            // Auditoria
            'audit.view',
            'audit.export',

            // Admin
            'admin.access',
            'admin.system-health',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ─── Papéis ──────────────────────────────────────────────────

        // super_admin — TI: acesso total irrestrito
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // admin — gerencia workspace, canais e usuários comuns
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'user.view',
            'user.create',
            'user.edit',
            'user.deactivate',
            'channel.view',
            'channel.create',
            'channel.edit',
            'channel.delete',
            'channel.archive',
            'channel.manage-members',
            'message.view',
            'message.create',
            'message.edit-own',
            'message.edit-any',
            'message.delete-own',
            'message.delete-any',
            'message.pin',
            'message.broadcast',
            'audit.view',
            'admin.access',
        ]);

        // member — usuário comum com acesso de leitura e escrita em canais públicos
        $member = Role::firstOrCreate(['name' => 'member']);
        $member->givePermissionTo([
            'channel.view',
            'channel.create',
            'message.view',
            'message.create',
            'message.edit-own',
            'message.delete-own',
        ]);

        // guest — somente leitura
        $guest = Role::firstOrCreate(['name' => 'guest']);
        $guest->givePermissionTo([
            'channel.view',
            'message.view',
        ]);
    }
}
