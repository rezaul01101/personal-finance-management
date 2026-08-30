import { Camera, X } from 'lucide-react';
import { type ChangeEvent, useRef, useState } from 'react';
import { IconActionButton } from '@/components/finance/icon-action-button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { ExpenseAttachment } from '@/types/finance';

type Preview =
    | { key: string; url: string; kind: 'existing'; id: number }
    | { key: string; url: string; kind: 'pending'; index: number };

export function ReceiptPicker({
    files,
    onFilesChange,
    existingAttachments = [],
    onRemoveExisting,
}: {
    files: File[];
    onFilesChange: (files: File[]) => void;
    existingAttachments?: ExpenseAttachment[];
    onRemoveExisting?: (attachmentId: number) => void;
}) {
    const [open, setOpen] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    const previews: Preview[] = [
        ...existingAttachments.map((attachment) => ({
            key: `existing-${attachment.id}`,
            url: attachment.url,
            kind: 'existing' as const,
            id: attachment.id,
        })),
        ...files.map((file, index) => ({
            key: `pending-${index}`,
            url: URL.createObjectURL(file),
            kind: 'pending' as const,
            index,
        })),
    ];

    function handleTrigger() {
        if (previews.length > 0) {
            setOpen(true);
        } else {
            inputRef.current?.click();
        }
    }

    function handleSelect(event: ChangeEvent<HTMLInputElement>) {
        const selected = Array.from(event.target.files ?? []);
        onFilesChange([...files, ...selected]);
        event.target.value = '';
    }

    function removePending(index: number) {
        onFilesChange(files.filter((_, i) => i !== index));
    }

    return (
        <>
            <input
                ref={inputRef}
                type="file"
                accept="image/*"
                capture="environment"
                multiple
                className="hidden"
                onChange={handleSelect}
            />

            <IconActionButton
                icon={Camera}
                label="Receipt"
                thumbnail={previews[0]?.url}
                badge={previews.length > 1 ? previews.length - 1 : undefined}
                onClick={handleTrigger}
            />

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Receipts</DialogTitle>
                    </DialogHeader>

                    <div className="grid grid-cols-3 gap-3">
                        {previews.map((preview) => (
                            <div
                                key={preview.key}
                                className="relative aspect-square"
                            >
                                <img
                                    src={preview.url}
                                    alt=""
                                    className="size-full rounded-lg object-cover"
                                />
                                <button
                                    type="button"
                                    onClick={() =>
                                        preview.kind === 'existing'
                                            ? onRemoveExisting?.(preview.id)
                                            : removePending(preview.index)
                                    }
                                    className="bg-background absolute -top-2 -right-2 rounded-full border p-0.5"
                                >
                                    <X className="size-3.5" />
                                </button>
                            </div>
                        ))}

                        <button
                            type="button"
                            onClick={() => inputRef.current?.click()}
                            className="border-input bg-secondary text-muted-foreground flex aspect-square items-center justify-center rounded-lg border border-dashed"
                        >
                            <Camera className="size-5" />
                        </button>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
