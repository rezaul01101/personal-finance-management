<?php

namespace App\Http\Controllers;

use App\Enums\CategoryStatus;
use App\Enums\LoanType;
use App\Http\Requests\Finance\StoreLoanTransferRequest;
use App\Http\Requests\Finance\UpdateLoanTransferRequest;
use App\Models\Loan;
use App\Models\LoanTransfer;
use App\Services\Finance\LoanCalculator;
use App\Services\Finance\LoanTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Moves money out of a loan given's held-but-untransferred pool into a real
 * account. Only meaningful for loans given (spec: a loan taken never has a
 * holding pool) - create/store 404 for a loan taken.
 */
class LoanTransferController extends Controller
{
    public function __construct(
        private readonly LoanTransferService $transfers,
        private readonly LoanCalculator $loanCalculator,
    ) {}

    /**
     * Show the form for transferring some or all of the held balance.
     */
    #[Authorize('view', 'loan')]
    public function create(Request $request, Loan $loan): Response
    {
        abort_unless($loan->type === LoanType::Given, 404);

        return Inertia::render('loans/transfers/create', [
            ...$this->formOptions($request),
            'loan' => $loan->load('contact'),
            'heldBalance' => $this->loanCalculator->heldBalance($loan)->toDecimalString(),
        ]);
    }

    /**
     * Store a newly recorded transfer.
     */
    #[Authorize('view', 'loan')]
    public function store(StoreLoanTransferRequest $request, Loan $loan): RedirectResponse
    {
        abort_unless($loan->type === LoanType::Given, 404);

        $this->transfers->create($request->user(), $loan, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transfer recorded.')]);

        return to_route('loans.show', $loan);
    }

    /**
     * Show the form for editing the transfer.
     */
    #[Authorize('view', 'transfer')]
    public function edit(Request $request, Loan $loan, LoanTransfer $transfer): Response
    {
        abort_unless($transfer->loan_id === $loan->id, 404);

        return Inertia::render('loans/transfers/edit', [
            ...$this->formOptions($request),
            'loan' => $loan->load('contact'),
            'transfer' => $transfer,
            'heldBalance' => $this->loanCalculator->heldBalance($loan)->toDecimalString(),
        ]);
    }

    /**
     * Update the transfer, reversing and reapplying its account effect.
     */
    #[Authorize('update', 'transfer')]
    public function update(UpdateLoanTransferRequest $request, Loan $loan, LoanTransfer $transfer): RedirectResponse
    {
        abort_unless($transfer->loan_id === $loan->id, 404);

        $this->transfers->update($transfer, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transfer updated.')]);

        return to_route('loans.show', $loan);
    }

    /**
     * Remove the transfer, reversing its account effect.
     */
    #[Authorize('delete', 'transfer')]
    public function destroy(Loan $loan, LoanTransfer $transfer): RedirectResponse
    {
        abort_unless($transfer->loan_id === $loan->id, 404);

        $this->transfers->delete($transfer);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transfer deleted.')]);

        return to_route('loans.show', $loan);
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
