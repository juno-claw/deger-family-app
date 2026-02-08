import { Link, router } from '@inertiajs/react';
import { Clock, Heart, MoreVertical, Share2, Trash2, Users } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import type { Recipe } from '@/types';

const categoryLabels: Record<Recipe['category'], string> = {
    cooking: 'Kochen',
    baking: 'Backen',
    dessert: 'Dessert',
    snack: 'Snack',
    drink: 'Getraenk',
};

const categoryColors: Record<Recipe['category'], string> = {
    cooking: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    baking: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    dessert: 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300',
    snack: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    drink: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
};

interface RecipeCardProps {
    recipe: Recipe;
    isOwner: boolean;
    onShare?: (recipe: Recipe) => void;
}

export default function RecipeCard({ recipe, isOwner, onShare }: RecipeCardProps) {
    const [deleteOpen, setDeleteOpen] = useState(false);
    const getInitials = useInitials();

    const totalTime = (recipe.prep_time ?? 0) + (recipe.cook_time ?? 0);

    function handleFavorite(e: React.MouseEvent) {
        e.preventDefault();
        e.stopPropagation();
        router.patch(`/recipes/${recipe.id}/favorite`, {}, { preserveScroll: true });
    }

    function handleDeleteClick(e: React.MouseEvent) {
        e.preventDefault();
        e.stopPropagation();
        setDeleteOpen(true);
    }

    function handleDeleteConfirm() {
        router.delete(`/recipes/${recipe.id}`, { preserveScroll: true });
    }

    function handleShare(e: React.MouseEvent) {
        e.preventDefault();
        e.stopPropagation();
        onShare?.(recipe);
    }

    return (
        <>
            <Link
                href={`/recipes/${recipe.id}`}
                className="group relative block"
            >
                <div className="relative rounded-xl border bg-card p-4 shadow-sm transition-shadow hover:shadow-md">
                    {/* Favorite indicator */}
                    {recipe.is_favorite && (
                        <Heart className="absolute top-3 right-10 size-3.5 fill-red-500 text-red-500" />
                    )}

                    {/* Context menu */}
                    <div className="absolute top-2 right-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                className="rounded-full p-1 opacity-0 transition-opacity hover:bg-black/5 group-hover:opacity-100 dark:hover:bg-white/10"
                                aria-label="Rezept-Optionen"
                                onClick={(e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                }}
                            >
                                <MoreVertical className="size-4 text-muted-foreground" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem onClick={handleFavorite}>
                                    <Heart className="size-4" />
                                    {recipe.is_favorite ? 'Aus Favoriten entfernen' : 'Zu Favoriten'}
                                </DropdownMenuItem>
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
                                            Loeschen
                                        </DropdownMenuItem>
                                    </>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    {/* Category badge */}
                    <div className="mb-2">
                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${categoryColors[recipe.category]}`}>
                            {categoryLabels[recipe.category]}
                        </span>
                    </div>

                    {/* Title */}
                    <h3 className="mb-1 pr-8 text-sm font-semibold leading-snug">
                        {recipe.title}
                    </h3>

                    {/* Description */}
                    {recipe.description && (
                        <p className="mb-2 line-clamp-2 text-sm text-muted-foreground">
                            {recipe.description}
                        </p>
                    )}

                    {/* Meta info */}
                    <div className="flex items-center gap-3 text-xs text-muted-foreground">
                        {totalTime > 0 && (
                            <span className="flex items-center gap-1">
                                <Clock className="size-3" />
                                {totalTime} Min.
                            </span>
                        )}
                        {recipe.servings && (
                            <span className="flex items-center gap-1">
                                <Users className="size-3" />
                                {recipe.servings} Port.
                            </span>
                        )}
                    </div>

                    {/* Shared avatars */}
                    {recipe.shared_with && recipe.shared_with.length > 0 && (
                        <div className="mt-3 flex items-center gap-1">
                            {recipe.shared_with.map((user) => (
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
                title="Rezept loeschen?"
                description="Das Rezept wird unwiderruflich geloescht."
                confirmLabel="Loeschen"
            />
        </>
    );
}
