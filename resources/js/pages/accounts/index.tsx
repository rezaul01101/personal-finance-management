import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import accounts from '@/routes/accounts';
import type { Account, AccountType } from '@/types/finance';

const ACCOUNT_TYPE_LABELS: Record<AccountType, string> = {
    cash: 'Cash',
    bank: 'Bank',
    mobile_wallet: 'Mobile Wallet',
    card: 'Card',
    other: 'Other',
};

export default function AccountsIndex({
    accounts: items,
}: {
    accounts: Account[];
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Account | null>(null);

    const form = useForm<{
        name: string;
        type: AccountType;
        status: 'active' | 'archived';
    }>({
        name: '',
        type: 'cash',
        status: 'active',
    });

    function openCreate() {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(account: Account) {
        setEditing(account);
        form.setData({
            name: account.name,
            type: account.type,
            status: account.status,
        });
        form.clearErrors();
        setOpen(true);
    }

    function submit() {
        if (editing) {
            form.put(accounts.update.url(editing.id), {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            });
        } else {
            form.post(accounts.store.url(), {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            });
        }
    }

    function destroy(account: Account) {
        if (confirm(`Delete account "${account.name}"?`)) {
            router.delete(accounts.destroy.url(account.id), {
                preserveScroll: true,
            });
        }
    }

    return (
        <>
            <Head title="Accounts" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Accounts"
                        description="Where your money physically exists."
                    />

                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate}>Add Account</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {editing ? 'Edit Account' : 'Add Account'}
                                </DialogTitle>
                            </DialogHeader>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData('name', e.target.value)
                                        }
                                        placeholder="e.g. bKash"
                                    />
                                    <InputError message={form.errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="type">Type</Label>
                                    <Select
                                        value={form.data.type}
                                        onValueChange={(value) =>
                                            form.setData(
                                                'type',
                                                value as AccountType,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="type"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(
                                                Object.entries(
                                                    ACCOUNT_TYPE_LABELS,
                                                ) as [AccountType, string][]
                                            ).map(([value, label]) => (
                                                <SelectItem
                                                    key={value}
                                                    value={value}
                                                >
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.type} />
                                </div>

                                {editing && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="status">Status</Label>
                                        <Select
                                            value={form.data.status}
                                            onValueChange={(value) =>
                                                form.setData(
                                                    'status',
                                                    value as
                                                        | 'active'
                                                        | 'archived',
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                id="status"
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">
                                                    Active
                                                </SelectItem>
                                                <SelectItem value="archived">
                                                    Archived
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={form.errors.status}
                                        />
                                    </div>
                                )}
                            </div>

                            <DialogFooter>
                                <Button
                                    disabled={form.processing}
                                    onClick={submit}
                                >
                                    Save
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>

                {items.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            No accounts yet. Add your cash, bank, or mobile
                            wallet accounts to start tracking balances.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2">
                        {items.map((account) => (
                            <Card key={account.id}>
                                <CardHeader className="flex-row items-start justify-between">
                                    <div>
                                        <CardTitle>{account.name}</CardTitle>
                                        <p className="text-muted-foreground mt-1 text-sm">
                                            {ACCOUNT_TYPE_LABELS[account.type]}
                                            {account.status === 'archived' &&
                                                ' · Archived'}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => destroy(account)}
                                    >
                                        <Trash2 />
                                    </Button>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-semibold">
                                        ৳{account.balance}
                                    </p>
                                    <Button
                                        variant="link"
                                        className="h-auto p-0"
                                        onClick={() => openEdit(account)}
                                    >
                                        Edit
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

AccountsIndex.layout = {
    breadcrumbs: [{ title: 'Accounts', href: accounts.index() }],
};
