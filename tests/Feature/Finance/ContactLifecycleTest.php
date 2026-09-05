<?php

use App\Models\Account;
use App\Models\Contact;
use App\Models\Loan;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('contacts.index'))->assertRedirect(route('login'));
});

test('a contact can be created', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('contacts.store'), ['name' => 'Anamul'])
        ->assertRedirect(route('contacts.index'));

    $this->assertDatabaseHas('contacts', ['user_id' => $user->id, 'name' => 'Anamul']);
});

test('creating a contact via an ajax request returns it as json', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('contacts.store'), ['name' => 'Anamul'], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

    $response->assertCreated();
    expect($response->json('name'))->toBe('Anamul');
});

test('a contact name must be unique per user', function () {
    $user = User::factory()->create();
    Contact::factory()->for($user)->create(['name' => 'Anamul']);

    $this->actingAs($user)
        ->post(route('contacts.store'), ['name' => 'Anamul'])
        ->assertInvalid(['name']);
});

test('two different users can each have a contact with the same name', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    Contact::factory()->for($userA)->create(['name' => 'Anamul']);

    $this->actingAs($userB)
        ->post(route('contacts.store'), ['name' => 'Anamul'])
        ->assertRedirect(route('contacts.index'));

    $this->assertDatabaseHas('contacts', ['user_id' => $userB->id, 'name' => 'Anamul']);
});

test('a contact can be renamed', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create(['name' => 'Anamul']);

    $this->actingAs($user)
        ->put(route('contacts.update', $contact), ['name' => 'Anamul Haque'])
        ->assertRedirect(route('contacts.index'));

    expect($contact->fresh()->name)->toBe('Anamul Haque');
});

test('a contact with no loans can be deleted', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('contacts.destroy', $contact))
        ->assertRedirect(route('contacts.index'));

    $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
});

test('a contact with loans recorded against them cannot be deleted', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $contact = Contact::factory()->for($user)->create();
    Loan::factory()->for($user)->create(['account_id' => $account->id, 'contact_id' => $contact->id]);

    $this->actingAs($user)
        ->delete(route('contacts.destroy', $contact))
        ->assertRedirect(route('contacts.index'));

    $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
});

test('a user cannot rename or delete another users contact', function () {
    $owner = User::factory()->create();
    $contact = Contact::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->put(route('contacts.update', $contact), ['name' => 'Someone Else'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('contacts.destroy', $contact))
        ->assertForbidden();
});
