<?php

use App\Services\SqlStatementSplitter;

test('splits independent statements on their terminating semicolon', function () {
    $sql = "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n";

    expect((new SqlStatementSplitter)->split($sql))->toBe([
        'SET NAMES utf8mb4;',
        "\nSET FOREIGN_KEY_CHECKS=0;",
    ]);
});

test('does not split a statement on a semicolon inside a quoted value', function (string $quote) {
    $statement = "INSERT INTO notes (body) VALUES ({$quote}Milk; eggs; bread{$quote});";

    expect((new SqlStatementSplitter)->split($statement))->toBe([$statement]);
})->with([
    'single-quoted' => ["'"],
    'double-quoted' => ['"'],
    'backtick-quoted' => ['`'],
]);

test('does not treat an escaped quote as the end of a quoted value', function () {
    $statement = "INSERT INTO notes (body) VALUES ('It\\'s a semicolon: ;');";

    expect((new SqlStatementSplitter)->split($statement))->toBe([$statement]);
});

test('keeps a trailing statement that has no closing semicolon', function () {
    $statement = 'DROP TABLE IF EXISTS `accounts`';

    expect((new SqlStatementSplitter)->split($statement))->toBe([$statement]);
});

test('keeps a statement whose leading comments are followed by real sql', function () {
    $statement = "-- --------------------------------\n-- Table: accounts\n-- --------------------------------\n\nDROP TABLE IF EXISTS `accounts`;";

    expect((new SqlStatementSplitter)->split($statement))->toBe([$statement]);
});

test('returns no statements for a dump that is only comments', function () {
    $sql = "-- nothing but comments\n-- another comment\n";

    expect((new SqlStatementSplitter)->split($sql))->toBe([]);
});
