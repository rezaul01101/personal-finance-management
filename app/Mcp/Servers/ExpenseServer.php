<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetExpenseSummary;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Expense Server')]
#[Version('0.0.1')]
#[Instructions('Provides read-only access to the authenticated user\'s personal expense data.')]
class ExpenseServer extends Server
{
    protected array $tools = [
        GetExpenseSummary::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
