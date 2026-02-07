import { useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { store } from '@/routes/lists';

interface CreateListDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function CreateListDialog({ open, onOpenChange }: CreateListDialogProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        type: 'todo' as 'todo' | 'shopping',
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        post(store.url(), {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Neue Liste erstellen</DialogTitle>
                        <DialogDescription>
                            Erstelle eine neue Todo- oder Einkaufsliste.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="mt-4 space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="title">Titel</Label>
                            <Input
                                id="title"
                                placeholder="z.B. Wocheneinkauf"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                autoFocus
                            />
                            {errors.title && (
                                <p className="text-sm text-destructive">{errors.title}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="type">Typ</Label>
                            <Select
                                value={data.type}
                                onValueChange={(value: 'todo' | 'shopping') =>
                                    setData('type', value)
                                }
                            >
                                <SelectTrigger id="type">
                                    <SelectValue placeholder="Typ waehlen" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="todo">Todo-Liste</SelectItem>
                                    <SelectItem value="shopping">Einkaufsliste</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.type && (
                                <p className="text-sm text-destructive">{errors.type}</p>
                            )}
                        </div>
                    </div>
                    <DialogFooter className="mt-6">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Abbrechen
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Erstelle...' : 'Erstellen'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
