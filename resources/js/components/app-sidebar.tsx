import { Link } from '@inertiajs/react';
import { Bell, CalendarDays, ChefHat, LayoutGrid, ListTodo, StickyNote } from 'lucide-react';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
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
    {
        title: 'Rezepte',
        href: '/recipes',
        icon: ChefHat,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Benachrichtigungen',
        href: '/notifications',
        icon: Bell,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
