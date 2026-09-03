import { Head, Link, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ProgressBar } from '@/components/finance/progress-bar';
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
import savingsGoals from '@/routes/savings-goals';
import type {
    SavingsGoal,
    SavingsGoalStatus,
    SavingsSummary,
} from '@/types/finance';

export default function SavingsIndex({
    savingsGoals: goals,
    summaries,
}: {
    savingsGoals: SavingsGoal[];
    summaries: Record<number, SavingsSummary>;
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<SavingsGoal | null>(null);

    const form = useForm<{
        name: string;
        target_amount: string;
        target_date: string;
        description: string;
        status: SavingsGoalStatus;
    }>({
        name: '',
        target_amount: '',
        target_date: '',
        description: '',
        status: 'active',
    });

    function openCreate() {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(e: React.MouseEvent, goal: SavingsGoal) {
        e.preventDefault();
        e.stopPropagation();
        setEditing(goal);
        form.setData({
            name: goal.name,
            target_amount: goal.target_amount,
            target_date: goal.target_date ?? '',
            description: goal.description ?? '',
            status: goal.status,
        });
        form.clearErrors();
        setOpen(true);
    }

    function submit() {
        if (editing) {
            form.put(savingsGoals.update.url(editing.id), {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            });
        } else {
            form.post(savingsGoals.store.url(), {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            });
        }
    }

    function destroy(e: React.MouseEvent, goal: SavingsGoal) {
        e.preventDefault();
        e.stopPropagation();

        if (confirm(`Delete savings goal "${goal.name}"?`)) {
            router.delete(savingsGoals.destroy.url(goal.id), {
                preserveScroll: true,
            });
        }
    }

    return (
        <>
            <Head title="Savings" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Savings"
                        description="Goal-based savings, funded from your accounts."
                    />

                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate}>Add Goal</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {editing
                                        ? 'Edit Savings Goal'
                                        : 'Add Savings Goal'}
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
                                        placeholder="e.g. Emergency Fund"
                                    />
                                    <InputError message={form.errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="target_amount">
                                        Target Amount
                                    </Label>
                                    <Input
                                        id="target_amount"
                                        inputMode="decimal"
                                        value={form.data.target_amount}
                                        onChange={(e) =>
                                            form.setData(
                                                'target_amount',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. 200000"
                                    />
                                    <InputError
                                        message={form.errors.target_amount}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="target_date">
                                        Target Date (optional)
                                    </Label>
                                    <Input
                                        id="target_date"
                                        type="date"
                                        value={form.data.target_date}
                                        onChange={(e) =>
                                            form.setData(
                                                'target_date',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.target_date}
                                    />
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
                                                    value as SavingsGoalStatus,
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
                                                <SelectItem value="completed">
                                                    Completed
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

                {goals.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            No savings goals yet.
                            <br />
                            Create your first savings goal.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2">
                        {goals.map((goal) => {
                            const summary = summaries[goal.id];

                            return (
                                <Link
                                    key={goal.id}
                                    href={savingsGoals.show(goal.id)}
                                    className="block"
                                >
                                    <Card className="hover:border-primary/50 transition-colors">
                                        <CardContent className="space-y-3">
                                            <div className="flex items-start justify-between gap-2">
                                                <p className="font-semibold">
                                                    {goal.name}
                                                    {goal.status !==
                                                        'active' && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="ml-2"
                                                        >
                                                            {goal.status ===
                                                            'completed'
                                                                ? 'Completed'
                                                                : 'Archived'}
                                                        </Badge>
                                                    )}
                                                </p>
                                                <div className="flex items-center gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={(e) =>
                                                            openEdit(e, goal)
                                                        }
                                                    >
                                                        Edit
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={(e) =>
                                                            destroy(e, goal)
                                                        }
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </div>
                                            </div>

                                            {summary && (
                                                <>
                                                    <p className="text-sm">
                                                        <span className="text-lg font-bold">
                                                            ৳
                                                            {
                                                                summary.saved_amount
                                                            }
                                                        </span>
                                                        <span className="text-muted-foreground">
                                                            {' '}
                                                            / ৳
                                                            {
                                                                summary.target_amount
                                                            }
                                                        </span>
                                                    </p>
                                                    <ProgressBar
                                                        percentage={
                                                            summary.usage_percentage
                                                        }
                                                        health="healthy"
                                                    />
                                                </>
                                            )}

                                            {goal.target_date && (
                                                <p className="text-muted-foreground text-xs">
                                                    Target: {goal.target_date}
                                                </p>
                                            )}
                                        </CardContent>
                                    </Card>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}

SavingsIndex.layout = {
    breadcrumbs: [{ title: 'Savings', href: savingsGoals.index() }],
};
