import { Head, router, usePage } from '@inertiajs/react';
import { Plus, StickyNote } from 'lucide-react';
import { useState } from 'react';
import NoteCard from '@/components/notes/note-card';
import type { Note } from '@/components/notes/note-card';
import ShareDialog from '@/components/notes/share-dialog';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, User } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notizen',
        href: '/notes',
    },
];

interface Props {
    ownNotes: Note[];
    sharedNotes: Note[];
    users: User[];
}

export default function NotesIndex({ ownNotes, sharedNotes, users }: Props) {
    const { auth } = usePage().props as { auth: { user: User } };
    const [shareNote, setShareNote] = useState<Note | null>(null);

    function handleCreate() {
        router.post('/notes', { title: 'Neue Notiz', content: '' });
    }

    const hasNotes = ownNotes.length > 0 || sharedNotes.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notizen" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
                {!hasNotes ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4 text-center">
                        <div className="flex size-16 items-center justify-center rounded-full bg-muted">
                            <StickyNote className="size-8 text-muted-foreground" />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold">Keine Notizen</h3>
                            <p className="text-sm text-muted-foreground">
                                Erstelle deine erste Notiz!
                            </p>
                        </div>
                        <Button onClick={handleCreate}>
                            <Plus className="size-4" />
                            Neue Notiz
                        </Button>
                    </div>
                ) : (
                    <>
                        {/* Own Notes */}
                        {ownNotes.length > 0 && (
                            <section>
                                <h2 className="mb-3 text-sm font-medium text-muted-foreground">
                                    Meine Notizen
                                </h2>
                                <div className="columns-1 gap-3 sm:columns-2 lg:columns-3">
                                    {ownNotes.map((note) => (
                                        <div key={note.id} className="mb-3">
                                            <NoteCard
                                                note={note}
                                                isOwner={true}
                                                onShare={setShareNote}
                                            />
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}

                        {/* Shared Notes */}
                        {sharedNotes.length > 0 && (
                            <section>
                                <h2 className="mb-3 text-sm font-medium text-muted-foreground">
                                    Geteilt mit mir
                                </h2>
                                <div className="columns-1 gap-3 sm:columns-2 lg:columns-3">
                                    {sharedNotes.map((note) => (
                                        <div key={note.id} className="mb-3">
                                            <NoteCard
                                                note={note}
                                                isOwner={false}
                                            />
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}
                    </>
                )}
            </div>

            {/* FAB */}
            {hasNotes && (
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
                note={shareNote}
                users={users}
                open={shareNote !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setShareNote(null);
                    }
                }}
            />
        </AppLayout>
    );
}
