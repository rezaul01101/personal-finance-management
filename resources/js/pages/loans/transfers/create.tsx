import { Head, useForm } from '@inertiajs/react';
import { AccountPicker } from '@/components/finance/account-picker';
import { AmountDisplay } from '@/components/finance/amount-display';
import { DateField } from '@/components/finance/date-field';
import { NoteButton } from '@/components/finance/note-button';
import { NumericKeypad } from '@/components/finance/numeric-keypad';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import loans from '@/routes/loans';
import loanTransfers from '@/routes/loans/transfers';
import type { Account, Loan } from '@/types/finance';

export default function LoanTransfersCreate({
    loan,
    accounts,
    heldBalance,
}: {
    loan: Loan;
    accounts: Account[];
    heldBalance: string;
}) {
    const form = useForm<{
        amount: string;
        account_id: string;
        transferred_on: string;
        note: string;
    }>({
        amount: '',
        account_id: '',
        transferred_on: new Date().toISOString().slice(0, 10),
        note: '',
    });

    function submit() {
        form.post(loanTransfers.store.url(loan.id));
    }

    return (
        <>
            <Head title="Transfer to Account" />

            <div className="mx-auto flex min-h-[calc(100dvh-8rem)] max-w-lg flex-col justify-between gap-2 p-3 md:min-h-[calc(100dvh-4rem)]">
                <Heading
                    title={`Transfer from ${loan.contact.name}`}
                    description={`Available to transfer: ৳${heldBalance}`}
                />

                <AmountDisplay value={form.data.amount} />
                <InputError
                    message={form.errors.amount}
                    className="text-center"
                />

                <div>
                    <DateField
                        value={form.data.transferred_on}
                        onChange={(value) =>
                            form.setData('transferred_on', value)
                        }
                    />
                    <InputError
                        message={form.errors.transferred_on}
                        className="text-center"
                    />
                </div>

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
                    Save Transfer
                </Button>
            </div>
        </>
    );
}

LoanTransfersCreate.layout = (props: { loan: Loan }) => ({
    breadcrumbs: [
        { title: 'Loans', href: loans.index() },
        { title: props.loan.contact.name, href: loans.show(props.loan.id) },
        {
            title: 'Transfer to Account',
            href: loanTransfers.create(props.loan.id),
        },
    ],
});
