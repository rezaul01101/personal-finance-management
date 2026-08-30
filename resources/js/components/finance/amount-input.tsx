import { cn } from '@/lib/utils';

export function AmountInput({
    id = 'amount',
    value,
    onChange,
    autoFocus,
    className,
}: {
    id?: string;
    value: string;
    onChange: (value: string) => void;
    autoFocus?: boolean;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'focus-within:border-ring focus-within:ring-ring/50 flex items-center gap-2 rounded-md border bg-transparent px-3 py-2 focus-within:ring-[3px]',
                className,
            )}
        >
            <span className="text-muted-foreground text-2xl font-semibold">
                ৳
            </span>
            <input
                id={id}
                name={id}
                inputMode="decimal"
                autoFocus={autoFocus}
                placeholder="0"
                value={value}
                onChange={(e) =>
                    onChange(e.target.value.replace(/[^0-9.]/g, ''))
                }
                className="placeholder:text-muted-foreground/40 w-full border-none bg-transparent text-3xl font-semibold outline-none"
            />
        </div>
    );
}
