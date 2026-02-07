import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useRef, type FormEventHandler } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { store } from '@/routes/lists/items';

interface AddItemInputProps {
    listId: number;
}

export function AddItemInput({ listId }: AddItemInputProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, reset } = useForm({
        content: '',
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!data.content.trim()) return;

        post(store.url(listId), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                inputRef.current?.focus();
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className="flex items-center gap-2">
            <Input
                ref={inputRef}
                placeholder="Neues Element hinzufuegen..."
                value={data.content}
                onChange={(e) => setData('content', e.target.value)}
                className="flex-1"
                disabled={processing}
            />
            <Button
                type="submit"
                size="icon"
                disabled={processing || !data.content.trim()}
                className="shrink-0"
            >
                <Plus className="h-4 w-4" />
            </Button>
        </form>
    );
}
