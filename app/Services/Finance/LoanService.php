<?php

namespace App\Services\Finance;

use App\Enums\LoanType;
use App\Models\Account;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The only code path allowed to create/edit/delete a Loan. Giving a loan
 * debits the source account (money out is not an expense); taking a loan
 * credits the destination account (money in is not income) - see spec §28
 * and rules 3-5. Keeps that account-balance side effect atomic and
 * consistent with every write, including the reversal-then-reapply required
 * when editing.
 */
final class LoanService
{
    public function __construct(private readonly AccountBalanceService $accountBalance) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $photos
     */
    public function create(User $user, array $attributes, array $photos = []): Loan
    {
        return DB::transaction(function () use ($user, $attributes, $photos) {
            $loan = $user->loans()->create($attributes);

            $this->applyAccountEffect($loan->account, $loan->type, Money::of($loan->amount));

            $this->storePhotos($loan, $photos);

            return $loan;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $photos
     */
    public function update(Loan $loan, array $attributes, array $photos = []): Loan
    {
        return DB::transaction(function () use ($loan, $attributes, $photos) {
            $oldAccount = $loan->account;
            $type = $loan->type;
            $oldAmount = Money::of($loan->amount);

            $this->reverseAccountEffect($oldAccount, $type, $oldAmount);

            $loan->update($attributes);

            $newAccount = $loan->account_id === $oldAccount->id
                ? $oldAccount
                : Account::query()->findOrFail($loan->account_id);

            $this->applyAccountEffect($newAccount, $type, Money::of($loan->amount));

            $this->storePhotos($loan, $photos);

            return $loan;
        });
    }

    public function delete(Loan $loan): void
    {
        DB::transaction(function () use ($loan) {
            $this->reverseAccountEffect($loan->account, $loan->type, Money::of($loan->amount));

            foreach ($loan->attachments as $attachment) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }

            $loan->delete();
        });
    }

    private function applyAccountEffect(Account $account, LoanType $type, Money $amount): void
    {
        match ($type) {
            LoanType::Given => $this->accountBalance->debit($account, $amount),
            LoanType::Taken => $this->accountBalance->credit($account, $amount),
        };
    }

    private function reverseAccountEffect(Account $account, LoanType $type, Money $amount): void
    {
        match ($type) {
            LoanType::Given => $this->accountBalance->credit($account, $amount),
            LoanType::Taken => $this->accountBalance->debit($account, $amount),
        };
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    private function storePhotos(Loan $loan, array $photos): void
    {
        foreach ($photos as $photo) {
            $path = $photo->store('loans/'.$loan->id, 'public');

            $loan->attachments()->create([
                'disk' => 'public',
                'path' => $path,
                'original_filename' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getClientMimeType(),
                'size_bytes' => $photo->getSize(),
            ]);
        }
    }
}
