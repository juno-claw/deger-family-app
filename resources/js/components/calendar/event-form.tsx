import { router, useForm } from '@inertiajs/react';
import { Trash2Icon } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';
import { cn } from '@/lib/utils';
import type { CalendarEvent } from '@/types/calendar';

const COLORS = [
    { value: '#3b82f6', label: 'Blau' },
    { value: '#ec4899', label: 'Pink' },
    { value: '#22c55e', label: 'Grün' },
    { value: '#f59e0b', label: 'Gelb' },
    { value: '#8b5cf6', label: 'Lila' },
    { value: '#ef4444', label: 'Rot' },
    { value: '#6b7280', label: 'Grau' },
];

const RECURRENCE_OPTIONS = [
    { value: 'none', label: 'Keine' },
    { value: 'daily', label: 'Täglich' },
    { value: 'weekly', label: 'Wöchentlich' },
    { value: 'monthly', label: 'Monatlich' },
    { value: 'yearly', label: 'Jährlich' },
];

interface EventFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    event?: CalendarEvent | null;
    defaultDate?: Date;
}

function formatDateForInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDateTimeForInput(date: Date): string {
    const dateStr = formatDateForInput(date);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${dateStr}T${hours}:${minutes}`;
}

function getInitialFormData(event?: CalendarEvent | null, defaultDate?: Date) {
    const initialDate = defaultDate ?? new Date();

    return {
        title: event?.title ?? '',
        description: event?.description ?? '',
        start_at: event
            ? event.all_day
                ? event.start_at.slice(0, 10)
                : event.start_at.slice(0, 16)
            : formatDateForInput(initialDate),
        end_at: event?.end_at
            ? event.all_day
                ? event.end_at.slice(0, 10)
                : event.end_at.slice(0, 16)
            : '',
        all_day: event?.all_day ?? true,
        recurrence: event?.recurrence ?? ('none' as CalendarEvent['recurrence']),
        color: event?.color ?? '',
    };
}

export function EventForm({ open, onOpenChange, event, defaultDate }: EventFormProps) {
    const isEditing = !!event;
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

    const { data, setData, post, put, processing, errors, reset } = useForm(
        getInitialFormData(event, defaultDate),
    );

    function handleSubmit(e: FormEvent) {
        e.preventDefault();

        const submitData = {
            ...data,
            start_at: data.all_day ? `${data.start_at}T00:00:00` : data.start_at,
            end_at: data.end_at
                ? data.all_day
                    ? `${data.end_at}T23:59:59`
                    : data.end_at
                : '',
        };

        const options = {
            data: submitData,
            preserveScroll: true,
            onSuccess: () => {
                onOpenChange(false);
                reset();
            },
        };

        if (isEditing) {
            put(`/calendar/events/${event.id}`, options);
        } else {
            post('/calendar/events', options);
        }
    }

    function handleDelete() {
        if (!event) return;

        router.delete(`/calendar/events/${event.id}`, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    }

    function handleAllDayToggle(checked: boolean) {
        if (checked) {
            setData((prev) => ({
                ...prev,
                all_day: true,
                start_at: prev.start_at.slice(0, 10),
                end_at: prev.end_at ? prev.end_at.slice(0, 10) : '',
            }));
        } else {
            const now = new Date();
            const later = new Date(now.getTime() + 3600000);
            setData((prev) => ({
                ...prev,
                all_day: false,
                start_at: prev.start_at
                    ? `${prev.start_at.slice(0, 10)}T${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`
                    : formatDateTimeForInput(now),
                end_at: prev.end_at
                    ? `${prev.end_at.slice(0, 10)}T${String(later.getHours()).padStart(2, '0')}:${String(later.getMinutes()).padStart(2, '0')}`
                    : formatDateTimeForInput(later),
            }));
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Event bearbeiten' : 'Neues Event'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Ändere die Details des Events.'
                            : 'Erstelle ein neues Kalender-Event.'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Title */}
                    <div className="space-y-2">
                        <Label htmlFor="title">Titel</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="Event-Titel..."
                            autoFocus
                        />
                        <InputError message={errors.title} />
                    </div>

                    {/* Description */}
                    <div className="space-y-2">
                        <Label htmlFor="description">Beschreibung</Label>
                        <Textarea
                            id="description"
                            value={data.description ?? ''}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="Optional..."
                            rows={3}
                        />
                        <InputError message={errors.description} />
                    </div>

                    {/* All Day Toggle */}
                    <div className="flex items-center justify-between">
                        <Label htmlFor="all_day">Ganztägig</Label>
                        <Switch
                            id="all_day"
                            checked={data.all_day}
                            onCheckedChange={handleAllDayToggle}
                        />
                    </div>

                    {/* Start Date/Time */}
                    <div className="space-y-2">
                        <Label htmlFor="start_at">
                            {data.all_day ? 'Startdatum' : 'Start'}
                        </Label>
                        <Input
                            id="start_at"
                            type={data.all_day ? 'date' : 'datetime-local'}
                            value={data.start_at}
                            onChange={(e) => setData('start_at', e.target.value)}
                        />
                        <InputError message={errors.start_at} />
                    </div>

                    {/* End Date/Time */}
                    <div className="space-y-2">
                        <Label htmlFor="end_at">
                            {data.all_day ? 'Enddatum' : 'Ende'}
                        </Label>
                        <Input
                            id="end_at"
                            type={data.all_day ? 'date' : 'datetime-local'}
                            value={data.end_at}
                            onChange={(e) => setData('end_at', e.target.value)}
                        />
                        <InputError message={errors.end_at} />
                    </div>

                    {/* Recurrence */}
                    <div className="space-y-2">
                        <Label>Wiederholung</Label>
                        <Select
                            value={data.recurrence}
                            onValueChange={(value) =>
                                setData('recurrence', value as CalendarEvent['recurrence'])
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {RECURRENCE_OPTIONS.map((opt) => (
                                    <SelectItem key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.recurrence} />
                    </div>

                    {/* Color */}
                    <div className="space-y-2">
                        <Label>Farbe</Label>
                        <div className="flex flex-wrap gap-2">
                            {COLORS.map((c) => (
                                <button
                                    key={c.value}
                                    type="button"
                                    title={c.label}
                                    aria-label={`Farbe ${c.label}${data.color === c.value ? ' (ausgewaehlt)' : ''}`}
                                    onClick={() =>
                                        setData('color', data.color === c.value ? '' : c.value)
                                    }
                                    className={cn(
                                        'size-11 rounded-full border-2 transition-all',
                                        data.color === c.value
                                            ? 'border-foreground scale-110'
                                            : 'border-transparent hover:scale-105',
                                    )}
                                    style={{ backgroundColor: c.value }}
                                />
                            ))}
                        </div>
                        <InputError message={errors.color} />
                    </div>

                    <DialogFooter className="gap-2">
                        {isEditing && (
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                onClick={() => setDeleteDialogOpen(true)}
                                className="mr-auto"
                                aria-label="Event loeschen"
                            >
                                <Trash2Icon className="size-4" />
                                Löschen
                            </Button>
                        )}
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Abbrechen
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing
                                ? 'Speichern...'
                                : isEditing
                                    ? 'Speichern'
                                    : 'Erstellen'}
                        </Button>
                    </DialogFooter>
                </form>

                <ConfirmDialog
                    open={deleteDialogOpen}
                    onOpenChange={setDeleteDialogOpen}
                    onConfirm={handleDelete}
                    title="Event loeschen?"
                    description="Das Event wird unwiderruflich geloescht."
                    confirmLabel="Loeschen"
                />
            </DialogContent>
        </Dialog>
    );
}
