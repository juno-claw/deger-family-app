import { useCallback, useEffect, useRef, useState } from 'react';
import { unreadCount as unreadCountRoute } from '@/actions/App/Http/Controllers/NotificationController';

const POLL_INTERVAL_MS = 30000;

export function useNotifications(initialCount: number = 0) {
    const [unreadCount, setUnreadCount] = useState(initialCount);
    const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const refresh = useCallback(() => {
        // Skip polling when the tab is not visible
        if (document.hidden) {
            return;
        }

        fetch(unreadCountRoute.url(), {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((res) => {
                if (res.ok) {
                    return res.json();
                }
                return null;
            })
            .then((data) => {
                if (data && typeof data.count === 'number') {
                    setUnreadCount((prev) =>
                        prev !== data.count ? data.count : prev,
                    );
                }
            })
            .catch(() => {
                // Silently ignore polling errors
            });
    }, []);

    useEffect(() => {
        setUnreadCount(initialCount);
    }, [initialCount]);

    useEffect(() => {
        intervalRef.current = setInterval(refresh, POLL_INTERVAL_MS);

        // Also refresh when the tab becomes visible again
        function handleVisibilityChange() {
            if (!document.hidden) {
                refresh();
            }
        }

        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
    }, [refresh]);

    return { unreadCount, refresh };
}
