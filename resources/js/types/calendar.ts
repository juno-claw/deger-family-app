import type { User } from './auth';

export interface EventReminder {
    id?: number;
    calendar_event_id?: number;
    user_id: number;
    user?: User;
    type: 'absolute' | 'relative';
    remind_at: string | null;
    minutes_before: number | null;
    sent_at?: string | null;
}

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
    reminders?: EventReminder[];
}
