<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed data realistas para desenvolvimento local.
     *
     * Cria:
     *  - 1 admin TI (super_admin)
     *  - 1 admin de workspace (admin)
     *  - 5 membros regulares (member)
     *  - 1 convidado (guest)
     *  - 1 workspace principal
     *  - 3 canais públicos: geral, dev, avisos
     *  - 1 canal privado: ti-interno
     *  - Mensagens de exemplo em cada canal
     */
    public function run(): void
    {
        // ─── Usuários ────────────────────────────────────────────────

        $it = User::firstOrCreate(
            ['email' => 'ti@fuseos.local'],
            [
                'name' => 'Administrador TI',
                'password' => Hash::make('password'),
            ]
        );
        $it->assignRole('super_admin');

        $admin = User::firstOrCreate(
            ['email' => 'admin@fuseos.local'],
            [
                'name' => 'Admin Workspace',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        $members = collect();
        $memberData = [
            ['name' => 'Ana Silva',    'email' => 'ana@fuseos.local'],
            ['name' => 'Bruno Costa',  'email' => 'bruno@fuseos.local'],
            ['name' => 'Carla Mendes', 'email' => 'carla@fuseos.local'],
            ['name' => 'Diego Rocha',  'email' => 'diego@fuseos.local'],
            ['name' => 'Elena Santos', 'email' => 'elena@fuseos.local'],
        ];

        foreach ($memberData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            $user->assignRole('member');
            $members->push($user);
        }

        $guest = User::firstOrCreate(
            ['email' => 'visitante@fuseos.local'],
            [
                'name' => 'Visitante Externo',
                'password' => Hash::make('password'),
            ]
        );
        $guest->assignRole('guest');

        // ─── Workspace ───────────────────────────────────────────────

        $workspace = Workspace::firstOrCreate(
            ['slug' => 'acme-corp'],
            [
                'name' => 'ACME Corp',
                'description' => 'Workspace principal da empresa para comunicação interna.',
                'owner_id' => $admin->id,
            ]
        );

        // Membros no workspace
        $allUsers = $members->prepend($admin)->prepend($it);
        foreach ($allUsers as $user) {
            $workspace->members()->syncWithoutDetaching([
                $user->id => ['role' => $user->hasRole('super_admin') || $user->hasRole('admin') ? 'admin' : 'member'],
            ]);
        }

        // ─── Canais Públicos ─────────────────────────────────────────

        $channelGeral = Channel::firstOrCreate(
            ['workspace_id' => $workspace->id, 'slug' => 'geral'],
            [
                'name' => 'geral',
                'description' => 'Canal para comunicações gerais da empresa.',
                'is_private' => false,
                'created_by_id' => $admin->id,
            ]
        );

        $channelDev = Channel::firstOrCreate(
            ['workspace_id' => $workspace->id, 'slug' => 'desenvolvimento'],
            [
                'name' => 'desenvolvimento',
                'description' => 'Discussões técnicas e desenvolvimento de software.',
                'is_private' => false,
                'created_by_id' => $admin->id,
            ]
        );

        $channelAvisos = Channel::firstOrCreate(
            ['workspace_id' => $workspace->id, 'slug' => 'avisos'],
            [
                'name' => 'avisos',
                'description' => 'Comunicados importantes da empresa. Somente admins postam.',
                'is_private' => false,
                'created_by_id' => $admin->id,
            ]
        );

        // ─── Canal Privado ───────────────────────────────────────────

        $channelTI = Channel::firstOrCreate(
            ['workspace_id' => $workspace->id, 'slug' => 'ti-interno'],
            [
                'name' => 'ti-interno',
                'description' => 'Canal restrito da equipe de TI.',
                'is_private' => true,
                'created_by_id' => $it->id,
            ]
        );

        // ─── Mensagens de Exemplo ────────────────────────────────────

        $this->seedMessages($channelGeral, $members->all(), [
            'Bom dia, equipe! Semana de entregas — vamos nessa! 🚀',
            'Lembrete: reunião de alinhamento hoje às 15h na sala de videoconferência.',
            'Alguém pode me ajudar com o relatório mensal?',
            'Enviei o arquivo de planilha por e-mail para todos.',
            'Ótimo trabalho no projeto X, pessoal! Parabéns! 🎉',
        ]);

        $this->seedMessages($channelDev, $members->all(), [
            'PR aberto para revisão: feat/add-reverb-events — pode dar uma olhada?',
            'Alguém conseguiu reproduzir o bug #142? Estou tentando aqui sem sucesso.',
            'Migrei o ambiente para Docker Compose, bem mais simples agora.',
            'Atualizei o README com as instruções de setup local.',
            'Code review feito! Aprovei com um comentário menor.',
            'Deploy em staging feito. Podem testar no ambiente de homologação.',
        ]);

        $this->seedMessages($channelAvisos, [$admin], [
            '📢 Comunicado: O sistema entrará em manutenção no próximo sábado das 22h às 00h.',
            '📢 Nova política de senhas a partir do mês que vem. Verifiquem o e-mail corporativo.',
        ]);

        // Mensagem do sistema no canal de TI
        Message::firstOrCreate(
            ['channel_id' => $channelTI->id, 'type' => 'system', 'content' => 'Canal criado.'],
            ['user_id' => null]
        );

        $this->command->info('✅ DevelopmentSeeder concluído.');
        $this->command->table(
            ['Papel', 'Email', 'Senha'],
            [
                ['super_admin', 'ti@fuseos.local', 'password'],
                ['admin', 'admin@fuseos.local', 'password'],
                ['member', 'ana@fuseos.local', 'password'],
                ['member', 'bruno@fuseos.local', 'password'],
                ['member', 'carla@fuseos.local', 'password'],
                ['member', 'diego@fuseos.local', 'password'],
                ['member', 'elena@fuseos.local', 'password'],
                ['guest', 'visitante@fuseos.local', 'password'],
            ]
        );
    }

    /**
     * Cria mensagens de exemplo em um canal com usuários rotacionados.
     *
     * @param  User[]  $users
     * @param  string[]  $contents
     */
    private function seedMessages(Channel $channel, array $users, array $contents): void
    {
        foreach ($contents as $i => $content) {
            $user = $users[$i % count($users)];
            Message::firstOrCreate(
                ['channel_id' => $channel->id, 'content' => $content],
                ['user_id' => $user->id, 'type' => 'text']
            );
        }
    }
}
