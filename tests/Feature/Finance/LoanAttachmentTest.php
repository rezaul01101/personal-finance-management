<?php

use App\Models\Account;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a loan can be created with no photo at all', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('loans.store'), [
            'type' => 'given',
            'person_name' => 'Karim',
            'amount' => '500',
            'account_id' => $account->id,
            'loan_date' => '2026-08-30',
        ])
        ->assertRedirect(route('loans.index', ['direction' => 'given']));

    $loan = Loan::query()->where('user_id', $user->id)->firstOrFail();
    expect($loan->attachments)->toHaveCount(0);
});

test('a photo can be attached when creating a loan', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)->post(route('loans.store'), [
        'type' => 'given',
        'person_name' => 'Karim',
        'amount' => '500',
        'account_id' => $account->id,
        'loan_date' => '2026-08-30',
        'photos' => [UploadedFile::fake()->image('loan.jpg')],
    ]);

    $loan = Loan::query()->where('user_id', $user->id)->firstOrFail();
    expect($loan->attachments)->toHaveCount(1);
    Storage::disk('public')->assertExists($loan->attachments->first()->path);
});

test('a photo can be removed from a loan, deleting the file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $loan = Loan::factory()->for($user)->create(['account_id' => $account->id]);
    $attachment = $loan->attachments()->create([
        'disk' => 'public',
        'path' => UploadedFile::fake()->image('loan.jpg')->store('loans/'.$loan->id, 'public'),
        'original_filename' => 'loan.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1000,
    ]);

    $this->actingAs($user)
        ->delete(route('loans.attachments.destroy', ['loan' => $loan, 'attachment' => $attachment]))
        ->assertRedirect(route('loans.edit', $loan));

    $this->assertDatabaseMissing('loan_attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertMissing($attachment->path);
});

test('a user cannot remove an attachment belonging to another users loan', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $account = Account::factory()->for($owner)->create();
    $loan = Loan::factory()->for($owner)->create(['account_id' => $account->id]);
    $attachment = $loan->attachments()->create([
        'disk' => 'public',
        'path' => UploadedFile::fake()->image('loan.jpg')->store('loans/'.$loan->id, 'public'),
        'original_filename' => 'loan.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1000,
    ]);

    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->delete(route('loans.attachments.destroy', ['loan' => $loan, 'attachment' => $attachment]))
        ->assertForbidden();

    Storage::disk('public')->assertExists($attachment->path);
});

test('deleting a loan deletes its photo files', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $loan = Loan::factory()->for($user)->create(['account_id' => $account->id]);
    $path = UploadedFile::fake()->image('loan.jpg')->store('loans/'.$loan->id, 'public');
    $loan->attachments()->create([
        'disk' => 'public',
        'path' => $path,
        'original_filename' => 'loan.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1000,
    ]);

    $this->actingAs($user)->delete(route('loans.destroy', $loan));

    Storage::disk('public')->assertMissing($path);
});
