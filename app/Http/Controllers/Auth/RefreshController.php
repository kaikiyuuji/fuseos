<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefreshController extends Controller
{
    /**
     * AUTH-05 — Rotaciona o token: revoga o atual e emite um novo.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();
        $token = $user->createToken('auth')->plainTextToken;

        return response()->json(['token' => $token]);
    }
}
