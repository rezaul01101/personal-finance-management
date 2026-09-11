<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BackupImportRequest;
use App\Services\DatabaseBackupService;
use App\Services\DatabaseRestoreService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * Show the backup settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/backup');
    }

    /**
     * Stream a full SQL dump of the application database as a download.
     */
    public function download(DatabaseBackupService $backups): StreamedResponse
    {
        set_time_limit(0);

        $filename = 'backup-'.now()->format('Y-m-d-His').'.sql';

        return response()->streamDownload(function () use ($backups) {
            $backups->writeTo(fopen('php://output', 'wb'));
        }, $filename, ['Content-Type' => 'application/sql']);
    }

    /**
     * Restore the application database from an uploaded SQL dump,
     * replacing every table it describes.
     */
    public function import(BackupImportRequest $request, DatabaseRestoreService $restores): RedirectResponse
    {
        set_time_limit(0);

        $restores->restore($request->file('backup')->getContent());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Database restored from backup.')]);

        return to_route('backup.edit');
    }
}
