<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ChartColumn,
    GraduationCap,
    History,
    LayoutGrid,
    Mic,
    RotateCcw,
} from '@lucide/vue';
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
import { index as studyIndex, review as studyReview } from '@/routes/study';
import type { NavGroup } from '@/types';

const page = usePage();

const navGroups = computed<NavGroup[]>(() => {
    const groups: NavGroup[] = [
        {
            label: 'Geral',
            items: [{ title: 'Início', href: dashboard(), icon: LayoutGrid }],
        },
        {
            label: 'Entrevistar',
            items: [
                {
                    title: 'Nova entrevista',
                    href: createInterview(),
                    icon: Mic,
                },
                { title: 'Histórico', href: interviewHistory(), icon: History },
            ],
        },
        {
            label: 'Estudar',
            items: [
                { title: 'Trilha', href: studyIndex(), icon: GraduationCap },
                {
                    title: 'Revisão',
                    href: studyReview(),
                    icon: RotateCcw,
                    badge: page.props.study?.reviewsDue ?? 0,
                },
            ],
        },
    ];

    // Only pilot admins (FALADEV_PILOT_ADMINS) see the metrics page.
    if (page.props.auth.canViewPilotMetrics) {
        groups.push({
            label: 'Piloto',
            items: [
                {
                    title: 'Métricas do piloto',
                    href: pilotMetrics(),
                    icon: ChartColumn,
                },
            ],
        });
    }

    return groups;
});
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
            <NavMain
                v-for="group in navGroups"
                :key="group.label"
                :label="group.label"
                :items="group.items"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
