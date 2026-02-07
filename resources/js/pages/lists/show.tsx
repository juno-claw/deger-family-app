import { Head, router } from '@inertiajs/react';
import { CheckSquare, Share2, ShoppingCart, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { AddItemInput } from '@/components/lists/add-item-input';
import { ListItemRow } from '@/components/lists/list-item-row';
import { ShareDialog } from '@/components/lists/share-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { destroy } from '@/routes/lists';
import type { BreadcrumbItem, FamilyList, User } from '@/types';

interface ListShowProps {
    list: FamilyList;
    users: User[];
}

export default function ListShow({ list, users }: ListShowProps) {
    const [shareDialogOpen, setShareDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Listen', href: '/lists' },
        { title: list.title, href: `/lists/${list.id}` },
    ];

    const items = list.items ?? [];
    const activeItems = useMemo(
        () => items.filter((i) => !i.is_completed),
        [items],
    );
    const completedItems = useMemo(
        () => items.filter((i) => i.is_completed),
        [items],
    );

    const completedCount = completedItems.length;
    const totalCount = items.length;

    const Icon = list.type === 'shopping' ? ShoppingCart : CheckSquare;

    const handleDelete = () => {
        router.delete(destroy.url(list.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={list.title} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
                {/* Header */}
                <div className="flex items-start justify-between gap-4">
                    <div className="flex items-start gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <Icon className="h-5 w-5" />
                        </div>
                        <div>
                            <h1 className="text-xl font-semibold tracking-tight">
                                {list.title}
                            </h1>
                            <div className="mt-1 flex items-center gap-2">
                                <Badge variant="secondary" className="text-xs">
                                    {list.type === 'shopping'
                                        ? 'Einkauf'
                                        : 'Todo'}
                                </Badge>
                                {totalCount > 0 && (
                                    <span className="text-xs text-muted-foreground">
                                        {completedCount}/{totalCount} erledigt
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => setShareDialogOpen(true)}
                            title="Liste teilen"
                            aria-label="Liste teilen"
                        >
                            <Share2 className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => setDeleteDialogOpen(true)}
                            title="Liste loeschen"
                            aria-label="Liste loeschen"
                            className="text-destructive hover:text-destructive"
                        >
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                {/* Shared with info */}
                {list.shared_with && list.shared_with.length > 0 && (
                    <div className="flex flex-wrap items-center gap-1.5">
                        <span className="text-xs text-muted-foreground">
                            Geteilt mit:
                        </span>
                        {list.shared_with.map((user) => (
                            <Badge
                                key={user.id}
                                variant="outline"
                                className="text-xs"
                            >
                                {user.name}
                            </Badge>
                        ))}
                    </div>
                )}

                <Separator />

                {/* Active Items */}
                <div className="flex-1 space-y-1">
                    {activeItems.length === 0 && completedItems.length === 0 && (
                        <div className="flex flex-col items-center justify-center gap-2 py-12 text-center">
                            <p className="text-sm text-muted-foreground">
                                Noch keine Eintraege. Fuege den ersten hinzu!
                            </p>
                        </div>
                    )}

                    {activeItems.map((item) => (
                        <ListItemRow
                            key={item.id}
                            item={item}
                            listId={list.id}
                        />
                    ))}

                    {/* Completed Items */}
                    {completedItems.length > 0 && (
                        <div className="mt-4">
                            <p className="mb-2 px-2 text-xs font-medium text-muted-foreground">
                                Erledigt ({completedItems.length})
                            </p>
                            {completedItems.map((item) => (
                                <ListItemRow
                                    key={item.id}
                                    item={item}
                                    listId={list.id}
                                />
                            ))}
                        </div>
                    )}
                </div>

                {/* Add Item Input - sticky at bottom */}
                <div className="sticky bottom-20 md:bottom-0">
                    <AddItemInput listId={list.id} />
                </div>
            </div>

            <ShareDialog
                open={shareDialogOpen}
                onOpenChange={setShareDialogOpen}
                list={list}
                users={users}
            />

            <ConfirmDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={handleDelete}
                title="Liste loeschen?"
                description="Die Liste und alle Eintraege werden unwiderruflich geloescht."
                confirmLabel="Loeschen"
            />
        </AppLayout>
    );
}
