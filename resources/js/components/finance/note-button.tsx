import { StickyNote } from 'lucide-react';
import { useState } from 'react';
import { IconActionButton } from '@/components/finance/icon-action-button';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';

export function NoteButton({
    value,
    onChange,
}: {
    value: string;
    onChange: (value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState(value);

    function openSheet() {
        setDraft(value);
        setOpen(true);
    }

    function save() {
        onChange(draft);
        setOpen(false);
    }

    return (
        <>
            <IconActionButton
                icon={StickyNote}
                label={value ? 'Edit Note' : 'Note'}
                onClick={openSheet}
            />

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="bottom" className="rounded-t-2xl px-4 pb-8">
                    <SheetHeader className="px-0">
                        <SheetTitle>Note</SheetTitle>
                    </SheetHeader>

                    <Textarea
                        value={draft}
                        onChange={(e) => setDraft(e.target.value)}
                        placeholder="Add a short note…"
                        rows={4}
                        autoFocus
                    />

                    <SheetFooter className="px-0">
                        <Button onClick={save} className="w-full">
                            Save Note
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>
        </>
    );
}
