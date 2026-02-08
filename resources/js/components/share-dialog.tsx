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
import type { User } from '@/types';

export type SharedUser = User & { pivot: { permission: 'view' | 'edit' } };

interface ShareDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    users: User[];
    sharedWith?: SharedUser[];
    onShare: (userId: number, permission: 'view' | 'edit') => void;
    onUnshare: (userId: number) => void;
}

export function ShareDialog({
    open,
    onOpenChange,
    title,
    description,
    users,
    sharedWith = [],
    onShare,
    onUnshare,
}: ShareDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <div className="mt-4 space-y-3">
                    {users.map((user) => {
                        const shared = sharedWith.find((u) => u.id === user.id);

                        return (
                            <ShareUserRow
                                key={user.id}
                                user={user}
                                isShared={!!shared}
                                permission={shared?.pivot.permission}
                                onShare={onShare}
                                onUnshare={onUnshare}
                            />
                        );
                    })}
                    {users.length === 0 && (
                        <p className="py-4 text-center text-sm text-muted-foreground">
                            Keine weiteren Familienmitglieder verfügbar.
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
