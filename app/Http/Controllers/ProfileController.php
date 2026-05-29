<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * AUTH-04 — Retorna o perfil do usuário autenticado.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->present($request->user()));
    }

    /**
     * AUTH-04 — Atualiza nome, avatar e/ou status (parcial).
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->only(['name', 'avatar', 'status']));
        $user->save();

        return response()->json($this->present($user));
    }

    /**
     * Formato público do perfil.
     *
     * @return array<string, mixed>
     */
    private function present($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'status' => $user->status,
        ];
    }
}
