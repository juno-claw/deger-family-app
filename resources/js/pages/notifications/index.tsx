import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Benachrichtigungen',
        href: '/notifications',
    },
];

export default function NotificationsIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Benachrichtigungen" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
                <Heading
                    title="Benachrichtigungen"
                    description="Kommt bald..."
                />
            </div>
        </AppLayout>
    );
}
