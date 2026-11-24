<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /** POST /api/register — после успеха SPA ведёт на страницу входа (токен не выдаём). */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated()); // пароль хешируется кастом 'hashed' в модели User

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    /** POST /api/login → { token, user } */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Неверный email или пароль'], 401);
        }

        return response()->json([
            'token' => $user->createToken('spa')->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    /** POST /api/logout — удаляет текущий токен */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /** GET /api/me */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
