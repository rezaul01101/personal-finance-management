import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    Landmark,
    PiggyBank,
    Receipt,
    ShieldCheck,
    Sparkles,
    Target,
    TrendingUp,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import InstallAppButton from '@/components/install-app-button';
import PwaInstallSection from '@/components/pwa-install-section';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';

const features = [
    {
        icon: Receipt,
        title: 'Expenses',
        description:
            'Log expenses in seconds and categorise every purchase.',
    },
    {
        icon: PiggyBank,
        title: 'Budgets',
        description:
            'Set monthly budgets by category and track your progress.',
    },
    {
        icon: TrendingUp,
        title: 'Incomes',
        description:
            'Record income from every source and see your full picture.',
    },
    {
        icon: Landmark,
        title: 'Accounts',
        description:
            'Manage multiple accounts, cash, bank, cards, in one place.',
    },
    {
        icon: Target,
        title: 'Savings goals',
        description: 'Set savings goals and watch your progress grow.',
    },
    {
        icon: ArrowLeftRight,
        title: 'Transfers',
        description:
            'Move money between accounts without breaking your reports.',
    },
] as const;

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome" />
            <div className="bg-background text-foreground flex min-h-screen flex-col">
                <header className="border-border/60 bg-background/80 sticky top-0 z-10 border-b backdrop-blur">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <Link
                            href="/"
                            className="flex items-center space-x-2"
                        >
                            <AppLogo />
                        </Link>
                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={register()}>
                                            Get started
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex-1">
                    <section className="mx-auto w-full max-w-4xl px-6 pt-20 pb-16 text-center">
                        <span className="bg-accent text-accent-foreground mx-auto inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium">
                            <Sparkles className="size-3.5" />
                            Personal Finance Management
                        </span>
                        <h1 className="mt-6 text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                            Know exactly where your money goes.
                        </h1>
                        <p className="text-muted-foreground mx-auto mt-4 max-w-xl text-lg text-balance">
                            Track expenses, plan budgets, and grow your
                            savings, all from one simple dashboard.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                            {auth.user ? (
                                <Button size="lg" asChild>
                                    <Link href={dashboard()}>
                                        Go to dashboard
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button size="lg" asChild>
                                        <Link href={register()}>
                                            Get started free
                                        </Link>
                                    </Button>
                                    <Button
                                        size="lg"
                                        variant="outline"
                                        asChild
                                    >
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                </>
                            )}
                            <InstallAppButton size="lg" variant="outline" />
                        </div>
                    </section>

                    <section className="mx-auto w-full max-w-6xl px-6 pb-16">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map(
                                ({ icon: Icon, title, description }) => (
                                    <Card key={title}>
                                        <CardContent className="flex flex-col gap-3">
                                            <div className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-lg">
                                                <Icon className="size-5" />
                                            </div>
                                            <h3 className="font-semibold">
                                                {title}
                                            </h3>
                                            <p className="text-muted-foreground text-sm">
                                                {description}
                                            </p>
                                        </CardContent>
                                    </Card>
                                ),
                            )}
                        </div>
                    </section>

                    <PwaInstallSection />
                </main>

                <footer className="border-border/60 border-t">
                    <div className="text-muted-foreground mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-2 px-6 py-8 text-sm sm:flex-row">
                        <span className="flex items-center gap-1.5">
                            <ShieldCheck className="size-4" />
                            Your data stays private and secure.
                        </span>
                        <span>
                            &copy; {new Date().getFullYear()} Personal Finance
                            Management. All rights reserved.
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
