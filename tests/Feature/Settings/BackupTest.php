<?php

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('guests are redirected to the login page', function () {
    $this->get(route('backup.edit'))->assertRedirect(route('login'));
    $this->get(route('backup.download'))->assertRedirect(route('login'));
    $this->post(route('backup.import'))->assertRedirect(route('login'));
});

test('the backup page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('backup.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/backup'));
});

test('the sql backup contains application data and excludes ephemeral tables', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create(['name' => 'Cash Wallet']);

    $sql = $this->actingAs($user)
        ->get(route('backup.download'))
        ->assertOk()
        ->assertHeader('content-type', 'application/sql')
        ->streamedContent();

    expect($sql)
        ->toContain('DROP TABLE IF EXISTS `users`;')
        ->toContain('DROP TABLE IF EXISTS `accounts`;')
        ->toContain('Cash Wallet')
        ->toContain($user->email)
        ->not->toContain('DROP TABLE IF EXISTS `sessions`;')
        ->not->toContain('DROP TABLE IF EXISTS `cache`;');
});

test('importing a backup requires a sql file', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('backup.import'), ['password' => 'password'])
        ->assertSessionHasErrors('backup');
});

test('importing a backup rejects a file that is not sql', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('backup.import'), [
            'backup' => UploadedFile::fake()->create('backup.txt', 10),
            'password' => 'password',
        ])
        ->assertSessionHasErrors('backup');
});

test('importing a backup requires the current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('backup.import'), [
            'backup' => UploadedFile::fake()->createWithContent('backup.sql', 'SELECT 1;'),
            'password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('password');
});

test('a valid backup file restores the database', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create(['name' => 'Cash Wallet']);

    $sql = $this->actingAs($user)
        ->get(route('backup.download'))
        ->streamedContent();

    Account::query()->update(['name' => 'Tampered']);

    $this->actingAs($user)
        ->post(route('backup.import'), [
            'backup' => UploadedFile::fake()->createWithContent('backup.sql', $sql),
            'password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('backup.edit'));

    expect(Account::first()->name)->toBe('Cash Wallet');
});
