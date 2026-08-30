import { Delete } from 'lucide-react';
import { cn } from '@/lib/utils';

const KEYS = [
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '.',
    '0',
    'backspace',
] as const;

export function applyKeypadKey(current: string, key: string): string {
    if (key === 'backspace') {
        return current.slice(0, -1);
    }

    if (key === '.') {
        if (current.includes('.')) {
            return current;
        }

        return current === '' ? '0.' : current + '.';
    }

    const decimals = current.split('.')[1];
    if (decimals && decimals.length >= 2) {
        return current;
    }

    if (current === '0') {
        return key;
    }

    return current + key;
}

export function NumericKeypad({
    value,
    onChange,
    className,
}: {
    value: string;
    onChange: (value: string) => void;
    className?: string;
}) {
    return (
        <div className={cn('grid grid-cols-3 gap-1.5', className)}>
            {KEYS.map((key) => (
                <button
                    key={key}
                    type="button"
                    onClick={() => onChange(applyKeypadKey(value, key))}
                    className="bg-secondary text-secondary-foreground active:bg-muted flex h-11 items-center justify-center rounded-xl text-lg font-semibold transition-transform active:scale-95"
                >
                    {key === 'backspace' ? <Delete className="size-5" /> : key}
                </button>
            ))}
        </div>
    );
}
