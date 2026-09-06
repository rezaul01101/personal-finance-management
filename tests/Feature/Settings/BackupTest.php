<?php

use App\Models\Account;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('backup.edit'))->assertRedirect(route('login'));
    $this->get(route('backup.download'))->assertRedirect(route('login'));
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
