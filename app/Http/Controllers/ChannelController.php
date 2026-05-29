<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChannelRequest;
use App\Models\Channel;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChannelController extends Controller
{
    /**
     * CHAN-03 — Lista/busca canais visíveis ao usuário no workspace.
     * Canais públicos são sempre visíveis; privados, apenas para membros.
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();
        $this->assertWorkspaceMember($workspace, $user->id);

        $query = $workspace->channels()
            ->where(function ($q) use ($user) {
                $q->where('is_private', false)
                    ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id));
            });

        if ($search = $request->query('search')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return response()->json(['channels' => $query->orderBy('name')->get()]);
    }

    /**
     * CHAN-02 — Cria um canal público ou privado no workspace.
     */
    public function store(StoreChannelRequest $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();
        $this->assertWorkspaceMember($workspace, $user->id);

        $channel = Channel::create([
            'workspace_id' => $workspace->id,
            'name' => $request->string('name'),
            'slug' => $this->uniqueSlug($workspace, (string) $request->string('name')),
            'description' => $request->input('description'),
            'is_private' => $request->boolean('is_private'),
            'created_by_id' => $user->id,
        ]);

        // O criador entra automaticamente no canal.
        $channel->members()->attach($user->id);

        return response()->json([
            'channel' => $channel->only([
                'id', 'workspace_id', 'name', 'slug', 'description', 'is_private', 'created_by_id',
            ]),
        ], 201);
    }

    /**
     * Garante que o usuário pertence ao workspace (403 caso contrário).
     */
    private function assertWorkspaceMember(Workspace $workspace, int $userId): void
    {
        abort_unless(
            $workspace->members()->where('users.id', $userId)->exists(),
            403,
            'Você não pertence a este workspace.'
        );
    }

    /**
     * Gera um slug único dentro do workspace.
     */
    private function uniqueSlug(Workspace $workspace, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $n = 1;

        while ($workspace->channels()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$n);
        }

        return $slug;
    }
}
