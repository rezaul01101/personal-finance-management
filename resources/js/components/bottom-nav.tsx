import { Link } from '@inertiajs/react';
import { HandCoins, LayoutGrid, Menu, Plus, Receipt } from 'lucide-react';
import { useState } from 'react';
import { MoreMenu } from '@/components/more-menu';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import expenses from '@/routes/expenses';
import loans from '@/routes/loans';
import type { NavItem } from '@/types';

const LEFT_ITEMS: NavItem[] = [
    { title: 'Home', href: dashboard(), icon: LayoutGrid },
    { title: 'Activity', href: expenses.index(), icon: Receipt },
];

const RIGHT_ITEMS: NavItem[] = [
    { title: 'Loans', href: loans.index(), icon: HandCoins },
];

/** Floating near-white tab bar with a raised red "+" (mirrors the mobile app's BottomTabBar). */
export function BottomNav() {
    const { isCurrentUrl } = useCurrentUrl();
    const [moreOpen, setMoreOpen] = useState(false);

    return (
        <>
            <nav
                className="fixed inset-x-3.5 bottom-[calc(env(safe-area-inset-bottom)+0.625rem)] z-40 mx-auto flex h-[70px] max-w-xl items-center rounded-[30px] px-1.5 shadow-[0_8px_24px_rgba(0,0,0,0.14)] md:hidden"
                style={{ backgroundColor: 'var(--bottom-bar)' }}
            >
                {LEFT_ITEMS.map((item) => (
                    <BottomNavLink
                        key={item.title}
                        item={item}
                        active={isCurrentUrl(item.href)}
                    />
                ))}

                <div className="flex flex-1 items-center justify-center">
                    <Link
                        href={expenses.create()}
                        className="-translate-y-4 grid size-[54px] place-items-center rounded-full border-[3px] border-white/75 shadow-[0_8px_10px_rgba(0,0,0,0.35)] active:opacity-90"
                        style={{
                            backgroundColor: 'var(--add-button)',
                            color: 'var(--add-button-foreground)',
                        }}
                    >
                        <Plus className="size-[26px]" strokeWidth={3} />
                        <span className="sr-only">Add Expense</span>
                    </Link>
                </div>

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
                    className={tabClasses(moreOpen)}
                    style={moreOpen ? undefined : { color: 'var(--bottom-bar-inactive)' }}
                >
                    <Menu className="size-[22px]" />
                    More
                </button>
            </nav>

            <MoreMenu open={moreOpen} onOpenChange={setMoreOpen} />
        </>
    );
}

function tabClasses(active: boolean) {
    return cn(
        'flex min-h-12 flex-1 flex-col items-center justify-center gap-[3px] text-[11px] leading-[15px]',
        active ? 'text-destructive font-bold' : 'font-semibold',
    );
}

function BottomNavLink({ item, active }: { item: NavItem; active: boolean }) {
    return (
        <Link
            href={item.href}
            className={tabClasses(active)}
            style={active ? undefined : { color: 'var(--bottom-bar-inactive)' }}
        >
            {item.icon && <item.icon className="size-[22px]" />}
            {item.title}
        </Link>
    );
}
