import { Head, Link, router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { INCOME_SOURCE_LABELS } from '@/components/finance/income-sources';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import incomes from '@/routes/incomes';
import type { Income, Paginated } from '@/types/finance';

export default function IncomesIndex({
    incomes: paginated,
}: {
    incomes: Paginated<Income>;
}) {
    function destroy(income: Income) {
        if (confirm('Delete this income entry?')) {
            router.delete(incomes.destroy.url(income.id), {
                preserveScroll: true,
            });
        }
    }

    return (
        <>
            <Head title="Income" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Income"
                        description="Everything you've received, most recent first."
                    />
                    <Button asChild>
                        <Link href={incomes.create()}>
                            <Plus className="size-4" />
                            Add Income
                        </Link>
                    </Button>
                </div>

                {paginated.data.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            No income recorded yet.
                            <br />
                            Add your first entry to see it here.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="divide-y rounded-lg border">
                        {paginated.data.map((income) => (
                            <div
                                key={income.id}
                                className="flex items-center justify-between gap-4 p-4"
                            >
                                <Link
                                    href={incomes.edit(income.id)}
                                    className="min-w-0 flex-1"
                                >
                                    <p className="font-medium">
                                        {INCOME_SOURCE_LABELS[income.source]}
                                        <span className="text-muted-foreground ml-2 text-xs">
                                            {income.account?.name}
                                        </span>
                                    </p>
                                    <p className="text-muted-foreground text-sm">
                                        {income.received_on}
                                        {income.note && ` · ${income.note}`}
                                    </p>
                                </Link>
                                <div className="flex items-center gap-2">
                                    <p className="font-semibold text-green-600 dark:text-green-500">
                                        +৳{income.amount}
                                    </p>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => destroy(income)}
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </div>
                        ))}
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

IncomesIndex.layout = {
    breadcrumbs: [{ title: 'Income', href: incomes.index() }],
};
