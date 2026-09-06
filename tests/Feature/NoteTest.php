<?php

use App\Models\Note;
use App\Models\User;

test('guests cannot access notes', function () {
    $this->getJson(route('notes.index'))->assertUnauthorized();
});

test('a user can list their own notes newest first', function () {
    $user = User::factory()->create();
    $older = Note::factory()->for($user)->create(['created_at' => now()->subDay()]);
    $newer = Note::factory()->for($user)->create(['created_at' => now()]);

    $response = $this->actingAs($user)->getJson(route('notes.index'));

    $response->assertOk();
    expect($response->json('*.id'))->toBe([$newer->id, $older->id]);
});

test('a user only sees their own notes', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Note::factory()->for($user)->create();
    Note::factory()->for($otherUser)->create();

    $response = $this->actingAs($user)->getJson(route('notes.index'));

    $response->assertOk()->assertJsonCount(1);
});

test('a note can be created with a title and description', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('notes.store'), [
        'title' => 'Groceries',
        'description' => 'Milk, eggs, bread',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('notes', [
        'user_id' => $user->id,
        'title' => 'Groceries',
        'description' => 'Milk, eggs, bread',
    ]);
});

test('a note can be created with only a title or only a description', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('notes.store'), ['title' => 'Just a title'])
        ->assertCreated();

    $this->actingAs($user)
        ->postJson(route('notes.store'), ['description' => 'Just a description'])
        ->assertCreated();
});

test('a note requires at least a title or a description', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('notes.store'), ['title' => '', 'description' => ''])
        ->assertInvalid(['title', 'description']);
});

test('a note can be updated', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create(['title' => 'Old title']);

    $response = $this->actingAs($user)->putJson(route('notes.update', $note), [
        'title' => 'New title',
        'description' => 'New description',
    ]);

    $response->assertOk();
    expect($note->fresh()->title)->toBe('New title');
});

test('a note can be deleted', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->deleteJson(route('notes.destroy', $note))
        ->assertNoContent();

    $this->assertDatabaseMissing('notes', ['id' => $note->id]);
});

test('a user cannot update or delete another users note', function () {
    $owner = User::factory()->create();
    $note = Note::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->putJson(route('notes.update', $note), ['title' => 'Hijacked'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->deleteJson(route('notes.destroy', $note))
        ->assertForbidden();

    $this->assertDatabaseHas('notes', ['id' => $note->id, 'title' => $note->title]);
});
