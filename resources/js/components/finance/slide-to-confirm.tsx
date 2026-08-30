import { Check, type LucideIcon } from 'lucide-react';
import { useRef, useState } from 'react';
import { cn } from '@/lib/utils';

const THUMB_SIZE = 48;
const TRACK_PADDING = 4;
const CONFIRM_THRESHOLD = 0.85;

export function SlideToConfirm({
    fromIcon: FromIcon,
    toIcon: ToIcon,
    label = 'Slide to confirm',
    confirmingLabel = 'Confirming…',
    onConfirm,
    disabled = false,
}: {
    fromIcon: LucideIcon;
    toIcon: LucideIcon;
    label?: string;
    confirmingLabel?: string;
    onConfirm: () => void;
    disabled?: boolean;
}) {
    const trackRef = useRef<HTMLDivElement>(null);
    const [dragX, setDragX] = useState(0);
    const [dragging, setDragging] = useState(false);
    const [confirmed, setConfirmed] = useState(false);

    function maxDrag(): number {
        const width = trackRef.current?.offsetWidth ?? 0;

        return Math.max(0, width - THUMB_SIZE - TRACK_PADDING * 2);
    }

    function handlePointerDown(e: React.PointerEvent) {
        if (disabled || confirmed) {
            return;
        }

        setDragging(true);
        (e.target as HTMLElement).setPointerCapture(e.pointerId);
    }

    function handlePointerMove(e: React.PointerEvent) {
        if (!dragging || disabled || confirmed) {
            return;
        }

        const rect = trackRef.current?.getBoundingClientRect();
        if (!rect) {
            return;
        }

        const x = e.clientX - rect.left - TRACK_PADDING - THUMB_SIZE / 2;
        setDragX(Math.max(0, Math.min(x, maxDrag())));
    }

    function handlePointerUp() {
        if (!dragging) {
            return;
        }

        setDragging(false);

        const max = maxDrag();
        if (max > 0 && dragX >= max * CONFIRM_THRESHOLD) {
            setDragX(max);
            setConfirmed(true);
            onConfirm();
        } else {
            setDragX(0);
        }
    }

    const max = maxDrag();
    const progress = max > 0 ? dragX / max : 0;

    return (
        <div
            ref={trackRef}
            className={cn(
                'bg-secondary relative h-14 w-full touch-none rounded-full select-none',
                disabled && !confirmed && 'opacity-50',
            )}
        >
            <div
                className="bg-primary/15 absolute inset-y-0 left-0 rounded-full"
                style={{
                    width: `${THUMB_SIZE + TRACK_PADDING * 2 + dragX}px`,
                    transition: dragging ? 'none' : 'width 200ms ease',
                }}
            />

            <div className="pointer-events-none absolute inset-0 flex items-center justify-between px-5">
                <span
                    className="text-muted-foreground text-sm font-medium transition-opacity"
                    style={{ opacity: 1 - progress * 1.5 }}
                >
                    {confirmed ? confirmingLabel : label}
                </span>
                <ToIcon
                    className={cn(
                        'text-muted-foreground size-5 transition-opacity',
                        progress > 0.5 && 'text-primary',
                    )}
                />
            </div>

            <div
                onPointerDown={handlePointerDown}
                onPointerMove={handlePointerMove}
                onPointerUp={handlePointerUp}
                onPointerCancel={handlePointerUp}
                className={cn(
                    'bg-primary text-primary-foreground absolute top-1/2 left-1 grid size-12 -translate-y-1/2 place-items-center rounded-full shadow-md',
                    !disabled &&
                        !confirmed &&
                        'cursor-grab active:cursor-grabbing',
                )}
                style={{
                    transform: `translateY(-50%) translateX(${dragX}px)`,
                    transition: dragging ? 'none' : 'transform 200ms ease',
                }}
            >
                {confirmed ? (
                    <Check className="size-5" />
                ) : (
                    <FromIcon className="size-5" />
                )}
            </div>
        </div>
    );
}
