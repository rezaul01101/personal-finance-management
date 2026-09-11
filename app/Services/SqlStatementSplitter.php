<?php

namespace App\Services;

/**
 * Splits a multi-statement SQL dump into individual statements. Tracks
 * quoted strings so a `;` inside a value doesn't split one statement in
 * two, and drops any chunk that is blank once its leading `--` comments
 * are stripped.
 */
final class SqlStatementSplitter
{
    /**
     * @return array<int, string>
     */
    public function split(string $sql): array
    {
        $statements = [];
        $current = '';
        $quote = null;

        for ($i = 0, $length = strlen($sql); $i < $length; $i++) {
            $char = $sql[$i];
            $current .= $char;

            if ($quote !== null) {
                if ($char === '\\') {
                    $current .= $sql[++$i] ?? '';
                } elseif ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
            } elseif ($char === ';') {
                $statements[] = $current;
                $current = '';
            }
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return array_values(array_filter(
            $statements,
            fn (string $statement) => $this->hasExecutableSql($statement),
        ));
    }

    private function hasExecutableSql(string $statement): bool
    {
        $withoutComments = preg_replace('/^\s*--.*$/m', '', $statement);

        return trim((string) $withoutComments) !== '';
    }
}
