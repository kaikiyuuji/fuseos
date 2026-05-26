<?php

use App\Models\Channel;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels — FuseOS
|--------------------------------------------------------------------------
|
| Aqui estão os canais de broadcast que o Reverb utiliza.
| Canais privados exigem autenticação; canais de presença
| também rastreiam quem está online no canal.
|
*/

// Canal de notificação pessoal de cada usuário (privado)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ─── Canais de Workspace ─────────────────────────────────────────────────

// Canal privado por workspace: eventos gerais do workspace (ex: novo canal criado)
Broadcast::channel('private-workspace.{workspaceId}', function ($user, $workspaceId) {
    // Autoriza se o usuário é membro do workspace
    return $user->workspaces()->where('workspaces.id', $workspaceId)->exists();
});

// ─── Canais de Chat ──────────────────────────────────────────────────────

// Canal privado por canal de chat: recebe novas mensagens e edições
Broadcast::channel('private-channel.{channelId}', function ($user, $channelId) {
    // Autoriza se o canal é público ou o usuário é membro do workspace pai
    $channel = Channel::find($channelId);

    if (! $channel) {
        return false;
    }

    if (! $channel->is_private) {
        // Canal público: qualquer membro do workspace pode escutar
        return $user->workspaces()->where('workspaces.id', $channel->workspace_id)->exists();
    }

    // Canal privado: somente admin ou o criador do canal por agora
    // (a lógica de membros de canal privado será expandida em CHAN-04)
    return $user->hasRole(['super_admin', 'admin'])
        || $channel->created_by_id === $user->id;
});

// Canal de presença por canal: rastreia quem está online em tempo real
Broadcast::channel('presence-channel.{channelId}', function ($user, $channelId) {
    $channel = Channel::find($channelId);

    if (! $channel) {
        return false;
    }

    $isMember = $user->workspaces()->where('workspaces.id', $channel->workspace_id)->exists();

    if (! $isMember) {
        return false;
    }

    // Retorna os dados que ficarão disponíveis para outros usuários no canal de presença
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
