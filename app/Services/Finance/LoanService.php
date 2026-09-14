<?php

namespace App\Services\Finance;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The only code path allowed to create/edit/delete a Loan.
 */
final class LoanService
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $photos
     */
    public function create(User $user, array $attributes, array $photos = []): Loan
    {
        return DB::transaction(function () use ($user, $attributes, $photos) {
            $loan = $user->loans()->create($attributes);
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
            $loan->update($attributes);

            $this->storePhotos($loan, $photos);

            return $loan;
        });
    }

    public function delete(Loan $loan): void
    {
        DB::transaction(function () use ($loan) {
            foreach ($loan->attachments as $attachment) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }

            $loan->delete();
        });
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
