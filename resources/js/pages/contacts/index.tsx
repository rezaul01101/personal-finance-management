import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
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
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import contacts from '@/routes/contacts';
import type { Contact } from '@/types/finance';

export default function ContactsIndex({
    contacts: items,
}: {
    contacts: Contact[];
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Contact | null>(null);

    const form = useForm<{ name: string }>({ name: '' });

    function openCreate() {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setOpen(true);
    }

    function openEdit(contact: Contact) {
        setEditing(contact);
        form.setData({ name: contact.name });
        form.clearErrors();
        setOpen(true);
    }

    function submit() {
        if (editing) {
            form.put(contacts.update.url(editing.id), {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            });
        } else {
            form.post(contacts.store.url(), {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            });
        }
    }

    function destroy(contact: Contact) {
        if (confirm(`Delete "${contact.name}"?`)) {
            router.delete(contacts.destroy.url(contact.id), {
                preserveScroll: true,
            });
        }
    }

    return (
        <>
            <Head title="People" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="People"
                        description="Everyone you lend to or borrow from."
                    />

                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate}>Add Person</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {editing ? 'Edit Person' : 'Add Person'}
                                </DialogTitle>
                            </DialogHeader>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData(
                                                'name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. Anamul"
                                    />
                                    <InputError message={form.errors.name} />
                                </div>
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
                            No people yet. Add someone to start recording
                            loans given to or taken from them.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="divide-y rounded-lg border">
                        {items.map((contact) => (
                            <div
                                key={contact.id}
                                className="flex items-center justify-between gap-4 p-4"
                            >
                                <button
                                    type="button"
                                    onClick={() => openEdit(contact)}
                                    className="min-w-0 flex-1 text-left font-medium"
                                >
                                    {contact.name}
                                </button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => destroy(contact)}
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ContactsIndex.layout = {
    breadcrumbs: [{ title: 'People', href: contacts.index() }],
};
