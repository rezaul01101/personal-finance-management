export function TopCategoryList({
    items,
}: {
    items: { label: string; amount: string; percentage: number }[];
}) {
    return (
        <div className="space-y-4">
            {items.map((item) => (
                <div key={item.label}>
                    <div className="mb-1.5 flex items-center justify-between text-sm">
                        <span className="font-medium">{item.label}</span>
                        <span className="text-muted-foreground">
                            ৳{item.amount}
                        </span>
                    </div>
                    <div className="bg-muted h-2 overflow-hidden rounded-full">
                        <div
                            className="bg-primary h-full rounded-full"
                            style={{
                                width: `${Math.min(Math.max(item.percentage, 0), 100)}%`,
                            }}
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}
