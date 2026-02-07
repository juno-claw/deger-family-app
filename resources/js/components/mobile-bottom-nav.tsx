import { Link } from '@inertiajs/react';
import { CalendarDays, LayoutGrid, ListTodo, StickyNote } from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';

const navItems = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Listen',
        href: '/lists',
        icon: ListTodo,
    },
    {
        title: 'Kalender',
        href: '/calendar',
        icon: CalendarDays,
    },
    {
        title: 'Notizen',
        href: '/notes',
        icon: StickyNote,
    },
];

export function MobileBottomNav() {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <nav className="fixed bottom-0 left-0 z-50 w-full border-t bg-background md:hidden">
            <div
                className="grid h-16 grid-cols-4"
                style={{ paddingBottom: 'env(safe-area-inset-bottom, 0px)' }}
            >
                {navItems.map((item) => {
                    const active = isCurrentUrl(item.href);
                    return (
                        <Link
                            key={item.title}
                            href={item.href}
                            className={cn(
                                'flex min-h-[44px] flex-col items-center justify-center gap-0.5 text-xs transition-colors',
                                active
                                    ? 'text-primary'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            <item.icon
                                className={cn(
                                    'h-5 w-5',
                                    active && 'text-primary',
                                )}
                            />
                            <span className="truncate">{item.title}</span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
