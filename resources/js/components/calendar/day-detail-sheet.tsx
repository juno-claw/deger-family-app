import { ClockIcon, PlusIcon, SunIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { EventBadge } from './event-badge';
import type { CalendarEvent } from '@/types/calendar';

interface DayDetailSheetProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    date: Date | null;
    events: CalendarEvent[];
    onCreateEvent: () => void;
    onEditEvent: (event: CalendarEvent) => void;
}

function formatTime(dateStr: string): string {
    const date = new Date(dateStr);
    return date.toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDate(date: Date): string {
    return date.toLocaleDateString('de-DE', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export function DayDetailSheet({
    open,
    onOpenChange,
    date,
    events,
    onCreateEvent,
    onEditEvent,
}: DayDetailSheetProps) {
    if (!date) return null;

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="bottom" className="max-h-[70vh] rounded-t-xl">
                <SheetHeader>
                    <SheetTitle>{formatDate(date)}</SheetTitle>
                    <SheetDescription>
                        {events.length === 0
                            ? 'Keine Events an diesem Tag.'
                            : `${events.length} Event${events.length !== 1 ? 's' : ''}`}
                    </SheetDescription>
                </SheetHeader>

                <div className="flex-1 overflow-y-auto px-4 pb-4">
                    {events.length > 0 && (
                        <div className="space-y-2">
                            {events.map((event) => (
                                <button
                                    key={event.id}
                                    type="button"
                                    onClick={() => onEditEvent(event)}
                                    className="flex w-full items-start gap-3 rounded-lg border p-3 text-left transition-colors hover:bg-accent/50 active:bg-accent"
                                >
                                    <EventBadge
                                        color={event.color ?? '#6b7280'}
                                        compact
                                        className="mt-1.5 size-2.5"
                                    />
                                    <div className="flex-1 min-w-0">
                                        <p className="font-medium text-sm truncate">
                                            {event.title}
                                        </p>
                                        <div className="flex items-center gap-1 text-xs text-muted-foreground mt-0.5">
                                            {event.all_day ? (
                                                <>
                                                    <SunIcon className="size-3" />
                                                    <span>Ganztägig</span>
                                                </>
                                            ) : (
                                                <>
                                                    <ClockIcon className="size-3" />
                                                    <span>
                                                        {formatTime(event.start_at)}
                                                        {event.end_at &&
                                                            ` – ${formatTime(event.end_at)}`}
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                        {event.description && (
                                            <p className="text-xs text-muted-foreground mt-1 line-clamp-2">
                                                {event.description}
                                            </p>
                                        )}
                                        {event.owner && (
                                            <p className="text-[11px] text-muted-foreground/70 mt-1">
                                                von {event.owner.name}
                                            </p>
                                        )}
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}

                    <Button
                        variant="outline"
                        className="mt-4 w-full"
                        onClick={onCreateEvent}
                    >
                        <PlusIcon className="size-4" />
                        Neues Event
                    </Button>
                </div>
            </SheetContent>
        </Sheet>
    );
}
