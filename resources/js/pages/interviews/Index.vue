<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import InterviewSessionList from '@/components/interview/InterviewSessionList.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/interviews';
import type { InterviewSessionSummary, Paginated } from '@/types';

defineProps<{
    sessions: Paginated<InterviewSessionSummary>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: dashboard() },
            { title: 'Histórico', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Histórico" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Histórico"
            description="Todas as suas entrevistas. Abra uma para ver a transcrição e o relatório, ou para apagá-la."
        />

        <Card v-if="sessions.total === 0" class="border-dashed">
            <CardContent
                class="flex flex-col items-center gap-3 py-8 text-center"
            >
                <p class="font-medium">
                    Você ainda não fez nenhuma entrevista.
                </p>
                <Button as-child>
                    <Link :href="create()">Nova entrevista</Link>
                </Button>
            </CardContent>
        </Card>

        <template v-else>
            <InterviewSessionList :sessions="sessions.data" />

            <nav
                v-if="sessions.last_page > 1"
                class="flex items-center justify-between gap-2"
                aria-label="Paginação"
            >
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!sessions.prev_page_url"
                    :as-child="Boolean(sessions.prev_page_url)"
                >
                    <Link
                        v-if="sessions.prev_page_url"
                        :href="sessions.prev_page_url"
                        preserve-scroll
                    >
                        <ChevronLeft />
                        Anterior
                    </Link>
                    <template v-else><ChevronLeft /> Anterior</template>
                </Button>
                <span class="text-sm text-muted-foreground">
                    Página {{ sessions.current_page }} de
                    {{ sessions.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!sessions.next_page_url"
                    :as-child="Boolean(sessions.next_page_url)"
                >
                    <Link
                        v-if="sessions.next_page_url"
                        :href="sessions.next_page_url"
                        preserve-scroll
                    >
                        Próxima
                        <ChevronRight />
                    </Link>
                    <template v-else>Próxima <ChevronRight /></template>
                </Button>
            </nav>
        </template>
    </div>
</template>
