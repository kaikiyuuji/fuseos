<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    /**
     * CHAN-01 — Cria um workspace (empresa). O criador vira dono e membro admin.
     */
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $user = $request->user();

        $workspace = Workspace::create([
            'name' => $request->string('name'),
            'slug' => $this->uniqueSlug((string) $request->string('name')),
            'description' => $request->input('description'),
            'owner_id' => $user->id,
        ]);

        $workspace->members()->attach($user->id, ['role' => 'admin']);

        return response()->json([
            'workspace' => $workspace->only(['id', 'name', 'slug', 'description', 'owner_id']),
        ], 201);
    }

    /**
     * Gera um slug único para o workspace (acme-corp, acme-corp-2, ...).
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $n = 1;

        while (Workspace::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$n);
        }

        return $slug;
    }
}
