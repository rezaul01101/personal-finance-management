<?php

use App\Models\Account;
use App\Models\Contact;
use App\Models\Loan;
use App\Models\LoanAttachment;
use App\Models\LoanRepayment;
use App\Models\LoanTransfer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function makeLoan(User $user, string $type = 'given', string $amount = '1000.00', array $overrides = []): Loan
{
    return Loan::factory()->for($user)->create([
        'type' => $type,
        'amount' => $amount,
        'account_id' => Account::factory()->for($user),
        'contact_id' => Contact::factory()->for($user),
        'loan_date' => '2026-09-01',
        'expected_return_date' => null,
        ...$overrides,
    ]);
}

function makeRepayment(Loan $loan, string $amount): LoanRepayment
{
    return LoanRepayment::factory()->create([
        'user_id' => $loan->user_id,
        'loan_id' => $loan->id,
        'amount' => $amount,
        'repaid_on' => '2026-09-05',
    ]);
}

test('guests cannot use the loans api', function () {
    $this->getJson('/api/v1/loans')->assertUnauthorized();
    $this->getJson('/api/v1/loans/summary')->assertUnauthorized();
    $this->postJson('/api/v1/loans')->assertUnauthorized();
    $this->postJson('/api/v1/loans/1/repayments')->assertUnauthorized();
    $this->postJson('/api/v1/loans/1/transfers')->assertUnauthorized();
});

test('the summary keeps given and taken separate', function () {
    $user = User::factory()->create();
    $given = makeLoan($user, 'given', '1000.00');
    makeRepayment($given, '300.00');
    $taken = makeLoan($user, 'taken', '500.00');
    makeRepayment($taken, '100.00');
    makeLoan(User::factory()->create(), 'given', '9999.00');

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/loans/summary')
        ->assertOk()
        ->assertJsonPath('data.total_given', '1000.00')
        ->assertJsonPath('data.total_returned_by_borrowers', '300.00')
        ->assertJsonPath('data.outstanding_receivable', '700.00')
        ->assertJsonPath('data.total_taken', '500.00')
        ->assertJsonPath('data.total_paid_to_lenders', '100.00')
        ->assertJsonPath('data.outstanding_payable', '400.00');
});

test('loans are paginated, filterable and never include other users loans', function () {
    $user = User::factory()->create();
    makeLoan($user, 'given', '1000.00');
    makeLoan($user, 'taken', '200.00');
    makeLoan(User::factory()->create());

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/loans')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'last_page']]);

    $this->getJson('/api/v1/loans?direction=taken')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'taken')
        ->assertJsonPath('data.0.amount', '200.00')
        ->assertJsonPath('data.0.progress.outstanding', '200.00');
});

test('loans are grouped by contact with combined balances', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create(['name' => 'Rahim']);
    $first = makeLoan($user, 'given', '5000.00', ['contact_id' => $contact->id]);
    makeLoan($user, 'given', '3000.00', ['contact_id' => $contact->id]);
    makeLoan($user, 'taken', '700.00', ['contact_id' => $contact->id]);
    makeRepayment($first, '1000.00');

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/loans/contacts?direction=given')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Rahim')
        ->assertJsonPath('data.0.loans_count', 2)
        ->assertJsonPath('data.0.total_amount', '8000.00')
        ->assertJsonPath('data.0.outstanding', '7000.00');

    $this->getJson("/api/v1/loans/contacts/{$contact->id}?direction=given")
        ->assertOk()
        ->assertJsonPath('data.contact.name', 'Rahim')
        ->assertJsonPath('data.total_amount', '8000.00')
        ->assertJsonPath('data.outstanding', '7000.00')
        ->assertJsonCount(2, 'data.loans');

    $this->getJson('/api/v1/loans/contacts?direction=taken')
        ->assertJsonPath('data.0.total_amount', '700.00');
});

test("another user's contact cannot be viewed", function () {
    $contact = Contact::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/loans/contacts/{$contact->id}")->assertForbidden();
});

test('a loan can be created with photos', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $contact = Contact::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->post('/api/v1/loans', [
        'type' => 'given',
        'contact_id' => $contact->id,
        'account_id' => $account->id,
        'amount' => '2500.50',
        'loan_date' => '2026-09-10',
        'expected_return_date' => '2026-10-10',
        'note' => 'For rent',
        'photos' => [UploadedFile::fake()->image('slip.jpg')],
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.type', 'given')
        ->assertJsonPath('data.amount', '2500.50')
        ->assertJsonPath('data.loan_date', '2026-09-10')
        ->assertJsonPath('data.contact.id', $contact->id)
        ->assertJsonPath('data.account.id', $account->id)
        ->assertJsonPath('data.progress.outstanding', '2500.50')
        ->assertJsonCount(1, 'data.attachments')
        ->assertJsonPath('data.attachments.0.original_filename', 'slip.jpg');

    expect(Storage::disk('public')->allFiles())->toHaveCount(1);
});

test('loan creation is validated', function () {
    $user = User::factory()->create();
    $foreignContact = Contact::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/loans', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'contact_id', 'amount', 'account_id', 'loan_date']);

    $this->postJson('/api/v1/loans', [
        'type' => 'given',
        'contact_id' => $foreignContact->id,
        'account_id' => Account::factory()->for($user)->create()->id,
        'amount' => '0',
        'loan_date' => '2026-09-10',
        'expected_return_date' => '2026-09-01',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['contact_id', 'amount', 'expected_return_date']);
});

test('a loan shows its progress, repayments, transfers and attachments', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user, 'given', '1000.00');
    makeRepayment($loan, '400.00');
    LoanTransfer::factory()->create([
        'user_id' => $user->id,
        'loan_id' => $loan->id,
        'account_id' => Account::factory()->for($user),
        'amount' => '150.00',
    ]);
    LoanAttachment::factory()->create(['loan_id' => $loan->id]);
    Sanctum::actingAs($user);

    $this->getJson("/api/v1/loans/{$loan->id}")
        ->assertOk()
        ->assertJsonPath('data.progress.total_repaid', '400.00')
        ->assertJsonPath('data.progress.outstanding', '600.00')
        ->assertJsonPath('data.progress.total_transferred', '150.00')
        ->assertJsonPath('data.progress.held_balance', '250.00')
        ->assertJsonCount(1, 'data.repayments')
        ->assertJsonCount(1, 'data.transfers')
        ->assertJsonCount(1, 'data.attachments');
});

test('a loan can be updated but not below what was repaid', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user, 'given', '1000.00');
    makeRepayment($loan, '400.00');
    Sanctum::actingAs($user);

    $payload = [
        'contact_id' => $loan->contact_id,
        'account_id' => $loan->account_id,
        'loan_date' => '2026-09-02',
        'note' => 'Updated',
    ];

    $this->putJson("/api/v1/loans/{$loan->id}", [...$payload, 'amount' => '300'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    $this->putJson("/api/v1/loans/{$loan->id}", [...$payload, 'amount' => '1500'])
        ->assertOk()
        ->assertJsonPath('data.amount', '1500.00')
        ->assertJsonPath('data.note', 'Updated')
        ->assertJsonPath('data.type', 'given')
        ->assertJsonPath('data.progress.outstanding', '1100.00');
});

test('a loan can be deleted', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user);
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/loans/{$loan->id}")->assertNoContent();

    $this->assertDatabaseMissing('loans', ['id' => $loan->id]);
});

test("another user's loan is forbidden everywhere", function () {
    $loan = makeLoan(User::factory()->create());
    $repayment = makeRepayment($loan, '100.00');
    $transfer = LoanTransfer::factory()->create(['user_id' => $loan->user_id, 'loan_id' => $loan->id, 'amount' => '10.00']);
    $attachment = LoanAttachment::factory()->create(['loan_id' => $loan->id]);
    $intruder = User::factory()->create();
    Sanctum::actingAs($intruder);

    $this->getJson("/api/v1/loans/{$loan->id}")->assertForbidden();
    $this->putJson("/api/v1/loans/{$loan->id}", [])->assertForbidden();
    $this->deleteJson("/api/v1/loans/{$loan->id}")->assertForbidden();
    $this->postJson("/api/v1/loans/{$loan->id}/repayments", [])->assertForbidden();
    $this->postJson("/api/v1/loans/{$loan->id}/transfers", [])->assertForbidden();
    $this->getJson("/api/v1/repayments/{$repayment->id}")->assertForbidden();
    $this->putJson("/api/v1/repayments/{$repayment->id}", [])->assertForbidden();
    $this->deleteJson("/api/v1/repayments/{$repayment->id}")->assertForbidden();
    $this->putJson("/api/v1/loans/{$loan->id}/transfers/{$transfer->id}", [])->assertForbidden();
    $this->deleteJson("/api/v1/loans/{$loan->id}/transfers/{$transfer->id}")->assertForbidden();
    $this->postJson("/api/v1/loans/{$loan->id}/attachments", [])->assertForbidden();
    $this->deleteJson("/api/v1/loans/{$loan->id}/attachments/{$attachment->id}")->assertForbidden();
});

test('photos can be added to and removed from a loan', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $loan = makeLoan($user);
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/loans/{$loan->id}/attachments", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('photos');

    $id = $this->post("/api/v1/loans/{$loan->id}/attachments", [
        'photos' => [UploadedFile::fake()->image('a.jpg')],
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonCount(1, 'data')
        ->json('data.0.id');

    $attachment = LoanAttachment::findOrFail($id);
    Storage::disk('public')->assertExists($attachment->path);

    $this->deleteJson("/api/v1/loans/{$loan->id}/attachments/{$id}")->assertNoContent();

    Storage::disk('public')->assertMissing($attachment->path);
    $this->assertDatabaseMissing('loan_attachments', ['id' => $id]);
});

test('an attachment must belong to the loan in the url', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user);
    $other = makeLoan($user);
    $attachment = LoanAttachment::factory()->create(['loan_id' => $other->id]);
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/loans/{$loan->id}/attachments/{$attachment->id}")->assertNotFound();
});

test('repayments on a loan given never touch an account', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user, 'given', '1000.00');
    $account = Account::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/loans/{$loan->id}/repayments", [
        'amount' => '250',
        'account_id' => $account->id,
        'repaid_on' => '2026-09-12',
    ])
        ->assertCreated()
        ->assertJsonPath('data.amount', '250.00')
        ->assertJsonPath('data.repaid_on', '2026-09-12')
        ->assertJsonPath('data.account_id', null);
});

test('repayments on a loan taken require an account', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user, 'taken', '1000.00');
    $account = Account::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/loans/{$loan->id}/repayments", ['amount' => '100', 'repaid_on' => '2026-09-12'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('account_id');

    $this->postJson("/api/v1/loans/{$loan->id}/repayments", [
        'amount' => '100',
        'account_id' => $account->id,
        'repaid_on' => '2026-09-12',
    ])
        ->assertCreated()
        ->assertJsonPath('data.account.id', $account->id);
});

test('a repayment cannot exceed the outstanding balance', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user, 'given', '1000.00');
    makeRepayment($loan, '900.00');
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/loans/{$loan->id}/repayments", ['amount' => '200', 'repaid_on' => '2026-09-12'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');
});

test('a repayment can be viewed, updated and deleted', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user, 'given', '1000.00');
    $repayment = makeRepayment($loan, '400.00');
    Sanctum::actingAs($user);

    $this->getJson("/api/v1/repayments/{$repayment->id}")->assertOk()->assertJsonPath('data.amount', '400.00');

    $this->putJson("/api/v1/repayments/{$repayment->id}", ['amount' => '1000', 'repaid_on' => '2026-09-06'])
        ->assertOk()
        ->assertJsonPath('data.amount', '1000.00');

    $this->putJson("/api/v1/repayments/{$repayment->id}", ['amount' => '1001', 'repaid_on' => '2026-09-06'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    $this->deleteJson("/api/v1/repayments/{$repayment->id}")->assertNoContent();
    $this->assertDatabaseMissing('loan_repayments', ['id' => $repayment->id]);
});

test('deleting a repayment that a transfer depends on is a 422 on amount', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user, 'given', '1000.00');
    $repayment = makeRepayment($loan, '400.00');
    LoanTransfer::factory()->create([
        'user_id' => $user->id,
        'loan_id' => $loan->id,
        'account_id' => Account::factory()->for($user),
        'amount' => '300.00',
    ]);
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/repayments/{$repayment->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    $this->assertDatabaseHas('loan_repayments', ['id' => $repayment->id]);
});

test('transfers move held money into an account and are limited to the held balance', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user, 'given', '1000.00');
    makeRepayment($loan, '400.00');
    $account = Account::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $payload = ['account_id' => $account->id, 'transferred_on' => '2026-09-13'];

    $this->postJson("/api/v1/loans/{$loan->id}/transfers", [...$payload, 'amount' => '500'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    $id = $this->postJson("/api/v1/loans/{$loan->id}/transfers", [...$payload, 'amount' => '300'])
        ->assertCreated()
        ->assertJsonPath('data.amount', '300.00')
        ->assertJsonPath('data.account.id', $account->id)
        ->json('data.id');

    $this->getJson("/api/v1/loans/{$loan->id}/transfers/{$id}")->assertOk();

    $this->putJson("/api/v1/loans/{$loan->id}/transfers/{$id}", [...$payload, 'amount' => '400'])
        ->assertOk()
        ->assertJsonPath('data.amount', '400.00');

    $this->putJson("/api/v1/loans/{$loan->id}/transfers/{$id}", [...$payload, 'amount' => '401'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    $this->deleteJson("/api/v1/loans/{$loan->id}/transfers/{$id}")->assertNoContent();
    $this->assertDatabaseMissing('loan_transfers', ['id' => $id]);
});

test('a loan taken has no transfers', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user, 'taken', '1000.00');
    $account = Account::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/loans/{$loan->id}/transfers", [
        'amount' => '10',
        'account_id' => $account->id,
        'transferred_on' => '2026-09-13',
    ])->assertNotFound();
});

test('a transfer must belong to the loan in the url', function () {
    $user = User::factory()->create();
    $loan = makeLoan($user);
    $other = makeLoan($user);
    $transfer = LoanTransfer::factory()->create(['user_id' => $user->id, 'loan_id' => $other->id, 'amount' => '10.00']);
    Sanctum::actingAs($user);

    $this->getJson("/api/v1/loans/{$loan->id}/transfers/{$transfer->id}")->assertNotFound();
});
