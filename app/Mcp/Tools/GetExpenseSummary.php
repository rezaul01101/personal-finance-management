<?php

namespace App\Mcp\Tools;

use App\Models\Expense;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Returns the total spending and itemized expenses for the authenticated user over a given period (today, this week, or this month). Use this to answer questions about how much the user has spent and on what.')]
#[IsReadOnly]
#[IsIdempotent]
class GetExpenseSummary extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'range' => ['sometimes', 'string', 'in:today,week,month'],
        ]);

        $range = $validated['range'] ?? 'week';

        [$start, $end] = match ($range) {
            'today' => [Carbon::today(), Carbon::today()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            default => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
        };

        $expenses = Expense::query()
            ->with('expenseCategory')
            ->where('user_id', $request->user()->id)
            ->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('spent_on')
            ->get();

        $total = (float) $expenses->sum('amount');

        $items = $expenses->map(fn (Expense $expense): array => [
            'id' => $expense->id,
            'amount' => (float) $expense->amount,
            'category' => $expense->expenseCategory->name,
            'note' => $expense->note,
            'spent_on' => $expense->spent_on->toDateString(),
        ])->all();

        $summary = sprintf(
            'You spent $%s across %d expense%s %s.',
            number_format($total, 2),
            $expenses->count(),
            $expenses->count() === 1 ? '' : 's',
            match ($range) {
                'today' => 'today',
                'month' => 'this month',
                default => 'this week',
            },
        );

        return Response::make(Response::text($summary))->withStructuredContent([
            'total' => $total,
            'count' => $expenses->count(),
            'items' => $items,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'range' => $schema->string()
                ->enum(['today', 'week', 'month'])
                ->description('The time period to summarize spending for.')
                ->default('week'),
        ];
    }
}
