import { Head, router, usePage } from '@inertiajs/react';
import { ChefHat, Plus } from 'lucide-react';
import { useState } from 'react';
import RecipeCard from '@/components/recipes/recipe-card';
import ShareDialog from '@/components/recipes/share-dialog';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Recipe, User } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rezepte',
        href: '/recipes',
    },
];

const categoryFilters = [
    { value: '', label: 'Alle' },
    { value: 'cooking', label: 'Kochen' },
    { value: 'baking', label: 'Backen' },
    { value: 'dessert', label: 'Dessert' },
    { value: 'snack', label: 'Snack' },
    { value: 'drink', label: 'Getraenk' },
];

interface Props {
    ownRecipes: Recipe[];
    sharedRecipes: Recipe[];
    users: User[];
    category: string | null;
}

export default function RecipesIndex({ ownRecipes, sharedRecipes, users, category }: Props) {
    const { auth } = usePage().props as { auth: { user: User } };
    const [shareRecipe, setShareRecipe] = useState<Recipe | null>(null);
    const [activeCategory, setActiveCategory] = useState(category ?? '');

    const filteredOwnRecipes = activeCategory
        ? ownRecipes.filter((r) => r.category === activeCategory)
        : ownRecipes;

    const filteredSharedRecipes = activeCategory
        ? sharedRecipes.filter((r) => r.category === activeCategory)
        : sharedRecipes;

    const hasRecipes = ownRecipes.length > 0 || sharedRecipes.length > 0;
    const hasFilteredRecipes = filteredOwnRecipes.length > 0 || filteredSharedRecipes.length > 0;

    function handleCreate() {
        router.visit('/recipes/create');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rezepte" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
                {!hasRecipes ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <ChefHat className="size-8 text-muted-foreground" />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold">Keine Rezepte</h3>
                            <p className="text-sm text-muted-foreground">
                                Erstelle dein erstes Rezept!
                            </p>
                        </div>
                        <Button onClick={handleCreate}>
                            <Plus className="size-4" />
                            Neues Rezept
                        </Button>
                    </div>
                ) : (
                    <>
                        {/* Category filter */}
                        <div className="flex flex-wrap gap-2">
                            {categoryFilters.map((filter) => (
                                <Button
                                    key={filter.value}
                                    variant={activeCategory === filter.value ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => setActiveCategory(filter.value)}
                                >
                                    {filter.label}
                                </Button>
                            ))}
                        </div>

                        {!hasFilteredRecipes ? (
                            <div className="flex flex-1 flex-col items-center justify-center gap-2 text-center">
                                <p className="text-sm text-muted-foreground">
                                    Keine Rezepte in dieser Kategorie.
                                </p>
                            </div>
                        ) : (
                            <>
                                {/* Own Recipes */}
                                {filteredOwnRecipes.length > 0 && (
                                    <section>
                                        <h2 className="mb-3 text-sm font-medium text-muted-foreground">
                                            Meine Rezepte
                                        </h2>
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                            {filteredOwnRecipes.map((recipe) => (
                                                <RecipeCard
                                                    key={recipe.id}
                                                    recipe={recipe}
                                                    isOwner={true}
                                                    onShare={setShareRecipe}
                                                />
                                            ))}
                                        </div>
                                    </section>
                                )}

                                {/* Shared Recipes */}
                                {filteredSharedRecipes.length > 0 && (
                                    <section>
                                        <h2 className="mb-3 text-sm font-medium text-muted-foreground">
                                            Geteilt mit mir
                                        </h2>
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                            {filteredSharedRecipes.map((recipe) => (
                                                <RecipeCard
                                                    key={recipe.id}
                                                    recipe={recipe}
                                                    isOwner={false}
                                                />
                                            ))}
                                        </div>
                                    </section>
                                )}
                            </>
                        )}
                    </>
                )}
            </div>

            {/* FAB */}
            {hasRecipes && (
                <Button
                    onClick={handleCreate}
                    className="fixed right-4 bottom-20 z-40 size-14 rounded-full shadow-lg md:right-8 md:bottom-8"
                    size="icon-lg"
                >
                    <Plus className="size-6" />
                </Button>
            )}

            {/* Share Dialog */}
            <ShareDialog
                recipe={shareRecipe}
                users={users}
                open={shareRecipe !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setShareRecipe(null);
                    }
                }}
            />
        </AppLayout>
    );
}
