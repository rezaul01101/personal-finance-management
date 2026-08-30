import { Wallet } from 'lucide-react';
import { useState } from 'react';
import { ChipSelect } from '@/components/finance/chip-select';
import { IconActionButton } from '@/components/finance/icon-action-button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { Account } from '@/types/finance';

export function AccountPicker({
    accounts,
    value,
    onChange,
}: {
    accounts: Account[];
    value: string;
    onChange: (value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const selected = accounts.find((account) => String(account.id) === value);

    return (
        <>
            <IconActionButton
                icon={Wallet}
                label={selected?.name ?? 'Account'}
                onClick={() => setOpen(true)}
            />

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="bottom" className="rounded-t-2xl px-4 pb-8">
                    <SheetHeader className="px-0">
                        <SheetTitle>Select Account</SheetTitle>
                    </SheetHeader>

                    <ChipSelect
                        options={accounts.map((account) => ({
                            id: account.id,
                            label: account.name,
                        }))}
                        value={value}
                        onChange={(next) => {
                            onChange(next);
                            setOpen(false);
                        }}
                        wrap
                    />
                </SheetContent>
            </Sheet>
        </>
    );
}
