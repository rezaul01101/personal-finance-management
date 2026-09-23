import { usePage } from '@inertiajs/react';
import { Bell, ChevronDown, Search } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

function todayRemainingDaysLabel(): string {
    const today = new Date();
    const daysInMonth = new Date(
        today.getFullYear(),
        today.getMonth() + 1,
        0,
    ).getDate();
    const remainingDays = daysInMonth - today.getDate() + 1;

    return `${remainingDays} ${remainingDays === 1 ? 'day' : 'days'} left`;
}

function todayDateLabel(): string {
    return new Date().toLocaleDateString('en-US', {
        weekday: 'short',
        day: 'numeric',
    });
}

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;
    const title = breadcrumbs.at(-1)?.title ?? 'Dashboard';

    return (
        <header className="border-sidebar-border/50 bg-card grid h-16 shrink-0 grid-cols-[auto_1fr_auto] items-center gap-4 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <span className="truncate font-semibold">{title}</span>
            </div>

            <div className="flex min-w-0 items-center justify-center gap-2 text-sm">
                <span className="font-semibold">{todayDateLabel()}</span>
                <span className="text-muted-foreground">·</span>
                <span className="text-dark-900 truncate text-sm font-semibold">
                    {todayRemainingDaysLabel()}
                    <span className="hidden sm:inline"> this month</span>
                </span>
            </div>

            <div className="flex items-center justify-end gap-2">
                <div className="relative hidden w-56 lg:block">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        type="search"
                        placeholder="Search expenses, categories…"
                        aria-label="Search"
                        className="bg-secondary border-transparent pl-9"
                    />
                </div>

                <button
                    type="button"
                    title="Notifications"
                    className="border-border hover:border-primary hover:text-primary hover:bg-accent relative hidden size-9 place-items-center rounded-md border transition-colors md:grid"
                >
                    <Bell className="size-4" />
                    <span className="border-card bg-primary absolute top-1.5 right-1.5 size-2 rounded-full border-2" />
                </button>

                {auth.user && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button
                                type="button"
                                className="hover:bg-accent ml-1 flex items-center gap-2 rounded-md md:border-l md:pl-3"
                            >
                                <UserInfo
                                    user={auth.user}
                                    nameClassName="hidden md:grid"
                                />
                                <ChevronDown className="text-muted-foreground hidden size-4 md:block" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>
        </header>
    );
}
