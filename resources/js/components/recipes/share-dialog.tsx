import { router } from '@inertiajs/react';
import { ShareDialog, type SharedUser } from '@/components/share-dialog';
import type { Recipe, User } from '@/types';

interface RecipeShareDialogProps {
    recipe: Recipe | null;
    users: User[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function RecipeShareDialog({ recipe, users, open, onOpenChange }: RecipeShareDialogProps) {
    if (!recipe) {
        return null;
    }

    function handleShare(userId: number, permission: 'view' | 'edit') {
        router.post(
            `/recipes/${recipe!.id}/share`,
            { user_id: userId, permission },
            { preserveScroll: true },
        );
    }

    function handleUnshare(userId: number) {
        router.delete(`/recipes/${recipe!.id}/unshare`, {
            data: { user_id: userId },
            preserveScroll: true,
        });
    }

    return (
        <ShareDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Rezept teilen"
            description={`Teile \u201e${recipe.title}\u201c mit deiner Familie.`}
            users={users}
            sharedWith={recipe.shared_with as SharedUser[] | undefined}
            onShare={handleShare}
            onUnshare={handleUnshare}
        />
    );
}
