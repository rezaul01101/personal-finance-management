<?php

namespace App\Http\Controllers;

use App\Enums\CategoryStatus;
use App\Http\Requests\Finance\StoreIncomeRequest;
use App\Http\Requests\Finance\UpdateIncomeRequest;
use App\Models\Income;
use App\Services\Finance\IncomeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class IncomeController extends Controller
{
    public function __construct(private readonly IncomeService $incomes) {}

    /**
     * Display a listing of the user's income, most recent first.
     */
    public function index(Request $request): Response
    {
        $incomes = $request->user()->incomes()
            ->with('account')
            ->orderByDesc('received_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('incomes/index', [
            'incomes' => $incomes,
        ]);
    }

    /**
     * Show the form for adding a new income entry.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('incomes/create', $this->formOptions($request));
    }

    /**
     * Store a newly created income entry.
     */
    public function store(StoreIncomeRequest $request): RedirectResponse
    {
        $this->incomes->create($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Income added.')]);

        return to_route('incomes.index');
    }

    /**
     * Show the form for editing the income entry.
     */
    #[Authorize('view', 'income')]
    public function edit(Request $request, Income $income): Response
    {
        return Inertia::render('incomes/edit', [
            ...$this->formOptions($request),
            'income' => $income,
        ]);
    }

    /**
     * Update the income entry, reversing and reapplying all related calculations.
     */
    #[Authorize('update', 'income')]
    public function update(UpdateIncomeRequest $request, Income $income): RedirectResponse
    {
        $this->incomes->update($income, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Income updated.')]);

        return to_route('incomes.index');
    }

    /**
     * Remove the income entry, reversing its account balance effect.
     */
    #[Authorize('delete', 'income')]
    public function destroy(Income $income): RedirectResponse
    {
        $this->incomes->delete($income);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Income deleted.')]);

        return to_route('incomes.index');
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
