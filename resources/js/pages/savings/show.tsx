import { Head, Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { ProgressBar } from '@/components/finance/progress-bar';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import savingsGoals from '@/routes/savings-goals';
import transactions from '@/routes/transactions';
import type {
    SavingsGoal,
    SavingsSummary,
    SavingsTransaction,
} from '@/types/finance';

interface TransactionGroup {
    date: string;
    label: string;
    transactions: SavingsTransaction[];
}

export default function SavingsShow({
    savingsGoal,
    summary,
    transactionGroups,
}: {
    savingsGoal: SavingsGoal;
    summary: SavingsSummary;
    transactionGroups: TransactionGroup[];
}) {
    function destroy(transaction: SavingsTransaction) {
        if (confirm('Delete this savings entry?')) {
            router.delete(transactions.destroy.url(transaction.id), {
                preserveScroll: true,
            });
        }
    }

    return (
        <>
            <Head title={savingsGoal.name} />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={savingsGoal.name}
                        description={savingsGoal.description ?? undefined}
                    />
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link
                                href={savingsGoals.transactions.create(
                                    savingsGoal.id,
                                    { query: { type: 'withdrawal' } },
                                )}
                            >
                                Withdraw
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={savingsGoals.transactions.create(
                                    savingsGoal.id,
                                    { query: { type: 'contribution' } },
                                )}
                            >
                                Contribute
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardContent className="space-y-3">
                        <div>
                            <p className="text-3xl font-bold">
                                ৳{summary.saved_amount}{' '}
                                <span className="text-muted-foreground text-base font-normal">
                                    saved
                                </span>
                            </p>
                            <p className="text-muted-foreground text-sm">
                                of ৳{summary.target_amount}
                            </p>
                        </div>

                        <ProgressBar
                            percentage={summary.usage_percentage}
                            health="healthy"
                        />

                        <div className="flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">
                                Remaining{' '}
                                <b className="text-foreground">
                                    ৳{summary.remaining_amount}
                                </b>
                            </span>
                            {savingsGoal.target_date && (
                                <span className="text-muted-foreground">
                                    Target{' '}
                                    <b className="text-foreground">
                                        {savingsGoal.target_date}
                                    </b>
                                </span>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>History</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {transactionGroups.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No contributions or withdrawals yet.
                            </p>
                        ) : (
                            transactionGroups.map((group) => (
                                <div key={group.date}>
                                    <p className="text-muted-foreground mb-2 text-xs font-semibold tracking-wide uppercase">
                                        {group.label}
                                    </p>
                                    <div className="divide-y rounded-lg border">
                                        {group.transactions.map(
                                            (transaction) => (
                                                <div
                                                    key={transaction.id}
                                                    className="flex items-center justify-between gap-4 p-3"
                                                >
                                                    <Link
                                                        href={transactions.edit(
                                                            transaction.id,
                                                        )}
                                                        className="min-w-0 flex-1"
                                                    >
                                                        <p className="font-medium">
                                                            {transaction.type ===
                                                            'contribution'
                                                                ? 'Contribution'
                                                                : 'Withdrawal'}
                                                        </p>
                                                        <p className="text-muted-foreground text-sm">
                                                            {
                                                                transaction
                                                                    .account
                                                                    ?.name
                                                            }
                                                            {transaction.note &&
                                                                ` · ${transaction.note}`}
                                                        </p>
                                                    </Link>
                                                    <div className="flex items-center gap-2">
                                                        <p
                                                            className={
                                                                transaction.type ===
                                                                'contribution'
                                                                    ? 'font-semibold text-green-600 dark:text-green-500'
                                                                    : 'font-semibold'
                                                            }
                                                        >
                                                            {transaction.type ===
                                                            'contribution'
                                                                ? '+'
                                                                : '-'}
                                                            ৳
                                                            {transaction.amount}
                                                        </p>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                destroy(
                                                                    transaction,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 />
                                                        </Button>
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

SavingsShow.layout = (props: { savingsGoal: SavingsGoal }) => ({
    breadcrumbs: [
        { title: 'Savings', href: savingsGoals.index() },
        {
            title: props.savingsGoal.name,
            href: savingsGoals.show(props.savingsGoal.id),
        },
    ],
});
