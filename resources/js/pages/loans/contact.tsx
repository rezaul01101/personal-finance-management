import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { ChipSelect } from '@/components/finance/chip-select';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import loans from '@/routes/loans';
import type {
    Contact,
    ContactLoanSummary,
    Loan,
    LoanProgress,
    LoanType,
} from '@/types/finance';

const DIRECTION_OPTIONS = [
    { id: 'given', label: 'Given' },
    { id: 'taken', label: 'Taken' },
];

export default function LoansContact({
    contact,
    direction,
    loans: contactLoans,
    progress,
    summary,
}: {
    contact: Contact;
    direction: LoanType;
    loans: Loan[];
    progress: Record<number, LoanProgress>;
    summary: ContactLoanSummary;
}) {
    function changeDirection(value: string) {
        router.get(
            loans.contacts.show.url(contact.id, {
                query: { direction: value },
            }),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={contact.name} />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={contact.name}
                        description={
                            direction === 'given'
                                ? 'Money lent to this person.'
                                : 'Money borrowed from this person.'
                        }
                    />
                    <Button asChild>
                        <Link
                            href={loans.create({
                                query: {
                                    direction,
                                    contact_id: contact.id,
                                },
                            })}
                        >
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

                <Card>
                    <CardContent className="flex items-center justify-between">
                        <div>
                            <p className="text-muted-foreground text-sm">
                                Total {direction === 'given' ? 'given' : 'taken'}
                            </p>
                            <p className="text-2xl font-semibold">
                                ৳{summary.total_amount}
                            </p>
                        </div>
                        <div className="text-right">
                            <p className="text-muted-foreground text-sm">
                                Outstanding
                            </p>
                            <p className="text-2xl font-semibold">
                                ৳{summary.outstanding}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                {contactLoans.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            {direction === 'given'
                                ? 'No loans given to this person yet.'
                                : 'No loans taken from this person yet.'}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="divide-y rounded-lg border">
                        {contactLoans.map((loan) => {
                            const loanProgress = progress[loan.id];

                            return (
                                <Link
                                    key={loan.id}
                                    href={loans.show(loan.id)}
                                    className="flex items-center justify-between gap-4 p-4"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="font-medium">
                                            {loan.account?.name}
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            {loan.loan_date}
                                            {loan.note && ` · ${loan.note}`}
                                        </p>
                                    </div>
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
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}

LoansContact.layout = (props: { contact: Contact }) => ({
    breadcrumbs: [
        { title: 'Loans', href: loans.index() },
        {
            title: props.contact.name,
            href: loans.contacts.show(props.contact.id),
        },
    ],
});
