import { Head, Link, router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import expenses from '@/routes/expenses';
import type { Expense, Paginated } from '@/types/finance';

export default function ExpensesIndex({
    expenses: paginated,
}: {
    expenses: Paginated<Expense>;
}) {
    function destroy(expense: Expense) {
        if (confirm('Delete this expense?')) {
            router.delete(expenses.destroy.url(expense.id), {
                preserveScroll: true,
            });
        }
    }

    return (
        <>
            <Head title="Expenses" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Expenses"
                        description="Everything you've spent, most recent first."
                    />
                    <Button asChild>
                        <Link href={expenses.create()}>
                            <Plus className="size-4" />
                            Add Expense
                        </Link>
                    </Button>
                </div>

                {paginated.data.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            No expenses yet.
                            <br />
                            Start tracking your spending.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="divide-y rounded-lg border">
                        {paginated.data.map((expense) => (
                            <div
                                key={expense.id}
                                className="flex items-center justify-between gap-4 p-4"
                            >
                                <Link
                                    href={expenses.edit(expense.id)}
                                    className="min-w-0 flex-1"
                                >
                                    <p className="font-medium">
                                        {expense.expense_category?.name}
                                        <span className="text-muted-foreground ml-2 text-xs">
                                            {expense.budget_category?.name} ·{' '}
                                            {expense.account?.name}
                                        </span>
                                    </p>
                                    <p className="text-muted-foreground text-sm">
                                        {expense.spent_on}
                                        {expense.note && ` · ${expense.note}`}
                                    </p>
                                </Link>
                                <div className="flex items-center gap-2">
                                    <p className="font-semibold">
                                        -৳{expense.amount}
                                    </p>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => destroy(expense)}
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {paginated.last_page > 1 && (
                    <div className="flex flex-wrap gap-1">
                        {paginated.links.map((link, index) =>
                            link.url ? (
                                <Button
                                    key={index}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    asChild
                                >
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                </Button>
                            ) : (
                                <Button
                                    key={index}
                                    variant="outline"
                                    size="sm"
                                    disabled
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

ExpensesIndex.layout = {
    breadcrumbs: [{ title: 'Expenses', href: expenses.index() }],
};
