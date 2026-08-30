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

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;
    const title = breadcrumbs.at(-1)?.title ?? 'Dashboard';

    return (
        <header className="border-sidebar-border/50 bg-card flex h-16 shrink-0 items-center gap-4 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <span className="truncate font-semibold">{title}</span>
            </div>

            <div className="relative ml-2 hidden max-w-sm flex-1 md:block">
                <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                <Input
                    type="search"
                    placeholder="Search expenses, categories…"
                    aria-label="Search"
                    className="bg-secondary border-transparent pl-9"
                />
            </div>

            <div className="ml-auto flex items-center gap-2">
                <button
                    type="button"
                    title="Notifications"
                    className="border-border hover:border-primary hover:text-primary hover:bg-accent relative grid size-9 place-items-center rounded-md border transition-colors"
                >
                    <Bell className="size-4" />
                    <span className="border-card bg-primary absolute top-1.5 right-1.5 size-2 rounded-full border-2" />
                </button>

                {auth.user && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button
                                type="button"
                                className="hover:bg-accent ml-1 flex items-center gap-2 rounded-md border-l pl-3"
                            >
                                <UserInfo user={auth.user} />
                                <ChevronDown className="text-muted-foreground size-4" />
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
