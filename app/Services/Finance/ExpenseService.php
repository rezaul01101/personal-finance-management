<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The only code path allowed to create/edit/delete an Expense - keeps the
 * account-balance side effect (spec Rule 7/9/46) atomic and consistent with
 * every write, including the reversal-then-reapply required when editing.
 */
final class ExpenseService
{
    public function __construct(private readonly AccountBalanceService $accountBalance) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $receipts
     */
    public function create(User $user, array $attributes, array $receipts = []): Expense
    {
        return DB::transaction(function () use ($user, $attributes, $receipts) {
            $expense = $user->expenses()->create($attributes);

            $this->accountBalance->debit($expense->account, Money::of($expense->amount));

            $this->storeReceipts($expense, $receipts);

            return $expense;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $receipts
     */
    public function update(Expense $expense, array $attributes, array $receipts = []): Expense
    {
        return DB::transaction(function () use ($expense, $attributes, $receipts) {
            $oldAccount = $expense->account;
            $oldAmount = Money::of($expense->amount);

            $this->accountBalance->credit($oldAccount, $oldAmount);

            $expense->update($attributes);

            $newAccount = $expense->account_id === $oldAccount->id
                ? $oldAccount
                : Account::query()->findOrFail($expense->account_id);

            $this->accountBalance->debit($newAccount, Money::of($expense->amount));

            $this->storeReceipts($expense, $receipts);

            return $expense;
        });
    }

    public function delete(Expense $expense): void
    {
        DB::transaction(function () use ($expense) {
            $this->accountBalance->credit($expense->account, Money::of($expense->amount));

            foreach ($expense->attachments as $attachment) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }

            $expense->delete();
        });
    }

    /**
     * @param  array<int, UploadedFile>  $receipts
     */
    private function storeReceipts(Expense $expense, array $receipts): void
    {
        foreach ($receipts as $receipt) {
            $path = $receipt->store('expenses/'.$expense->id, 'public');

            $expense->attachments()->create([
                'disk' => 'public',
                'path' => $path,
                'original_filename' => $receipt->getClientOriginalName(),
                'mime_type' => $receipt->getClientMimeType(),
                'size_bytes' => $receipt->getSize(),
            ]);
        }
    }
}
