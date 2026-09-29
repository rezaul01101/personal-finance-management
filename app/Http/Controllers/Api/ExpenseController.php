<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\IndexExpenseRequest;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Http\Requests\Finance\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\Finance\ExpenseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfWriter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ExpenseController extends Controller
{
    private const RELATIONS = ['expenseCategory', 'budgetCategory', 'account', 'attachments'];

    public function __construct(private readonly ExpenseService $expenses) {}

    public function index(IndexExpenseRequest $request): AnonymousResourceCollection
    {
        $filters = $request->filters();

        $expenses = $this->filteredQuery($request, $filters)
            ->orderByDesc('spent_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return ExpenseResource::collection($expenses);
    }

    public function show(Expense $expense): ExpenseResource
    {
        Gate::authorize('view', $expense);

        return new ExpenseResource($expense->load(self::RELATIONS));
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $this->expenses->create(
            $request->user(),
            $request->safe()->except('receipts'),
            $request->file('receipts', []),
        );

        return (new ExpenseResource($expense->load(self::RELATIONS)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        Gate::authorize('update', $expense);

        $expense = $this->expenses->update(
            $expense,
            $request->safe()->except('receipts'),
            $request->file('receipts', []),
        );

        return new ExpenseResource($expense->load(self::RELATIONS));
    }

    public function destroy(Expense $expense): Response
    {
        Gate::authorize('delete', $expense);

        $this->expenses->delete($expense);

        return response()->noContent();
    }

    /**
     * Export the filtered expenses as a PDF, grouped by date (same report as the web app).
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
     * @param  array<string, string>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<Expense>
     */
    private function filteredQuery(Request $request, array $filters): Builder
    {
        return $request->user()->expenses()
            ->with(self::RELATIONS)
            ->when($filters['expense_category_id'] ?? null, fn (Builder $query, string $value) => $query->where('expense_category_id', $value))
            ->when($filters['budget_category_id'] ?? null, fn (Builder $query, string $value) => $query->where('budget_category_id', $value))
            ->when($filters['account_id'] ?? null, fn (Builder $query, string $value) => $query->where('account_id', $value))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $value) => $query->whereDate('spent_on', '>=', $value))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $value) => $query->whereDate('spent_on', '<=', $value))
            ->when($filters['search'] ?? null, fn (Builder $query, string $value) => $query->where('note', 'like', '%'.$value.'%'));
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
     * Register the Bengali-script font so the PDF can render Bangla text.
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
}
