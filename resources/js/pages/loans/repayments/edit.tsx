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
import repayments from '@/routes/repayments';
import type { Account, Loan, LoanRepayment } from '@/types/finance';

export default function LoanRepaymentsEdit({
    loan,
    repayment,
    accounts,
}: {
    loan: Loan;
    repayment: LoanRepayment;
    accounts?: Account[];
}) {
    const isGiven = loan.type === 'given';

    const form = useForm<{
        amount: string;
        account_id: string;
        repaid_on: string;
        note: string;
    }>({
        amount: repayment.amount,
        account_id: repayment.account_id ? String(repayment.account_id) : '',
        repaid_on: repayment.repaid_on,
        note: repayment.note ?? '',
    });

    function submit() {
        form.put(repayments.update.url(repayment.id));
    }

    return (
        <>
            <Head title="Edit Repayment" />

            <div className="mx-auto flex min-h-[calc(100dvh-8rem)] max-w-lg flex-col justify-between gap-2 p-3 md:min-h-[calc(100dvh-4rem)]">
                <Heading
                    title={
                        isGiven
                            ? `Repayment from ${loan.contact.name}`
                            : `Repay ${loan.contact.name}`
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
                    Save Changes
                </Button>
            </div>
        </>
    );
}

LoanRepaymentsEdit.layout = (props: {
    loan: Loan;
    repayment: LoanRepayment;
}) => ({
    breadcrumbs: [
        { title: 'Loans', href: loans.index() },
        { title: props.loan.contact.name, href: loans.show(props.loan.id) },
        {
            title: 'Edit Repayment',
            href: repayments.edit(props.repayment.id),
        },
    ],
});
