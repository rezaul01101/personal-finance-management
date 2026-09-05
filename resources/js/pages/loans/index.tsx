import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { ChipSelect } from '@/components/finance/chip-select';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import loans from '@/routes/loans';
import type { Contact, ContactLoanSummary, LoanType } from '@/types/finance';

const DIRECTION_OPTIONS = [
    { id: 'given', label: 'Given' },
    { id: 'taken', label: 'Taken' },
];

export default function LoansIndex({
    contacts,
    direction,
    summaries,
}: {
    contacts: Contact[];
    direction: LoanType;
    summaries: Record<number, ContactLoanSummary>;
}) {
    function changeDirection(value: string) {
        router.get(
            loans.index.url({ query: { direction: value } }),
            {},
            { preserveState: true, preserveScroll: true },
        );
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

                {contacts.length === 0 ? (
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
                        {contacts.map((contact) => {
                            const summary = summaries[contact.id];

                            return (
                                <Link
                                    key={contact.id}
                                    href={loans.contacts.show(contact.id, {
                                        query: { direction },
                                    })}
                                    className="flex items-center justify-between gap-4 p-4"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="font-medium">
                                            {contact.name}
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            {contact.loans_count}{' '}
                                            {contact.loans_count === 1
                                                ? 'loan'
                                                : 'loans'}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="font-semibold">
                                            ৳{summary?.outstanding}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            outstanding of ৳
                                            {summary?.total_amount}
                                        </p>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}

LoansIndex.layout = {
    breadcrumbs: [{ title: 'Loans', href: loans.index() }],
};
