<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DashboardRequest;
use App\Http\Requests\Finance\StoreBudgetCategoryRequest;
use App\Http\Requests\Finance\UpdateBudgetCategoryRequest;
use App\Http\Resources\BudgetCategoryResource;
use App\Http\Resources\BudgetExpenseResource;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\MonthlyBudget;
use App\Services\Finance\BudgetCalculator;
use App\Services\Finance\BudgetMath;
use App\Services\Finance\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Collection;

class BudgetCategoryController extends Controller
{
    public function __construct(private readonly BudgetCalculator $budgetCalculator) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return BudgetCategoryResource::collection(
            $request->user()->budgetCategories()->orderBy('sort_order')->orderBy('name')->get(),
        );
    }

    public function store(StoreBudgetCategoryRequest $request): JsonResponse
    {
        $category = $request->user()->budgetCategories()->create($request->validated())->refresh();

        return (new BudgetCategoryResource($category))->response()->setStatusCode(201);
    }

    #[Authorize('view', 'budget_category')]
    public function show(BudgetCategory $budgetCategory): BudgetCategoryResource
    {
        return new BudgetCategoryResource($budgetCategory);
    }

    /**
     * The category's month view: budget health, a spend breakdown by expense
     * category and the month's expenses grouped by date.
     */
    #[Authorize('view', 'budget_category')]
    public function month(DashboardRequest $request, BudgetCategory $budgetCategory): JsonResponse
    {
        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);
        $user = $request->user();

        $monthlyBudget = MonthlyBudget::query()
            ->where('user_id', $user->id)
            ->where('budget_category_id', $budgetCategory->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        $summary = null;
        if ($monthlyBudget) {
            $monthlyBudget->setRelation('budgetCategory', $budgetCategory);
            $summary = $this->budgetCalculator->summarize($monthlyBudget)->toArray();
        }

        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();

        $expenses = Expense::query()
            ->where('user_id', $user->id)
            ->where('budget_category_id', $budgetCategory->id)
            ->whereBetween('spent_on', [$periodStart->toDateString(), $periodStart->endOfMonth()->toDateString()])
            ->with(['expenseCategory', 'account'])
            ->orderByDesc('spent_on')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => [
            'year' => $year,
            'month' => $month,
            'budget_category' => (new BudgetCategoryResource($budgetCategory))->resolve(),
            'summary' => $summary,
            'category_breakdown' => $this->categoryBreakdown($expenses),
            'transaction_groups' => $this->groupByDate($expenses, $request),
        ]]);
    }

    #[Authorize('update', 'budget_category')]
    public function update(UpdateBudgetCategoryRequest $request, BudgetCategory $budgetCategory): BudgetCategoryResource
    {
        $budgetCategory->update($request->validated());

        return new BudgetCategoryResource($budgetCategory);
    }

    /**
     * Remove the budget category, unless expenses still reference it.
     */
    #[Authorize('delete', 'budget_category')]
    public function destroy(BudgetCategory $budgetCategory): Response|JsonResponse
    {
        try {
            $budgetCategory->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'This category has expenses recorded against it and cannot be deleted. Archive it instead.',
            ], 409);
        }

        return response()->noContent();
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @return array<int, array{expense_category: array<string, mixed>, total: string, percentage: float}>
     */
    private function categoryBreakdown(Collection $expenses): array
    {
        $totalUsed = $expenses->reduce(
            fn (Money $carry, Expense $expense) => $carry->add(Money::of($expense->amount)),
            Money::zero(),
        );

        return $expenses
            ->groupBy('expense_category_id')
            ->map(function (Collection $group) use ($totalUsed) {
                $categoryTotal = $group->reduce(
                    fn (Money $carry, Expense $expense) => $carry->add(Money::of($expense->amount)),
                    Money::zero(),
                );

                return [
                    'expense_category' => [
                        'id' => $group->first()->expenseCategory->id,
                        'name' => $group->first()->expenseCategory->name,
                        'icon' => $group->first()->expenseCategory->icon,
                    ],
                    'total' => $categoryTotal->toDecimalString(),
                    'percentage' => BudgetMath::usagePercentage($totalUsed, $categoryTotal),
                    'sortKey' => $categoryTotal->toFloat(),
                ];
            })
            ->sortByDesc('sortKey')
            ->map(fn (array $row) => [
                'expense_category' => $row['expense_category'],
                'total' => $row['total'],
                'percentage' => $row['percentage'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @return array<int, array{date: string, label: string, expenses: array<int, mixed>}>
     */
    private function groupByDate(Collection $expenses, Request $request): array
    {
        $today = CarbonImmutable::today();

        return $expenses
            ->groupBy(fn (Expense $expense) => $expense->spent_on->format('Y-m-d'))
            ->map(function (Collection $group, string $date) use ($today, $request) {
                $day = CarbonImmutable::parse($date);

                $label = match (true) {
                    $day->isSameDay($today) => 'Today',
                    $day->isSameDay($today->subDay()) => 'Yesterday',
                    default => $day->format('j F'),
                };

                return [
                    'date' => $date,
                    'label' => $label,
                    'expenses' => BudgetExpenseResource::collection($group->values())->resolve($request),
                ];
            })
            ->values()
            ->all();
    }
}
