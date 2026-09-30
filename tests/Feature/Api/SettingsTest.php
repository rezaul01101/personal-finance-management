<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('guests cannot reach settings endpoints', function () {
    $this->patchJson('/api/v1/settings/profile', [])->assertUnauthorized();
    $this->putJson('/api/v1/settings/password', [])->assertUnauthorized();
    $this->deleteJson('/api/v1/settings/account', [])->assertUnauthorized();
    $this->postJson('/api/v1/settings/backup/link')->assertUnauthorized();
    $this->postJson('/api/v1/settings/avatar', [])->assertUnauthorized();
    $this->deleteJson('/api/v1/settings/avatar')->assertUnauthorized();
});

test('the profile can be updated and a changed email is unverified', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/settings/profile', ['name' => 'New Name', 'email' => 'new@example.com'])
        ->assertOk()
        ->assertJsonPath('user.name', 'New Name')
        ->assertJsonPath('user.email', 'new@example.com');

    expect($user->fresh())
        ->name->toBe('New Name')
        ->email_verified_at->toBeNull();
});

test('keeping the same email stays verified and duplicates are rejected', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/settings/profile', ['name' => 'Same', 'email' => $user->email])->assertOk();
    expect($user->fresh()->email_verified_at)->not->toBeNull();

    $this->patchJson('/api/v1/settings/profile', ['name' => 'Same', 'email' => $other->email])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('the password can be changed with the current password', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/settings/password', [
        'current_password' => 'password',
        'password' => 'a-new-password-123',
        'password_confirmation' => 'a-new-password-123',
    ])->assertNoContent();

    expect(Hash::check('a-new-password-123', $user->fresh()->password))->toBeTrue();
});

test('changing the password fails with a wrong current password or mismatch', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->putJson('/api/v1/settings/password', [
        'current_password' => 'wrong',
        'password' => 'a-new-password-123',
        'password_confirmation' => 'different',
    ])->assertUnprocessable()->assertJsonValidationErrors(['current_password', 'password']);
});

test('the account can be deleted with the password', function () {
    $user = User::factory()->create();
    $user->createToken('mobile');
    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/settings/account', ['password' => 'wrong'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');
    $this->assertModelExists($user);

    $this->deleteJson('/api/v1/settings/account', ['password' => 'password'])->assertNoContent();
    $this->assertModelMissing($user);
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('the backup link is signed and downloads as sql without a token', function () {
    Sanctum::actingAs(User::factory()->create());

    $url = $this->postJson('/api/v1/settings/backup/link')
        ->assertOk()
        ->assertJsonStructure(['data' => ['url', 'expires_at']])
        ->json('data.url');

    expect($url)->toContain('signature=');

    app('auth')->forgetGuards();

    // The dump itself uses MySQL-only SQL, so only the response headers are asserted (the body is streamed lazily).
    $response = $this->get($url)->assertOk()->assertHeader('content-type', 'application/sql');

    expect($response->headers->get('content-disposition'))->toContain('attachment')->toContain('backup-')->toContain('.sql');
});

test('the backup download rejects an unsigned or tampered url', function () {
    $this->get('/api/v1/settings/backup/download')->assertForbidden();

    Sanctum::actingAs(User::factory()->create());
    $this->get('/api/v1/settings/backup/download')->assertForbidden();
});

test('a profile photo can be uploaded and replaces the previous one', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $first = $this->post('/api/v1/settings/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg')], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('user.id', $user->id);

    $firstPath = $user->fresh()->avatar_path;
    expect($firstPath)->toStartWith('avatars/');
    Storage::disk('public')->assertExists($firstPath);
    expect($first->json('user.avatar_url'))->toBe(Storage::disk('public')->url($firstPath));

    $this->post('/api/v1/settings/avatar', ['avatar' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])
        ->assertOk();

    $secondPath = $user->fresh()->avatar_path;
    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);
});

test('the profile photo must be an image under 5 MB', function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->create());

    $this->post('/api/v1/settings/avatar', ['avatar' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('avatar');

    $this->post('/api/v1/settings/avatar', ['avatar' => UploadedFile::fake()->image('huge.jpg')->size(6000)], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('avatar');

    $this->postJson('/api/v1/settings/avatar', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('avatar');
});

test('the profile photo can be removed', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->post('/api/v1/settings/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg')], ['Accept' => 'application/json'])->assertOk();
    $path = $user->fresh()->avatar_path;

    $this->deleteJson('/api/v1/settings/avatar')
        ->assertOk()
        ->assertJsonPath('user.avatar_url', null);

    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);

    // Removing when there is no photo is a harmless no-op.
    $this->deleteJson('/api/v1/settings/avatar')->assertOk()->assertJsonPath('user.avatar_url', null);
});

test('the user payload includes the avatar url', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('user.avatar_url', null);

    $this->post('/api/v1/settings/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg')], ['Accept' => 'application/json'])->assertOk();

    expect($this->getJson('/api/v1/auth/me')->assertOk()->json('user.avatar_url'))
        ->toBe(Storage::disk('public')->url($user->fresh()->avatar_path));
});

test('deleting the account also deletes the profile photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->post('/api/v1/settings/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg')], ['Accept' => 'application/json'])->assertOk();
    $path = $user->fresh()->avatar_path;

    $this->deleteJson('/api/v1/settings/account', ['password' => 'password'])->assertNoContent();

    Storage::disk('public')->assertMissing($path);
});
