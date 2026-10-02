import { Link, usePage } from '@inertiajs/react';
import { Bell, ChevronDown, ChevronLeft, Search } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { edit as editProfile } from '@/routes/profile';
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
    const parent = breadcrumbs.length > 1 ? breadcrumbs.at(-2) : undefined;

    return (
        <header className="glass mx-3 mt-2 flex h-[60px] shrink-0 items-center gap-2.5 rounded-[26px] px-2.5 md:mx-0 md:mt-0 md:grid md:h-16 md:grid-cols-[auto_1fr_auto] md:gap-4 md:rounded-none md:border-x-0 md:border-t-0 md:px-4">
            <div className="flex min-w-0 flex-1 items-center gap-2.5 md:flex-none md:gap-2">
                <SidebarTrigger className="-ml-1 hidden md:inline-flex" />
                {parent ? (
                    <Link
                        href={parent.href}
                        aria-label="Go back"
                        className="grid size-[38px] shrink-0 place-items-center rounded-full bg-white/70 md:hidden"
                    >
                        <ChevronLeft className="size-[22px]" />
                    </Link>
                ) : (
                    <span className="bg-primary grid size-[38px] shrink-0 place-items-center rounded-[13px] text-[22px] leading-none font-bold text-white md:hidden">
                        ৳
                    </span>
                )}
                <span className="truncate text-lg font-bold md:text-base md:font-semibold">
                    {title}
                </span>
            </div>

            <div className="hidden min-w-0 items-center justify-center gap-2 text-sm md:flex">
                <span className="font-semibold">{todayDateLabel()}</span>
                <span className="text-muted-foreground">·</span>
                <span className="truncate text-sm font-semibold">
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
                        className="border-transparent pl-9"
                    />
                </div>

                <button
                    type="button"
                    title="Notifications"
                    className="border-border hover:border-primary hover:text-primary hover:bg-accent relative hidden size-9 place-items-center rounded-full border transition-colors md:grid"
                >
                    <Bell className="size-4" />
                    <span className="border-card bg-primary absolute top-1.5 right-1.5 size-2 rounded-full border-2" />
                </button>

                {auth.user && (
                    <>
                        <Link
                            href={editProfile()}
                            aria-label="Open settings"
                            className="flex md:hidden"
                        >
                            <UserInfo
                                user={auth.user}
                                nameClassName="hidden"
                                avatarClassName="size-10"
                            />
                        </Link>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button
                                    type="button"
                                    className="hover:bg-accent ml-1 hidden items-center gap-2 rounded-md md:flex md:border-l md:pl-3"
                                >
                                    <UserInfo
                                        user={auth.user}
                                        nameClassName="hidden md:grid"
                                    />
                                    <ChevronDown className="text-muted-foreground size-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </>
                )}
            </div>
        </header>
    );
}
