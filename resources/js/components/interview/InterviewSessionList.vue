<script setup lang="ts">
/**
 * List of interview sessions (dashboard and history), each linking to its
 * page. Colour is never the only signal: every status also shows its label.
 */
import { Link } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import { show } from '@/routes/interviews';
import type { InterviewSessionSummary, InterviewStatus } from '@/types';

defineProps<{
    sessions: InterviewSessionSummary[];
}>();

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
    <Card class="py-0">
        <ul class="divide-y divide-border">
            <li
                v-for="session in sessions"
                :key="session.id"
                data-test="session-list-item"
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
                            {{ session.level }} · {{ session.duration }} ·
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
</template>
