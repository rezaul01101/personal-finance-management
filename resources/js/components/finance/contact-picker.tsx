import { useHttp } from '@inertiajs/react';
import { ChevronDown, Plus, User } from 'lucide-react';
import { useState } from 'react';
import { ChipSelect } from '@/components/finance/chip-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import contacts from '@/routes/contacts';
import type { Contact } from '@/types/finance';

export function ContactPicker({
    contacts: initialContacts,
    value,
    onChange,
}: {
    contacts: Contact[];
    value: string;
    onChange: (value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState(initialContacts);
    const selected = items.find((contact) => String(contact.id) === value);

    const http = useHttp<{ name: string }, Contact>({ name: '' });

    function addContact() {
        http.post(contacts.store.url(), {
            onSuccess: (created) => {
                setItems((prev) => [...prev, created]);
                onChange(String(created.id));
                http.setData('name', '');
                http.clearErrors();
                setOpen(false);
            },
        });
    }

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="border-input flex h-9 w-full min-w-0 items-center justify-between gap-2 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
            >
                <span className="flex items-center gap-2 truncate">
                    <User className="text-muted-foreground size-4 shrink-0" />
                    <span
                        className={
                            selected ? '' : 'text-muted-foreground truncate'
                        }
                    >
                        {selected?.name ?? 'Select person'}
                    </span>
                </span>
                <ChevronDown className="text-muted-foreground size-4 shrink-0" />
            </button>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="bottom" className="rounded-t-2xl px-4 pb-8">
                    <SheetHeader className="px-0">
                        <SheetTitle>Select Person</SheetTitle>
                    </SheetHeader>

                    <ChipSelect
                        options={items.map((contact) => ({
                            id: contact.id,
                            label: contact.name,
                        }))}
                        value={value}
                        onChange={(next) => {
                            onChange(next);
                            setOpen(false);
                        }}
                        wrap
                    />

                    <div className="mt-4 flex gap-2">
                        <Input
                            value={http.data.name}
                            onChange={(e) =>
                                http.setData('name', e.target.value)
                            }
                            placeholder="Add new person"
                        />
                        <Button
                            type="button"
                            disabled={
                                http.processing || !http.data.name.trim()
                            }
                            onClick={addContact}
                        >
                            <Plus className="size-4" />
                            Add
                        </Button>
                    </div>
                    <InputError message={http.errors.name} />
                </SheetContent>
            </Sheet>
        </>
    );
}
