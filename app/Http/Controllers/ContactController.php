<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\StoreContactRequest;
use App\Http\Requests\Finance\UpdateContactRequest;
use App\Models\Contact;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Display a listing of the user's contacts.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('contacts/index', [
            'contacts' => $request->user()->contacts()->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created contact.
     *
     * Returns JSON when called from the loan form's inline "add new person"
     * picker (via `useHttp`, which does not trigger an Inertia page visit),
     * so the in-progress loan form isn't lost.
     */
    public function store(StoreContactRequest $request): RedirectResponse|JsonResponse
    {
        $contact = $request->user()->contacts()->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json($contact, 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact added.')]);

        return to_route('contacts.index');
    }

    /**
     * Update the contact.
     */
    #[Authorize('update', 'contact')]
    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $contact->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact updated.')]);

        return to_route('contacts.index');
    }

    /**
     * Remove the contact, unless loans still reference it.
     */
    #[Authorize('delete', 'contact')]
    public function destroy(Contact $contact): RedirectResponse
    {
        try {
            $contact->delete();
        } catch (QueryException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This person has loans recorded against them and cannot be deleted.')]);

            return to_route('contacts.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact deleted.')]);

        return to_route('contacts.index');
    }
}
