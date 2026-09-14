<?php

namespace App\Http\Controllers;

use App\Enums\CategoryStatus;
use App\Http\Requests\Finance\IndexExpenseRequest;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Http\Requests\Finance\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\Finance\ExpenseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfWriter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $expenses) {}

    /**
     * Display a listing of the user's expenses, most recent first.
     */
    public function index(IndexExpenseRequest $request): Response
    {
        $filters = $request->filters();

        $expenses = $this->filteredQuery($request, $filters)
            ->orderByDesc('spent_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('expenses/index', [
            'expenses' => $expenses,
            'filters' => $filters,
            ...$this->formOptions($request),
        ]);
    }

    /**
     * Export the filtered expenses as a PDF, grouped by date.
     */
    public function exportPdf(IndexExpenseRequest $request): SymfonyResponse
    {
        $filters = $request->filters();

        $expenses = $this->filteredQuery($request, $filters)
            ->orderBy('spent_on')
            ->orderBy('id')
            ->get();

        $categoryName = null;
        if ($id = $filters['expense_category_id'] ?? null) {
            $categoryName = $request->user()->expenseCategories()->find($id)?->name;
        }

        $pdf = Pdf::setOption('enable_font_subsetting', true);
        $this->registerBengaliFont($pdf);

        $pdf->loadView('exports.expenses', [
            'title' => $categoryName ? "{$categoryName} Report" : 'Expense Report',
            'groupedExpenses' => $expenses->groupBy(fn (Expense $expense) => $expense->spent_on->toDateString()),
            'filterSummary' => $this->filterSummary($request, $filters),
            'total' => $expenses->sum('amount'),
        ]);

        return $pdf->download('expenses-'.now()->format('Y-m-d-His').'.pdf');
    }

    /**
     * Register the Bengali-script font so PDF exports can render Bangla text
     * (dompdf's bundled DejaVu fonts only cover Latin/Cyrillic/Greek).
     */
    private function registerBengaliFont(PdfWriter $pdf): void
    {
        $fontMetrics = $pdf->getDomPDF()->getFontMetrics();

        $fontMetrics->registerFont(
            ['family' => 'Noto Sans Bengali', 'style' => 'normal', 'weight' => 'normal'],
            'file://'.resource_path('fonts/NotoSansBengali-Regular.ttf'),
        );

        $fontMetrics->registerFont(
            ['family' => 'Noto Sans Bengali', 'style' => 'normal', 'weight' => 'bold'],
            'file://'.resource_path('fonts/NotoSansBengali-Bold.ttf'),
        );
    }

    /**
     * Human-readable labels for the active filters, for display in the PDF header.
     *
     * @param  array<string, string>  $filters
     * @return array<int, string>
     */
    private function filterSummary(Request $request, array $filters): array
    {
        $summary = [];

        if ($id = $filters['budget_category_id'] ?? null) {
            $summary[] = 'Budget: '.$request->user()->budgetCategories()->find($id)?->name;
        }

        if ($id = $filters['account_id'] ?? null) {
            $summary[] = 'Account: '.$request->user()->accounts()->find($id)?->name;
        }

        if ($from = $filters['date_from'] ?? null) {
            $summary[] = 'From: '.$from;
        }

        if ($to = $filters['date_to'] ?? null) {
            $summary[] = 'To: '.$to;
        }

        if ($search = $filters['search'] ?? null) {
            $summary[] = 'Search: "'.$search.'"';
        }

        return $summary;
    }

    /**
     * @param  array<string, string>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<Expense>
     */
    private function filteredQuery(Request $request, array $filters): Builder
    {
        return $request->user()->expenses()
            ->with(['expenseCategory', 'budgetCategory', 'account', 'attachments'])
            ->when(
                $filters['expense_category_id'] ?? null,
                fn (Builder $query, string $value) => $query->where('expense_category_id', $value),
            )
            ->when(
                $filters['budget_category_id'] ?? null,
                fn (Builder $query, string $value) => $query->where('budget_category_id', $value),
            )
            ->when(
                $filters['account_id'] ?? null,
                fn (Builder $query, string $value) => $query->where('account_id', $value),
            )
            ->when(
                $filters['date_from'] ?? null,
                fn (Builder $query, string $value) => $query->whereDate('spent_on', '>=', $value),
            )
            ->when(
                $filters['date_to'] ?? null,
                fn (Builder $query, string $value) => $query->whereDate('spent_on', '<=', $value),
            )
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $value) => $query->where('note', 'like', '%'.$value.'%'),
            );
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
     * Remove the expense.
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
