import { Link, router } from '@inertiajs/react';
import { MoreVertical, Pin, Share2, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import { cn, getContrastTextColor } from '@/lib/utils';
import type { User } from '@/types';

export interface Note {
    id: number;
    title: string;
    content: string | null;
    owner_id: number;
    is_pinned: boolean;
    color: string | null;
    owner?: User;
    shared_with?: (User & { pivot: { permission: string } })[];
    created_at: string;
    updated_at: string;
}

interface NoteCardProps {
    note: Note;
    isOwner: boolean;
    onShare?: (note: Note) => void;
}

export default function NoteCard({ note, isOwner, onShare }: NoteCardProps) {
    const [deleteOpen, setDeleteOpen] = useState(false);
    const getInitials = useInitials();

    function handlePin(e: React.MouseEvent) {
        e.preventDefault();
        e.stopPropagation();
        router.patch(`/notes/${note.id}/pin`, {}, { preserveScroll: true });
    }

    function handleDeleteClick(e: React.MouseEvent) {
        e.preventDefault();
        e.stopPropagation();
        setDeleteOpen(true);
    }

    function handleDeleteConfirm() {
        router.delete(`/notes/${note.id}`, { preserveScroll: true });
    }

    function handleShare(e: React.MouseEvent) {
        e.preventDefault();
        e.stopPropagation();
        onShare?.(note);
    }

    return (
        <>
            <Link
                href={`/notes/${note.id}`}
                className="group relative block break-inside-avoid"
            >
                <div
                    className={cn(
                        'relative rounded-xl border p-4 shadow-sm transition-shadow hover:shadow-md',
                        'bg-card text-card-foreground',
                    )}
                    style={
                        note.color
                            ? { backgroundColor: note.color, color: getContrastTextColor(note.color) }
                            : undefined
                    }
                >
                    {/* Pin indicator */}
                    {note.is_pinned && (
                        <Pin className="absolute top-3 right-10 size-3.5 fill-current text-muted-foreground/70" />
                    )}

                    {/* Context menu */}
                    <div className="absolute top-2 right-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                className="rounded-full p-1 opacity-0 transition-opacity hover:bg-black/5 group-hover:opacity-100 dark:hover:bg-white/10"
                                aria-label="Notiz-Optionen"
                                onClick={(e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                }}
                            >
                                <MoreVertical className="size-4 text-muted-foreground" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {isOwner && (
                                    <DropdownMenuItem onClick={handlePin}>
                                        <Pin className="size-4" />
                                        {note.is_pinned ? 'Lösen' : 'Anpinnen'}
                                    </DropdownMenuItem>
                                )}
                                {isOwner && (
                                    <DropdownMenuItem onClick={handleShare}>
                                        <Share2 className="size-4" />
                                        Teilen
                                    </DropdownMenuItem>
                                )}
                                {isOwner && (
                                    <>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem variant="destructive" onClick={handleDeleteClick}>
                                            <Trash2 className="size-4" />
                                            Löschen
                                        </DropdownMenuItem>
                                    </>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    {/* Title */}
                    {note.title && (
                        <h3 className="mb-1 pr-8 text-sm font-semibold leading-snug">
                            {note.title}
                        </h3>
                    )}

                    {/* Content preview */}
                    {note.content && (
                        <p
                            className="line-clamp-3 text-sm opacity-75"
                            style={
                                note.color
                                    ? { color: getContrastTextColor(note.color) }
                                    : undefined
                            }
                        >
                            {note.content.replace(/<[^>]*>/g, '')}
                        </p>
                    )}

                    {/* Shared avatars */}
                    {note.shared_with && note.shared_with.length > 0 && (
                        <div className="mt-3 flex items-center gap-1">
                            {note.shared_with.map((user) => (
                                <div
                                    key={user.id}
                                    className="flex size-6 items-center justify-center rounded-full bg-muted text-[10px] font-medium text-muted-foreground"
                                    title={user.name}
                                    aria-label={`Geteilt mit ${user.name}`}
                                >
                                    {getInitials(user.name)}
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </Link>

            <ConfirmDialog
                open={deleteOpen}
                onOpenChange={setDeleteOpen}
                onConfirm={handleDeleteConfirm}
                title="Notiz loeschen?"
                description="Die Notiz wird unwiderruflich geloescht."
                confirmLabel="Loeschen"
            />
        </>
    );
}
