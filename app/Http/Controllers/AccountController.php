<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\StoreAccountRequest;
use App\Http\Requests\Finance\UpdateAccountRequest;
use App\Models\Account;
use App\Services\Finance\AccountCalculator;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __construct(private readonly AccountCalculator $accountCalculator) {}

    /**
     * Display a listing of the user's accounts with their live balances.
     */
    public function index(Request $request): Response
    {
        $accounts = $request->user()->accounts()->orderBy('name')->get();

        return Inertia::render('accounts/index', [
            'accounts' => $accounts,
            'summaries' => $accounts->mapWithKeys(
                fn (Account $account) => [$account->id => $this->accountCalculator->summarize($account)->toArray()],
            ),
        ]);
    }

    /**
     * Store a newly created account.
     */
    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $request->user()->accounts()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account created.')]);

        return to_route('accounts.index');
    }

    /**
     * Update the account.
     */
    #[Authorize('update', 'account')]
    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $account->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account updated.')]);

        return to_route('accounts.index');
    }

    /**
     * Remove the account, unless transactions still reference it.
     */
    #[Authorize('delete', 'account')]
    public function destroy(Account $account): RedirectResponse
    {
        try {
            $account->delete();
        } catch (QueryException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This account has transactions recorded against it and cannot be deleted. Archive it instead.')]);

            return to_route('accounts.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account deleted.')]);

        return to_route('accounts.index');
    }
}
