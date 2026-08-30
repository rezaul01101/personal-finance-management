import { cn } from '@/lib/utils';

export type BudgetHealth = 'healthy' | 'warning' | 'exceeded';

export function budgetHealth(usagePercentage: number): BudgetHealth {
    if (usagePercentage > 100) {
        return 'exceeded';
    }

    if (usagePercentage >= 80) {
        return 'warning';
    }

    return 'healthy';
}

const HEALTH_BAR_CLASSES: Record<BudgetHealth, string> = {
    healthy: 'bg-chart-3',
    warning: 'bg-chart-4',
    exceeded: 'bg-destructive',
};

export function ProgressBar({
    percentage,
    health,
    className,
}: {
    percentage: number;
    health: BudgetHealth;
    className?: string;
}) {
    const width = Math.min(Math.max(percentage, 0), 100);

    return (
        <div
            className={cn(
                'bg-muted h-2 w-full overflow-hidden rounded-full',
                className,
            )}
        >
            <div
                className={cn(
                    'h-full rounded-full transition-[width]',
                    HEALTH_BAR_CLASSES[health],
                )}
                style={{ width: `${width}%` }}
            />
        </div>
    );
}
