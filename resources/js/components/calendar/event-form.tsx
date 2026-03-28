import { router, useForm, usePage } from '@inertiajs/react';
import { BellIcon, PlusIcon, Trash2Icon, XIcon } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { SharedData, User } from '@/types';
import type { CalendarEvent, EventReminder } from '@/types/calendar';

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

const RELATIVE_PRESETS = [
    { value: 15, label: '15 Minuten vorher' },
    { value: 30, label: '30 Minuten vorher' },
    { value: 60, label: '1 Stunde vorher' },
    { value: 120, label: '2 Stunden vorher' },
    { value: 1440, label: '1 Tag vorher' },
    { value: 2880, label: '2 Tage vorher' },
    { value: 10080, label: '1 Woche vorher' },
];

interface ReminderFormData {
    type: 'absolute' | 'relative';
    minutes_before: number | null;
    remind_at: string;
    user_ids: number[];
}

interface EventFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    event?: CalendarEvent | null;
    defaultDate?: Date;
    users?: User[];
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

function groupRemindersForForm(reminders: EventReminder[]): ReminderFormData[] {
    const groups = new Map<string, ReminderFormData>();

    for (const r of reminders) {
        const key = r.type === 'relative'
            ? `relative-${r.minutes_before}`
            : `absolute-${r.remind_at}`;

        const existing = groups.get(key);
        if (existing) {
            if (!existing.user_ids.includes(r.user_id)) {
                existing.user_ids.push(r.user_id);
            }
        } else {
            groups.set(key, {
                type: r.type,
                minutes_before: r.minutes_before,
                remind_at: r.remind_at?.slice(0, 16) ?? '',
                user_ids: [r.user_id],
            });
        }
    }

    return Array.from(groups.values());
}

function getInitialFormData(event?: CalendarEvent | null, defaultDate?: Date, currentUserId?: number) {
    const initialDate = defaultDate ?? new Date();

    const existingReminders = event?.reminders
        ? groupRemindersForForm(event.reminders.filter((r) => !r.sent_at))
        : [];

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
        reminders: existingReminders as ReminderFormData[],
    };
}

export function EventForm({ open, onOpenChange, event, defaultDate, users = [] }: EventFormProps) {
    const isEditing = !!event;
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const { auth } = usePage<SharedData>().props;
    const currentUser = auth.user;

    const allUsers: User[] = [currentUser, ...users.filter((u) => u.id !== currentUser.id)];

    const { data, setData, post, put, processing, errors, reset } = useForm(
        getInitialFormData(event, defaultDate, currentUser.id),
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

    function addReminder() {
        setData('reminders', [
            ...data.reminders,
            {
                type: 'relative' as const,
                minutes_before: 60,
                remind_at: '',
                user_ids: allUsers.map((u) => u.id),
            },
        ]);
    }

    function removeReminder(index: number) {
        setData(
            'reminders',
            data.reminders.filter((_, i) => i !== index),
        );
    }

    function updateReminder(index: number, updates: Partial<ReminderFormData>) {
        setData(
            'reminders',
            data.reminders.map((r, i) => (i === index ? { ...r, ...updates } : r)),
        );
    }

    function toggleReminderUser(index: number, userId: number) {
        const reminder = data.reminders[index];
        const userIds = reminder.user_ids.includes(userId)
            ? reminder.user_ids.filter((id) => id !== userId)
            : [...reminder.user_ids, userId];

        if (userIds.length > 0) {
            updateReminder(index, { user_ids: userIds });
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

                    <div className="flex items-center justify-between">
                        <Label htmlFor="all_day">Ganztägig</Label>
                        <Switch
                            id="all_day"
                            checked={data.all_day}
                            onCheckedChange={handleAllDayToggle}
                        />
                    </div>

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

                    {/* Reminders */}
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <Label className="flex items-center gap-1.5">
                                <BellIcon className="size-4" />
                                Erinnerungen
                            </Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addReminder}
                            >
                                <PlusIcon className="mr-1 size-3.5" />
                                Hinzufügen
                            </Button>
                        </div>

                        {data.reminders.length === 0 && (
                            <p className="text-muted-foreground text-sm">
                                Keine Erinnerungen gesetzt.
                            </p>
                        )}

                        {data.reminders.map((reminder, idx) => (
                            <div
                                key={idx}
                                className="bg-muted/50 space-y-3 rounded-lg border p-3"
                            >
                                <div className="flex items-start justify-between">
                                    <Select
                                        value={reminder.type}
                                        onValueChange={(value: 'absolute' | 'relative') => {
                                            updateReminder(idx, {
                                                type: value,
                                                minutes_before: value === 'relative' ? 60 : null,
                                                remind_at: '',
                                            });
                                        }}
                                    >
                                        <SelectTrigger className="w-[160px]">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="relative">Relativ</SelectItem>
                                            <SelectItem value="absolute">Absolut</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="size-8"
                                        onClick={() => removeReminder(idx)}
                                    >
                                        <XIcon className="size-4" />
                                    </Button>
                                </div>

                                {reminder.type === 'relative' ? (
                                    <Select
                                        value={String(reminder.minutes_before ?? 60)}
                                        onValueChange={(value) =>
                                            updateReminder(idx, {
                                                minutes_before: parseInt(value, 10),
                                            })
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {RELATIVE_PRESETS.map((preset) => (
                                                <SelectItem
                                                    key={preset.value}
                                                    value={String(preset.value)}
                                                >
                                                    {preset.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : (
                                    <Input
                                        type="datetime-local"
                                        value={reminder.remind_at}
                                        onChange={(e) =>
                                            updateReminder(idx, { remind_at: e.target.value })
                                        }
                                    />
                                )}

                                <div className="space-y-1.5">
                                    <span className="text-muted-foreground text-xs font-medium">
                                        Empfänger
                                    </span>
                                    <div className="flex flex-wrap gap-3">
                                        {allUsers.map((user) => (
                                            <label
                                                key={user.id}
                                                className="flex cursor-pointer items-center gap-1.5 text-sm"
                                            >
                                                <Checkbox
                                                    checked={reminder.user_ids.includes(user.id)}
                                                    onCheckedChange={() =>
                                                        toggleReminderUser(idx, user.id)
                                                    }
                                                />
                                                {user.name}
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        ))}
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
