import { cn } from '@/lib/utils';

export interface ChipOption {
    id: number | string;
    label: string;
    icon?: string | null;
}

export function ChipSelect({
    options,
    value,
    onChange,
    wrap = false,
}: {
    options: ChipOption[];
    value: string;
    onChange: (id: string) => void;
    wrap?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex gap-2',
                wrap
                    ? 'flex-wrap'
                    : '[scrollbar-width:none] flex-nowrap overflow-x-auto [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden',
            )}
        >
            {options.map((option) => {
                const selected = value === String(option.id);

                return (
                    <button
                        key={option.id}
                        type="button"
                        onClick={() => onChange(String(option.id))}
                        className={cn(
                            'shrink-0 rounded-full border px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors',
                            selected
                                ? 'bg-primary text-primary-foreground border-primary'
                                : 'bg-secondary text-secondary-foreground hover:bg-muted border-transparent',
                        )}
                    >
                        {option.icon && `${option.icon} `}
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}
