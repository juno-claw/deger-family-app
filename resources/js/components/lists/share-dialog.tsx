import { router } from '@inertiajs/react';
import { UserMinus, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
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
import { share, unshare } from '@/routes/lists';
import type { FamilyList, User } from '@/types';

interface ShareDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    list: FamilyList;
    users: User[];
}

export function ShareDialog({
    open,
    onOpenChange,
    list,
    users,
}: ShareDialogProps) {
    const sharedUserIds = (list.shared_with ?? []).map((u) => u.id);

    const handleShare = (userId: number, permission: 'view' | 'edit') => {
        router.post(share.url(list.id), {
            user_id: userId,
            permission,
        }, {
            preserveScroll: true,
        });
    };

    const handleUnshare = (userId: number) => {
        router.delete(unshare.url(list.id), {
            data: { user_id: userId },
            preserveScroll: true,
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Liste teilen</DialogTitle>
                    <DialogDescription>
                        Teile &quot;{list.title}&quot; mit deiner Familie.
                    </DialogDescription>
                </DialogHeader>
                <div className="mt-4 space-y-3">
                    {users.map((user) => {
                        const sharedWith = list.shared_with?.find(
                            (u) => u.id === user.id,
                        );
                        const isShared = !!sharedWith;

                        return (
                            <ShareUserRow
                                key={user.id}
                                user={user}
                                isShared={isShared}
                                permission={sharedWith?.pivot.permission}
                                onShare={handleShare}
                                onUnshare={handleUnshare}
                            />
                        );
                    })}
                    {users.length === 0 && (
                        <p className="py-4 text-center text-sm text-muted-foreground">
                            Keine weiteren Familienmitglieder verfuegbar.
                        </p>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

function ShareUserRow({
    user,
    isShared,
    permission,
    onShare,
    onUnshare,
}: {
    user: User;
    isShared: boolean;
    permission?: 'view' | 'edit';
    onShare: (userId: number, permission: 'view' | 'edit') => void;
    onUnshare: (userId: number) => void;
}) {
    const [selectedPermission, setSelectedPermission] = useState<'view' | 'edit'>(
        permission ?? 'view',
    );

    return (
        <div className="flex items-center justify-between gap-3 rounded-lg border p-3">
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">{user.name}</p>
                <p className="truncate text-xs text-muted-foreground">
                    {user.email}
                </p>
            </div>
            <div className="flex items-center gap-2">
                {isShared ? (
                    <>
                        <Badge variant="secondary" className="text-[10px]">
                            {permission === 'edit' ? 'Bearbeiten' : 'Lesen'}
                        </Badge>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8 text-destructive hover:text-destructive"
                            onClick={() => onUnshare(user.id)}
                        >
                            <UserMinus className="h-4 w-4" />
                        </Button>
                    </>
                ) : (
                    <>
                        <Select
                            value={selectedPermission}
                            onValueChange={(v: 'view' | 'edit') =>
                                setSelectedPermission(v)
                            }
                        >
                            <SelectTrigger className="h-8 w-28 text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="view">Lesen</SelectItem>
                                <SelectItem value="edit">Bearbeiten</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8 text-primary hover:text-primary"
                            onClick={() =>
                                onShare(user.id, selectedPermission)
                            }
                        >
                            <UserPlus className="h-4 w-4" />
                        </Button>
                    </>
                )}
            </div>
        </div>
    );
}
