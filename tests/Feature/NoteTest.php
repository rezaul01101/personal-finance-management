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

test('note formatting - bold, lists, and safe colors are preserved', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('notes.store'), [
        'description' => '<b>Bold</b> <ul><li>One</li><li>Two</li></ul> <span style="color: #dc2626; background-color: #fef08a;">Colored</span>',
    ]);

    $response->assertCreated();
    $note = Note::query()->where('user_id', $user->id)->firstOrFail();

    expect($note->description)
        ->toContain('<b>Bold</b>')
        ->toContain('<ul><li>One</li><li>Two</li></ul>')
        ->toContain('style="color: #dc2626; background-color: #fef08a"');
});

test('note formatting strips scripts, event handlers, and unknown tags', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('notes.store'), [
        'description' => '<script>alert(1)</script><img src=x onerror="alert(1)"><p onclick="evil()">Hello <b onmouseover="evil()">world</b></p>',
    ]);

    $response->assertCreated();
    $note = Note::query()->where('user_id', $user->id)->firstOrFail();

    expect($note->description)
        ->not->toContain('<script')
        ->not->toContain('<img')
        ->not->toContain('alert(1)')
        ->not->toContain('onerror')
        ->not->toContain('onclick')
        ->not->toContain('onmouseover')
        ->toContain('Hello')
        ->toContain('<b>world</b>');
});

test('note formatting strips unsafe style values but keeps safe ones', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('notes.store'), [
        'description' => '<span style="color: red; background-color: url(javascript:alert(1)); position: fixed;">x</span>',
    ]);

    $response->assertCreated();
    $note = Note::query()->where('user_id', $user->id)->firstOrFail();

    expect($note->description)
        ->toContain('color: red')
        ->not->toContain('url(')
        ->not->toContain('position');
});

test('a note that sanitizes down to no visible content is treated as empty', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('notes.store'), [
            'title' => '',
            'description' => '<div><br></div>',
        ])
        ->assertInvalid(['title', 'description']);
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
