import { Head, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Clock, Edit2, Heart, Share2, Trash2, Users, X } from 'lucide-react';
import { useState } from 'react';
import ShareDialog from '@/components/recipes/share-dialog';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Recipe, User } from '@/types';

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

interface Props {
    recipe: Recipe;
    users: User[];
}

export default function RecipeShow({ recipe, users }: Props) {
    const { auth } = usePage().props as { auth: { user: User } };
    const isOwner = recipe.owner_id === auth.user.id;
    const sharedUser = recipe.shared_with?.find((u) => u.id === auth.user.id);
    const canEdit = isOwner || sharedUser?.pivot.permission === 'edit';

    const [shareOpen, setShareOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);

    const { data, setData, put, processing, errors, reset } = useForm({
        title: recipe.title,
        description: recipe.description ?? '',
        category: recipe.category,
        servings: recipe.servings ?? ('' as string | number),
        prep_time: recipe.prep_time ?? ('' as string | number),
        cook_time: recipe.cook_time ?? ('' as string | number),
        ingredients: recipe.ingredients,
        instructions: recipe.instructions,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Rezepte', href: '/recipes' },
        { title: recipe.title, href: `/recipes/${recipe.id}` },
    ];

    const totalTime = (recipe.prep_time ?? 0) + (recipe.cook_time ?? 0);

    function handleFavorite() {
        router.patch(`/recipes/${recipe.id}/favorite`, {}, { preserveScroll: true });
    }

    function handleDelete() {
        router.delete(`/recipes/${recipe.id}`);
    }

    function handleBack() {
        router.visit('/recipes');
    }

    function handleSave(e: React.FormEvent) {
        e.preventDefault();
        put(`/recipes/${recipe.id}`, {
            preserveScroll: true,
            onSuccess: () => setIsEditing(false),
        });
    }

    function handleCancelEdit() {
        reset();
        setIsEditing(false);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={recipe.title} />
            <div className="flex h-full flex-1 flex-col">
                {/* Toolbar */}
                <div className="flex items-center justify-between border-b px-4 py-2">
                    <div className="flex items-center gap-1">
                        <Button variant="ghost" size="icon" onClick={handleBack} aria-label="Zurueck zu Rezepten">
                            <ArrowLeft className="size-5" />
                        </Button>
                    </div>

                    <div className="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={handleFavorite}
                            title={recipe.is_favorite ? 'Aus Favoriten entfernen' : 'Zu Favoriten'}
                            aria-label={recipe.is_favorite ? 'Aus Favoriten entfernen' : 'Zu Favoriten hinzufuegen'}
                        >
                            <Heart className={`size-5 ${recipe.is_favorite ? 'fill-red-500 text-red-500' : ''}`} />
                        </Button>

                        {canEdit && !isEditing && (
                            <Button variant="ghost" size="icon" onClick={() => setIsEditing(true)} title="Bearbeiten" aria-label="Rezept bearbeiten">
                                <Edit2 className="size-5" />
                            </Button>
                        )}

                        {isOwner && (
                            <Button variant="ghost" size="icon" onClick={() => setShareOpen(true)} title="Teilen" aria-label="Rezept teilen">
                                <Share2 className="size-5" />
                            </Button>
                        )}

                        {isOwner && (
                            <Button variant="ghost" size="icon" onClick={() => setDeleteOpen(true)} title="Loeschen" aria-label="Rezept loeschen">
                                <Trash2 className="size-5 text-destructive" />
                            </Button>
                        )}
                    </div>
                </div>

                {isEditing ? (
                    /* Edit mode */
                    <form onSubmit={handleSave} className="mx-auto w-full max-w-2xl space-y-6 p-4 md:p-6">
                        <div className="space-y-2">
                            <Label htmlFor="title">Titel *</Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                aria-invalid={!!errors.title}
                            />
                            {errors.title && <p className="text-sm text-destructive">{errors.title}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Beschreibung</Label>
                            <Input
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                            />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Kategorie *</Label>
                                <Select value={data.category} onValueChange={(v) => setData('category', v as Recipe['category'])}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="cooking">Kochen</SelectItem>
                                        <SelectItem value="baking">Backen</SelectItem>
                                        <SelectItem value="dessert">Dessert</SelectItem>
                                        <SelectItem value="snack">Snack</SelectItem>
                                        <SelectItem value="drink">Getraenk</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="servings">Portionen</Label>
                                <Input
                                    id="servings"
                                    type="number"
                                    min="1"
                                    value={data.servings}
                                    onChange={(e) => setData('servings', e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="prep_time">Vorbereitungszeit (Min.)</Label>
                                <Input
                                    id="prep_time"
                                    type="number"
                                    min="0"
                                    value={data.prep_time}
                                    onChange={(e) => setData('prep_time', e.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="cook_time">Zubereitungszeit (Min.)</Label>
                                <Input
                                    id="cook_time"
                                    type="number"
                                    min="0"
                                    value={data.cook_time}
                                    onChange={(e) => setData('cook_time', e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="ingredients">Zutaten *</Label>
                            <Textarea
                                id="ingredients"
                                value={data.ingredients}
                                onChange={(e) => setData('ingredients', e.target.value)}
                                className="min-h-32"
                                aria-invalid={!!errors.ingredients}
                            />
                            {errors.ingredients && <p className="text-sm text-destructive">{errors.ingredients}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="instructions">Zubereitung *</Label>
                            <Textarea
                                id="instructions"
                                value={data.instructions}
                                onChange={(e) => setData('instructions', e.target.value)}
                                className="min-h-40"
                                aria-invalid={!!errors.instructions}
                            />
                            {errors.instructions && <p className="text-sm text-destructive">{errors.instructions}</p>}
                        </div>

                        <div className="flex justify-end gap-3 pt-2">
                            <Button type="button" variant="outline" onClick={handleCancelEdit}>
                                <X className="size-4" />
                                Abbrechen
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Speichert...' : 'Speichern'}
                            </Button>
                        </div>
                    </form>
                ) : (
                    /* View mode */
                    <div className="mx-auto w-full max-w-2xl p-4 md:p-6">
                        {/* Category badge */}
                        <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${categoryColors[recipe.category]}`}>
                            {categoryLabels[recipe.category]}
                        </span>

                        {/* Title */}
                        <h1 className="mt-3 text-2xl font-bold md:text-3xl">{recipe.title}</h1>

                        {/* Description */}
                        {recipe.description && (
                            <p className="mt-1 text-muted-foreground">{recipe.description}</p>
                        )}

                        {/* Meta info */}
                        <div className="mt-4 flex flex-wrap gap-4 text-sm text-muted-foreground">
                            {recipe.prep_time != null && recipe.prep_time > 0 && (
                                <span className="flex items-center gap-1.5">
                                    <Clock className="size-4" />
                                    Vorbereitung: {recipe.prep_time} Min.
                                </span>
                            )}
                            {recipe.cook_time != null && recipe.cook_time > 0 && (
                                <span className="flex items-center gap-1.5">
                                    <Clock className="size-4" />
                                    Zubereitung: {recipe.cook_time} Min.
                                </span>
                            )}
                            {totalTime > 0 && (
                                <span className="flex items-center gap-1.5 font-medium text-foreground">
                                    <Clock className="size-4" />
                                    Gesamt: {totalTime} Min.
                                </span>
                            )}
                            {recipe.servings && (
                                <span className="flex items-center gap-1.5">
                                    <Users className="size-4" />
                                    {recipe.servings} Portionen
                                </span>
                            )}
                        </div>

                        {/* Ingredients */}
                        <div className="mt-8">
                            <h2 className="mb-3 text-lg font-semibold">Zutaten</h2>
                            <ul className="space-y-1.5">
                                {recipe.ingredients.split('\n').filter(Boolean).map((ingredient, i) => (
                                    <li key={i} className="flex items-start gap-2 text-sm">
                                        <span className="mt-1.5 size-1.5 shrink-0 rounded-full bg-primary" />
                                        {ingredient}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        {/* Instructions */}
                        <div className="mt-8">
                            <h2 className="mb-3 text-lg font-semibold">Zubereitung</h2>
                            <ol className="space-y-3">
                                {recipe.instructions.split('\n').filter(Boolean).map((step, i) => (
                                    <li key={i} className="flex gap-3 text-sm">
                                        <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">
                                            {i + 1}
                                        </span>
                                        <span className="pt-0.5">{step}</span>
                                    </li>
                                ))}
                            </ol>
                        </div>

                        {/* Owner info */}
                        {recipe.owner && (
                            <div className="mt-8 border-t pt-4 text-xs text-muted-foreground">
                                Erstellt von {recipe.owner.name}
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Share Dialog */}
            <ShareDialog
                recipe={recipe}
                users={users}
                open={shareOpen}
                onOpenChange={setShareOpen}
            />

            {/* Delete Confirm Dialog */}
            <ConfirmDialog
                open={deleteOpen}
                onOpenChange={setDeleteOpen}
                onConfirm={handleDelete}
                title="Rezept loeschen?"
                description="Das Rezept wird unwiderruflich geloescht."
                confirmLabel="Loeschen"
            />
        </AppLayout>
    );
}
