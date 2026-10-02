import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { User } from '@/types';

export function UserInfo({
    user,
    showEmail = false,
    nameClassName,
    avatarClassName,
}: {
    user: User;
    showEmail?: boolean;
    nameClassName?: string;
    avatarClassName?: string;
}) {
    const getInitials = useInitials();

    return (
        <>
            <Avatar className={cn('h-8 w-8 overflow-hidden rounded-full', avatarClassName)}>
                <AvatarImage src={user.avatar} alt={user.name} />
                <AvatarFallback className="bg-primary rounded-full text-white">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div
                className={cn(
                    'grid flex-1 text-left text-sm leading-tight',
                    nameClassName,
                )}
            >
                <span className="truncate font-medium">{user.name}</span>
                {showEmail && (
                    <span className="text-muted-foreground truncate text-xs">
                        {user.email}
                    </span>
                )}
            </div>
        </>
    );
}
