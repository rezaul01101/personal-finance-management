import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import transfers from '@/routes/transfers';
import type { AccountTransfer, Paginated } from '@/types/finance';

export default function TransfersIndex({
    transfers: paginated,
}: {
    transfers: Paginated<AccountTransfer>;
}) {
    function destroy(transfer: AccountTransfer) {
        if (confirm('Delete this transfer?')) {
            router.delete(transfers.destroy.url(transfer.id), {
                preserveScroll: true,
            });
        }
    }

    return (
        <>
            <Head title="Transfers" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Transfers"
                        description="Money moved between your own accounts."
                    />
                    <Button asChild>
                        <Link href={transfers.create()}>
                            <Plus className="size-4" />
                            Transfer Money
                        </Link>
                    </Button>
                </div>

                {paginated.data.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            No transfers yet.
                            <br />
                            Move money between your accounts to see it here.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="divide-y rounded-lg border">
                        {paginated.data.map((transfer) => (
                            <div
                                key={transfer.id}
                                className="flex items-center justify-between gap-4 p-4"
                            >
                                <Link
                                    href={transfers.edit(transfer.id)}
                                    className="min-w-0 flex-1"
                                >
                                    <p className="flex items-center gap-1.5 font-medium">
                                        {transfer.from_account?.name}
                                        <ArrowRight className="text-muted-foreground size-3.5" />
                                        {transfer.to_account?.name}
                                    </p>
                                    <p className="text-muted-foreground text-sm">
                                        {transfer.transferred_on}
                                        {transfer.note && ` · ${transfer.note}`}
                                    </p>
                                </Link>
                                <div className="flex items-center gap-2">
                                    <p className="font-semibold">
                                        ৳{transfer.amount}
                                    </p>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => destroy(transfer)}
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

TransfersIndex.layout = {
    breadcrumbs: [{ title: 'Transfers', href: transfers.index() }],
};
