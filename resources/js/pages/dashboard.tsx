import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { AmountDisplay } from '@/components/finance/amount-display';
import {
    BudgetOverview,
    type DashboardTotals,
} from '@/components/finance/budget-overview';
import { MonthSelector } from '@/components/finance/month-selector';
import {
    TopCategoryList,
    type TopCategory,
} from '@/components/finance/top-category-list';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import budgets from '@/routes/budgets';
import expenses from '@/routes/expenses';
import loans from '@/routes/loans';
import type { BudgetSummary, Expense, LoanSummary } from '@/types/finance';

interface BudgetRow {
    category: { id: number; name: string; icon: string | null };
    summary: BudgetSummary;
}

const AVATAR_TINTS = ['#d32a30', '#f08a8a', '#8f1218', '#e9a23b', '#5b8def', '#7a6565'];

const GROUP_ORDER = [
    'Today',
    'Yesterday',
    'Last week',
    'Earlier this month',
    'Last month',
    'Older',
];

function toDateString(date: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/** Buckets expenses (already newest first) by how long ago they were spent; empty buckets are dropped. */
function groupExpenses(list: Expense[]): { label: string; items: Expense[] }[] {
    const now = new Date();
    const daysAgo = (n: number) =>
        toDateString(new Date(now.getFullYear(), now.getMonth(), now.getDate() - n));
    const today = daysAgo(0);
    const yesterday = daysAgo(1);
    const weekStart = daysAgo(7);
    const thisMonth = today.slice(0, 7);
    const lastMonth = toDateString(
        new Date(now.getFullYear(), now.getMonth() - 1, 1),
    ).slice(0, 7);

    const labelFor = (spentOn: string) => {
        const day = spentOn.slice(0, 10);

        if (day >= today) return 'Today';
        if (day === yesterday) return 'Yesterday';
        if (day >= weekStart) return 'Last week';
        if (day.startsWith(thisMonth)) return 'Earlier this month';
        if (day.startsWith(lastMonth)) return 'Last month';

        return 'Older';
    };

    const groups: { label: string; items: Expense[] }[] = [];

    for (const expense of list) {
        const label = labelFor(expense.spent_on);
        const group = groups.find((g) => g.label === label);

        if (group) {
            group.items.push(expense);
        } else {
            groups.push({ label, items: [expense] });
        }
    }

    return groups.sort(
        (a, b) => GROUP_ORDER.indexOf(a.label) - GROUP_ORDER.indexOf(b.label),
    );
}

export default function Dashboard({
    year,
    month,
    budgets: budgetRows,
    totals,
    topExpenseCategories,
    recentExpenses,
    loanSummary,
    hasLoans,
}: {
    year: number;
    month: number;
    budgets: BudgetRow[];
    totals: DashboardTotals;
    topExpenseCategories: TopCategory[];
    recentExpenses: Expense[];
    loanSummary: LoanSummary;
    hasLoans: boolean;
}) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center gap-3">
                    <MonthSelector
                        year={year}
                        month={month}
                        buildHref={(y, m) =>
                            dashboard.url({ query: { year: y, month: m } })
                        }
                    />
                    <Button asChild className="hidden md:inline-flex">
                        <Link href={expenses.create()}>
                            <Plus className="size-4" />
                            Add Expense
                        </Link>
                    </Button>
                </div>

                {budgetRows.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            No budgets set for this month yet.{' '}
                            <Link
                                href={budgets.index()}
                                className="text-primary font-medium underline"
                            >
                                Set a budget
                            </Link>{' '}
                            to see your spending health here.
                        </CardContent>
                    </Card>
                ) : (
                    <BudgetOverview
                        totals={totals}
                        budgets={budgetRows}
                        year={year}
                        month={month}
                    />
                )}

                <section className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold">Top Expenses</h2>
                    {topExpenseCategories.length === 0 ? (
                        <Card>
                            <CardContent className="text-muted-foreground text-sm">
                                No expenses yet this month.
                            </CardContent>
                        </Card>
                    ) : (
                        <TopCategoryList
                            items={topExpenseCategories}
                            year={year}
                            month={month}
                        />
                    )}
                </section>

                <section className="flex flex-col gap-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-semibold">
                            Recent Expenses
                        </h2>
                        <Link
                            href={expenses.index()}
                            className="text-primary text-sm font-semibold underline-offset-4 hover:underline"
                        >
                            View all
                        </Link>
                    </div>
                    {recentExpenses.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No expenses yet this month.
                        </p>
                    ) : (
                        groupExpenses(recentExpenses).map((group) => (
                            <div
                                key={group.label}
                                className="flex flex-col gap-2"
                            >
                                <p className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">
                                    {group.label}
                                </p>
                                {group.items.map((expense) => {
                                    const name =
                                        expense.expense_category?.name ?? '';
                                    const tint =
                                        AVATAR_TINTS[
                                            expense.expense_category_id %
                                                AVATAR_TINTS.length
                                        ];

                                    return (
                                        <div
                                            key={expense.id}
                                            className="glass flex items-center gap-3 rounded-[18px] p-3"
                                        >
                                            <div
                                                className="grid size-11 shrink-0 place-items-center rounded-full text-lg font-bold"
                                                style={{
                                                    backgroundColor: `${tint}26`,
                                                    color: tint,
                                                }}
                                            >
                                                {expense.budget_category
                                                    ?.icon ||
                                                    name.charAt(0).toUpperCase()}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate font-semibold">
                                                    {name}
                                                </p>
                                                <p className="text-muted-foreground truncate text-xs">
                                                    {expense.budget_category?.name}{' '}
                                                    · {expense.spent_on}
                                                </p>
                                            </div>
                                            <p className="shrink-0 font-bold">
                                                -৳{expense.amount}
                                            </p>
                                        </div>
                                    );
                                })}
                            </div>
                        ))
                    )}
                </section>

                {/* Loans given/taken - kept separate, never summed together (spec §27) */}
                <Card>
                    <CardHeader className="flex-row items-center justify-between">
                        <CardTitle className="text-sm">Loans</CardTitle>
                        <Link
                            href={loans.index()}
                            className="text-primary text-sm font-semibold underline-offset-4 hover:underline"
                        >
                            View all
                        </Link>
                    </CardHeader>
                    <CardContent>
                        {!hasLoans ? (
                            <p className="text-muted-foreground text-sm">
                                No active loans.{' '}
                                <Link
                                    href={loans.create()}
                                    className="text-primary font-medium underline"
                                >
                                    Add Loan
                                </Link>
                            </p>
                        ) : (
                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1">
                                    <p className="text-muted-foreground text-xs">
                                        Given (outstanding)
                                    </p>
                                    <AmountDisplay
                                        value={
                                            loanSummary.outstanding_receivable
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <p className="text-muted-foreground text-xs">
                                        Taken (outstanding)
                                    </p>
                                    <AmountDisplay
                                        value={loanSummary.outstanding_payable}
                                    />
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
