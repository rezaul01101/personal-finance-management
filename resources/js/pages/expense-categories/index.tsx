import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import expenseCategories from '@/routes/expense-categories';
import type { ExpenseCategory } from '@/types/finance';

export default function ExpenseCategoriesIndex({
    expenseCategories: items,
}: {
    expenseCategories: ExpenseCategory[];
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ExpenseCategory | null>(null);

    const form = useForm<{
        name: string;
        icon: string;
        status: 'active' | 'archived';
    }>({
        name: '',
        icon: '',
        status: 'active',
    });

    function openCreate() {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(category: ExpenseCategory) {
        setEditing(category);
        form.setData({
            name: category.name,
            icon: category.icon ?? '',
            status: category.status,
        });
        form.clearErrors();
        setOpen(true);
    }

    function submit() {
        if (editing) {
            form.put(expenseCategories.update.url(editing.id), {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            });
        } else {
            form.post(expenseCategories.store.url(), {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            });
        }
    }

    function destroy(category: ExpenseCategory) {
        if (confirm(`Delete expense category "${category.name}"?`)) {
            router.delete(expenseCategories.destroy.url(category.id), {
                preserveScroll: true,
            });
        }
    }

    return (
        <>
            <Head title="Expense Categories" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Expense Categories"
                        description="What each expense is for, e.g. Food, Grocery, Transport."
                    />

                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate}>Add Category</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {editing
                                        ? 'Edit Expense Category'
                                        : 'Add Expense Category'}
                                </DialogTitle>
                            </DialogHeader>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData('name', e.target.value)
                                        }
                                        placeholder="e.g. Food"
                                    />
                                    <InputError message={form.errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="icon">
                                        Icon (optional)
                                    </Label>
                                    <Input
                                        id="icon"
                                        value={form.data.icon}
                                        onChange={(e) =>
                                            form.setData('icon', e.target.value)
                                        }
                                        placeholder="e.g. 🍔"
                                    />
                                    <InputError message={form.errors.icon} />
                                </div>

                                {editing && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="status">Status</Label>
                                        <Select
                                            value={form.data.status}
                                            onValueChange={(value) =>
                                                form.setData(
                                                    'status',
                                                    value as
                                                        | 'active'
                                                        | 'archived',
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                id="status"
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">
                                                    Active
                                                </SelectItem>
                                                <SelectItem value="archived">
                                                    Archived
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={form.errors.status}
                                        />
                                    </div>
                                )}
                            </div>

                            <DialogFooter>
                                <Button
                                    disabled={form.processing}
                                    onClick={submit}
                                >
                                    Save
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>

                {items.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            No expense categories yet. Create categories like
                            Food, Grocery, or Transport to categorize expenses.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="divide-y rounded-lg border">
                        {items.map((category) => (
                            <div
                                key={category.id}
                                className="flex items-center justify-between gap-4 p-4"
                            >
                                <p className="font-medium">
                                    {category.icon && (
                                        <span className="mr-2">
                                            {category.icon}
                                        </span>
                                    )}
                                    {category.name}
                                    {category.status === 'archived' && (
                                        <Badge
                                            variant="secondary"
                                            className="ml-2"
                                        >
                                            Archived
                                        </Badge>
                                    )}
                                </p>
                                <div className="flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => openEdit(category)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => destroy(category)}
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ExpenseCategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Expense Categories', href: expenseCategories.index() },
    ],
};
