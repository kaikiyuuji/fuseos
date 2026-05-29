<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * AUTH-02 — Cria um usuário (restrito à TI via permission:user.create).
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => $request->string('password'), // cast 'hashed' aplica o hash
            'is_active' => true,
        ]);

        $user->assignRole($request->input('role', 'member'));

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ],
        ], 201);
    }

    /**
     * AUTH-03 — Desativa um usuário (sem apagar o registro) e revoga seus tokens.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->is_active = false;
        $user->save();
        $user->tokens()->delete();

        return response()->json(['message' => 'Usuário desativado.']);
    }
}
