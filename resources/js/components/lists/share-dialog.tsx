import { router } from '@inertiajs/react';
import { ShareDialog as BaseShareDialog, type SharedUser } from '@/components/share-dialog';
import { share, unshare } from '@/routes/lists';
import type { FamilyList, User } from '@/types';

interface ListShareDialogProps {
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
}: ListShareDialogProps) {
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
        <BaseShareDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Liste teilen"
            description={`Teile "${list.title}" mit deiner Familie.`}
            users={users}
            sharedWith={list.shared_with as SharedUser[] | undefined}
            onShare={handleShare}
            onUnshare={handleUnshare}
        />
    );
}
