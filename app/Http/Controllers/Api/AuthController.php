<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        return response()->json($this->authPayload($user, $validated['device_name'] ?? null), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // Token login skips Fortify's two-factor challenge, so refuse rather than bypass it.
        if ($user->two_factor_confirmed_at !== null) {
            throw ValidationException::withMessages([
                'email' => ['Two-factor authentication is enabled on this account and is not yet supported in the mobile app.'],
            ]);
        }

        // Without "remember", the token expires after a day instead of never.
        $expiresAt = ($validated['remember'] ?? true) ? null : now()->addDay();

        return response()->json($this->authPayload($user, $validated['device_name'] ?? null, $expiresAt));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array{user: array{id: int, name: string, email: string, avatar_url: string|null}, token: string}
     */
    private function authPayload(User $user, ?string $deviceName, ?DateTimeInterface $expiresAt = null): array
    {
        return [
            'user' => $this->userPayload($user),
            'token' => $user->createToken($deviceName ?? 'mobile', ['*'], $expiresAt)->plainTextToken,
        ];
    }

    /**
     * @return array{id: int, name: string, email: string, avatar_url: string|null}
     */
    private function userPayload(User $user): array
    {
        return $user->toApiPayload();
    }
}
