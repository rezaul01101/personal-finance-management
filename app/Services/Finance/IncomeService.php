<?php

namespace App\Services\Finance;

use App\Models\Income;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only code path allowed to create/edit/delete an Income.
 */
final class IncomeService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Income
    {
        return DB::transaction(function () use ($user, $attributes) {
            return $user->incomes()->create($attributes);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Income $income, array $attributes): Income
    {
        return DB::transaction(function () use ($income, $attributes) {
            $income->update($attributes);

            return $income;
        });
    }

    public function delete(Income $income): void
    {
        DB::transaction(function () use ($income) {
            $income->delete();
        });
    }
}
