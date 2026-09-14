<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The only code path allowed to create/edit/delete an Expense.
 */
final class ExpenseService
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $receipts
     */
    public function create(User $user, array $attributes, array $receipts = []): Expense
    {
        return DB::transaction(function () use ($user, $attributes, $receipts) {
            $expense = $user->expenses()->create($attributes);

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
            $expense->update($attributes);

            $this->storeReceipts($expense, $receipts);

            return $expense;
        });
    }

    public function delete(Expense $expense): void
    {
        DB::transaction(function () use ($expense) {
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
