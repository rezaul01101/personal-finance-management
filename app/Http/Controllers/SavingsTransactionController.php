<?php

namespace App\Http\Controllers;

use App\Enums\CategoryStatus;
use App\Enums\SavingsTransactionType;
use App\Http\Requests\Finance\StoreSavingsTransactionRequest;
use App\Http\Requests\Finance\UpdateSavingsTransactionRequest;
use App\Models\SavingsGoal;
use App\Models\SavingsTransaction;
use App\Services\Finance\SavingsTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class SavingsTransactionController extends Controller
{
    public function __construct(private readonly SavingsTransactionService $transactions) {}

    /**
     * Show the form for recording a contribution or withdrawal against the
     * given goal. The `type` query parameter pre-selects contribution vs
     * withdrawal (e.g. from the goal's "Contribute"/"Withdraw" buttons) but
     * remains editable.
     */
    #[Authorize('view', 'savings_goal')]
    public function create(Request $request, SavingsGoal $savingsGoal): Response
    {
        $type = SavingsTransactionType::tryFrom((string) $request->query('type'))
            ?? SavingsTransactionType::Contribution;

        return Inertia::render('savings/transactions/create', [
            ...$this->formOptions($request),
            'savingsGoal' => $savingsGoal,
            'type' => $type->value,
        ]);
    }

    /**
     * Store a newly recorded contribution or withdrawal.
     */
    #[Authorize('view', 'savings_goal')]
    public function store(StoreSavingsTransactionRequest $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        $this->transactions->create($request->user(), [
            ...$request->validated(),
            'savings_goal_id' => $savingsGoal->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Savings transaction recorded.')]);

        return to_route('savings-goals.show', $savingsGoal);
    }

    /**
     * Show the form for editing the transaction.
     */
    #[Authorize('view', 'transaction')]
    public function edit(Request $request, SavingsTransaction $transaction): Response
    {
        return Inertia::render('savings/transactions/edit', [
            ...$this->formOptions($request),
            'transaction' => $transaction,
            'savingsGoal' => $transaction->savingsGoal,
        ]);
    }

    /**
     * Update the transaction, reversing and reapplying its account effect.
     */
    #[Authorize('update', 'transaction')]
    public function update(UpdateSavingsTransactionRequest $request, SavingsTransaction $transaction): RedirectResponse
    {
        $this->transactions->update($transaction, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Savings transaction updated.')]);

        return to_route('savings-goals.show', $transaction->savings_goal_id);
    }

    /**
     * Remove the transaction, reversing its account effect.
     */
    #[Authorize('delete', 'transaction')]
    public function destroy(SavingsTransaction $transaction): RedirectResponse
    {
        $goalId = $transaction->savings_goal_id;

        $this->transactions->delete($transaction);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Savings transaction deleted.')]);

        return to_route('savings-goals.show', $goalId);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        return [
            'accounts' => $request->user()->accounts()
                ->where('status', CategoryStatus::Active)
                ->orderBy('name')
                ->get(),
        ];
    }
}
