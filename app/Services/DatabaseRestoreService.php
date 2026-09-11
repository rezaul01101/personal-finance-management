<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Restores the application database from a plain SQL dump, such as one
 * produced by DatabaseBackupService. Statements are executed one at a
 * time (rather than as a single multi-statement query) since PDO's MySQL
 * driver does not support running several statements per call.
 */
final class DatabaseRestoreService
{
    public function __construct(private readonly SqlStatementSplitter $splitter) {}

    public function restore(string $sql): void
    {
        DB::unprepared('SET FOREIGN_KEY_CHECKS=0;');

        try {
            foreach ($this->splitter->split($sql) as $statement) {
                // @phpstan-ignore argument.type (statement is parsed from an uploaded SQL dump, not a literal by nature)
                DB::unprepared($statement);
            }
        } finally {
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
