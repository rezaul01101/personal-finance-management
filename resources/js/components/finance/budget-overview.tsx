import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import budgetCategories from '@/routes/budget-categories';
import type { BudgetSummary } from '@/types/finance';

export interface DashboardTotals {
    total_budget: string;
    total_used: string;
    total_available: string;
    is_exceeded: boolean;
    daily_safe_spend: string;
    usage_percentage: number;
}

interface BudgetRow {
    category: { id: number; name: string; icon: string | null };
    summary: BudgetSummary;
}

type Status = 'healthy' | 'warning' | 'exceeded';

const STATUS_LABEL: Record<Status, string> = {
    healthy: 'On track',
    warning: 'Warning',
    exceeded: 'Exceeded',
};

const STATUS_BADGE: Record<Status, string> = {
    healthy: 'bg-success text-white',
    warning: 'bg-[#f5a94a] text-foreground',
    exceeded: 'bg-primary text-white',
};

const STATUS_BAR: Record<Status, string> = {
    healthy: 'bg-success',
    warning: 'bg-[#e9a23b]',
    exceeded: 'bg-[#7d0d14]',
};

function whole(value: string): string {
    return `৳${Math.round(parseFloat(value || '0')).toLocaleString('en-US')}`;
}

function statusOf(summary: BudgetSummary): Status {
    if (summary.is_exceeded || summary.usage_percentage > 100) {
        return 'exceeded';
    }

    return summary.usage_percentage >= 80 ? 'warning' : 'healthy';
}

/** Dark glass hero: what is safe to spend today (or the overspend), then used vs budget. */
function TotalCard({ totals }: { totals: DashboardTotals }) {
    const progress = Math.max(0, Math.min(100, totals.usage_percentage));
    const fill = totals.is_exceeded
        ? 'bg-[#ff6b73]'
        : totals.usage_percentage >= 75
          ? 'bg-[#f5a94a]'
          : 'bg-[#7fd49a]';

    return (
        <div className="glass-dark flex flex-col gap-2 rounded-3xl p-4">
            <p className="text-xs font-bold opacity-80">
                {totals.is_exceeded ? 'Over budget by' : 'Safe to spend today'}
            </p>
            <div className="flex items-baseline gap-1.5">
                <p className="truncate text-4xl font-extrabold tabular-nums">
                    {whole(
                        totals.is_exceeded
                            ? totals.total_available
                            : totals.daily_safe_spend,
                    )}
                </p>
                {!totals.is_exceeded && (
                    <p className="text-sm font-bold opacity-80">
                        of {whole(totals.total_available)} left
                    </p>
                )}
            </div>
            <div className="h-2.5 overflow-hidden rounded-full bg-white/20">
                <div
                    className={cn('h-full rounded-full', fill)}
                    style={{ width: `${progress}%` }}
                />
            </div>
            <div className="flex gap-2">
                <div className="flex flex-1 items-baseline justify-between rounded-xl bg-white/10 px-2.5 py-1.5">
                    <span className="text-xs opacity-80">
                        Used ({Math.round(totals.usage_percentage)}%)
                    </span>
                    <span className="text-sm font-bold">
                        {whole(totals.total_used)}
                    </span>
                </div>
                <div className="flex flex-1 items-baseline justify-between rounded-xl bg-white/10 px-2.5 py-1.5">
                    <span className="text-xs opacity-80">Budget</span>
                    <span className="text-sm font-bold">
                        {whole(totals.total_budget)}
                    </span>
                </div>
            </div>
        </div>
    );
}

function BudgetListRow({
    row,
    year,
    month,
}: {
    row: BudgetRow;
    year: number;
    month: number;
}) {
    const { category, summary } = row;
    const status = statusOf(summary);
    const planned = parseFloat(summary.budget_amount);
    const usage =
        planned > 0
            ? Math.max(0, Math.min(1, parseFloat(summary.used_amount) / planned))
            : summary.is_exceeded
              ? 1
              : 0;

    return (
        <Link
            href={budgetCategories.show.url(category.id, {
                query: { year, month },
            })}
            className="border-t-foreground/10 flex flex-col gap-1.5 border-t py-3 first:border-t-0"
        >
            <div className="flex items-center justify-between gap-2">
                <p className="truncate text-base font-extrabold">
                    {category.icon ? `${category.icon} ` : ''}
                    {category.name}
                </p>
                <span
                    className={cn(
                        'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold',
                        STATUS_BADGE[status],
                    )}
                >
                    {STATUS_LABEL[status]}
                </span>
            </div>
            <div className="bg-foreground/10 h-[7px] overflow-hidden rounded-full">
                <div
                    className={cn('h-full rounded-full', STATUS_BAR[status])}
                    style={{ width: `${usage * 100}%` }}
                />
            </div>
            <div className="flex items-center justify-between gap-2">
                <div className="flex items-baseline gap-1.5">
                    <span className="text-[22px] leading-7 font-extrabold">
                        {whole(summary.used_amount)}
                    </span>
                    <span className="text-muted-foreground text-xs">
                        of {whole(summary.budget_amount)}
                    </span>
                </div>
                {summary.is_exceeded ? (
                    <span className="text-sm font-bold text-[#7d0d14]">
                        {whole(summary.over_budget_amount)} over
                    </span>
                ) : (
                    <span className="text-muted-foreground text-right text-xs">
                        Spend{' '}
                        <b className="text-foreground">
                            {whole(summary.daily_safe_spend)}/day
                        </b>
                        {' · '}Left{' '}
                        <b className="text-foreground">
                            {whole(summary.available_amount)}
                        </b>
                    </span>
                )}
            </div>
        </Link>
    );
}

/** The dashboard budgets block: the total card, then every budget together in a single glass card. */
export function BudgetOverview({
    totals,
    budgets,
    year,
    month,
}: {
    totals: DashboardTotals;
    budgets: BudgetRow[];
    year: number;
    month: number;
}) {
    return (
        <div className="flex flex-col gap-3">
            <TotalCard totals={totals} />
            <div className="glass rounded-3xl px-4 py-1.5">
                {budgets.map((row) => (
                    <BudgetListRow
                        key={row.category.id}
                        row={row}
                        year={year}
                        month={month}
                    />
                ))}
            </div>
        </div>
    );
}
