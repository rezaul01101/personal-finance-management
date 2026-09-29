<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class NoteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return NoteResource::collection(
            $request->user()->notes()->latest()->orderByDesc('id')->paginate(20),
        );
    }

    public function store(StoreNoteRequest $request): JsonResponse
    {
        $note = $request->user()->notes()->create($request->validated());

        return (new NoteResource($note->refresh()))->response()->setStatusCode(201);
    }

    public function show(Note $note): NoteResource
    {
        Gate::authorize('view', $note);

        return new NoteResource($note);
    }

    public function update(UpdateNoteRequest $request, Note $note): NoteResource
    {
        Gate::authorize('update', $note);

        $note->update($request->validated());

        return new NoteResource($note->refresh());
    }

    public function destroy(Note $note): Response
    {
        Gate::authorize('delete', $note);

        $note->delete();

        return response()->noContent();
    }
}
