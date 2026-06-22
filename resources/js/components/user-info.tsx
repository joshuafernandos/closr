import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { Business, User } from '@/types';

export function UserInfo({
    user,
    showEmail = false,
    business = null,
}: {
    user: User;
    showEmail?: boolean;
    business?: Business | null;
}) {
    const getInitials = useInitials();
    const showAvatar = Boolean(user.avatar && user.avatar !== '');

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-lg">
                {showAvatar ? (
                    <AvatarImage src={user.avatar} alt={user.name} />
                ) : null}
                <AvatarFallback className="rounded-lg text-black dark:text-white">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">{user.name}</span>
                {business ? (
                    <span className="truncate text-xs text-muted-foreground">
                        {business.name}
                    </span>
                ) : null}
                {!business && showEmail ? (
                    <span className="truncate text-xs text-muted-foreground">
                        {user.email}
                    </span>
                ) : null}
            </div>
        </>
    );
}
