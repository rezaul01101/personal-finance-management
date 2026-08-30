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
import { Textarea } from '@/components/ui/textarea';
import budgetCategories from '@/routes/budget-categories';
import type { BudgetCategory } from '@/types/finance';

export default function BudgetCategoriesIndex({
    budgetCategories: items,
}: {
    budgetCategories: BudgetCategory[];
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<BudgetCategory | null>(null);

    const form = useForm<{
        name: string;
        icon: string;
        description: string;
        status: 'active' | 'archived';
    }>({
        name: '',
        icon: '',
        description: '',
        status: 'active',
    });

    function openCreate() {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(category: BudgetCategory) {
        setEditing(category);
        form.setData({
            name: category.name,
            icon: category.icon ?? '',
            description: category.description ?? '',
            status: category.status,
        });
        form.clearErrors();
        setOpen(true);
    }

    function submit() {
        if (editing) {
            form.put(budgetCategories.update.url(editing.id), {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            });
        } else {
            form.post(budgetCategories.store.url(), {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            });
        }
    }

    function destroy(category: BudgetCategory) {
        if (confirm(`Delete budget category "${category.name}"?`)) {
            router.delete(budgetCategories.destroy.url(category.id), {
                preserveScroll: true,
            });
        }
    }

    return (
        <>
            <Head title="Budget Categories" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Budget Categories"
                        description="The groups you set a monthly budget for, e.g. Family, Personal, Lending."
                    />

                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate}>Add Category</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {editing
                                        ? 'Edit Budget Category'
                                        : 'Add Budget Category'}
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
                                        placeholder="e.g. Family"
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
                                        placeholder="e.g. 🏠"
                                    />
                                    <InputError message={form.errors.icon} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description">
                                        Description (optional)
                                    </Label>
                                    <Textarea
                                        id="description"
                                        value={form.data.description}
                                        onChange={(e) =>
                                            form.setData(
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.description}
                                    />
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
                            No budget categories yet. Create categories like
                            Family, Personal, or Lending to start budgeting.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="divide-y rounded-lg border">
                        {items.map((category) => (
                            <div
                                key={category.id}
                                className="flex items-center justify-between gap-4 p-4"
                            >
                                <div>
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
                                    {category.description && (
                                        <p className="text-muted-foreground mt-0.5 text-sm">
                                            {category.description}
                                        </p>
                                    )}
                                </div>
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

BudgetCategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Budget Categories', href: budgetCategories.index() },
    ],
};
