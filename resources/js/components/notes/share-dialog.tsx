import { router } from '@inertiajs/react';
import { UserMinus, UserPlus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { User } from '@/types';
import type { Note } from './note-card';

interface ShareDialogProps {
    note: Note | null;
    users: User[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

export default function ShareDialog({ note, users, open, onOpenChange }: ShareDialogProps) {
    if (!note) {
        return null;
    }

    const sharedUserIds = note.shared_with?.map((u) => u.id) ?? [];

    function handleShare(userId: number, permission: string) {
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

    function handlePermissionChange(userId: number, permission: string) {
        router.post(
            `/notes/${note!.id}/share`,
            { user_id: userId, permission },
            { preserveScroll: true },
        );
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Notiz teilen</DialogTitle>
                    <DialogDescription>
                        Teile &ldquo;{note.title}&rdquo; mit Familienmitgliedern.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-3">
                    {users.map((user) => {
                        const sharedUser = note.shared_with?.find((u) => u.id === user.id);
                        const isShared = sharedUserIds.includes(user.id);

                        return (
                            <div
                                key={user.id}
                                className="flex items-center justify-between gap-3 rounded-lg border p-3"
                            >
                                <div className="flex items-center gap-3">
                                    <div className="flex size-9 items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground">
                                        {getInitials(user.name)}
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium">{user.name}</p>
                                        <p className="text-xs text-muted-foreground">{user.email}</p>
                                    </div>
                                </div>

                                <div className="flex items-center gap-2">
                                    {isShared ? (
                                        <>
                                            <Select
                                                value={sharedUser?.pivot.permission ?? 'view'}
                                                onValueChange={(value) =>
                                                    handlePermissionChange(user.id, value)
                                                }
                                            >
                                                <SelectTrigger className="h-8 w-24">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="view">Ansehen</SelectItem>
                                                    <SelectItem value="edit">Bearbeiten</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                onClick={() => handleUnshare(user.id)}
                                                title="Freigabe aufheben"
                                            >
                                                <UserMinus className="size-4 text-destructive" />
                                            </Button>
                                        </>
                                    ) : (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleShare(user.id, 'view')}
                                        >
                                            <UserPlus className="size-4" />
                                            Teilen
                                        </Button>
                                    )}
                                </div>
                            </div>
                        );
                    })}

                    {users.length === 0 && (
                        <p className="py-4 text-center text-sm text-muted-foreground">
                            Keine weiteren Familienmitglieder vorhanden.
                        </p>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
