import type { LucideIcon } from 'lucide-react';

export function IconActionButton({
    icon: Icon,
    label,
    thumbnail,
    badge,
    onClick,
}: {
    icon: LucideIcon;
    label: string;
    thumbnail?: string;
    badge?: number;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="bg-secondary relative flex h-12 flex-col items-center justify-center gap-0.5 overflow-hidden rounded-xl"
        >
            {thumbnail ? (
                <img
                    src={thumbnail}
                    alt=""
                    className="absolute inset-0 size-full object-cover"
                />
            ) : (
                <>
                    <Icon className="size-4" />
                    <span className="max-w-full truncate px-1 text-[10px] font-medium">
                        {label}
                    </span>
                </>
            )}
            {thumbnail && badge && badge > 0 && (
                <span className="bg-primary text-primary-foreground absolute right-1 bottom-1 grid size-5 place-items-center rounded-full text-[10px] font-bold">
                    +{badge}
                </span>
            )}
        </button>
    );
}
