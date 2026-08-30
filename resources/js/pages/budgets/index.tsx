import { Head, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { AmountInput } from '@/components/finance/amount-input';
import { MonthSelector } from '@/components/finance/month-selector';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import budgets from '@/routes/budgets';
import type { BudgetCategory } from '@/types/finance';

export default function BudgetsIndex({
    year,
    month,
    budgetCategories,
    amounts,
    suggestedCategoryIds,
}: {
    year: number;
    month: number;
    budgetCategories: BudgetCategory[];
    amounts: Record<number, string>;
    suggestedCategoryIds: number[];
}) {
    const suggestedIds = new Set(suggestedCategoryIds);
    const form = useForm<{
        year: number;
        month: number;
        budgets: { budget_category_id: number; amount: string }[];
    }>({
        year,
        month,
        budgets: budgetCategories.map((category) => ({
            budget_category_id: category.id,
            amount: amounts[category.id] ?? '',
        })),
    });

    useEffect(() => {
        form.setData({
            year,
            month,
            budgets: budgetCategories.map((category) => ({
                budget_category_id: category.id,
                amount: amounts[category.id] ?? '',
            })),
        });
        // Resync the form whenever the selected month changes via navigation.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [year, month]);

    function setAmount(index: number, value: string) {
        const next = [...form.data.budgets];
        next[index] = { ...next[index], amount: value };
        form.setData('budgets', next);
    }

    function submit() {
        form.post(budgets.store.url(), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Budgets" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Monthly Budget"
                        description="Amounts are pre-filled from last month where available — edit or save as is."
                    />
                    <MonthSelector
                        year={year}
                        month={month}
                        buildHref={(y, m) =>
                            budgets.index.url({ query: { year: y, month: m } })
                        }
                    />
                </div>

                {budgetCategories.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            Create a budget category first before setting a
                            monthly budget.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="max-w-lg space-y-4">
                        {budgetCategories.map((category, index) => (
                            <div key={category.id} className="grid gap-2">
                                <Label htmlFor={`amount-${category.id}`}>
                                    {category.icon && (
                                        <span className="mr-1">
                                            {category.icon}
                                        </span>
                                    )}
                                    {category.name}
                                </Label>
                                <AmountInput
                                    id={`amount-${category.id}`}
                                    value={
                                        form.data.budgets[index]?.amount ?? ''
                                    }
                                    onChange={(value) =>
                                        setAmount(index, value)
                                    }
                                />
                                {suggestedIds.has(category.id) &&
                                    amounts[category.id] !== undefined &&
                                    form.data.budgets[index]?.amount ===
                                        amounts[category.id] && (
                                        <p className="text-muted-foreground text-xs">
                                            Carried over from last month — edit
                                            or save as is.
                                        </p>
                                    )}
                            </div>
                        ))}

                        <Button disabled={form.processing} onClick={submit}>
                            Save Budget
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

BudgetsIndex.layout = {
    breadcrumbs: [{ title: 'Budgets', href: budgets.index() }],
};
