import { Head, useForm } from '@inertiajs/react';
import { AccountPicker } from '@/components/finance/account-picker';
import { AmountDisplay } from '@/components/finance/amount-display';
import { ChipSelect } from '@/components/finance/chip-select';
import { DateField } from '@/components/finance/date-field';
import { INCOME_SOURCE_OPTIONS } from '@/components/finance/income-sources';
import { NoteButton } from '@/components/finance/note-button';
import { NumericKeypad } from '@/components/finance/numeric-keypad';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import incomes from '@/routes/incomes';
import type { Account, IncomeSource } from '@/types/finance';

export default function IncomesCreate({ accounts }: { accounts: Account[] }) {
    const form = useForm<{
        amount: string;
        source: IncomeSource | '';
        account_id: string;
        received_on: string;
        note: string;
    }>({
        amount: '',
        source: '',
        account_id: '',
        received_on: new Date().toISOString().slice(0, 10),
        note: '',
    });

    function submit() {
        form.post(incomes.store.url());
    }

    return (
        <>
            <Head title="Add Income" />

            <div className="mx-auto flex min-h-[calc(100dvh-8rem)] max-w-lg flex-col justify-between gap-2 p-3 md:min-h-[calc(100dvh-4rem)]">
                <div>
                    <DateField
                        value={form.data.received_on}
                        onChange={(value) => form.setData('received_on', value)}
                    />
                    <InputError
                        message={form.errors.received_on}
                        className="text-center"
                    />
                </div>

                <div className="grid gap-1">
                    <Label className="text-xs">Source</Label>
                    <ChipSelect
                        options={INCOME_SOURCE_OPTIONS}
                        value={form.data.source}
                        onChange={(value) =>
                            form.setData('source', value as IncomeSource)
                        }
                    />
                    <InputError message={form.errors.source} />
                </div>

                <AmountDisplay value={form.data.amount} />
                <InputError
                    message={form.errors.amount}
                    className="text-center"
                />

                {form.data.note && (
                    <p className="text-muted-foreground text-center text-xs">
                        {form.data.note}
                    </p>
                )}

                {/* 2 quick-action buttons merged with the keypad into one card */}
                <div className="space-y-2 rounded-2xl border p-2">
                    <div className="grid grid-cols-2 gap-2">
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
                    Save Income
                </Button>
            </div>
        </>
    );
}

IncomesCreate.layout = {
    breadcrumbs: [
        { title: 'Income', href: incomes.index() },
        { title: 'Add Income', href: incomes.create() },
    ],
};
