<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class NoteController extends Controller
{
    /**
     * List the user's quick notes, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->notes()->latest()->get(),
        );
    }

    /**
     * Store a newly created note.
     */
    public function store(StoreNoteRequest $request): JsonResponse
    {
        $note = $request->user()->notes()->create($request->validated());

        return response()->json($note, 201);
    }

    /**
     * Update the note.
     */
    #[Authorize('update', 'note')]
    public function update(UpdateNoteRequest $request, Note $note): JsonResponse
    {
        $note->update($request->validated());

        return response()->json($note);
    }

    /**
     * Remove the note.
     */
    #[Authorize('delete', 'note')]
    public function destroy(Note $note): JsonResponse
    {
        $note->delete();

        return response()->json(null, 204);
    }
}
