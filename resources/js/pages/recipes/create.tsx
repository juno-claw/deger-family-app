import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rezepte', href: '/recipes' },
    { title: 'Neues Rezept', href: '/recipes/create' },
];

export default function RecipeCreate() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        category: 'cooking' as string,
        servings: '' as string | number,
        prep_time: '' as string | number,
        cook_time: '' as string | number,
        ingredients: '',
        instructions: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/recipes');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Neues Rezept" />
            <div className="flex h-full flex-1 flex-col">
                {/* Header */}
                <div className="flex items-center gap-2 border-b px-4 py-2">
                    <Button variant="ghost" size="icon" onClick={() => history.back()} aria-label="Zurueck">
                        <ArrowLeft className="size-5" />
                    </Button>
                    <h1 className="text-lg font-semibold">Neues Rezept</h1>
                </div>

                <form onSubmit={handleSubmit} className="mx-auto w-full max-w-2xl space-y-6 p-4 md:p-6">
                    {/* Title */}
                    <div className="space-y-2">
                        <Label htmlFor="title">Titel *</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="z.B. Spaghetti Bolognese"
                            aria-invalid={!!errors.title}
                        />
                        {errors.title && <p className="text-sm text-destructive">{errors.title}</p>}
                    </div>

                    {/* Description */}
                    <div className="space-y-2">
                        <Label htmlFor="description">Beschreibung</Label>
                        <Input
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="Kurze Beschreibung des Rezepts"
                        />
                        {errors.description && <p className="text-sm text-destructive">{errors.description}</p>}
                    </div>

                    {/* Category + Servings */}
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Kategorie *</Label>
                            <Select value={data.category} onValueChange={(v) => setData('category', v)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Kategorie waehlen" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="cooking">Kochen</SelectItem>
                                    <SelectItem value="baking">Backen</SelectItem>
                                    <SelectItem value="dessert">Dessert</SelectItem>
                                    <SelectItem value="snack">Snack</SelectItem>
                                    <SelectItem value="drink">Getraenk</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.category && <p className="text-sm text-destructive">{errors.category}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="servings">Portionen</Label>
                            <Input
                                id="servings"
                                type="number"
                                min="1"
                                max="100"
                                value={data.servings}
                                onChange={(e) => setData('servings', e.target.value)}
                                placeholder="z.B. 4"
                            />
                            {errors.servings && <p className="text-sm text-destructive">{errors.servings}</p>}
                        </div>
                    </div>

                    {/* Prep time + Cook time */}
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="prep_time">Vorbereitungszeit (Min.)</Label>
                            <Input
                                id="prep_time"
                                type="number"
                                min="0"
                                value={data.prep_time}
                                onChange={(e) => setData('prep_time', e.target.value)}
                                placeholder="z.B. 15"
                            />
                            {errors.prep_time && <p className="text-sm text-destructive">{errors.prep_time}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="cook_time">Zubereitungszeit (Min.)</Label>
                            <Input
                                id="cook_time"
                                type="number"
                                min="0"
                                value={data.cook_time}
                                onChange={(e) => setData('cook_time', e.target.value)}
                                placeholder="z.B. 30"
                            />
                            {errors.cook_time && <p className="text-sm text-destructive">{errors.cook_time}</p>}
                        </div>
                    </div>

                    {/* Ingredients */}
                    <div className="space-y-2">
                        <Label htmlFor="ingredients">Zutaten *</Label>
                        <Textarea
                            id="ingredients"
                            value={data.ingredients}
                            onChange={(e) => setData('ingredients', e.target.value)}
                            placeholder={"500g Spaghetti\n400g Hackfleisch\n1 Dose Tomaten\n..."}
                            className="min-h-32"
                            aria-invalid={!!errors.ingredients}
                        />
                        <p className="text-xs text-muted-foreground">Eine Zutat pro Zeile</p>
                        {errors.ingredients && <p className="text-sm text-destructive">{errors.ingredients}</p>}
                    </div>

                    {/* Instructions */}
                    <div className="space-y-2">
                        <Label htmlFor="instructions">Zubereitung *</Label>
                        <Textarea
                            id="instructions"
                            value={data.instructions}
                            onChange={(e) => setData('instructions', e.target.value)}
                            placeholder={"1. Wasser zum Kochen bringen\n2. Spaghetti kochen\n3. ..."}
                            className="min-h-40"
                            aria-invalid={!!errors.instructions}
                        />
                        <p className="text-xs text-muted-foreground">Schritt fuer Schritt beschreiben</p>
                        {errors.instructions && <p className="text-sm text-destructive">{errors.instructions}</p>}
                    </div>

                    {/* Submit */}
                    <div className="flex justify-end gap-3 pt-2">
                        <Button type="button" variant="outline" onClick={() => history.back()}>
                            Abbrechen
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Speichert...' : 'Rezept speichern'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
