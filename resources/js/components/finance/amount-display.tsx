import { cn } from '@/lib/utils';

export function AmountDisplay({
    value,
    className,
}: {
    value: string;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'bg-secondary rounded-2xl p-2 text-center',
                className,
            )}
        >
            <p className="text-3xl font-bold tracking-tight">
                ৳{value === '' ? '0' : value}
            </p>
        </div>
    );
}
