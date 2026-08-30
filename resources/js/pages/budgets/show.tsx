import { Head } from '@inertiajs/react';
import { budgetHealth, ProgressBar } from '@/components/finance/progress-bar';
import { MonthSelector } from '@/components/finance/month-selector';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import budgetCategories from '@/routes/budget-categories';
import type { BudgetCategory, BudgetSummary, Expense } from '@/types/finance';

interface TransactionGroup {
    date: string;
    label: string;
    expenses: Expense[];
}

interface CategoryBreakdownRow {
    expense_category: { id: number; name: string; icon: string | null };
    total: string;
    percentage: number;
}

export default function BudgetCategoryShow({
    year,
    month,
    budgetCategory,
    summary,
    categoryBreakdown,
    transactionGroups,
}: {
    year: number;
    month: number;
    budgetCategory: BudgetCategory;
    summary: BudgetSummary | null;
    categoryBreakdown: CategoryBreakdownRow[];
    transactionGroups: TransactionGroup[];
}) {
    const health = summary ? budgetHealth(summary.usage_percentage) : 'healthy';

    return (
        <>
            <Head title={budgetCategory.name} />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={`${budgetCategory.icon ? budgetCategory.icon + ' ' : ''}${budgetCategory.name}`}
                    />
                    <MonthSelector
                        year={year}
                        month={month}
                        buildHref={(y, m) =>
                            budgetCategories.show.url(budgetCategory.id, {
                                query: { year: y, month: m },
                            })
                        }
                    />
                </div>

                {summary ? (
                    <Card>
                        <CardContent className="space-y-3">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-3xl font-bold">
                                        ৳{summary.used_amount}{' '}
                                        <span className="text-muted-foreground text-base font-normal">
                                            used
                                        </span>
                                    </p>
                                    <p className="text-muted-foreground text-sm">
                                        of ৳{summary.budget_amount}
                                    </p>
                                </div>
                                {health === 'exceeded' && (
                                    <Badge variant="destructive">
                                        Budget exceeded
                                    </Badge>
                                )}
                                {health === 'warning' && (
                                    <Badge className="bg-chart-4 text-white">
                                        Warning
                                    </Badge>
                                )}
                            </div>

                            <ProgressBar
                                percentage={summary.usage_percentage}
                                health={health}
                            />

                            {summary.is_exceeded ? (
                                <p className="text-destructive text-sm font-semibold">
                                    ৳{summary.over_budget_amount} over budget
                                </p>
                            ) : (
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        Available{' '}
                                        <b className="text-foreground">
                                            ৳{summary.available_amount}
                                        </b>
                                    </span>
                                    <span className="text-muted-foreground">
                                        Daily Safe Spend{' '}
                                        <b className="text-foreground">
                                            ৳{summary.daily_safe_spend}/day
                                        </b>
                                    </span>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="text-muted-foreground py-8 text-center text-sm">
                            No budget set for {budgetCategory.name} this month.
                        </CardContent>
                    </Card>
                )}

                {categoryBreakdown.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Summary</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {categoryBreakdown.map((row) => (
                                <div key={row.expense_category.id}>
                                    <div className="mb-1.5 flex items-center justify-between text-sm">
                                        <span className="font-medium">
                                            {row.expense_category.icon &&
                                                `${row.expense_category.icon} `}
                                            {row.expense_category.name}
                                        </span>
                                        <span className="text-muted-foreground">
                                            ৳{row.total} · {row.percentage}%
                                        </span>
                                    </div>
                                    <div className="bg-muted h-2 overflow-hidden rounded-full">
                                        <div
                                            className="bg-primary h-full rounded-full"
                                            style={{
                                                width: `${Math.min(Math.max(row.percentage, 0), 100)}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Transactions</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {transactionGroups.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No expenses in this category yet.
                            </p>
                        ) : (
                            transactionGroups.map((group) => (
                                <div key={group.date}>
                                    <p className="text-muted-foreground mb-2 text-xs font-semibold tracking-wide uppercase">
                                        {group.label}
                                    </p>
                                    <div className="divide-y rounded-lg border">
                                        {group.expenses.map((expense) => (
                                            <div
                                                key={expense.id}
                                                className="flex items-center justify-between gap-4 p-3"
                                            >
                                                <div>
                                                    <p className="font-medium">
                                                        {
                                                            expense
                                                                .expense_category
                                                                ?.name
                                                        }
                                                    </p>
                                                    {expense.note && (
                                                        <p className="text-muted-foreground text-sm">
                                                            {expense.note}
                                                        </p>
                                                    )}
                                                </div>
                                                <p className="font-semibold">
                                                    -৳{expense.amount}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

BudgetCategoryShow.layout = (props: { budgetCategory: BudgetCategory }) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        {
            title: props.budgetCategory.name,
            href: budgetCategories.show(props.budgetCategory.id),
        },
    ],
});
