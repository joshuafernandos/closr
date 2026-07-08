import { Link } from '@inertiajs/react';
import { LayoutGrid, LayoutTemplate, MessageSquare, Plug } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { dashboard } from '@/routes';
import { index as connectors } from '@/routes/connector';
import { index as messages } from '@/routes/messages';
import { index as widgets } from '@/routes/widgets';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    const dashboardUrl = dashboard();

    const workspaceItems: NavItem[] = [
        { title: 'Dashboard', href: dashboardUrl, icon: LayoutGrid },
        { title: 'Connectors', href: connectors(), icon: Plug },
        {
            title: 'Widgets',
            href: widgets(),
            icon: LayoutTemplate,
            matchSubPaths: true,
        },
        {
            title: 'Messages',
            href: messages(),
            icon: MessageSquare,
            matchSubPaths: true,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Workspace</SidebarGroupLabel>
                    <SidebarMenu>
                        {workspaceItems.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={
                                        item.matchSubPaths
                                            ? isCurrentOrParentUrl(item.href)
                                            : isCurrentUrl(item.href)
                                    }
                                    tooltip={{ children: item.title }}
                                    className="data-[active=true]:bg-primary data-[active=true]:text-primary-foreground data-[active=true]:hover:bg-primary data-[active=true]:hover:text-primary-foreground [&>svg]:data-[active=true]:text-primary-foreground"
                                >
                                    <Link href={item.href} prefetch>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
