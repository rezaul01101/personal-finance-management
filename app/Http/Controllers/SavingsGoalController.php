<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\StoreSavingsGoalRequest;
use App\Http\Requests\Finance\UpdateSavingsGoalRequest;
use App\Models\SavingsGoal;
use App\Models\SavingsTransaction;
use App\Services\Finance\SavingsCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SavingsGoalController extends Controller
{
    public function __construct(private readonly SavingsCalculator $savingsCalculator) {}

    /**
     * Display a listing of the user's savings goals with their live progress.
     */
    public function index(Request $request): Response
    {
        $goals = $request->user()->savingsGoals()->orderByDesc('created_at')->get();

        return Inertia::render('savings/index', [
            'savingsGoals' => $goals,
            'summaries' => $goals->mapWithKeys(
                fn (SavingsGoal $goal) => [$goal->id => $this->savingsCalculator->summarize($goal)->toArray()],
            ),
        ]);
    }

    /**
     * Store a newly created savings goal.
     */
    public function store(StoreSavingsGoalRequest $request): RedirectResponse
    {
        $request->user()->savingsGoals()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Savings goal created.')]);

        return to_route('savings-goals.index');
    }

    /**
     * Show the goal's progress and complete contribution/withdrawal history.
     */
    #[Authorize('view', 'savings_goal')]
    public function show(SavingsGoal $savingsGoal): Response
    {
        $transactions = $savingsGoal->transactions()
            ->with('account')
            ->orderByDesc('transacted_on')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('savings/show', [
            'savingsGoal' => $savingsGoal,
            'summary' => $this->savingsCalculator->summarize($savingsGoal)->toArray(),
            'transactionGroups' => $this->groupByDate($transactions),
        ]);
    }

    /**
     * Update the savings goal.
     */
    #[Authorize('update', 'savings_goal')]
    public function update(UpdateSavingsGoalRequest $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        $savingsGoal->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Savings goal updated.')]);

        return to_route('savings-goals.index');
    }

    /**
     * Remove the savings goal.
     */
    #[Authorize('delete', 'savings_goal')]
    public function destroy(SavingsGoal $savingsGoal): RedirectResponse
    {
        $savingsGoal->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Savings goal deleted.')]);

        return to_route('savings-goals.index');
    }

    /**
     * @param  Collection<int, SavingsTransaction>  $transactions
     * @return array<int, array{date: string, label: string, transactions: array<int, SavingsTransaction>}>
     */
    private function groupByDate(Collection $transactions): array
    {
        $today = CarbonImmutable::today();

        return $transactions
            ->groupBy(fn (SavingsTransaction $transaction) => $transaction->transacted_on->format('Y-m-d'))
            ->map(function (Collection $group, string $date) use ($today) {
                $day = CarbonImmutable::parse($date);

                $label = match (true) {
                    $day->isSameDay($today) => 'Today',
                    $day->isSameDay($today->subDay()) => 'Yesterday',
                    default => $day->format('j F'),
                };

                return [
                    'date' => $date,
                    'label' => $label,
                    'transactions' => $group->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
