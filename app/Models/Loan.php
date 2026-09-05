<?php

namespace App\Models;

use App\Enums\LoanType;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $account_id
 * @property LoanType $type
 * @property string $person_name
 * @property string $amount
 * @property Carbon $loan_date
 * @property Carbon|null $expected_return_date
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['account_id', 'type', 'person_name', 'amount', 'loan_date', 'expected_return_date', 'note'])]
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LoanType::class,
            'amount' => 'decimal:2',
            'loan_date' => 'date:Y-m-d',
            'expected_return_date' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return HasMany<LoanRepayment, $this>
     */
    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    /**
     * @return HasMany<LoanTransfer, $this>
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(LoanTransfer::class);
    }

    /**
     * @return HasMany<LoanAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(LoanAttachment::class);
    }
}
