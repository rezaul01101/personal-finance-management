<?php

namespace App\Http\Controllers;

use App\Enums\CategoryStatus;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Http\Requests\Finance\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\Finance\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $expenses) {}

    /**
     * Display a listing of the user's expenses, most recent first.
     */
    public function index(Request $request): Response
    {
        $expenses = $request->user()->expenses()
            ->with(['expenseCategory', 'budgetCategory', 'account', 'attachments'])
            ->orderByDesc('spent_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('expenses/index', [
            'expenses' => $expenses,
        ]);
    }

    /**
     * Show the form for adding a new expense.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('expenses/create', $this->formOptions($request));
    }

    /**
     * Store a newly created expense.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->expenses->create(
            $request->user(),
            $request->safe()->except('receipts'),
            $request->file('receipts', []),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense added.')]);

        return to_route('expenses.index');
    }

    /**
     * Show the form for editing the expense.
     */
    #[Authorize('view', 'expense')]
    public function edit(Request $request, Expense $expense): Response
    {
        return Inertia::render('expenses/edit', [
            ...$this->formOptions($request),
            'expense' => $expense->load('attachments'),
        ]);
    }

    /**
     * Update the expense, reversing and reapplying all related calculations.
     */
    #[Authorize('update', 'expense')]
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->expenses->update(
            $expense,
            $request->safe()->except('receipts'),
            $request->file('receipts', []),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense updated.')]);

        return to_route('expenses.index');
    }

    /**
     * Remove the expense, reversing its account balance effect.
     */
    #[Authorize('delete', 'expense')]
    public function destroy(Expense $expense): RedirectResponse
    {
        $this->expenses->delete($expense);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense deleted.')]);

        return to_route('expenses.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        return [
            'expenseCategories' => $request->user()->expenseCategories()
                ->where('status', CategoryStatus::Active)
                ->orderBy('name')
                ->get(),
            'budgetCategories' => $request->user()->budgetCategories()
                ->where('status', CategoryStatus::Active)
                ->orderBy('name')
                ->get(),
            'accounts' => $request->user()->accounts()
                ->where('status', CategoryStatus::Active)
                ->orderBy('name')
                ->get(),
        ];
    }
}
