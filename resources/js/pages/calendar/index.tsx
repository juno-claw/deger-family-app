import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Plus } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { DayDetailSheet } from '@/components/calendar/day-detail-sheet';
import { EventForm } from '@/components/calendar/event-form';
import { MonthView, getEventsForDay } from '@/components/calendar/month-view';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, User } from '@/types';
import type { CalendarEvent } from '@/types/calendar';

const MONTH_NAMES = [
    'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
    'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember',
];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kalender',
        href: '/calendar',
    },
];

interface Props {
    events: CalendarEvent[];
    users: User[];
    month: number;
    year: number;
}

export default function CalendarIndex({ events, users, month, year }: Props) {
    const [selectedDate, setSelectedDate] = useState<Date | null>(null);
    const [daySheetOpen, setDaySheetOpen] = useState(false);
    const [eventFormOpen, setEventFormOpen] = useState(false);
    const [editingEvent, setEditingEvent] = useState<CalendarEvent | null>(null);

    const selectedDayEvents = useMemo(() => {
        if (!selectedDate) return [];
        return getEventsForDay(events, selectedDate);
    }, [events, selectedDate]);

    const navigateMonth = useCallback(
        (direction: -1 | 1) => {
            let newMonth = month + direction;
            let newYear = year;
            if (newMonth < 1) {
                newMonth = 12;
                newYear -= 1;
            } else if (newMonth > 12) {
                newMonth = 1;
                newYear += 1;
            }
            router.visit(`/calendar?month=${newMonth}&year=${newYear}`, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        [month, year],
    );

    const goToToday = useCallback(() => {
        const now = new Date();
        router.visit(
            `/calendar?month=${now.getMonth() + 1}&year=${now.getFullYear()}`,
            { preserveState: true, preserveScroll: true },
        );
    }, []);

    function handleDayClick(date: Date) {
        setSelectedDate(date);
        setDaySheetOpen(true);
    }

    function handleCreateEvent() {
        setEditingEvent(null);
        setDaySheetOpen(false);
        setEventFormOpen(true);
    }

    function handleEditEvent(event: CalendarEvent) {
        setEditingEvent(event);
        setDaySheetOpen(false);
        setEventFormOpen(true);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kalender" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
                {/* Month navigation header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => navigateMonth(-1)}
                            title="Vorheriger Monat"
                        >
                            <ChevronLeft className="size-5" />
                        </Button>
                        <h1 className="text-xl font-semibold tracking-tight">
                            {MONTH_NAMES[month - 1]} {year}
                        </h1>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => navigateMonth(1)}
                            title="Nächster Monat"
                        >
                            <ChevronRight className="size-5" />
                        </Button>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={goToToday}
                        >
                            Heute
                        </Button>
                    </div>
                </div>

                {/* Month grid */}
                <MonthView
                    month={month}
                    year={year}
                    events={events}
                    onDayClick={handleDayClick}
                />

                {/* FAB for creating event */}
                <Button
                    className="fixed right-6 bottom-24 z-40 size-14 rounded-full shadow-lg md:bottom-8"
                    size="icon"
                    onClick={handleCreateEvent}
                    title="Neues Event"
                >
                    <Plus className="size-6" />
                </Button>
            </div>

            {/* Day detail sheet */}
            <DayDetailSheet
                open={daySheetOpen}
                onOpenChange={setDaySheetOpen}
                date={selectedDate}
                events={selectedDayEvents}
                onCreateEvent={handleCreateEvent}
                onEditEvent={handleEditEvent}
            />

            {/* Event create/edit form */}
            <EventForm
                open={eventFormOpen}
                onOpenChange={setEventFormOpen}
                event={editingEvent}
                defaultDate={selectedDate ?? undefined}
            />
        </AppLayout>
    );
}
