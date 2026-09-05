import { Head, Link, router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { ChipSelect } from '@/components/finance/chip-select';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import loans from '@/routes/loans';
import type { Loan, LoanProgress, LoanType, Paginated } from '@/types/finance';

const DIRECTION_OPTIONS = [
    { id: 'given', label: 'Given' },
    { id: 'taken', label: 'Taken' },
];

export default function LoansIndex({
    loans: paginated,
    direction,
    progress,
}: {
    loans: Paginated<Loan>;
    direction: LoanType;
    progress: Record<number, LoanProgress>;
}) {
    function changeDirection(value: string) {
        router.get(
            loans.index.url({ query: { direction: value } }),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    function destroy(loan: Loan) {
        if (confirm('Delete this loan?')) {
            router.delete(loans.destroy.url(loan.id), {
                preserveScroll: true,
            });
        }
    }

    return (
        <>
            <Head title="Loans" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Loans"
                        description="Money you've lent out or borrowed."
                    />
                    <Button asChild>
                        <Link href={loans.create({ query: { direction } })}>
                            <Plus className="size-4" />
                            Add Loan
                        </Link>
                    </Button>
                </div>

                <ChipSelect
                    options={DIRECTION_OPTIONS}
                    value={direction}
                    onChange={changeDirection}
                />

                {paginated.data.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            {direction === 'given'
                                ? 'No loans given yet.'
                                : 'No loans taken yet.'}
                            <br />
                            {direction === 'given'
                                ? 'Track money you lend to others.'
                                : 'Track money you borrow from others.'}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="divide-y rounded-lg border">
                        {paginated.data.map((loan) => {
                            const loanProgress = progress[loan.id];

                            return (
                                <div
                                    key={loan.id}
                                    className="flex items-center justify-between gap-4 p-4"
                                >
                                    <Link
                                        href={loans.show(loan.id)}
                                        className="min-w-0 flex-1"
                                    >
                                        <p className="font-medium">
                                            {loan.person_name}
                                            <span className="text-muted-foreground ml-2 text-xs">
                                                {loan.account?.name}
                                            </span>
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            {loan.loan_date}
                                            {loan.note && ` · ${loan.note}`}
                                        </p>
                                    </Link>
                                    <div className="flex items-center gap-2">
                                        <div className="text-right">
                                            <p className="font-semibold">
                                                ৳
                                                {loanProgress?.outstanding ??
                                                    loan.amount}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                outstanding
                                            </p>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => destroy(loan)}
                                        >
                                            <Trash2 />
                                        </Button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}

                {paginated.last_page > 1 && (
                    <div className="flex flex-wrap gap-1">
                        {paginated.links.map((link, index) =>
                            link.url ? (
                                <Button
                                    key={index}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    asChild
                                >
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                </Button>
                            ) : (
                                <Button
                                    key={index}
                                    variant="outline"
                                    size="sm"
                                    disabled
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

LoansIndex.layout = {
    breadcrumbs: [{ title: 'Loans', href: loans.index() }],
};
