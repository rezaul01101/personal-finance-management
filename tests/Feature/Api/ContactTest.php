<?php

use App\Models\Contact;
use App\Models\Loan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guests cannot use the contacts api', function () {
    $this->getJson('/api/v1/contacts')->assertUnauthorized();
    $this->postJson('/api/v1/contacts', ['name' => 'Rahim'])->assertUnauthorized();
});

test('contacts are listed for the current user only, ordered by name', function () {
    $user = User::factory()->create();
    Contact::factory()->for($user)->create(['name' => 'Zara']);
    $contact = Contact::factory()->for($user)->create(['name' => 'Abid']);
    Loan::factory()->for($user)->create(['contact_id' => $contact->id]);
    Contact::factory()->create(['name' => 'Stranger']);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/contacts')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Abid')
        ->assertJsonPath('data.0.loans_count', 1)
        ->assertJsonPath('data.1.name', 'Zara');
});

test('a contact can be created, viewed, updated and deleted', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $id = $this->postJson('/api/v1/contacts', ['name' => '  Rahim  '])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Rahim')
        ->json('data.id');

    $this->getJson("/api/v1/contacts/{$id}")->assertOk()->assertJsonPath('data.name', 'Rahim');

    $this->putJson("/api/v1/contacts/{$id}", ['name' => 'Karim'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Karim');

    $this->deleteJson("/api/v1/contacts/{$id}")->assertNoContent();

    $this->assertDatabaseMissing('contacts', ['id' => $id]);
});

test('contact names are validated and unique per user', function () {
    $user = User::factory()->create();
    Contact::factory()->for($user)->create(['name' => 'Rahim']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/contacts', ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    $this->postJson('/api/v1/contacts', ['name' => 'Rahim'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    // Another user may reuse the same name.
    Contact::factory()->create(['name' => 'Shared']);
    $this->postJson('/api/v1/contacts', ['name' => 'Shared'])->assertCreated();
});

test('a contact with loans cannot be deleted', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create();
    Loan::factory()->for($user)->create(['contact_id' => $contact->id]);
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/contacts/{$contact->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('contact');

    $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
});

test("another user's contact is forbidden", function () {
    $contact = Contact::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/contacts/{$contact->id}")->assertForbidden();
    $this->putJson("/api/v1/contacts/{$contact->id}", ['name' => 'Hijack'])->assertForbidden();
    $this->deleteJson("/api/v1/contacts/{$contact->id}")->assertForbidden();
});
