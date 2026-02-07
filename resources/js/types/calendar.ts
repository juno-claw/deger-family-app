import type { User } from './auth';

export interface CalendarEvent {
    id: number;
    title: string;
    description: string | null;
    start_at: string;
    end_at: string | null;
    all_day: boolean;
    recurrence: 'none' | 'daily' | 'weekly' | 'monthly' | 'yearly';
    color: string | null;
    owner_id: number;
    owner?: User;
    shared_with?: (User & { pivot: { status: string } })[];
}
