<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { History, Mic } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create, show } from '@/routes/interviews';
import type {
    InterviewSessionSummary,
    InterviewStatus,
    UsageSummary,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Início',
                href: dashboard(),
            },
        ],
    },
});

const props = defineProps<{
    recentSessions: InterviewSessionSummary[];
    usage: UsageSummary;
}>();

const page = usePage();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);

const usagePercent = computed(() =>
    props.usage.limit > 0
        ? Math.min((props.usage.used / props.usage.limit) * 100, 100)
        : 100,
);

// Colour is never the only signal: every status also shows its text label.
const statusDotClass = (status: InterviewStatus): string => {
    switch (status) {
        case 'report_ready':
        case 'completed':
            return 'bg-success';
        case 'failed_retryable':
            return 'bg-destructive';
        case 'evaluating':
            return 'bg-highlight';
        case 'draft':
        case 'ready':
            return 'bg-muted-foreground';
        default:
            return 'bg-voice';
    }
};

const dateFormatter = new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

const formatDate = (iso: string | null): string =>
    iso ? dateFormatter.format(new Date(iso)) : '';
</script>

<template>
    <Head title="Início" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="`Olá, ${firstName}`"
            description="Pratique entrevistas técnicas em inglês, por voz e com IA."
        />

        <div class="grid gap-4 md:grid-cols-3">
            <Card class="md:col-span-2">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Mic class="size-5 text-voice" aria-hidden="true" />
                        Nova entrevista
                    </CardTitle>
                    <CardDescription>
                        Escolha a categoria (algoritmos, system design ou
                        comportamental), o nível e a duração. A entrevista é
                        conduzida em inglês e termina com feedback técnico, de
                        comunicação e de inglês.
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col items-start gap-2">
                    <Button
                        v-if="usage.remaining > 0"
                        size="lg"
                        as-child
                        data-test="new-interview-button"
                    >
                        <Link :href="create()">Nova entrevista</Link>
                    </Button>
                    <template v-else>
                        <Button size="lg" disabled>Nova entrevista</Button>
                        <p class="text-xs text-muted-foreground">
                            Você atingiu o limite deste mês.
                        </p>
                    </template>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Uso neste mês</CardTitle>
                    <CardDescription>
                        Contam as entrevistas iniciadas, não as apenas
                        configuradas.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <p class="text-sm">
                        <span class="text-2xl font-semibold">{{
                            usage.used
                        }}</span>
                        <span class="text-muted-foreground">
                            de {{ usage.limit }} entrevistas</span
                        >
                    </p>
                    <div
                        class="h-2 w-full overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="usage.used"
                        aria-valuemin="0"
                        :aria-valuemax="usage.limit"
                        aria-label="Entrevistas usadas neste mês"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-[width]"
                            :style="{ width: `${usagePercent}%` }"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        <template v-if="usage.remaining > 0">
                            Restam {{ usage.remaining }}
                            {{
                                usage.remaining === 1
                                    ? 'entrevista'
                                    : 'entrevistas'
                            }}
                            neste mês.
                        </template>
                        <template v-else>
                            Você atingiu o limite deste mês.
                        </template>
                    </p>
                </CardContent>
            </Card>
        </div>

        <section aria-labelledby="recent-sessions-title" class="space-y-3">
            <h2
                id="recent-sessions-title"
                class="flex items-center gap-2 text-lg font-semibold"
            >
                <History
                    class="size-5 text-muted-foreground"
                    aria-hidden="true"
                />
                Entrevistas recentes
            </h2>

            <Card v-if="recentSessions.length === 0" class="border-dashed">
                <CardContent class="py-8 text-center">
                    <p class="font-medium">
                        Você ainda não fez nenhuma entrevista.
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Suas entrevistas e relatórios aparecerão aqui.
                    </p>
                </CardContent>
            </Card>

            <Card v-else class="py-0">
                <ul class="divide-y divide-border">
                    <li
                        v-for="session in recentSessions"
                        :key="session.id"
                        data-test="recent-session"
                    >
                        <Link
                            :href="show(session.id)"
                            class="flex flex-col gap-1 px-4 py-3 transition-colors hover:bg-accent focus-visible:bg-accent focus-visible:outline-none sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ session.category }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ session.level }} ·
                                    {{ session.duration }} ·
                                    {{ formatDate(session.createdAt) }}
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center gap-2 text-sm text-muted-foreground"
                            >
                                <span
                                    class="size-2 rounded-full"
                                    :class="statusDotClass(session.status)"
                                    aria-hidden="true"
                                />
                                {{ session.statusLabel }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </Card>
        </section>
    </div>
</template>
