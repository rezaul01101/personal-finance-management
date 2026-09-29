<?php

use App\Models\Note;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guests cannot reach note endpoints', function () {
    $this->getJson('/api/v1/notes')->assertUnauthorized();
    $this->postJson('/api/v1/notes', [])->assertUnauthorized();
    $this->deleteJson('/api/v1/notes/1')->assertUnauthorized();
});

test('notes are paginated, newest first and only the owners', function () {
    $user = User::factory()->create();
    Note::factory()->for($user)->count(25)->create();
    Note::factory()->count(3)->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/notes')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.total', 25);

    $this->getJson('/api/v1/notes?page=2')->assertJsonCount(5, 'data');
});

test('a note can be created, shown, updated and deleted', function () {
    Sanctum::actingAs(User::factory()->create());

    $id = $this->postJson('/api/v1/notes', ['title' => '  Groceries ', 'description' => 'Milk<script>x</script><br>Eggs'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Groceries')
        ->assertJsonPath('data.description', 'Milk<br>Eggs')
        ->json('data.id');

    $this->getJson("/api/v1/notes/{$id}")->assertOk()->assertJsonPath('data.title', 'Groceries');

    $this->putJson("/api/v1/notes/{$id}", ['title' => 'Shopping', 'description' => null])
        ->assertOk()
        ->assertJsonPath('data.title', 'Shopping')
        ->assertJsonPath('data.description', null);

    $this->deleteJson("/api/v1/notes/{$id}")->assertNoContent();
    $this->assertDatabaseMissing('notes', ['id' => $id]);
});

test('a note needs a title or a description', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/notes', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'description']);
});

test('another users note is forbidden', function () {
    $note = Note::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/notes/{$note->id}")->assertForbidden();
    $this->putJson("/api/v1/notes/{$note->id}", ['title' => 'Mine now'])->assertForbidden();
    $this->deleteJson("/api/v1/notes/{$note->id}")->assertForbidden();
    $this->assertModelExists($note);
});
