import { Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useNotifications } from '@/hooks/use-notifications';
import { cn } from '@/lib/utils';
import { index } from '@/actions/App/Http/Controllers/NotificationController';
import type { SharedData } from '@/types';

export function NotificationBell() {
    const { notificationCount } = usePage<SharedData>().props;
    const { unreadCount } = useNotifications(notificationCount);

    return (
        <Link
            href={index.url()}
            className="relative inline-flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            aria-label={`Benachrichtigungen${unreadCount > 0 ? ` (${unreadCount} ungelesen)` : ''}`}
        >
            <Bell className="h-5 w-5" />
            {unreadCount > 0 && (
                <span
                    className={cn(
                        'absolute right-0.5 top-0.5 flex items-center justify-center rounded-full bg-destructive text-[10px] font-bold text-white',
                        unreadCount > 9
                            ? 'h-4 min-w-4 px-0.5'
                            : 'h-4 w-4',
                    )}
                >
                    {unreadCount > 99 ? '99+' : unreadCount}
                </span>
            )}
        </Link>
    );
}
