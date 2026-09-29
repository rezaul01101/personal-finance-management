<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreAccountRequest;
use App\Http\Requests\Finance\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class AccountController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return AccountResource::collection($request->user()->accounts()->orderBy('name')->get());
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = $request->user()->accounts()->create($request->validated())->refresh();

        return (new AccountResource($account))->response()->setStatusCode(201);
    }

    #[Authorize('view', 'account')]
    public function show(Account $account): AccountResource
    {
        return new AccountResource($account);
    }

    #[Authorize('update', 'account')]
    public function update(UpdateAccountRequest $request, Account $account): AccountResource
    {
        $account->update($request->validated());

        return new AccountResource($account);
    }

    /**
     * Remove the account, unless transactions still reference it.
     */
    #[Authorize('delete', 'account')]
    public function destroy(Account $account): Response|JsonResponse
    {
        try {
            $account->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'This account has transactions recorded against it and cannot be deleted. Archive it instead.',
            ], 409);
        }

        return response()->noContent();
    }
}
