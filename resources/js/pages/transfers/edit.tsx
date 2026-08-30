import { Head, useForm } from '@inertiajs/react';
import { ACCOUNT_TYPE_ICONS } from '@/components/finance/account-types';
import { AmountDisplay } from '@/components/finance/amount-display';
import { ChipSelect } from '@/components/finance/chip-select';
import { DateField } from '@/components/finance/date-field';
import { NoteButton } from '@/components/finance/note-button';
import { NumericKeypad } from '@/components/finance/numeric-keypad';
import { SlideToConfirm } from '@/components/finance/slide-to-confirm';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import transfers from '@/routes/transfers';
import type { Account, AccountTransfer } from '@/types/finance';

export default function TransfersEdit({
    transfer,
    accounts,
}: {
    transfer: AccountTransfer;
    accounts: Account[];
}) {
    const form = useForm<{
        amount: string;
        from_account_id: string;
        to_account_id: string;
        transferred_on: string;
        note: string;
    }>({
        amount: transfer.amount,
        from_account_id: String(transfer.from_account_id),
        to_account_id: String(transfer.to_account_id),
        transferred_on: transfer.transferred_on,
        note: transfer.note ?? '',
    });

    const fromAccount = accounts.find(
        (account) => String(account.id) === form.data.from_account_id,
    );
    const toAccount = accounts.find(
        (account) => String(account.id) === form.data.to_account_id,
    );

    const canSubmit =
        !!fromAccount &&
        !!toAccount &&
        fromAccount.id !== toAccount.id &&
        parseFloat(form.data.amount || '0') > 0;

    function submit() {
        form.put(transfers.update.url(transfer.id));
    }

    return (
        <>
            <Head title="Edit Transfer" />

            <div className="mx-auto flex min-h-[calc(100dvh-8rem)] max-w-lg flex-col justify-between gap-2 p-3 md:min-h-[calc(100dvh-4rem)]">
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

                <div className="grid gap-1">
                    <Label className="text-xs">From</Label>
                    <ChipSelect
                        options={accounts
                            .filter(
                                (account) =>
                                    String(account.id) !==
                                    form.data.to_account_id,
                            )
                            .map((account) => ({
                                id: account.id,
                                label: account.name,
                            }))}
                        value={form.data.from_account_id}
                        onChange={(value) =>
                            form.setData('from_account_id', value)
                        }
                    />
                    <InputError message={form.errors.from_account_id} />
                </div>

                <div className="grid gap-1">
                    <Label className="text-xs">To</Label>
                    <ChipSelect
                        options={accounts
                            .filter(
                                (account) =>
                                    String(account.id) !==
                                    form.data.from_account_id,
                            )
                            .map((account) => ({
                                id: account.id,
                                label: account.name,
                            }))}
                        value={form.data.to_account_id}
                        onChange={(value) =>
                            form.setData('to_account_id', value)
                        }
                    />
                    <InputError message={form.errors.to_account_id} />
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
                    <div className="flex justify-center">
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
                <InputError message={form.errors.note} />

                <SlideToConfirm
                    key={JSON.stringify(form.errors)}
                    fromIcon={ACCOUNT_TYPE_ICONS[fromAccount?.type ?? 'other']}
                    toIcon={ACCOUNT_TYPE_ICONS[toAccount?.type ?? 'other']}
                    label={
                        fromAccount && toAccount
                            ? `Slide to update transfer to ${toAccount.name}`
                            : 'Slide to update'
                    }
                    confirmingLabel="Saving…"
                    onConfirm={submit}
                    disabled={form.processing || !canSubmit}
                />
            </div>
        </>
    );
}

TransfersEdit.layout = (props: { transfer: AccountTransfer }) => ({
    breadcrumbs: [
        { title: 'Transfers', href: transfers.index() },
        { title: 'Edit Transfer', href: transfers.edit(props.transfer.id) },
    ],
});
