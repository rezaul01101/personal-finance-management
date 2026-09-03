import { Head, useForm } from '@inertiajs/react';
import { AccountPicker } from '@/components/finance/account-picker';
import { AmountDisplay } from '@/components/finance/amount-display';
import { ChipSelect } from '@/components/finance/chip-select';
import { DateField } from '@/components/finance/date-field';
import { NoteButton } from '@/components/finance/note-button';
import { NumericKeypad } from '@/components/finance/numeric-keypad';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import savingsGoals from '@/routes/savings-goals';
import type {
    Account,
    SavingsGoal,
    SavingsTransactionType,
} from '@/types/finance';

const TYPE_OPTIONS = [
    { id: 'contribution', label: 'Contribute' },
    { id: 'withdrawal', label: 'Withdraw' },
];

export default function SavingsTransactionsCreate({
    savingsGoal,
    accounts,
    type,
}: {
    savingsGoal: SavingsGoal;
    accounts: Account[];
    type: SavingsTransactionType;
}) {
    const form = useForm<{
        type: SavingsTransactionType;
        amount: string;
        account_id: string;
        transacted_on: string;
        note: string;
    }>({
        type,
        amount: '',
        account_id: '',
        transacted_on: new Date().toISOString().slice(0, 10),
        note: '',
    });

    function submit() {
        form.post(savingsGoals.transactions.store.url(savingsGoal.id));
    }

    return (
        <>
            <Head
                title={
                    form.data.type === 'contribution'
                        ? 'Contribute'
                        : 'Withdraw'
                }
            />

            <div className="mx-auto flex min-h-[calc(100dvh-8rem)] max-w-lg flex-col justify-between gap-2 p-3 md:min-h-[calc(100dvh-4rem)]">
                <div>
                    <DateField
                        value={form.data.transacted_on}
                        onChange={(value) =>
                            form.setData('transacted_on', value)
                        }
                    />
                    <InputError
                        message={form.errors.transacted_on}
                        className="text-center"
                    />
                </div>

                <div className="grid gap-1">
                    <Label className="text-xs">{savingsGoal.name}</Label>
                    <ChipSelect
                        options={TYPE_OPTIONS}
                        value={form.data.type}
                        onChange={(value) =>
                            form.setData(
                                'type',
                                value as SavingsTransactionType,
                            )
                        }
                    />
                    <InputError message={form.errors.type} />
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
                    {form.data.type === 'contribution'
                        ? 'Save Contribution'
                        : 'Record Withdrawal'}
                </Button>
            </div>
        </>
    );
}

SavingsTransactionsCreate.layout = (props: { savingsGoal: SavingsGoal }) => ({
    breadcrumbs: [
        { title: 'Savings', href: savingsGoals.index() },
        {
            title: props.savingsGoal.name,
            href: savingsGoals.show(props.savingsGoal.id),
        },
    ],
});
