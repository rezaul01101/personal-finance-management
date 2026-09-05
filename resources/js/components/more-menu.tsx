import { Link } from '@inertiajs/react';
import {
    ArrowLeftRight,
    Banknote,
    HandCoins,
    ListTree,
    PiggyBank,
    Settings,
    Tags,
    Users,
    Wallet,
} from 'lucide-react';
import type { ComponentType } from 'react';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import accounts from '@/routes/accounts';
import budgetCategories from '@/routes/budget-categories';
import contacts from '@/routes/contacts';
import expenseCategories from '@/routes/expense-categories';
import incomes from '@/routes/incomes';
import loans from '@/routes/loans';
import { edit as editProfile } from '@/routes/profile';
import savingsGoals from '@/routes/savings-goals';
import transfers from '@/routes/transfers';
import type { InertiaLinkProps } from '@inertiajs/react';

const MANAGE_ITEMS: {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: ComponentType<{ className?: string }>;
}[] = [
    { title: 'Income', href: incomes.index(), icon: Banknote },
    { title: 'Savings', href: savingsGoals.index(), icon: PiggyBank },
    { title: 'Loans', href: loans.index(), icon: HandCoins },
    { title: 'People', href: contacts.index(), icon: Users },
    { title: 'Transfers', href: transfers.index(), icon: ArrowLeftRight },
    {
        title: 'Budget Categories',
        href: budgetCategories.index(),
        icon: ListTree,
    },
    {
        title: 'Expense Categories',
        href: expenseCategories.index(),
        icon: Tags,
    },
    { title: 'Accounts', href: accounts.index(), icon: Wallet },
    { title: 'Settings', href: editProfile(), icon: Settings },
];

export function MoreMenu({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="bottom" className="rounded-t-2xl px-4 pb-8">
                <SheetHeader className="px-0">
                    <SheetTitle>More</SheetTitle>
                </SheetHeader>

                <div className="grid grid-cols-4 gap-3">
                    {MANAGE_ITEMS.map((item) => (
                        <Link
                            key={item.title}
                            href={item.href}
                            onClick={() => onOpenChange(false)}
                            className="hover:bg-accent hover:text-accent-foreground flex flex-col items-center gap-1.5 rounded-lg border p-3 text-center"
                        >
                            <item.icon className="size-5" />
                            <span className="text-xs leading-tight font-medium">
                                {item.title}
                            </span>
                        </Link>
                    ))}
                </div>
            </SheetContent>
        </Sheet>
    );
}
