<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('users can register and receive a token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'device_name' => 'iPhone',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.email', 'test@example.com')
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token'])
        ->assertJsonMissingPath('user.password');

    $user = User::where('email', 'test@example.com')->firstOrFail();
    expect(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->tokens()->where('name', 'iPhone')->count())->toBe(1);
});

test('registration validates input', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => '',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('users can log in with valid credentials', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure(['token']);
});

test('login tokens do not expire when remember is true', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ])->assertOk();

    expect($user->tokens()->firstOrFail()->expires_at)->toBeNull();
});

test('login tokens expire after a day when remember is false', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => false,
    ])->assertOk();

    expect($user->tokens()->firstOrFail()->expires_at)->not->toBeNull()
        ->and($user->tokens()->firstOrFail()->expires_at->isBetween(now()->addHours(23), now()->addHours(25)))->toBeTrue();
});

test('login fails with an invalid password', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);

    expect($user->tokens()->count())->toBe(0);
});

test('login is refused for accounts with two factor enabled', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);

    expect($user->tokens()->count())->toBe(0);
});

test('the token authenticates requests to me', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('user.email', $user->email);
});

test('me requires authentication', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

test('logout revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
