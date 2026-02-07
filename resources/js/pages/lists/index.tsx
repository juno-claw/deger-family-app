import { Head } from '@inertiajs/react';
import { ListTodo, Plus } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { CreateListDialog } from '@/components/lists/create-list-dialog';
import { ListCard } from '@/components/lists/list-card';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, FamilyList } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Listen',
        href: '/lists',
    },
];

interface ListsIndexProps {
    ownLists: FamilyList[];
    sharedLists: FamilyList[];
}

export default function ListsIndex({ ownLists, sharedLists }: ListsIndexProps) {
    const [createDialogOpen, setCreateDialogOpen] = useState(false);
    const hasLists = ownLists.length > 0 || sharedLists.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Listen" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-start justify-between">
                    <Heading
                        title="Listen"
                        description="Deine Todo- und Einkaufslisten"
                    />
                    <Button
                        onClick={() => setCreateDialogOpen(true)}
                        className="hidden md:inline-flex"
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        Neue Liste
                    </Button>
                </div>

                {!hasLists ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4 rounded-lg border border-dashed p-8 text-center">
                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <ListTodo className="h-8 w-8" />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold">
                                Noch keine Listen
                            </h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Erstelle deine erste Liste!
                            </p>
                        </div>
                        <Button onClick={() => setCreateDialogOpen(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Liste erstellen
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-8">
                        {ownLists.length > 0 && (
                            <section>
                                <h3 className="mb-3 text-sm font-medium text-muted-foreground">
                                    Meine Listen
                                </h3>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {ownLists.map((list) => (
                                        <ListCard key={list.id} list={list} />
                                    ))}
                                </div>
                            </section>
                        )}

                        {sharedLists.length > 0 && (
                            <section>
                                <h3 className="mb-3 text-sm font-medium text-muted-foreground">
                                    Geteilt mit mir
                                </h3>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {sharedLists.map((list) => (
                                        <ListCard
                                            key={list.id}
                                            list={list}
                                            showOwner
                                        />
                                    ))}
                                </div>
                            </section>
                        )}
                    </div>
                )}
            </div>

            {/* Floating Action Button - mobile only */}
            <button
                onClick={() => setCreateDialogOpen(true)}
                className="fixed bottom-20 right-4 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform hover:scale-105 active:scale-95 md:hidden"
                aria-label="Neue Liste erstellen"
            >
                <Plus className="h-6 w-6" />
            </button>

            <CreateListDialog
                open={createDialogOpen}
                onOpenChange={setCreateDialogOpen}
            />
        </AppLayout>
    );
}
