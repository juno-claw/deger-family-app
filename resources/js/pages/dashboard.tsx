import { Head, Link } from '@inertiajs/react';
import { Bell, CalendarDays, ListTodo, StickyNote } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const quickLinks = [
    {
        title: 'Listen',
        description: 'Todo- und Einkaufslisten verwalten',
        href: '/lists',
        icon: ListTodo,
    },
    {
        title: 'Kalender',
        description: 'Termine und Events planen',
        href: '/calendar',
        icon: CalendarDays,
    },
    {
        title: 'Notizen',
        description: 'Notizen erstellen und teilen',
        href: '/notes',
        icon: StickyNote,
    },
    {
        title: 'Benachrichtigungen',
        description: 'Neuigkeiten und Updates',
        href: '/notifications',
        icon: Bell,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
                    <p className="text-muted-foreground">
                        Willkommen bei der Deger Family App!
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {quickLinks.map((item) => (
                        <Link key={item.title} href={item.href} className="group">
                            <Card className="h-full transition-colors group-hover:border-primary/50 group-hover:shadow-md">
                                <CardHeader className="items-center text-center">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <item.icon className="h-6 w-6" />
                                    </div>
                                    <CardTitle className="text-sm md:text-base">
                                        {item.title}
                                    </CardTitle>
                                    <CardDescription className="hidden text-xs md:block">
                                        {item.description}
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
