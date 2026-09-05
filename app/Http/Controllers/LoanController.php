<?php

namespace App\Http\Controllers;

use App\Enums\CategoryStatus;
use App\Enums\LoanType;
use App\Http\Requests\Finance\StoreLoanRequest;
use App\Http\Requests\Finance\UpdateLoanRequest;
use App\Models\Contact;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanTransfer;
use App\Services\Finance\LoanCalculator;
use App\Services\Finance\LoanService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function __construct(
        private readonly LoanService $loans,
        private readonly LoanCalculator $loanCalculator,
    ) {}

    /**
     * Display the user's contacts for the given direction (given/taken),
     * each with its combined total and outstanding balance across every
     * loan they have - so lending the same person 5k then 3k shows as one
     * 8k entry, not two unrelated ones.
     */
    public function index(Request $request): Response
    {
        $direction = LoanType::tryFrom((string) $request->query('direction')) ?? LoanType::Given;

        $contacts = $request->user()->contacts()
            ->whereHas('loans', fn ($query) => $query->where('type', $direction))
            ->withCount(['loans as loans_count' => fn ($query) => $query->where('type', $direction)])
            ->orderBy('name')
            ->get();

        return Inertia::render('loans/index', [
            'contacts' => $contacts,
            'direction' => $direction->value,
            'summaries' => $contacts->mapWithKeys(fn (Contact $contact) => [
                $contact->id => [
                    'total_amount' => $this->loanCalculator->contactTotal($contact, $direction)->toDecimalString(),
                    'outstanding' => $this->loanCalculator->contactOutstanding($contact, $direction)->toDecimalString(),
                ],
            ]),
        ]);
    }

    /**
     * Show the form for adding a new loan given or taken.
     */
    public function create(Request $request): Response
    {
        $direction = LoanType::tryFrom((string) $request->query('direction')) ?? LoanType::Given;

        $preselectedContact = $request->user()->contacts()
            ->find($request->integer('contact_id'));

        return Inertia::render('loans/create', [
            ...$this->formOptions($request),
            'direction' => $direction->value,
            'preselectedContactId' => $preselectedContact?->id,
        ]);
    }

    /**
     * Display one contact's individual loans of the given direction, with
     * their combined total/outstanding balance.
     */
    #[Authorize('view', 'contact')]
    public function contact(Request $request, Contact $contact): Response
    {
        $direction = LoanType::tryFrom((string) $request->query('direction')) ?? LoanType::Given;

        $loans = $contact->loans()
            ->where('type', $direction)
            ->with('account')
            ->orderByDesc('loan_date')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('loans/contact', [
            'contact' => $contact,
            'direction' => $direction->value,
            'loans' => $loans,
            'progress' => $loans->mapWithKeys(
                fn (Loan $loan) => [$loan->id => $this->loanCalculator->progress($loan)->toArray()],
            ),
            'summary' => [
                'total_amount' => $this->loanCalculator->contactTotal($contact, $direction)->toDecimalString(),
                'outstanding' => $this->loanCalculator->contactOutstanding($contact, $direction)->toDecimalString(),
            ],
        ]);
    }

    /**
     * Store a newly created loan.
     */
    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $loan = $this->loans->create(
            $request->user(),
            $request->safe()->except('photos'),
            $request->file('photos', []),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Loan added.')]);

        return to_route('loans.index', ['direction' => $loan->type->value]);
    }

    /**
     * Show the loan's progress and complete repayment/transfer history.
     */
    #[Authorize('view', 'loan')]
    public function show(Loan $loan): Response
    {
        $loan->load('account', 'contact', 'attachments');

        return Inertia::render('loans/show', [
            'loan' => $loan,
            'progress' => $this->loanCalculator->progress($loan)->toArray(),
            'historyGroups' => $this->groupByDate($this->historyItems($loan)),
        ]);
    }

    /**
     * Show the form for editing the loan.
     */
    #[Authorize('view', 'loan')]
    public function edit(Request $request, Loan $loan): Response
    {
        return Inertia::render('loans/edit', [
            ...$this->formOptions($request),
            'loan' => $loan->load('contact', 'attachments'),
        ]);
    }

    /**
     * Update the loan, reversing and reapplying its account effect.
     */
    #[Authorize('update', 'loan')]
    public function update(UpdateLoanRequest $request, Loan $loan): RedirectResponse
    {
        $this->loans->update(
            $loan,
            $request->safe()->except('photos'),
            $request->file('photos', []),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Loan updated.')]);

        return to_route('loans.index', ['direction' => $loan->type->value]);
    }

    /**
     * Remove the loan, reversing its account balance effect.
     */
    #[Authorize('delete', 'loan')]
    public function destroy(Loan $loan): RedirectResponse
    {
        $direction = $loan->type->value;

        $this->loans->delete($loan);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Loan deleted.')]);

        return to_route('loans.index', ['direction' => $direction]);
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
            'contacts' => $request->user()->contacts()->orderBy('name')->get(),
        ];
    }

    /**
     * Merge repayments (and, for a loan given, transfers) into one
     * chronological list of history entries with a common shape.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function historyItems(Loan $loan): Collection
    {
        $repayments = $loan->repayments()->with('account')->get()
            ->map(fn (LoanRepayment $repayment) => [
                'kind' => 'repayment',
                'id' => $repayment->id,
                'amount' => $repayment->amount,
                'date' => $repayment->repaid_on->format('Y-m-d'),
                'note' => $repayment->note,
                'account' => $repayment->account,
            ]);

        $transfers = $loan->type === LoanType::Given
            ? $loan->transfers()->with('account')->get()
                ->map(fn (LoanTransfer $transfer) => [
                    'kind' => 'transfer',
                    'id' => $transfer->id,
                    'amount' => $transfer->amount,
                    'date' => $transfer->transferred_on->format('Y-m-d'),
                    'note' => $transfer->note,
                    'account' => $transfer->account,
                ])
            : collect();

        return $repayments->concat($transfers)
            ->sortByDesc(fn (array $item) => $item['date'].'-'.$item['id'])
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<int, array{date: string, label: string, items: array<int, array<string, mixed>>}>
     */
    private function groupByDate(Collection $items): array
    {
        $today = CarbonImmutable::today();

        return $items
            ->groupBy(fn (array $item) => $item['date'])
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
                    'items' => $group->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
