import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

const MONTH_NAMES = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

export function MonthSelector({
    year,
    month,
    buildHref,
}: {
    year: number;
    month: number;
    buildHref: (year: number, month: number) => string;
}) {
    const prev =
        month === 1
            ? { year: year - 1, month: 12 }
            : { year, month: month - 1 };
    const next =
        month === 12
            ? { year: year + 1, month: 1 }
            : { year, month: month + 1 };

    return (
        <div className="flex items-center gap-2">
            <Link
                href={buildHref(prev.year, prev.month)}
                preserveScroll
                className="hover:bg-accent rounded-md p-1.5"
            >
                <ChevronLeft className="size-5" />
            </Link>
            <span className="min-w-40 text-center text-lg font-semibold">
                {MONTH_NAMES[month - 1]} {year}
            </span>
            <Link
                href={buildHref(next.year, next.month)}
                preserveScroll
                className="hover:bg-accent rounded-md p-1.5"
            >
                <ChevronRight className="size-5" />
            </Link>
        </div>
    );
}
