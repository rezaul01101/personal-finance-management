import { Link } from '@inertiajs/react';
import {
    ArrowLeftRight,
    Banknote,
    HandCoins,
    LayoutGrid,
    ListTree,
    PiggyBank,
    Receipt,
    Tags,
    Wallet,
    WalletCards,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import accounts from '@/routes/accounts';
import budgetCategories from '@/routes/budget-categories';
import budgets from '@/routes/budgets';
import expenseCategories from '@/routes/expense-categories';
import expenses from '@/routes/expenses';
import incomes from '@/routes/incomes';
import loans from '@/routes/loans';
import savingsGoals from '@/routes/savings-goals';
import transfers from '@/routes/transfers';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const budgetingNavItems: NavItem[] = [
    {
        title: 'Expenses',
        href: expenses.index(),
        icon: Receipt,
    },
    {
        title: 'Income',
        href: incomes.index(),
        icon: Banknote,
    },
    {
        title: 'Budgets',
        href: budgets.index(),
        icon: WalletCards,
    },
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
];

const accountsNavItems: NavItem[] = [
    {
        title: 'Accounts',
        href: accounts.index(),
        icon: Wallet,
    },
    {
        title: 'Transfers',
        href: transfers.index(),
        icon: ArrowLeftRight,
    },
];

const savingsNavItems: NavItem[] = [
    {
        title: 'Savings',
        href: savingsGoals.index(),
        icon: PiggyBank,
    },
];

const loansNavItems: NavItem[] = [
    {
        title: 'Loans',
        href: loans.index(),
        icon: HandCoins,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} label="Main" />
                <NavMain items={budgetingNavItems} label="Budgeting" />
                <NavMain items={accountsNavItems} label="Accounts" />
                <NavMain items={savingsNavItems} label="Savings" />
                <NavMain items={loansNavItems} label="Loans" />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
