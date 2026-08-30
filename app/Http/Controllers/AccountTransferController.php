<?php

namespace App\Http\Controllers;

use App\Enums\CategoryStatus;
use App\Http\Requests\Finance\StoreAccountTransferRequest;
use App\Http\Requests\Finance\UpdateAccountTransferRequest;
use App\Models\AccountTransfer;
use App\Services\Finance\AccountTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class AccountTransferController extends Controller
{
    public function __construct(private readonly AccountTransferService $transfers) {}

    /**
     * Display a listing of the user's account transfers, most recent first.
     */
    public function index(Request $request): Response
    {
        $transfers = $request->user()->accountTransfers()
            ->with(['fromAccount', 'toAccount'])
            ->orderByDesc('transferred_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('transfers/index', [
            'transfers' => $transfers,
        ]);
    }

    /**
     * Show the form for adding a new transfer.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('transfers/create', $this->formOptions($request));
    }

    /**
     * Store a newly created transfer.
     */
    public function store(StoreAccountTransferRequest $request): RedirectResponse
    {
        $this->transfers->create($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transfer complete.')]);

        return to_route('transfers.index');
    }

    /**
     * Show the form for editing the transfer.
     */
    #[Authorize('view', 'transfer')]
    public function edit(Request $request, AccountTransfer $transfer): Response
    {
        return Inertia::render('transfers/edit', [
            ...$this->formOptions($request),
            'transfer' => $transfer,
        ]);
    }

    /**
     * Update the transfer, reversing and reapplying all related calculations.
     */
    #[Authorize('update', 'transfer')]
    public function update(UpdateAccountTransferRequest $request, AccountTransfer $transfer): RedirectResponse
    {
        $this->transfers->update($transfer, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transfer updated.')]);

        return to_route('transfers.index');
    }

    /**
     * Remove the transfer, reversing its account balance effects.
     */
    #[Authorize('delete', 'transfer')]
    public function destroy(AccountTransfer $transfer): RedirectResponse
    {
        $this->transfers->delete($transfer);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transfer deleted.')]);

        return to_route('transfers.index');
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
