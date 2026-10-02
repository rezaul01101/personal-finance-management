import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import expenses from '@/routes/expenses';

export interface TopCategory {
    id: number;
    label: string;
    amount: string;
    percentage: number;
}

const DOT_COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
    'var(--muted-foreground)',
];

const RING = 72;
const STROKE = 10;

/** Donut showing a category's share of spending; the arc starts at 12 o'clock. */
function ShareRing({ percentage }: { percentage: number }) {
    const radius = (RING - STROKE) / 2;
    const circumference = 2 * Math.PI * radius;
    const pct = Math.max(0, Math.min(1, percentage / 100));

    return (
        <div
            className="relative grid shrink-0 place-items-center"
            style={{ width: RING, height: RING }}
        >
            <svg
                width={RING}
                height={RING}
                className="absolute -rotate-90"
                aria-hidden
            >
                <circle
                    cx={RING / 2}
                    cy={RING / 2}
                    r={radius}
                    stroke="var(--muted)"
                    strokeWidth={STROKE}
                    fill="none"
                />
                <circle
                    cx={RING / 2}
                    cy={RING / 2}
                    r={radius}
                    stroke="var(--chart-1)"
                    strokeWidth={STROKE}
                    fill="none"
                    strokeLinecap="round"
                    strokeDasharray={`${circumference * pct} ${circumference}`}
                />
            </svg>
            <span className="text-sm font-bold">{Math.round(percentage)}%</span>
        </div>
    );
}

/** The month's top three expense categories: the biggest full width with a share ring, the next two side by side. */
export function TopCategoryList({
    items,
    year,
    month,
}: {
    items: TopCategory[];
    year: number;
    month: number;
}) {
    const top = items.slice(0, 3);
    const mm = String(month).padStart(2, '0');
    const lastDay = String(new Date(year, month, 0).getDate()).padStart(2, '0');

    const tile = (item: TopCategory, index: number) => (
        <Link
            key={item.id}
            href={expenses.index.url({
                query: {
                    expense_category_id: item.id,
                    date_from: `${year}-${mm}-01`,
                    date_to: `${year}-${mm}-${lastDay}`,
                },
            })}
            className={cn(
                'glass flex items-center justify-between gap-3 rounded-3xl p-3.5 transition-colors hover:bg-white/70',
                index === 0 ? 'col-span-2' : 'col-span-1',
            )}
        >
            <div className="flex min-w-0 flex-1 flex-col gap-1">
                <div className="flex items-center gap-2">
                    <span
                        className="size-2.5 shrink-0 rounded-full"
                        style={{ backgroundColor: DOT_COLORS[index] }}
                    />
                    <span className="truncate text-sm font-bold">
                        {item.label}
                    </span>
                </div>
                <p className="truncate text-[22px] leading-[30px] font-extrabold">
                    ৳{item.amount}
                </p>
                <p className="text-muted-foreground truncate text-xs">
                    {Math.round(item.percentage)}% of spending
                </p>
            </div>
            {index === 0 && <ShareRing percentage={item.percentage} />}
        </Link>
    );

    return <div className="grid grid-cols-2 gap-3">{top.map(tile)}</div>;
}
