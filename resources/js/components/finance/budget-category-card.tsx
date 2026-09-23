import { Link } from '@inertiajs/react';
import { budgetHealth, ProgressBar } from '@/components/finance/progress-bar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import budgetCategories from '@/routes/budget-categories';
import type { BudgetSummary } from '@/types/finance';

export function BudgetCategoryCard({
    category,
    summary,
    year,
    month,
}: {
    category: { id: number; name: string; icon: string | null };
    summary: BudgetSummary;
    year: number;
    month: number;
}) {
    const health = budgetHealth(summary.usage_percentage);

    return (
        <Link
            href={budgetCategories.show.url(category.id, {
                query: { year, month },
            })}
        >
            <Card className="transition-shadow hover:shadow-md">
                <CardHeader className="flex-row items-center justify-between">
                    <div className="flex items-center gap-2">
                        {category.icon && <span>{category.icon}</span>}
                        <span className="font-semibold">{category.name}</span>
                    </div>
                    {health === 'exceeded' && (
                        <Badge variant="destructive">Exceeded</Badge>
                    )}
                    {health === 'warning' && (
                        <Badge className="bg-chart-4 text-white">Warning</Badge>
                    )}
                </CardHeader>
                <CardContent className="space-y-3">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-2xl font-bold">
                                ৳{summary.used_amount}{' '}
                                <span className="text-muted-foreground text-sm font-normal">
                                    used
                                </span>
                            </p>
                            <p className="text-muted-foreground text-sm">
                                of ৳{summary.budget_amount}
                            </p>
                        </div>

                        {summary.is_exceeded ? (
                            <p className="text-destructive text-right text-sm font-semibold">
                                ৳{summary.over_budget_amount} over
                            </p>
                        ) : (
                            <div className="grid grid-cols-[auto_auto] gap-x-1.5 gap-y-0.5 text-sm">
                                <span className="text-muted-foreground">
                                    Daily Safe Spend
                                </span>
                                <b className="text-foreground">
                                    ৳{summary.daily_safe_spend}/day
                                </b>
                                <span className="text-muted-foreground">
                                    Available
                                </span>
                                <b className="text-foreground">
                                    ৳{summary.available_amount}
                                </b>
                            </div>
                        )}
                    </div>

                    <ProgressBar
                        percentage={summary.usage_percentage}
                        health={health}
                    />
                </CardContent>
            </Card>
        </Link>
    );
}
