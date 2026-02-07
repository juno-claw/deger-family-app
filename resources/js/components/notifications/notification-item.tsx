import { router } from '@inertiajs/react';
import { useCallback } from 'react';
import { cn, formatRelativeTime } from '@/lib/utils';
import { markAsRead } from '@/actions/App/Http/Controllers/NotificationController';
import type { AppNotification } from '@/types';

interface NotificationItemProps {
    notification: AppNotification;
}

function getNotificationHref(notification: AppNotification): string | null {
    const data = notification.data;
    switch (notification.type) {
        case 'list_shared':
            return data?.list_id ? `/lists/${data.list_id}` : null;
        case 'event_shared':
            return '/calendar';
        case 'note_shared':
            return data?.note_id ? `/notes/${data.note_id}` : null;
        default:
            return null;
    }
}

function getInitial(name?: string): string {
    if (!name) return '?';
    return name
        .split(' ')
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

export function NotificationItem({ notification }: NotificationItemProps) {
    const isUnread = !notification.read_at;
    const href = getNotificationHref(notification);

    const handleClick = useCallback(() => {
        if (isUnread) {
            router.patch(markAsRead.url(notification.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    if (href) {
                        router.visit(href);
                    }
                },
            });
        } else if (href) {
            router.visit(href);
        }
    }, [isUnread, href, notification.id]);

    return (
        <button
            type="button"
            onClick={handleClick}
            className={cn(
                'flex w-full items-start gap-3 rounded-lg p-3 text-left transition-colors hover:bg-accent/50',
                isUnread && 'bg-muted/50',
            )}
        >
            {/* Avatar / Sender Initial */}
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                {getInitial(notification.from_user?.name)}
            </div>

            {/* Content */}
            <div className="min-w-0 flex-1">
                <p className={cn('text-sm', isUnread ? 'font-semibold' : 'font-medium')}>
                    {notification.title}
                </p>
                <p className="mt-0.5 text-sm text-muted-foreground line-clamp-2">
                    {notification.message}
                </p>
                <p className="mt-1 text-xs text-muted-foreground/70">
                    {formatRelativeTime(notification.created_at)}
                </p>
            </div>

            {/* Unread indicator */}
            {isUnread && (
                <div className="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-blue-500" />
            )}
        </button>
    );
}
