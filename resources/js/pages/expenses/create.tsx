import { Head, useForm } from '@inertiajs/react';
import { AccountPicker } from '@/components/finance/account-picker';
import { AmountDisplay } from '@/components/finance/amount-display';
import { ChipSelect } from '@/components/finance/chip-select';
import { DateField } from '@/components/finance/date-field';
import { NoteButton } from '@/components/finance/note-button';
import { NumericKeypad } from '@/components/finance/numeric-keypad';
import { ReceiptPicker } from '@/components/finance/receipt-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import expenses from '@/routes/expenses';
import type { Account, BudgetCategory, ExpenseCategory } from '@/types/finance';

export default function ExpensesCreate({
    expenseCategories,
    budgetCategories,
    accounts,
}: {
    expenseCategories: ExpenseCategory[];
    budgetCategories: BudgetCategory[];
    accounts: Account[];
}) {
    const form = useForm<{
        amount: string;
        expense_category_id: string;
        budget_category_id: string;
        account_id: string;
        spent_on: string;
        note: string;
        receipts: File[];
    }>({
        amount: '',
        expense_category_id: '',
        budget_category_id: '',
        account_id: '',
        spent_on: new Date().toISOString().slice(0, 10),
        note: '',
        receipts: [],
    });

    function submit() {
        form.post(expenses.store.url(), { forceFormData: true });
    }

    return (
        <>
            <Head title="Add Expense" />

            <div className="mx-auto flex min-h-[calc(100dvh-8rem)] max-w-lg flex-col justify-between gap-2 p-3 md:min-h-[calc(100dvh-4rem)]">
                <div>
                    <DateField
                        value={form.data.spent_on}
                        onChange={(value) => form.setData('spent_on', value)}
                    />
                    <InputError
                        message={form.errors.spent_on}
                        className="text-center"
                    />
                </div>

                <div className="grid gap-1">
                    <Label className="text-xs">Category</Label>
                    <ChipSelect
                        options={expenseCategories.map((category) => ({
                            id: category.id,
                            label: category.name,
                            icon: category.icon,
                        }))}
                        value={form.data.expense_category_id}
                        onChange={(value) =>
                            form.setData('expense_category_id', value)
                        }
                    />
                    <InputError message={form.errors.expense_category_id} />
                </div>

                <AmountDisplay value={form.data.amount} />
                <InputError
                    message={form.errors.amount}
                    className="text-center"
                />

                <div className="grid gap-1">
                    <Label className="text-xs">Budget</Label>
                    <ChipSelect
                        options={budgetCategories.map((category) => ({
                            id: category.id,
                            label: category.name,
                            icon: category.icon,
                        }))}
                        value={form.data.budget_category_id}
                        onChange={(value) =>
                            form.setData('budget_category_id', value)
                        }
                        wrap
                    />
                    <InputError message={form.errors.budget_category_id} />
                </div>

                {form.data.note && (
                    <p className="text-muted-foreground text-center text-xs">
                        {form.data.note}
                    </p>
                )}

                {/* 3 quick-action buttons merged with the keypad into one card */}
                <div className="space-y-2 rounded-2xl border p-2">
                    <div className="grid grid-cols-3 gap-2">
                        <ReceiptPicker
                            files={form.data.receipts}
                            onFilesChange={(files) =>
                                form.setData('receipts', files)
                            }
                        />
                        <AccountPicker
                            accounts={accounts}
                            value={form.data.account_id}
                            onChange={(value) =>
                                form.setData('account_id', value)
                            }
                        />
                        <NoteButton
                            value={form.data.note}
                            onChange={(value) => form.setData('note', value)}
                        />
                    </div>

                    <NumericKeypad
                        value={form.data.amount}
                        onChange={(value) => form.setData('amount', value)}
                    />
                </div>
                <InputError message={form.errors.account_id} />
                <InputError message={form.errors.note} />

                <Button
                    disabled={form.processing}
                    onClick={submit}
                    className="w-full"
                >
                    Save Expense
                </Button>
            </div>
        </>
    );
}

ExpensesCreate.layout = {
    breadcrumbs: [
        { title: 'Expenses', href: expenses.index() },
        { title: 'Add Expense', href: expenses.create() },
    ],
};
