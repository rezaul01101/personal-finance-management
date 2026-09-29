<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreContactRequest;
use App\Http\Requests\Finance\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ContactResource::collection(
            $request->user()->contacts()->withCount('loans')->orderBy('name')->get(),
        );
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = $request->user()->contacts()->create($request->validated());

        return (new ContactResource($contact->loadCount('loans')))->response()->setStatusCode(201);
    }

    #[Authorize('view', 'contact')]
    public function show(Contact $contact): ContactResource
    {
        return new ContactResource($contact->loadCount('loans'));
    }

    #[Authorize('update', 'contact')]
    public function update(UpdateContactRequest $request, Contact $contact): ContactResource
    {
        $contact->update($request->validated());

        return new ContactResource($contact->loadCount('loans'));
    }

    #[Authorize('delete', 'contact')]
    public function destroy(Contact $contact): Response
    {
        try {
            $contact->delete();
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'contact' => __('This person has loans recorded against them and cannot be deleted.'),
            ]);
        }

        return response()->noContent();
    }
}
