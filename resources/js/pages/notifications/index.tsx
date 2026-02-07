import { Head, Link, router } from '@inertiajs/react';
import { BellOff, CheckCheck } from 'lucide-react';
import { useMemo } from 'react';
import Heading from '@/components/heading';
import { NotificationItem } from '@/components/notifications/notification-item';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { markAllAsRead } from '@/actions/App/Http/Controllers/NotificationController';
import type { AppNotification, BreadcrumbItem, PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Benachrichtigungen',
        href: '/notifications',
    },
];

interface Props {
    notifications: PaginatedData<AppNotification>;
}

interface GroupedNotifications {
    today: AppNotification[];
    yesterday: AppNotification[];
    older: AppNotification[];
}

function groupByDate(notifications: AppNotification[]): GroupedNotifications {
    const now = new Date();
    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterdayStart = new Date(todayStart.getTime() - 86400000);

    const groups: GroupedNotifications = {
        today: [],
        yesterday: [],
        older: [],
    };

    for (const notification of notifications) {
        const createdAt = new Date(notification.created_at);
        if (createdAt >= todayStart) {
            groups.today.push(notification);
        } else if (createdAt >= yesterdayStart) {
            groups.yesterday.push(notification);
        } else {
            groups.older.push(notification);
        }
    }

    return groups;
}

function NotificationGroup({
    title,
    notifications,
}: {
    title: string;
    notifications: AppNotification[];
}) {
    if (notifications.length === 0) return null;

    return (
        <div>
            <h3 className="mb-2 px-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                {title}
            </h3>
            <div className="space-y-1">
                {notifications.map((notification) => (
                    <NotificationItem
                        key={notification.id}
                        notification={notification}
                    />
                ))}
            </div>
        </div>
    );
}

export default function NotificationsIndex({ notifications }: Props) {
    const grouped = useMemo(
        () => groupByDate(notifications.data),
        [notifications.data],
    );

    const hasNotifications = notifications.data.length > 0;
    const hasUnread = notifications.data.some((n) => !n.read_at);

    function handleMarkAllAsRead() {
        router.post(markAllAsRead.url(), {}, {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Benachrichtigungen" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <Heading
                        title="Benachrichtigungen"
                        description={
                            hasNotifications
                                ? `${notifications.total} Benachrichtigung${notifications.total !== 1 ? 'en' : ''}`
                                : undefined
                        }
                    />
                    {hasUnread && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleMarkAllAsRead}
                            className="shrink-0"
                        >
                            <CheckCheck className="mr-1.5 h-4 w-4" />
                            Alle gelesen
                        </Button>
                    )}
                </div>

                {/* Notification list */}
                {hasNotifications ? (
                    <div className="space-y-6">
                        <NotificationGroup
                            title="Heute"
                            notifications={grouped.today}
                        />
                        <NotificationGroup
                            title="Gestern"
                            notifications={grouped.yesterday}
                        />
                        <NotificationGroup
                            title="Älter"
                            notifications={grouped.older}
                        />

                        {/* Pagination */}
                        {notifications.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2 pt-4">
                                {notifications.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url ?? '#'}
                                        preserveScroll
                                        className={
                                            link.active
                                                ? 'inline-flex h-9 min-w-9 items-center justify-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground'
                                                : link.url
                                                  ? 'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm text-muted-foreground transition-colors hover:bg-accent'
                                                  : 'inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm text-muted-foreground/40'
                                        }
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                ) : (
                    /* Empty state */
                    <div className="flex flex-1 flex-col items-center justify-center gap-3 py-20 text-center">
                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                            <BellOff className="h-8 w-8 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="text-lg font-medium">
                                Keine Benachrichtigungen
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Wenn jemand etwas mit dir teilt, siehst du es
                                hier.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
