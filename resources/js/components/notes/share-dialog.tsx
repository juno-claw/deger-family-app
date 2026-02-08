import { router } from '@inertiajs/react';
import { ShareDialog, type SharedUser } from '@/components/share-dialog';
import type { User } from '@/types';
import type { Note } from './note-card';

interface NoteShareDialogProps {
    note: Note | null;
    users: User[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function NoteShareDialog({ note, users, open, onOpenChange }: NoteShareDialogProps) {
    if (!note) {
        return null;
    }

    function handleShare(userId: number, permission: 'view' | 'edit') {
        router.post(
            `/notes/${note!.id}/share`,
            { user_id: userId, permission },
            { preserveScroll: true },
        );
    }

    function handleUnshare(userId: number) {
        router.delete(`/notes/${note!.id}/unshare`, {
            data: { user_id: userId },
            preserveScroll: true,
        });
    }

    return (
        <ShareDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Notiz teilen"
            description={`Teile \u201e${note.title}\u201c mit deiner Familie.`}
            users={users}
            sharedWith={note.shared_with as SharedUser[] | undefined}
            onShare={handleShare}
            onUnshare={handleUnshare}
        />
    );
}
