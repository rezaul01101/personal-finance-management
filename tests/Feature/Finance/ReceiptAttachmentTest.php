<?php

use App\Models\Account;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an expense can be created with no receipt at all', function () {
    $user = User::factory()->create();
    $expenseCategory = ExpenseCategory::factory()->for($user)->create();
    $budgetCategory = BudgetCategory::factory()->for($user)->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'amount' => '500',
            'expense_category_id' => $expenseCategory->id,
            'budget_category_id' => $budgetCategory->id,
            'account_id' => $account->id,
            'spent_on' => '2026-08-30',
        ])
        ->assertRedirect(route('expenses.index'));

    $expense = Expense::query()->where('user_id', $user->id)->firstOrFail();
    expect($expense->attachments)->toHaveCount(0);
});

test('a receipt image can be attached when creating an expense', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $expenseCategory = ExpenseCategory::factory()->for($user)->create();
    $budgetCategory = BudgetCategory::factory()->for($user)->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)->post(route('expenses.store'), [
        'amount' => '500',
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'spent_on' => '2026-08-30',
        'receipts' => [UploadedFile::fake()->image('receipt.jpg')],
    ]);

    $expense = Expense::query()->where('user_id', $user->id)->firstOrFail();
    expect($expense->attachments)->toHaveCount(1);
    Storage::disk('public')->assertExists($expense->attachments->first()->path);
});

test('a receipt can be removed from an expense, deleting the file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $expenseCategory = ExpenseCategory::factory()->for($user)->create();
    $budgetCategory = BudgetCategory::factory()->for($user)->create();
    $account = Account::factory()->for($user)->create();

    $expense = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
    ]);
    $attachment = $expense->attachments()->create([
        'disk' => 'public',
        'path' => UploadedFile::fake()->image('receipt.jpg')->store('expenses/'.$expense->id, 'public'),
        'original_filename' => 'receipt.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1000,
    ]);

    $this->actingAs($user)
        ->delete(route('expenses.attachments.destroy', ['expense' => $expense, 'attachment' => $attachment]))
        ->assertRedirect(route('expenses.edit', $expense));

    $this->assertDatabaseMissing('expense_attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertMissing($attachment->path);
});

test('a user cannot remove an attachment belonging to another users expense', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $expenseCategory = ExpenseCategory::factory()->for($owner)->create();
    $budgetCategory = BudgetCategory::factory()->for($owner)->create();
    $account = Account::factory()->for($owner)->create();
    $expense = Expense::factory()->for($owner)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
    ]);
    $attachment = $expense->attachments()->create([
        'disk' => 'public',
        'path' => UploadedFile::fake()->image('receipt.jpg')->store('expenses/'.$expense->id, 'public'),
        'original_filename' => 'receipt.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1000,
    ]);

    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->delete(route('expenses.attachments.destroy', ['expense' => $expense, 'attachment' => $attachment]))
        ->assertForbidden();

    Storage::disk('public')->assertExists($attachment->path);
});

test('deleting an expense deletes its receipt files', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $expenseCategory = ExpenseCategory::factory()->for($user)->create();
    $budgetCategory = BudgetCategory::factory()->for($user)->create();
    $account = Account::factory()->for($user)->create();
    $expense = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
    ]);
    $path = UploadedFile::fake()->image('receipt.jpg')->store('expenses/'.$expense->id, 'public');
    $expense->attachments()->create([
        'disk' => 'public',
        'path' => $path,
        'original_filename' => 'receipt.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1000,
    ]);

    $this->actingAs($user)->delete(route('expenses.destroy', $expense));

    Storage::disk('public')->assertMissing($path);
});
