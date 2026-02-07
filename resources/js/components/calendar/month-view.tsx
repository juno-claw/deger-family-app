import { useMemo, type ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { EventBadge } from './event-badge';
import type { CalendarEvent } from '@/types/calendar';

interface MonthViewProps {
    month: number;
    year: number;
    events: CalendarEvent[];
    onDayClick: (date: Date) => void;
}

const WEEKDAY_LABELS = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

function getMonthDays(year: number, month: number): (Date | null)[] {
    const firstDay = new Date(year, month - 1, 1);
    const lastDay = new Date(year, month, 0);
    const daysInMonth = lastDay.getDate();

    // Monday = 0, Sunday = 6
    let startDow = firstDay.getDay() - 1;
    if (startDow < 0) startDow = 6;

    const cells: (Date | null)[] = [];

    // Leading empty cells
    for (let i = 0; i < startDow; i++) {
        const prevDate = new Date(year, month - 1, -startDow + i + 1);
        cells.push(prevDate);
    }

    // Days of the month
    for (let d = 1; d <= daysInMonth; d++) {
        cells.push(new Date(year, month - 1, d));
    }

    // Trailing cells to fill grid to complete weeks
    const remaining = 7 - (cells.length % 7);
    if (remaining < 7) {
        for (let i = 1; i <= remaining; i++) {
            cells.push(new Date(year, month, i));
        }
    }

    return cells;
}

function isSameDay(a: Date, b: Date): boolean {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

function isToday(date: Date): boolean {
    return isSameDay(date, new Date());
}

function isCurrentMonth(date: Date, month: number, year: number): boolean {
    return date.getMonth() === month - 1 && date.getFullYear() === year;
}

function getEventsForDay(events: CalendarEvent[], date: Date): CalendarEvent[] {
    return events.filter((event) => {
        const start = new Date(event.start_at);
        const end = event.end_at ? new Date(event.end_at) : start;

        const dayStart = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const dayEnd = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 23, 59, 59);

        return start <= dayEnd && end >= dayStart;
    });
}

export function MonthView({ month, year, events, onDayClick }: MonthViewProps) {
    const days = useMemo(() => getMonthDays(year, month), [year, month]);

    const eventsByDay = useMemo(() => {
        const map = new Map<string, CalendarEvent[]>();
        for (const day of days) {
            if (!day) continue;
            const key = `${day.getFullYear()}-${day.getMonth()}-${day.getDate()}`;
            map.set(key, getEventsForDay(events, day));
        }
        return map;
    }, [days, events]);

    return (
        <div className="w-full select-none">
            {/* Weekday header */}
            <div className="grid grid-cols-7 mb-1">
                {WEEKDAY_LABELS.map((label) => (
                    <div
                        key={label}
                        className="text-center text-xs font-medium text-muted-foreground py-2"
                    >
                        {label}
                    </div>
                ))}
            </div>

            {/* Day grid */}
            <div className="grid grid-cols-7">
                {days.map((date, index) => {
                    if (!date) return <div key={index} />;

                    const inMonth = isCurrentMonth(date, month, year);
                    const today = isToday(date);
                    const key = `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
                    const dayEvents = eventsByDay.get(key) ?? [];

                    return (
                        <DayCell
                            key={index}
                            date={date}
                            inMonth={inMonth}
                            today={today}
                            events={dayEvents}
                            onClick={() => onDayClick(date)}
                        />
                    );
                })}
            </div>
        </div>
    );
}

interface DayCellProps {
    date: Date;
    inMonth: boolean;
    today: boolean;
    events: CalendarEvent[];
    onClick: () => void;
}

function DayCell({ date, inMonth, today, events, onClick }: DayCellProps) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'flex flex-col items-center justify-start min-h-[44px] py-1.5 rounded-lg transition-colors',
                'hover:bg-accent/50 active:bg-accent',
                !inMonth && 'opacity-30',
            )}
        >
            <span
                className={cn(
                    'flex items-center justify-center text-sm w-7 h-7 rounded-full',
                    today && 'bg-primary text-primary-foreground font-bold',
                    !today && inMonth && 'text-foreground',
                )}
            >
                {date.getDate()}
            </span>

            {/* Event dots */}
            {events.length > 0 && (
                <div className="flex items-center justify-center gap-0.5 mt-0.5 flex-wrap max-w-[90%]">
                    {events.slice(0, 3).map((event) => (
                        <EventBadge
                            key={event.id}
                            color={event.color ?? '#6b7280'}
                            title={event.title}
                            compact
                        />
                    ))}
                    {events.length > 3 && (
                        <span className="text-[9px] text-muted-foreground leading-none">
                            +{events.length - 3}
                        </span>
                    )}
                </div>
            )}
        </button>
    );
}

export { getEventsForDay };
