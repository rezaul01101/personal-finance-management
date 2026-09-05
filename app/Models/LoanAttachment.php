<?php

namespace App\Models;

use Database\Factories\LoanAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $loan_id
 * @property string $disk
 * @property string $path
 * @property string|null $original_filename
 * @property string|null $mime_type
 * @property int|null $size_bytes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['disk', 'path', 'original_filename', 'mime_type', 'size_bytes'])]
#[Appends(['url'])]
class LoanAttachment extends Model
{
    /** @use HasFactory<LoanAttachmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Loan, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * The publicly accessible URL for this attachment.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk($this->disk)->url($this->path),
        );
    }
}
