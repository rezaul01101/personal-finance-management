<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generates a plain SQL dump of the application database, written directly
 * to a stream rather than an intermediate file so it can be piped straight
 * into an HTTP download response without buffering the whole dump in memory.
 */
final class DatabaseBackupService
{
    /**
     * Framework-internal tables that hold ephemeral runtime state or
     * short-lived secrets rather than application data - never worth
     * restoring, and sessions/password reset tokens shouldn't leave the
     * server in a backup file at all.
     *
     * @var array<int, string>
     */
    private const EXCLUDED_TABLES = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'password_reset_tokens',
        'sessions',
    ];

    private const ROWS_PER_INSERT = 500;

    /**
     * @param  resource  $stream
     */
    public function writeTo($stream): void
    {
        fwrite($stream, "SET NAMES utf8mb4;\n");
        fwrite($stream, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($this->tableNames() as $table) {
            $this->writeTable($stream, $table);
        }

        fwrite($stream, "SET FOREIGN_KEY_CHECKS=1;\n");
    }

    /**
     * @return array<int, string>
     */
    private function tableNames(): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->reject(fn (string $name) => in_array($name, self::EXCLUDED_TABLES, true))
            ->values()
            ->all();
    }

    /**
     * @param  resource  $stream
     */
    private function writeTable($stream, string $table): void
    {
        fwrite($stream, "-- --------------------------------------------------------\n");
        fwrite($stream, "-- Table: {$table}\n");
        fwrite($stream, "-- --------------------------------------------------------\n\n");

        $createStatement = DB::selectOne("SHOW CREATE TABLE `{$table}`");
        $createSql = $createStatement->{'Create Table'};

        fwrite($stream, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($stream, "{$createSql};\n\n");

        $primaryColumn = collect(Schema::getIndexes($table))
            ->firstWhere('primary', true)['columns'][0] ?? null;

        $query = DB::table($table);

        if ($primaryColumn !== null) {
            $query->orderBy($primaryColumn);
        }

        $buffer = [];

        $flush = function () use ($stream, $table, &$buffer): void {
            if ($buffer === []) {
                return;
            }

            $columns = implode(', ', array_map(
                fn (string $column) => "`{$column}`",
                array_keys($buffer[0]),
            ));

            $rows = implode(",\n", array_map(
                fn (array $row) => '('.implode(', ', array_map($this->quote(...), $row)).')',
                $buffer,
            ));

            fwrite($stream, "INSERT INTO `{$table}` ({$columns}) VALUES\n{$rows};\n");

            $buffer = [];
        };

        $query->chunk(self::ROWS_PER_INSERT, function ($rows) use (&$buffer, $flush): void {
            foreach ($rows as $row) {
                $buffer[] = (array) $row;
            }

            $flush();
        });

        fwrite($stream, "\n");
    }

    private function quote(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return DB::connection()->getPdo()->quote((string) $value);
    }
}
