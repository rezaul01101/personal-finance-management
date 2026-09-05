import { Head, Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import loans from '@/routes/loans';
import loanTransfers from '@/routes/loans/transfers';
import repayments from '@/routes/repayments';
import type { Account, Loan, LoanProgress } from '@/types/finance';

interface HistoryItem {
    kind: 'repayment' | 'transfer';
    id: number;
    amount: string;
    date: string;
    note: string | null;
    account: Account | null;
}

interface HistoryGroup {
    date: string;
    label: string;
    items: HistoryItem[];
}

export default function LoansShow({
    loan,
    progress,
    historyGroups,
}: {
    loan: Loan;
    progress: LoanProgress;
    historyGroups: HistoryGroup[];
}) {
    const isGiven = loan.type === 'given';

    function destroyItem(item: HistoryItem) {
        const label = item.kind === 'repayment' ? 'repayment' : 'transfer';

        if (!confirm(`Delete this ${label}?`)) {
            return;
        }

        const url =
            item.kind === 'repayment'
                ? repayments.destroy.url(item.id)
                : loanTransfers.destroy.url({
                      loan: loan.id,
                      transfer: item.id,
                  });

        router.delete(url, { preserveScroll: true });
    }

    function editHref(item: HistoryItem) {
        return item.kind === 'repayment'
            ? repayments.edit(item.id)
            : loanTransfers.edit({ loan: loan.id, transfer: item.id });
    }

    return (
        <>
            <Head title={loan.contact.name} />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={loan.contact.name}
                        description={isGiven ? 'Loan given' : 'Loan taken'}
                    />
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href={loans.repayments.create(loan.id)}>
                                Record Repayment
                            </Link>
                        </Button>
                        {isGiven && (
                            <Button asChild>
                                <Link href={loans.transfers.create(loan.id)}>
                                    Transfer to Account
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <Card>
                    <CardContent className="space-y-3">
                        <div>
                            <p className="text-3xl font-bold">
                                ৳{progress.outstanding}{' '}
                                <span className="text-muted-foreground text-base font-normal">
                                    outstanding
                                </span>
                            </p>
                            <p className="text-muted-foreground text-sm">
                                of ৳{loan.amount}{' '}
                                {isGiven ? 'given' : 'borrowed'}
                            </p>
                        </div>

                        <div className="flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">
                                {isGiven ? 'Returned' : 'Paid'}{' '}
                                <b className="text-foreground">
                                    ৳{progress.total_repaid}
                                </b>
                            </span>
                            {loan.expected_return_date && (
                                <span className="text-muted-foreground">
                                    Expected{' '}
                                    <b className="text-foreground">
                                        {loan.expected_return_date}
                                    </b>
                                </span>
                            )}
                        </div>

                        {isGiven && (
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">
                                    Held, not yet transferred{' '}
                                    <b className="text-foreground">
                                        ৳{progress.held_balance}
                                    </b>
                                </span>
                                <span className="text-muted-foreground">
                                    Transferred{' '}
                                    <b className="text-foreground">
                                        ৳{progress.total_transferred}
                                    </b>
                                </span>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>History</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {historyGroups.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No repayments or transfers yet.
                            </p>
                        ) : (
                            historyGroups.map((group) => (
                                <div key={group.date}>
                                    <p className="text-muted-foreground mb-2 text-xs font-semibold tracking-wide uppercase">
                                        {group.label}
                                    </p>
                                    <div className="divide-y rounded-lg border">
                                        {group.items.map((item) => {
                                            const isCredit =
                                                item.kind === 'transfer' ||
                                                (item.kind === 'repayment' &&
                                                    isGiven);

                                            return (
                                                <div
                                                    key={`${item.kind}-${item.id}`}
                                                    className="flex items-center justify-between gap-4 p-3"
                                                >
                                                    <Link
                                                        href={editHref(item)}
                                                        className="min-w-0 flex-1"
                                                    >
                                                        <p className="font-medium">
                                                            {item.kind ===
                                                            'repayment'
                                                                ? isGiven
                                                                    ? 'Repayment received'
                                                                    : 'Repayment paid'
                                                                : 'Transferred to account'}
                                                        </p>
                                                        <p className="text-muted-foreground text-sm">
                                                            {item.account?.name}
                                                            {item.note &&
                                                                ` · ${item.note}`}
                                                        </p>
                                                    </Link>
                                                    <div className="flex items-center gap-2">
                                                        <p
                                                            className={
                                                                isCredit
                                                                    ? 'font-semibold text-green-600 dark:text-green-500'
                                                                    : 'font-semibold'
                                                            }
                                                        >
                                                            {isCredit
                                                                ? '+'
                                                                : '-'}
                                                            ৳{item.amount}
                                                        </p>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                destroyItem(
                                                                    item,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 />
                                                        </Button>
                                                    </div>
                                                </div>
                                            );
                                        })}
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

LoansShow.layout = (props: { loan: Loan }) => ({
    breadcrumbs: [
        { title: 'Loans', href: loans.index() },
        { title: props.loan.contact.name, href: loans.show(props.loan.id) },
    ],
});
