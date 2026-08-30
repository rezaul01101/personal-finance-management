import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { BudgetCategoryCard } from '@/components/finance/budget-category-card';
import { MonthSelector } from '@/components/finance/month-selector';
import { TopCategoryList } from '@/components/finance/top-category-list';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import budgets from '@/routes/budgets';
import expenses from '@/routes/expenses';
import type { BudgetSummary, Expense } from '@/types/finance';

interface BudgetRow {
    category: { id: number; name: string; icon: string | null };
    summary: BudgetSummary;
}

export default function Dashboard({
    year,
    month,
    remainingDays,
    budgets: budgetRows,
    topExpenseCategories,
    recentExpenses,
}: {
    year: number;
    month: number;
    remainingDays: number;
    budgets: BudgetRow[];
    topExpenseCategories: {
        label: string;
        amount: string;
        percentage: number;
    }[];
    recentExpenses: Expense[];
}) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Dashboard"
                        description={`${remainingDays} ${remainingDays === 1 ? 'day' : 'days'} left this month`}
                    />

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
                </div>

                {/* Primary content: budget category health, per spec §4-7 */}
                {budgetRows.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            No budgets set for this month yet.{' '}
                            <Link
                                href={budgets.index()}
                                className="text-primary underline"
                            >
                                Set a budget
                            </Link>{' '}
                            to see your spending health here.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {budgetRows.map((row) => (
                            <BudgetCategoryCard
                                key={row.category.id}
                                category={row.category}
                                summary={row.summary}
                                year={year}
                                month={month}
                            />
                        ))}
                    </div>
                )}

                {/* Secondary: where the money went this month */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Top Categories</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {topExpenseCategories.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    No expenses yet this month.
                                </p>
                            ) : (
                                <TopCategoryList items={topExpenseCategories} />
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle>Recent Expenses</CardTitle>
                            <Button
                                variant="link"
                                asChild
                                className="h-auto p-0"
                            >
                                <Link href={expenses.index()}>View all</Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="p-0">
                            {recentExpenses.length === 0 ? (
                                <p className="text-muted-foreground px-6 pb-6 text-sm">
                                    No expenses yet this month.
                                </p>
                            ) : (
                                <div className="divide-y">
                                    {recentExpenses.map((expense) => (
                                        <div
                                            key={expense.id}
                                            className="flex items-center justify-between gap-3 px-6 py-3"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {
                                                        expense.expense_category
                                                            ?.name
                                                    }
                                                </p>
                                                <p className="text-muted-foreground truncate text-sm">
                                                    {
                                                        expense.budget_category
                                                            ?.name
                                                    }{' '}
                                                    · {expense.spent_on}
                                                </p>
                                            </div>
                                            <p className="shrink-0 font-semibold">
                                                -৳{expense.amount}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
