import { Link } from '@inertiajs/react';
import { LayoutGrid, Menu, Plus, Receipt, WalletCards } from 'lucide-react';
import { useState } from 'react';
import { MoreMenu } from '@/components/more-menu';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import budgets from '@/routes/budgets';
import expenses from '@/routes/expenses';
import type { NavItem } from '@/types';

const LEFT_ITEMS: NavItem[] = [
    { title: 'Home', href: dashboard(), icon: LayoutGrid },
    { title: 'Transactions', href: expenses.index(), icon: Receipt },
];

const RIGHT_ITEMS: NavItem[] = [
    { title: 'Budgets', href: budgets.index(), icon: WalletCards },
];

export function BottomNav() {
    const { isCurrentUrl } = useCurrentUrl();
    const [moreOpen, setMoreOpen] = useState(false);

    return (
        <>
            <nav className="bg-card fixed inset-x-0 bottom-0 z-40 flex h-16 items-center justify-around border-t px-2 md:hidden">
                {LEFT_ITEMS.map((item) => (
                    <BottomNavLink
                        key={item.title}
                        item={item}
                        active={isCurrentUrl(item.href)}
                    />
                ))}

                <Link
                    href={expenses.create()}
                    className="bg-primary text-primary-foreground shadow-primary/30 -mt-7 grid size-14 place-items-center rounded-full shadow-lg"
                >
                    <Plus className="size-6" />
                    <span className="sr-only">Add Expense</span>
                </Link>

                {RIGHT_ITEMS.map((item) => (
                    <BottomNavLink
                        key={item.title}
                        item={item}
                        active={isCurrentUrl(item.href)}
                    />
                ))}

                <button
                    type="button"
                    onClick={() => setMoreOpen(true)}
                    className="text-muted-foreground flex flex-col items-center gap-1 text-xs"
                >
                    <Menu className="size-5" />
                    More
                </button>
            </nav>

            <MoreMenu open={moreOpen} onOpenChange={setMoreOpen} />
        </>
    );
}

function BottomNavLink({ item, active }: { item: NavItem; active: boolean }) {
    return (
        <Link
            href={item.href}
            className={cn(
                'flex flex-col items-center gap-1 text-xs font-medium',
                active ? 'text-primary' : 'text-muted-foreground',
            )}
        >
            {item.icon && <item.icon className="size-5" />}
            {item.title}
        </Link>
    );
}
