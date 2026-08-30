import { Link } from '@inertiajs/react';
import {
    ArrowLeftRight,
    Banknote,
    HandCoins,
    ListTree,
    PiggyBank,
    Settings,
    Tags,
    Wallet,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { Badge } from '@/components/ui/badge';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import accounts from '@/routes/accounts';
import budgetCategories from '@/routes/budget-categories';
import expenseCategories from '@/routes/expense-categories';
import incomes from '@/routes/incomes';
import { edit as editProfile } from '@/routes/profile';
import transfers from '@/routes/transfers';
import type { InertiaLinkProps } from '@inertiajs/react';

const COMING_SOON: {
    title: string;
    icon: ComponentType<{ className?: string }>;
}[] = [
    { title: 'Savings', icon: PiggyBank },
    { title: 'Loans Given', icon: HandCoins },
    { title: 'Loans Taken', icon: HandCoins },
];

const MANAGE_ITEMS: {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: ComponentType<{ className?: string }>;
}[] = [
    { title: 'Income', href: incomes.index(), icon: Banknote },
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
                            className="hover:bg-accent hover:text-accent-foreground flex flex-col items-center gap-1.5 rounded-lg border p-3 text-center"
                        >
                            <item.icon className="size-5" />
                            <span className="text-xs leading-tight font-medium">
                                {item.title}
                            </span>
                        </Link>
                    ))}
                </div>

                <p className="text-muted-foreground mt-2 text-xs font-semibold tracking-wide uppercase">
                    Coming soon
                </p>
                <div className="grid grid-cols-4 gap-3">
                    {COMING_SOON.map((item) => (
                        <div
                            key={item.title}
                            className="text-muted-foreground flex flex-col items-center gap-1.5 rounded-lg border p-3 text-center opacity-60"
                        >
                            <item.icon className="size-5" />
                            <span className="text-xs leading-tight font-medium">
                                {item.title}
                            </span>
                            <Badge variant="secondary" className="text-[10px]">
                                Soon
                            </Badge>
                        </div>
                    ))}
                </div>
            </SheetContent>
        </Sheet>
    );
}
