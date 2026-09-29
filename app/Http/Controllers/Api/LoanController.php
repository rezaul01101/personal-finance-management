<?php

namespace App\Http\Controllers\Api;

use App\Enums\LoanType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreLoanRequest;
use App\Http\Requests\Finance\UpdateLoanRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\LoanResource;
use App\Models\Contact;
use App\Models\Loan;
use App\Services\Finance\LoanCalculator;
use App\Services\Finance\LoanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class LoanController extends Controller
{
    public function __construct(
        private readonly LoanService $loans,
        private readonly LoanCalculator $loanCalculator,
    ) {}

    /**
     * Given and taken totals are never mixed.
     */
    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->loanCalculator->dashboardSummary($request->user())->toArray(),
        ]);
    }

    /**
     * The user's contacts for a direction, each with combined total and
     * outstanding across their loans (the web's loans index).
     */
    public function contacts(Request $request): JsonResponse
    {
        $direction = $this->direction($request);

        $contacts = $request->user()->contacts()
            ->whereHas('loans', fn (Builder $query) => $query->where('type', $direction))
            ->withCount(['loans as loans_count' => fn (Builder $query) => $query->where('type', $direction)])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $contacts->map(fn (Contact $contact) => [
                ...(new ContactResource($contact))->resolve($request),
                ...$this->contactSummary($contact, $direction),
            ])->all(),
        ]);
    }

    /**
     * One contact's loans for a direction with their combined balances.
     */
    #[Authorize('view', 'contact')]
    public function contact(Request $request, Contact $contact): JsonResponse
    {
        $direction = $this->direction($request);

        $loans = $contact->loans()
            ->where('type', $direction)
            ->with(['account', 'contact'])
            ->withSum('repayments', 'amount')
            ->withSum('transfers', 'amount')
            ->orderByDesc('loan_date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => [
                'contact' => (new ContactResource($contact))->resolve($request),
                'direction' => $direction->value,
                ...$this->contactSummary($contact, $direction),
                'loans' => LoanResource::collection($loans)->resolve($request),
            ],
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $direction = LoanType::tryFrom((string) $request->query('direction'));

        $loans = $request->user()->loans()
            ->with(['account', 'contact'])
            ->withSum('repayments', 'amount')
            ->withSum('transfers', 'amount')
            ->when($direction, fn (Builder $query) => $query->where('type', $direction))
            ->when($request->integer('contact_id'), fn (Builder $query, int $contactId) => $query->where('contact_id', $contactId))
            ->orderByDesc('loan_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return LoanResource::collection($loans);
    }

    public function store(StoreLoanRequest $request): JsonResponse
    {
        $loan = $this->loans->create(
            $request->user(),
            $request->safe()->except('photos'),
            $request->file('photos', []),
        );

        return (new LoanResource($this->loaded($loan)))->response()->setStatusCode(201);
    }

    #[Authorize('view', 'loan')]
    public function show(Loan $loan): LoanResource
    {
        return new LoanResource($this->loaded($loan));
    }

    #[Authorize('update', 'loan')]
    public function update(UpdateLoanRequest $request, Loan $loan): LoanResource
    {
        $this->loans->update(
            $loan,
            $request->safe()->except('photos'),
            $request->file('photos', []),
        );

        return new LoanResource($this->loaded($loan));
    }

    #[Authorize('delete', 'loan')]
    public function destroy(Loan $loan): Response
    {
        $this->loans->delete($loan);

        return response()->noContent();
    }

    private function direction(Request $request): LoanType
    {
        return LoanType::tryFrom((string) $request->query('direction')) ?? LoanType::Given;
    }

    /**
     * @return array{total_amount: string, outstanding: string}
     */
    private function contactSummary(Contact $contact, LoanType $direction): array
    {
        return [
            'total_amount' => $this->loanCalculator->contactTotal($contact, $direction)->toDecimalString(),
            'outstanding' => $this->loanCalculator->contactOutstanding($contact, $direction)->toDecimalString(),
        ];
    }

    private function loaded(Loan $loan): Loan
    {
        return $loan->load([
            'account',
            'contact',
            'attachments',
            'repayments' => fn ($query) => $query->with('account')->orderByDesc('repaid_on')->orderByDesc('id'),
            'transfers' => fn ($query) => $query->with('account')->orderByDesc('transferred_on')->orderByDesc('id'),
        ]);
    }
}
