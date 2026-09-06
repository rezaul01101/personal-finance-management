import { useHttp } from '@inertiajs/react';
import { ArrowLeft, NotebookPen, Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import notes from '@/routes/notes';
import type { Note } from '@/types/notes';

type NoteFormData = {
    title: string;
    description: string;
};

const EMPTY_FORM: NoteFormData = { title: '', description: '' };

function groupByDate(items: Note[]): { label: string; notes: Note[] }[] {
    const groups: { label: string; notes: Note[] }[] = [];

    for (const note of items) {
        const label = formatGroupLabel(note.created_at);
        const currentGroup = groups.at(-1);

        if (currentGroup?.label === label) {
            currentGroup.notes.push(note);
        } else {
            groups.push({ label, notes: [note] });
        }
    }

    return groups;
}

function formatGroupLabel(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const startOf = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const diffDays = Math.round(
        (startOf(now).getTime() - startOf(date).getTime()) / 86_400_000,
    );

    if (diffDays === 0) {
        return 'Today';
    }

    if (diffDays === 1) {
        return 'Yesterday';
    }

    return date.toLocaleDateString(undefined, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: date.getFullYear() === now.getFullYear() ? undefined : 'numeric',
    });
}

export function NotesWidget() {
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [loaded, setLoaded] = useState(false);
    const [items, setItems] = useState<Note[]>([]);
    const [editing, setEditing] = useState<Note | null>(null);
    const [formOpen, setFormOpen] = useState(false);

    const http = useHttp<NoteFormData>(EMPTY_FORM);

    useEffect(() => {
        if (!open || loaded) {
            return;
        }

        setLoading(true);
        http.get(notes.index.url(), {
            onSuccess: (response) => {
                setItems(response as Note[]);
                setLoaded(true);
            },
            onFinish: () => setLoading(false),
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, loaded]);

    function openCreate() {
        setEditing(null);
        http.setData(EMPTY_FORM);
        http.clearErrors();
        setFormOpen(true);
    }

    function openEdit(note: Note) {
        setEditing(note);
        http.setData({
            title: note.title ?? '',
            description: note.description ?? '',
        });
        http.clearErrors();
        setFormOpen(true);
    }

    function save() {
        if (editing) {
            http.put(notes.update.url(editing.id), {
                onSuccess: (response) => {
                    const updated = response as Note;
                    setItems((prev) =>
                        prev.map((note) =>
                            note.id === updated.id ? updated : note,
                        ),
                    );
                    setFormOpen(false);
                },
            });
        } else {
            http.post(notes.store.url(), {
                onSuccess: (response) => {
                    setItems((prev) => [response as Note, ...prev]);
                    setFormOpen(false);
                },
            });
        }
    }

    function destroy(note: Note) {
        if (!confirm('Delete this note?')) {
            return;
        }

        http.delete(notes.destroy.url(note.id), {
            onSuccess: () => {
                setItems((prev) => prev.filter((n) => n.id !== note.id));
            },
        });
    }

    const groups = groupByDate(items);

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="bg-primary text-primary-foreground shadow-primary/30 fixed right-4 bottom-20 z-40 grid size-14 place-items-center rounded-full shadow-lg md:right-6 md:bottom-6"
            >
                <NotebookPen className="size-6" />
                <span className="sr-only">Quick Notes</span>
            </button>

            <Sheet
                open={open}
                onOpenChange={(next) => {
                    setOpen(next);
                    if (!next) {
                        setFormOpen(false);
                    }
                }}
            >
                <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-none md:w-[30%] md:min-w-80">
                    {formOpen ? (
                        <>
                            <SheetHeader className="flex-row items-center gap-2 space-y-0 border-b">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => setFormOpen(false)}
                                >
                                    <ArrowLeft />
                                </Button>
                                <SheetTitle>
                                    {editing ? 'Edit Note' : 'New Note'}
                                </SheetTitle>
                            </SheetHeader>

                            <div className="flex-1 space-y-4 overflow-y-auto p-4">
                                <div className="space-y-2">
                                    <Input
                                        value={http.data.title}
                                        onChange={(e) =>
                                            http.setData(
                                                'title',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Title"
                                    />
                                    <InputError message={http.errors.title} />
                                </div>

                                <div className="space-y-2">
                                    <Textarea
                                        value={http.data.description}
                                        onChange={(e) =>
                                            http.setData(
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Write a note..."
                                        rows={10}
                                    />
                                    <InputError
                                        message={http.errors.description}
                                    />
                                </div>
                            </div>

                            <div className="flex gap-2 border-t p-4">
                                <Button
                                    variant="outline"
                                    className="flex-1"
                                    onClick={() => setFormOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    className="flex-1"
                                    disabled={http.processing}
                                    onClick={save}
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    ) : (
                        <>
                            <SheetHeader className="gap-3 border-b pr-10">
                                <SheetTitle>Quick Notes</SheetTitle>
                                <Button onClick={openCreate} className="w-full">
                                    <Plus />
                                    New Note
                                </Button>
                            </SheetHeader>

                            <div className="flex-1 overflow-y-auto p-4">
                                {loading ? (
                                    <div className="flex justify-center py-10">
                                        <Spinner />
                                    </div>
                                ) : groups.length === 0 ? (
                                    <p className="text-muted-foreground py-10 text-center text-sm">
                                        No notes yet. Tap "New" to jot
                                        something down.
                                    </p>
                                ) : (
                                    <div className="space-y-6">
                                        {groups.map((group) => (
                                            <div
                                                key={group.label}
                                                className="space-y-2"
                                            >
                                                <h3 className="text-muted-foreground text-xs font-semibold uppercase">
                                                    {group.label}
                                                </h3>

                                                <div className="space-y-2">
                                                    {group.notes.map(
                                                        (note) => (
                                                            <div
                                                                key={note.id}
                                                                className="group flex items-start justify-between gap-2 rounded-lg border p-3"
                                                            >
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        openEdit(
                                                                            note,
                                                                        )
                                                                    }
                                                                    className="min-w-0 flex-1 text-left"
                                                                >
                                                                    {note.title && (
                                                                        <p className="truncate font-medium">
                                                                            {
                                                                                note.title
                                                                            }
                                                                        </p>
                                                                    )}
                                                                    {note.description && (
                                                                        <p className="text-muted-foreground line-clamp-3 text-sm whitespace-pre-wrap">
                                                                            {
                                                                                note.description
                                                                            }
                                                                        </p>
                                                                    )}
                                                                </button>

                                                                <div className="flex shrink-0 gap-1">
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        onClick={() =>
                                                                            openEdit(
                                                                                note,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Pencil />
                                                                    </Button>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        onClick={() =>
                                                                            destroy(
                                                                                note,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2 />
                                                                    </Button>
                                                                </div>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>
        </>
    );
}
