<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AvatarUpdateRequest;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\DatabaseBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    public function updateProfile(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json(['user' => $user->toApiPayload()]);
    }

    public function updateAvatar(AvatarUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $previous = $user->avatar_path;

        $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        $user->save();

        if ($previous) {
            Storage::disk('public')->delete($previous);
        }

        return response()->json(['user' => $user->toApiPayload()]);
    }

    public function destroyAvatar(): JsonResponse
    {
        $user = request()->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        return response()->json(['user' => $user->toApiPayload()]);
    }

    public function updatePassword(PasswordUpdateRequest $request): Response
    {
        $request->user()->update(['password' => $request->password]);

        return response()->noContent();
    }

    public function destroy(ProfileDeleteRequest $request): Response
    {
        $user = $request->user();

        $user->tokens()->delete();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->delete();

        return response()->noContent();
    }

    /**
     * The app cannot attach its bearer token to a native file download, so hand it a short-lived signed URL.
     */
    public function backupLink(): JsonResponse
    {
        $expiresAt = now()->addMinutes(5);

        return response()->json(['data' => [
            'url' => URL::temporarySignedRoute('api.settings.backup.download', $expiresAt),
            'expires_at' => $expiresAt->toIso8601String(),
        ]]);
    }

    public function downloadBackup(DatabaseBackupService $backups): StreamedResponse
    {
        set_time_limit(0);

        $filename = 'backup-'.now()->format('Y-m-d-His').'.sql';

        return response()->streamDownload(function () use ($backups) {
            $backups->writeTo(fopen('php://output', 'wb'));
        }, $filename, ['Content-Type' => 'application/sql']);
    }
}
