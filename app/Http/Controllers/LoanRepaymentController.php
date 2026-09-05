<?php

namespace App\Http\Controllers;

use App\Enums\CategoryStatus;
use App\Enums\LoanType;
use App\Http\Requests\Finance\StoreLoanRepaymentRequest;
use App\Http\Requests\Finance\UpdateLoanRepaymentRequest;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Services\Finance\LoanRepaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class LoanRepaymentController extends Controller
{
    public function __construct(private readonly LoanRepaymentService $repayments) {}

    /**
     * Show the form for recording a repayment against the given loan.
     */
    #[Authorize('view', 'loan')]
    public function create(Request $request, Loan $loan): Response
    {
        return Inertia::render('loans/repayments/create', [
            ...$this->formOptions($request, $loan),
            'loan' => $loan,
        ]);
    }

    /**
     * Store a newly recorded repayment.
     */
    #[Authorize('view', 'loan')]
    public function store(StoreLoanRepaymentRequest $request, Loan $loan): RedirectResponse
    {
        $this->repayments->create($request->user(), $loan, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Repayment recorded.')]);

        return to_route('loans.show', $loan);
    }

    /**
     * Show the form for editing the repayment.
     */
    #[Authorize('view', 'repayment')]
    public function edit(Request $request, LoanRepayment $repayment): Response
    {
        return Inertia::render('loans/repayments/edit', [
            ...$this->formOptions($request, $repayment->loan),
            'repayment' => $repayment,
            'loan' => $repayment->loan,
        ]);
    }

    /**
     * Update the repayment, reversing and reapplying any account effect.
     */
    #[Authorize('update', 'repayment')]
    public function update(UpdateLoanRepaymentRequest $request, LoanRepayment $repayment): RedirectResponse
    {
        $this->repayments->update($repayment, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Repayment updated.')]);

        return to_route('loans.show', $repayment->loan_id);
    }

    /**
     * Remove the repayment, reversing any account effect.
     */
    #[Authorize('delete', 'repayment')]
    public function destroy(LoanRepayment $repayment): RedirectResponse
    {
        $loanId = $repayment->loan_id;

        $this->repayments->delete($repayment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Repayment deleted.')]);

        return to_route('loans.show', $loanId);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request, Loan $loan): array
    {
        if ($loan->type !== LoanType::Taken) {
            return [];
        }

        return [
            'accounts' => $request->user()->accounts()
                ->where('status', CategoryStatus::Active)
                ->orderBy('name')
                ->get(),
        ];
    }
}
