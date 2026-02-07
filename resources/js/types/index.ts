export type * from './auth';
export type * from './models';
export type * from './navigation';
export type * from './ui';

import type { Auth, User } from './auth';

export type SharedData = {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    notificationCount: number;
    [key: string]: unknown;
};

export interface AppNotification {
    id: number;
    user_id: number;
    from_user_id: number | null;
    type: string;
    title: string;
    message: string;
    data: Record<string, unknown> | null;
    read_at: string | null;
    from_user?: User;
    created_at: string;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}
