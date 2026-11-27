<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChartColumn, History, LayoutGrid, Mic } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
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
import {
    create as createInterview,
    index as interviewHistory,
} from '@/routes/interviews';
import { metrics as pilotMetrics } from '@/routes/pilot';
import type { NavItem } from '@/types';

const page = usePage();

const baseNavItems: NavItem[] = [
    {
        title: 'Início',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Nova entrevista',
        href: createInterview(),
        icon: Mic,
    },
    {
        title: 'Histórico',
        href: interviewHistory(),
        icon: History,
    },
];

// Only pilot admins (FALADEV_PILOT_ADMINS) see the metrics page.
const mainNavItems = computed<NavItem[]>(() =>
    page.props.auth.canViewPilotMetrics
        ? [
              ...baseNavItems,
              {
                  title: 'Métricas do piloto',
                  href: pilotMetrics(),
                  icon: ChartColumn,
              },
          ]
        : baseNavItems,
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
