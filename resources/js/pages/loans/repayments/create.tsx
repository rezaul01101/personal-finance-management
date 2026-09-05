import { Head, useForm } from '@inertiajs/react';
import { AccountPicker } from '@/components/finance/account-picker';
import { AmountDisplay } from '@/components/finance/amount-display';
import { DateField } from '@/components/finance/date-field';
import { NoteButton } from '@/components/finance/note-button';
import { NumericKeypad } from '@/components/finance/numeric-keypad';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import loans from '@/routes/loans';
import type { Account, Loan } from '@/types/finance';

export default function LoanRepaymentsCreate({
    loan,
    accounts,
}: {
    loan: Loan;
    accounts?: Account[];
}) {
    const isGiven = loan.type === 'given';

    const form = useForm<{
        amount: string;
        account_id: string;
        repaid_on: string;
        note: string;
    }>({
        amount: '',
        account_id: '',
        repaid_on: new Date().toISOString().slice(0, 10),
        note: '',
    });

    function submit() {
        form.post(loans.repayments.store.url(loan.id));
    }

    return (
        <>
            <Head title="Record Repayment" />

            <div className="mx-auto flex min-h-[calc(100dvh-8rem)] max-w-lg flex-col justify-between gap-2 p-3 md:min-h-[calc(100dvh-4rem)]">
                <Heading
                    title={
                        isGiven
                            ? `Repayment from ${loan.person_name}`
                            : `Repay ${loan.person_name}`
                    }
                    description={
                        isGiven
                            ? 'This is held until you transfer it to an account.'
                            : 'This is paid immediately from the account you choose.'
                    }
                />

                <AmountDisplay value={form.data.amount} />
                <InputError
                    message={form.errors.amount}
                    className="text-center"
                />

                <div>
                    <DateField
                        value={form.data.repaid_on}
                        onChange={(value) => form.setData('repaid_on', value)}
                    />
                    <InputError
                        message={form.errors.repaid_on}
                        className="text-center"
                    />
                </div>

                {form.data.note && (
                    <p className="text-muted-foreground text-center text-xs">
                        {form.data.note}
                    </p>
                )}

                <div className="space-y-2 rounded-2xl border p-2">
                    <div
                        className={cn(
                            'grid gap-2',
                            !isGiven && accounts
                                ? 'grid-cols-2'
                                : 'grid-cols-1',
                        )}
                    >
                        {!isGiven && accounts && (
                            <AccountPicker
                                accounts={accounts}
                                value={form.data.account_id}
                                onChange={(value) =>
                                    form.setData('account_id', value)
                                }
                            />
                        )}
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
                    Save Repayment
                </Button>
            </div>
        </>
    );
}

LoanRepaymentsCreate.layout = (props: { loan: Loan }) => ({
    breadcrumbs: [
        { title: 'Loans', href: loans.index() },
        { title: props.loan.person_name, href: loans.show(props.loan.id) },
        {
            title: 'Record Repayment',
            href: loans.repayments.create(props.loan.id),
        },
    ],
});
