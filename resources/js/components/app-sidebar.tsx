import { Link, usePage } from '@inertiajs/react';
import { IconCalendarEvent, IconClipboardList, IconDashboard, IconMapPin } from '@tabler/icons-react';
import AppLogo from '@/components/app-logo';
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
import { index as activityLogsIndex } from '@/routes/activity-logs';
import { index as attractionsIndex } from '@/routes/attractions';
import { index as sessionsIndex } from '@/routes/sessions';
import type { Auth, NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const isRecreationAdmin = auth.user?.role === 'recreation_admin';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: IconDashboard,
        },
        ...(isRecreationAdmin
            ? [
                  {
                      title: 'Attractions',
                      href: attractionsIndex().url,
                      icon: IconMapPin,
                  },
                  {
                      title: 'Sessions',
                      href: sessionsIndex().url,
                      icon: IconCalendarEvent,
                  },
                  {
                      title: 'Activity Log',
                      href: activityLogsIndex().url,
                      icon: IconClipboardList,
                  },
              ]
            : []),
    ];

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
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
