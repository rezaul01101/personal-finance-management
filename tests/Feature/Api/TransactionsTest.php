<?php

use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function expensePayload(User $user, array $overrides = []): array
{
    return [
        'amount' => '125.50',
        'expense_category_id' => ExpenseCategory::factory()->for($user)->create()->id,
        'budget_category_id' => BudgetCategory::factory()->for($user)->create()->id,
        'account_id' => Account::factory()->for($user)->create()->id,
        'spent_on' => '2026-09-10',
        'note' => 'Lunch',
        ...$overrides,
    ];
}

test('guests are rejected', function (string $path) {
    $this->getJson("/api/v1/{$path}")->assertUnauthorized();
})->with(['expenses', 'incomes', 'transfers']);

// Expenses

test('expenses are listed newest first, paginated, and only for the owner', function () {
    $user = User::factory()->create();
    $old = Expense::factory()->for($user)->create(['spent_on' => '2026-01-01']);
    $new = Expense::factory()->for($user)->create(['spent_on' => '2026-02-01']);
    Expense::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/expenses')->assertOk()
        ->assertJsonStructure(['data' => [['id', 'amount', 'spent_on', 'expense_category', 'budget_category', 'account', 'attachments']], 'links', 'meta' => ['current_page', 'last_page']]);

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$new->id, $old->id]);
});

test('expenses can be filtered', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->for($user)->create();
    $match = Expense::factory()->for($user)->create([
        'expense_category_id' => $category->id,
        'note' => 'Rice and dal',
        'spent_on' => '2026-03-05',
    ]);
    Expense::factory()->for($user)->create(['note' => 'Bus', 'spent_on' => '2026-03-05']);
    Sanctum::actingAs($user);

    $ids = fn (string $query) => collect($this->getJson("/api/v1/expenses?{$query}")->assertOk()->json('data'))->pluck('id')->all();

    expect($ids("expense_category_id={$category->id}"))->toBe([$match->id])
        ->and($ids('search=rice'))->toBe([$match->id])
        ->and($ids('date_from=2026-04-01'))->toBe([])
        ->and($ids('date_to=2026-03-05&search=dal'))->toBe([$match->id]);
});

test('expenses can be exported as a filtered pdf', function () {
    $user = User::factory()->create();
    Expense::factory()->for($user)->create(['note' => 'Rice']);
    Sanctum::actingAs($user);

    $response = $this->get('/api/v1/expenses/export/pdf?search=rice', ['Accept' => 'application/json'])->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
    $this->getJson('/api/v1/expenses/export/pdf?date_from=nonsense')->assertUnprocessable();
});

test('expense filters are validated', function () {
    Sanctum::actingAs(User::factory()->create());
    $foreign = ExpenseCategory::factory()->create();

    $this->getJson("/api/v1/expenses?expense_category_id={$foreign->id}&date_from=2026-05-02&date_to=2026-05-01")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expense_category_id', 'date_to']);
});

test('an expense can be created, viewed, updated and deleted', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $id = $this->postJson('/api/v1/expenses', expensePayload($user))
        ->assertCreated()
        ->assertJsonPath('data.amount', '125.50')
        ->assertJsonPath('data.spent_on', '2026-09-10')
        ->assertJsonPath('data.note', 'Lunch')
        ->json('data.id');

    $this->getJson("/api/v1/expenses/{$id}")->assertOk()->assertJsonPath('data.id', $id);

    $this->putJson("/api/v1/expenses/{$id}", expensePayload($user, ['amount' => 10, 'note' => null]))
        ->assertOk()
        ->assertJsonPath('data.amount', '10.00')
        ->assertJsonPath('data.note', null);

    $this->deleteJson("/api/v1/expenses/{$id}")->assertNoContent();
    expect(Expense::query()->find($id))->toBeNull();
});

test('expense validation errors are returned', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/expenses', ['amount' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount', 'expense_category_id', 'budget_category_id', 'account_id', 'spent_on']);
});

test('an expense cannot use another users category or account', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/expenses', expensePayload($user, [
        'account_id' => Account::factory()->for($other)->create()->id,
    ]))->assertUnprocessable()->assertJsonValidationErrors(['account_id']);
});

test('receipts can be uploaded with an expense and removed', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->post('/api/v1/expenses', expensePayload($user) + [
        'receipts' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
    ], ['Accept' => 'application/json'])->assertCreated()->assertJsonCount(2, 'data.attachments');

    $expenseId = $response->json('data.id');
    $attachment = ExpenseAttachment::query()->where('expense_id', $expenseId)->first();
    Storage::disk('public')->assertExists($attachment->path);

    $this->post("/api/v1/expenses/{$expenseId}", expensePayload($user) + [
        '_method' => 'PUT',
        'receipts' => [UploadedFile::fake()->image('c.jpg')],
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonCount(3, 'data.attachments');

    $this->deleteJson("/api/v1/expenses/{$expenseId}/attachments/{$attachment->id}")->assertNoContent();
    Storage::disk('public')->assertMissing($attachment->path);
    expect(ExpenseAttachment::query()->find($attachment->id))->toBeNull();
});

test('receipts must be images and at most five', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->post('/api/v1/expenses', expensePayload($user) + [
        'receipts' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
    ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors(['receipts.0']);

    $this->post('/api/v1/expenses', expensePayload($user) + [
        'receipts' => array_map(fn ($i) => UploadedFile::fake()->image("r{$i}.jpg"), range(1, 6)),
    ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors(['receipts']);
});

test('another users expense is forbidden', function () {
    $expense = Expense::factory()->create();
    $attachment = ExpenseAttachment::factory()->for($expense)->create();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson("/api/v1/expenses/{$expense->id}")->assertForbidden();
    $this->putJson("/api/v1/expenses/{$expense->id}", expensePayload($user))->assertForbidden();
    $this->deleteJson("/api/v1/expenses/{$expense->id}")->assertForbidden();
    $this->deleteJson("/api/v1/expenses/{$expense->id}/attachments/{$attachment->id}")->assertForbidden();
    expect(Expense::query()->find($expense->id))->not->toBeNull();
});

test('an attachment must belong to the expense in the url', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->for($user)->create();
    $attachment = ExpenseAttachment::factory()->for(Expense::factory()->for($user))->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/expenses/{$expense->id}/attachments/{$attachment->id}")->assertNotFound();
});

// Incomes

test('incomes are listed for the owner only', function () {
    $user = User::factory()->create();
    $income = Income::factory()->for($user)->create();
    Income::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/incomes')->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $income->id)
        ->assertJsonPath('data.0.account.id', $income->account_id)
        ->assertJsonStructure(['links', 'meta' => ['current_page', 'last_page']]);
});

test('an income can be created, viewed, updated and deleted', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    Sanctum::actingAs($user);
    $payload = ['amount' => '5000', 'source' => 'salary', 'account_id' => $account->id, 'received_on' => '2026-09-01', 'note' => 'Sept'];

    $id = $this->postJson('/api/v1/incomes', $payload)
        ->assertCreated()
        ->assertJsonPath('data.amount', '5000.00')
        ->assertJsonPath('data.source', 'salary')
        ->json('data.id');

    $this->getJson("/api/v1/incomes/{$id}")->assertOk();
    $this->putJson("/api/v1/incomes/{$id}", [...$payload, 'source' => 'bonus'])
        ->assertOk()->assertJsonPath('data.source', 'bonus');
    $this->deleteJson("/api/v1/incomes/{$id}")->assertNoContent();
    expect(Income::query()->find($id))->toBeNull();
});

test('income validation errors are returned', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/incomes', ['amount' => 0, 'source' => 'lottery'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount', 'source', 'account_id', 'received_on']);
});

test('another users income is forbidden', function () {
    $income = Income::factory()->create();
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $payload = ['amount' => 5, 'source' => 'salary', 'account_id' => Account::factory()->for($user)->create()->id, 'received_on' => '2026-09-01'];

    $this->getJson("/api/v1/incomes/{$income->id}")->assertForbidden();
    $this->putJson("/api/v1/incomes/{$income->id}", $payload)->assertForbidden();
    $this->deleteJson("/api/v1/incomes/{$income->id}")->assertForbidden();
});

// Transfers

test('transfers are listed for the owner only', function () {
    $user = User::factory()->create();
    $transfer = AccountTransfer::factory()->for($user)->create();
    AccountTransfer::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/transfers')->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $transfer->id)
        ->assertJsonPath('data.0.from_account.id', $transfer->from_account_id)
        ->assertJsonPath('data.0.to_account.id', $transfer->to_account_id);
});

test('a transfer can be created, viewed, updated and deleted', function () {
    $user = User::factory()->create();
    [$from, $to] = Account::factory()->for($user)->count(2)->create();
    Sanctum::actingAs($user);
    $payload = ['amount' => '250', 'from_account_id' => $from->id, 'to_account_id' => $to->id, 'transferred_on' => '2026-09-02'];

    $id = $this->postJson('/api/v1/transfers', $payload)
        ->assertCreated()
        ->assertJsonPath('data.amount', '250.00')
        ->json('data.id');

    $this->getJson("/api/v1/transfers/{$id}")->assertOk();
    $this->putJson("/api/v1/transfers/{$id}", [...$payload, 'amount' => 300, 'note' => 'Top up'])
        ->assertOk()->assertJsonPath('data.amount', '300.00')->assertJsonPath('data.note', 'Top up');
    $this->deleteJson("/api/v1/transfers/{$id}")->assertNoContent();
    expect(AccountTransfer::query()->find($id))->toBeNull();
});

test('transfer validation rejects identical and foreign accounts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/transfers', ['amount' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount', 'from_account_id', 'to_account_id', 'transferred_on']);

    $this->postJson('/api/v1/transfers', [
        'amount' => 10, 'from_account_id' => $account->id, 'to_account_id' => $account->id, 'transferred_on' => '2026-09-02',
    ])->assertUnprocessable()->assertJsonValidationErrors(['to_account_id']);

    $this->postJson('/api/v1/transfers', [
        'amount' => 10, 'from_account_id' => $account->id, 'to_account_id' => Account::factory()->create()->id, 'transferred_on' => '2026-09-02',
    ])->assertUnprocessable()->assertJsonValidationErrors(['to_account_id']);
});

test('another users transfer is forbidden', function () {
    $transfer = AccountTransfer::factory()->create();
    $user = User::factory()->create();
    [$from, $to] = Account::factory()->for($user)->count(2)->create();
    Sanctum::actingAs($user);

    $this->getJson("/api/v1/transfers/{$transfer->id}")->assertForbidden();
    $this->putJson("/api/v1/transfers/{$transfer->id}", [
        'amount' => 5, 'from_account_id' => $from->id, 'to_account_id' => $to->id, 'transferred_on' => '2026-09-02',
    ])->assertForbidden();
    $this->deleteJson("/api/v1/transfers/{$transfer->id}")->assertForbidden();
});
