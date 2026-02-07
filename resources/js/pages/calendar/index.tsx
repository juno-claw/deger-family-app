import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kalender',
        href: '/calendar',
    },
];

export default function CalendarIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kalender" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
                <Heading
                    title="Kalender"
                    description="Kommt bald..."
                />
            </div>
        </AppLayout>
    );
}
