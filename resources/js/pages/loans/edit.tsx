import { Head, router, useForm } from '@inertiajs/react';
import { AccountPicker } from '@/components/finance/account-picker';
import { AmountDisplay } from '@/components/finance/amount-display';
import { ContactPicker } from '@/components/finance/contact-picker';
import { DateField } from '@/components/finance/date-field';
import { NoteButton } from '@/components/finance/note-button';
import { NumericKeypad } from '@/components/finance/numeric-keypad';
import { ReceiptPicker } from '@/components/finance/receipt-picker';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import loanAttachments from '@/routes/loans/attachments';
import loans from '@/routes/loans';
import type { Account, Contact, Loan } from '@/types/finance';

export default function LoansEdit({
    loan,
    accounts,
    contacts,
}: {
    loan: Loan;
    accounts: Account[];
    contacts: Contact[];
}) {
    const form = useForm<{
        contact_id: string;
        amount: string;
        account_id: string;
        loan_date: string;
        expected_return_date: string;
        note: string;
        photos: File[];
    }>({
        contact_id: String(loan.contact_id),
        amount: loan.amount,
        account_id: String(loan.account_id),
        loan_date: loan.loan_date,
        expected_return_date: loan.expected_return_date ?? '',
        note: loan.note ?? '',
        photos: [],
    });

    const isGiven = loan.type === 'given';

    function submit() {
        form.put(loans.update.url(loan.id), { forceFormData: true });
    }

    function removeAttachment(attachmentId: number) {
        router.delete(
            loanAttachments.destroy.url({
                loan: loan.id,
                attachment: attachmentId,
            }),
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Edit Loan" />

            <div className="mx-auto flex min-h-[calc(100dvh-8rem)] max-w-lg flex-col justify-between gap-2 p-3 md:min-h-[calc(100dvh-4rem)]">
                <div className="flex justify-center">
                    <Badge variant="secondary">
                        {isGiven ? 'Loan Given' : 'Loan Taken'}
                    </Badge>
                </div>

                <div className="grid gap-1">
                    <Label className="text-xs">
                        {isGiven ? 'Given to' : 'Borrowed from'}
                    </Label>
                    <ContactPicker
                        contacts={contacts}
                        value={form.data.contact_id}
                        onChange={(value) =>
                            form.setData('contact_id', value)
                        }
                    />
                    <InputError message={form.errors.contact_id} />
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
                            existingAttachments={loan.attachments ?? []}
                            onRemoveExisting={removeAttachment}
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
                    Save Changes
                </Button>
            </div>
        </>
    );
}

LoansEdit.layout = (props: { loan: Loan }) => ({
    breadcrumbs: [
        { title: 'Loans', href: loans.index() },
        { title: 'Edit Loan', href: loans.edit(props.loan.id) },
    ],
});
