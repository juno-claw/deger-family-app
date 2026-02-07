import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { update, destroy } from '@/routes/lists/items';
import { cn } from '@/lib/utils';
import type { ListItem } from '@/types';

interface ListItemRowProps {
    item: ListItem;
    listId: number;
}

export function ListItemRow({ item, listId }: ListItemRowProps) {
    const handleToggle = () => {
        router.put(
            update.url({ list: listId, item: item.id }),
            { is_completed: !item.is_completed },
            { preserveScroll: true },
        );
    };

    const handleDelete = () => {
        router.delete(destroy.url({ list: listId, item: item.id }), {
            preserveScroll: true,
        });
    };

    return (
        <div className="group flex min-h-[44px] items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-muted/50">
            <Checkbox
                checked={item.is_completed}
                onCheckedChange={handleToggle}
                className="h-5 w-5"
            />
            <span
                className={cn(
                    'flex-1 text-sm transition-colors',
                    item.is_completed && 'text-muted-foreground line-through',
                )}
            >
                {item.content}
            </span>
            <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 shrink-0 opacity-0 transition-opacity group-hover:opacity-100 md:opacity-0"
                onClick={handleDelete}
            >
                <Trash2 className="h-4 w-4 text-muted-foreground" />
            </Button>
        </div>
    );
}
