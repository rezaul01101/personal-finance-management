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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import loans from '@/routes/loans';
import type { Account, LoanType } from '@/types/finance';

const DIRECTION_OPTIONS = [
    { id: 'given', label: 'Given' },
    { id: 'taken', label: 'Taken' },
];

export default function LoansCreate({
    accounts,
    direction,
}: {
    accounts: Account[];
    direction: LoanType;
}) {
    const form = useForm<{
        type: LoanType;
        person_name: string;
        amount: string;
        account_id: string;
        loan_date: string;
        expected_return_date: string;
        note: string;
        photos: File[];
    }>({
        type: direction,
        person_name: '',
        amount: '',
        account_id: '',
        loan_date: new Date().toISOString().slice(0, 10),
        expected_return_date: '',
        note: '',
        photos: [],
    });

    function submit() {
        form.post(loans.store.url(), { forceFormData: true });
    }

    const isGiven = form.data.type === 'given';

    return (
        <>
            <Head title="Add Loan" />

            <div className="mx-auto flex min-h-[calc(100dvh-8rem)] max-w-lg flex-col justify-between gap-2 p-3 md:min-h-[calc(100dvh-4rem)]">
                <div className="grid gap-1">
                    <Label className="text-xs">Direction</Label>
                    <ChipSelect
                        options={DIRECTION_OPTIONS}
                        value={form.data.type}
                        onChange={(value) =>
                            form.setData('type', value as LoanType)
                        }
                    />
                    <InputError message={form.errors.type} />
                </div>

                <div className="grid gap-1">
                    <Label htmlFor="person_name" className="text-xs">
                        {isGiven ? 'Given to' : 'Borrowed from'}
                    </Label>
                    <Input
                        id="person_name"
                        value={form.data.person_name}
                        onChange={(e) =>
                            form.setData('person_name', e.target.value)
                        }
                        placeholder="e.g. Anamul"
                    />
                    <InputError message={form.errors.person_name} />
                </div>

                <AmountDisplay value={form.data.amount} />
                <InputError
                    message={form.errors.amount}
                    className="text-center"
                />

                <div>
                    <Label className="text-xs">
                        {isGiven ? 'Given on' : 'Borrowed on'}
                    </Label>
                    <DateField
                        value={form.data.loan_date}
                        onChange={(value) => form.setData('loan_date', value)}
                    />
                    <InputError
                        message={form.errors.loan_date}
                        className="text-center"
                    />
                </div>

                <div>
                    <Label className="text-xs">
                        Expected return date (optional)
                    </Label>
                    <DateField
                        value={form.data.expected_return_date}
                        onChange={(value) =>
                            form.setData('expected_return_date', value)
                        }
                    />
                    <InputError
                        message={form.errors.expected_return_date}
                        className="text-center"
                    />
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
                            files={form.data.photos}
                            onFilesChange={(files) =>
                                form.setData('photos', files)
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
                    Save Loan
                </Button>
            </div>
        </>
    );
}

LoansCreate.layout = {
    breadcrumbs: [
        { title: 'Loans', href: loans.index() },
        { title: 'Add Loan', href: loans.create() },
    ],
};
