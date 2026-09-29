<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreAccountTransferRequest;
use App\Http\Requests\Finance\UpdateAccountTransferRequest;
use App\Http\Resources\AccountTransferResource;
use App\Models\AccountTransfer;
use App\Services\Finance\AccountTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AccountTransferController extends Controller
{
    private const RELATIONS = ['fromAccount', 'toAccount'];

    public function __construct(private readonly AccountTransferService $transfers) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $transfers = $request->user()->accountTransfers()
            ->with(self::RELATIONS)
            ->orderByDesc('transferred_on')
            ->orderByDesc('id')
            ->paginate(20);

        return AccountTransferResource::collection($transfers);
    }

    public function show(AccountTransfer $transfer): AccountTransferResource
    {
        Gate::authorize('view', $transfer);

        return new AccountTransferResource($transfer->load(self::RELATIONS));
    }

    public function store(StoreAccountTransferRequest $request): JsonResponse
    {
        $transfer = $this->transfers->create($request->user(), $request->validated());

        return (new AccountTransferResource($transfer->load(self::RELATIONS)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAccountTransferRequest $request, AccountTransfer $transfer): AccountTransferResource
    {
        Gate::authorize('update', $transfer);

        $transfer = $this->transfers->update($transfer, $request->validated());

        return new AccountTransferResource($transfer->load(self::RELATIONS));
    }

    public function destroy(AccountTransfer $transfer): Response
    {
        Gate::authorize('delete', $transfer);

        $this->transfers->delete($transfer);

        return response()->noContent();
    }
}
