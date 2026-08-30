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
import type { Account } from '@/types/finance';

export default function TransfersCreate({ accounts }: { accounts: Account[] }) {
    const form = useForm<{
        amount: string;
        from_account_id: string;
        to_account_id: string;
        transferred_on: string;
        note: string;
    }>({
        amount: '',
        from_account_id: '',
        to_account_id: '',
        transferred_on: new Date().toISOString().slice(0, 10),
        note: '',
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
        form.post(transfers.store.url());
    }

    return (
        <>
            <Head title="Transfer Money" />

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
                            ? `Slide to send to ${toAccount.name}`
                            : 'Slide to transfer'
                    }
                    confirmingLabel="Transferring…"
                    onConfirm={submit}
                    disabled={form.processing || !canSubmit}
                />
            </div>
        </>
    );
}

TransfersCreate.layout = {
    breadcrumbs: [
        { title: 'Transfers', href: transfers.index() },
        { title: 'Transfer Money', href: transfers.create() },
    ],
};
