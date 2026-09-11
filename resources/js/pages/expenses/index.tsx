import { Head, Link, router } from '@inertiajs/react';
import { FileDown, ListFilter, Plus, Trash2, X } from 'lucide-react';
import { FormEvent, useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import expenses from '@/routes/expenses';
import type {
    Account,
    BudgetCategory,
    Expense,
    ExpenseCategory,
    Paginated,
} from '@/types/finance';

interface ExpenseFilters {
    expense_category_id?: string;
    budget_category_id?: string;
    account_id?: string;
    date_from?: string;
    date_to?: string;
    search?: string;
    [key: string]: string | undefined;
}

const emptyFilters: Required<ExpenseFilters> = {
    expense_category_id: '',
    budget_category_id: '',
    account_id: '',
    date_from: '',
    date_to: '',
    search: '',
};

function formatDateHeading(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('en-US', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function groupByDate(expenses: Expense[]): { date: string; expenses: Expense[] }[] {
    const groups: { date: string; expenses: Expense[] }[] = [];

    for (const expense of expenses) {
        const currentGroup = groups[groups.length - 1];

        if (currentGroup && currentGroup.date === expense.spent_on) {
            currentGroup.expenses.push(expense);
        } else {
            groups.push({ date: expense.spent_on, expenses: [expense] });
        }
    }

    return groups;
}

export default function ExpensesIndex({
    expenses: paginated,
    filters,
    expenseCategories,
    budgetCategories,
    accounts,
}: {
    expenses: Paginated<Expense>;
    filters: ExpenseFilters;
    expenseCategories: ExpenseCategory[];
    budgetCategories: BudgetCategory[];
    accounts: Account[];
}) {
    const activeFilterCount = Object.values(filters).filter(Boolean).length;
    const [form, setForm] = useState<Required<ExpenseFilters>>({
        ...emptyFilters,
        ...filters,
    });
    const [filtersOpen, setFiltersOpen] = useState(activeFilterCount > 0);

    function update<K extends keyof ExpenseFilters>(key: K, value: string) {
        setForm((prev) => ({ ...prev, [key]: value }));
    }

    function submit(e: FormEvent) {
        e.preventDefault();

        router.get(expenses.index.url(), form, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function resetFilters() {
        setForm(emptyFilters);
        router.get(
            expenses.index.url(),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function destroy(expense: Expense) {
        if (confirm('Delete this expense?')) {
            router.delete(expenses.destroy.url(expense.id), {
                preserveScroll: true,
            });
        }
    }

    const exportHref = expenses.export.pdf.url({ query: filters });
    const groupedExpenses = groupByDate(paginated.data);

    return (
        <>
            <Head title="Expenses" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
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

                <Card>
                    <Collapsible open={filtersOpen} onOpenChange={setFiltersOpen}>
                        <CardContent className="space-y-3 py-3">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <CollapsibleTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="-ml-2 gap-2"
                                    >
                                        <ListFilter className="size-4" />
                                        Filters
                                        {activeFilterCount > 0 && (
                                            <span className="bg-primary text-primary-foreground rounded-full px-1.5 py-0.5 text-xs font-medium">
                                                {activeFilterCount}
                                            </span>
                                        )}
                                    </Button>
                                </CollapsibleTrigger>

                                <Button variant="outline" size="sm" asChild>
                                    <a href={exportHref}>
                                        <FileDown className="size-4" />
                                        Export PDF
                                    </a>
                                </Button>
                            </div>

                            <CollapsibleContent className="space-y-3">
                                <form
                                    onSubmit={submit}
                                    className="space-y-3"
                                >
                                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                                        <div className="grid gap-1">
                                            <Label className="text-xs">
                                                Category
                                            </Label>
                                            <Select
                                                value={
                                                    form.expense_category_id ||
                                                    'all'
                                                }
                                                onValueChange={(value) =>
                                                    update(
                                                        'expense_category_id',
                                                        value === 'all'
                                                            ? ''
                                                            : value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger
                                                    size="sm"
                                                    className="w-full"
                                                >
                                                    <SelectValue placeholder="All categories" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">
                                                        All categories
                                                    </SelectItem>
                                                    {expenseCategories.map(
                                                        (category) => (
                                                            <SelectItem
                                                                key={
                                                                    category.id
                                                                }
                                                                value={String(
                                                                    category.id,
                                                                )}
                                                            >
                                                                {category.name}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="grid gap-1">
                                            <Label className="text-xs">
                                                Budget
                                            </Label>
                                            <Select
                                                value={
                                                    form.budget_category_id ||
                                                    'all'
                                                }
                                                onValueChange={(value) =>
                                                    update(
                                                        'budget_category_id',
                                                        value === 'all'
                                                            ? ''
                                                            : value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger
                                                    size="sm"
                                                    className="w-full"
                                                >
                                                    <SelectValue placeholder="All budgets" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">
                                                        All budgets
                                                    </SelectItem>
                                                    {budgetCategories.map(
                                                        (category) => (
                                                            <SelectItem
                                                                key={
                                                                    category.id
                                                                }
                                                                value={String(
                                                                    category.id,
                                                                )}
                                                            >
                                                                {category.name}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="grid gap-1">
                                            <Label className="text-xs">
                                                Account
                                            </Label>
                                            <Select
                                                value={
                                                    form.account_id || 'all'
                                                }
                                                onValueChange={(value) =>
                                                    update(
                                                        'account_id',
                                                        value === 'all'
                                                            ? ''
                                                            : value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger
                                                    size="sm"
                                                    className="w-full"
                                                >
                                                    <SelectValue placeholder="All accounts" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">
                                                        All accounts
                                                    </SelectItem>
                                                    {accounts.map(
                                                        (account) => (
                                                            <SelectItem
                                                                key={
                                                                    account.id
                                                                }
                                                                value={String(
                                                                    account.id,
                                                                )}
                                                            >
                                                                {account.name}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="grid gap-1">
                                            <Label className="text-xs">
                                                From
                                            </Label>
                                            <Input
                                                type="date"
                                                className="h-8 text-sm"
                                                value={form.date_from}
                                                onChange={(e) =>
                                                    update(
                                                        'date_from',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-1">
                                            <Label className="text-xs">
                                                To
                                            </Label>
                                            <Input
                                                type="date"
                                                className="h-8 text-sm"
                                                value={form.date_to}
                                                onChange={(e) =>
                                                    update(
                                                        'date_to',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </div>

                                        <div className="col-span-2 grid gap-1 sm:col-span-3 lg:col-span-1">
                                            <Label className="text-xs">
                                                Description
                                            </Label>
                                            <Input
                                                type="text"
                                                className="h-8 text-sm"
                                                placeholder="Search description"
                                                value={form.search}
                                                onChange={(e) =>
                                                    update(
                                                        'search',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2">
                                        <Button type="submit" size="sm">
                                            Apply filters
                                        </Button>
                                        {activeFilterCount > 0 && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={resetFilters}
                                            >
                                                <X className="size-4" />
                                                Clear
                                            </Button>
                                        )}
                                    </div>
                                </form>
                            </CollapsibleContent>
                        </CardContent>
                    </Collapsible>
                </Card>

                {paginated.data.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            {activeFilterCount > 0 ? (
                                <>
                                    No expenses match these filters.
                                    <br />
                                    Try widening your search.
                                </>
                            ) : (
                                <>
                                    No expenses yet.
                                    <br />
                                    Start tracking your spending.
                                </>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {groupedExpenses.map((group) => {
                            const dayTotal = group.expenses.reduce(
                                (sum, expense) =>
                                    sum + parseFloat(expense.amount),
                                0,
                            );

                            return (
                                <div
                                    key={group.date}
                                    className="rounded-lg border"
                                >
                                    <div className="bg-muted/50 flex items-center justify-between rounded-t-lg px-4 py-2">
                                        <p className="text-sm font-semibold">
                                            {formatDateHeading(group.date)}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            -৳{dayTotal.toFixed(2)}
                                        </p>
                                    </div>
                                    <div className="divide-y">
                                        {group.expenses.map((expense) => (
                                            <div
                                                key={expense.id}
                                                className="flex items-center justify-between gap-4 p-4"
                                            >
                                                <Link
                                                    href={expenses.edit(
                                                        expense.id,
                                                    )}
                                                    className="min-w-0 flex-1"
                                                >
                                                    <p className="font-medium">
                                                        {
                                                            expense
                                                                .expense_category
                                                                ?.name
                                                        }
                                                        <span className="text-muted-foreground ml-2 text-xs">
                                                            {
                                                                expense
                                                                    .budget_category
                                                                    ?.name
                                                            }{' '}
                                                            ·{' '}
                                                            {
                                                                expense.account
                                                                    ?.name
                                                            }
                                                        </span>
                                                    </p>
                                                    {expense.note && (
                                                        <p className="text-muted-foreground text-sm">
                                                            {expense.note}
                                                        </p>
                                                    )}
                                                </Link>
                                                <div className="flex items-center gap-2">
                                                    <p className="font-semibold">
                                                        -৳{expense.amount}
                                                    </p>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            destroy(expense)
                                                        }
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
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
