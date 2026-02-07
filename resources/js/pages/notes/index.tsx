import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notizen',
        href: '/notes',
    },
];

export default function NotesIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notizen" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
                <Heading
                    title="Notizen"
                    description="Kommt bald..."
                />
            </div>
        </AppLayout>
    );
}
