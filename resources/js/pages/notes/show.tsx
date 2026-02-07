import { Head, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Check, Loader2, Palette, Pin, PinOff, Share2, Trash2 } from 'lucide-react';
import { useCallback, useState } from 'react';
import ColorPicker from '@/components/notes/color-picker';
import type { Note } from '@/components/notes/note-card';
import RichTextEditor from '@/components/notes/rich-text-editor';
import ShareDialog from '@/components/notes/share-dialog';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAutosave } from '@/hooks/use-autosave';
import AppLayout from '@/layouts/app-layout';
import { getContrastTextColor } from '@/lib/utils';
import type { BreadcrumbItem, User } from '@/types';

interface Props {
    note: Note;
    users: User[];
}

export default function NoteShow({ note, users }: Props) {
    const { auth } = usePage().props as { auth: { user: User } };
    const isOwner = note.owner_id === auth.user.id;
    const sharedUser = note.shared_with?.find((u) => u.id === auth.user.id);
    const canEdit = isOwner || sharedUser?.pivot.permission === 'edit';

    const [title, setTitle] = useState(note.title);
    const [content, setContent] = useState(note.content ?? '');
    const [color, setColor] = useState(note.color);
    const [shareOpen, setShareOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const textColor = getContrastTextColor(color);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Notizen', href: '/notes' },
        { title: note.title || 'Notiz', href: `/notes/${note.id}` },
    ];

    const { isSaving, lastSaved, error: saveError, save } = useAutosave({
        endpoint: `/notes/${note.id}`,
        method: 'PUT',
        debounceMs: 2000,
    });

    const handleSave = useCallback(
        (newTitle: string, newContent: string, newColor: string | null) => {
            if (!canEdit) {
                return;
            }
            save({ title: newTitle, content: newContent, color: newColor });
        },
        [canEdit, save],
    );

    function handleTitleChange(e: React.ChangeEvent<HTMLInputElement>) {
        const newTitle = e.target.value;
        setTitle(newTitle);
        handleSave(newTitle, content, color);
    }

    function handleContentChange(html: string) {
        setContent(html);
        handleSave(title, html, color);
    }

    function handleColorChange(newColor: string | null) {
        setColor(newColor);
        handleSave(title, content, newColor);
    }

    function handlePin() {
        router.patch(`/notes/${note.id}/pin`, {}, { preserveScroll: true });
    }

    function handleDelete() {
        router.delete(`/notes/${note.id}`);
    }

    function handleBack() {
        router.visit('/notes');
    }

    // Saved indicator text
    const savedText = (() => {
        if (isSaving) {
            return null;
        }
        if (lastSaved) {
            return 'Gespeichert';
        }
        return null;
    })();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={note.title || 'Notiz'} />
            <div
                className="flex h-full flex-1 flex-col"
                style={color ? { backgroundColor: color } : undefined}
            >
                {/* Toolbar */}
                <div
                    className="flex items-center justify-between border-b px-4 py-2"
                    style={textColor ? { color: textColor } : undefined}
                >
                    <div className="flex items-center gap-1">
                        <Button variant="ghost" size="icon" onClick={handleBack} aria-label="Zurueck zu Notizen">
                            <ArrowLeft className="size-5" />
                        </Button>
                    </div>

                    <div className="flex items-center gap-1">
                        {/* Save indicator */}
                        <div className="mr-2 flex items-center gap-1 text-xs opacity-60">
                            {isSaving && (
                                <>
                                    <Loader2 className="size-3 animate-spin" />
                                    <span>Speichert...</span>
                                </>
                            )}
                            {saveError && !isSaving && (
                                <span className="text-destructive font-medium opacity-100">{saveError}</span>
                            )}
                            {savedText && !saveError && (
                                <>
                                    <Check className="size-3" />
                                    <span>{savedText}</span>
                                </>
                            )}
                        </div>

                        {isOwner && (
                            <Button variant="ghost" size="icon" onClick={handlePin} title={note.is_pinned ? 'Lösen' : 'Anpinnen'} aria-label={note.is_pinned ? 'Notiz loesen' : 'Notiz anpinnen'}>
                                {note.is_pinned ? (
                                    <PinOff className="size-5" />
                                ) : (
                                    <Pin className="size-5" />
                                )}
                            </Button>
                        )}

                        {canEdit && (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" size="icon" title="Farbe" aria-label="Farbe aendern">
                                        <Palette className="size-5" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="p-3">
                                    <ColorPicker value={color} onChange={handleColorChange} />
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}

                        {isOwner && (
                            <Button variant="ghost" size="icon" onClick={() => setShareOpen(true)} title="Teilen" aria-label="Notiz teilen">
                                <Share2 className="size-5" />
                            </Button>
                        )}

                        {isOwner && (
                            <Button variant="ghost" size="icon" onClick={() => setDeleteOpen(true)} title="Löschen" aria-label="Notiz loeschen">
                                <Trash2 className="size-5 text-destructive" />
                            </Button>
                        )}
                    </div>
                </div>

                {/* Title */}
                <div className="px-4 pt-4 md:px-6 md:pt-6">
                    <input
                        type="text"
                        value={title}
                        onChange={handleTitleChange}
                        placeholder="Titel"
                        readOnly={!canEdit}
                        className="mb-2 w-full border-none bg-transparent text-2xl font-bold outline-none placeholder:opacity-40 md:text-3xl"
                        style={textColor ? { color: textColor } : undefined}
                    />
                </div>

                {/* Rich Text Editor */}
                <RichTextEditor
                    content={content}
                    onChange={handleContentChange}
                    editable={canEdit}
                    textColor={textColor || undefined}
                    placeholder="Notiz schreiben..."
                />
            </div>

            {/* Share Dialog */}
            <ShareDialog
                note={note}
                users={users}
                open={shareOpen}
                onOpenChange={setShareOpen}
            />

            {/* Delete Confirm Dialog */}
            <ConfirmDialog
                open={deleteOpen}
                onOpenChange={setDeleteOpen}
                onConfirm={handleDelete}
                title="Notiz loeschen?"
                description="Die Notiz wird unwiderruflich geloescht."
                confirmLabel="Loeschen"
            />
        </AppLayout>
    );
}
