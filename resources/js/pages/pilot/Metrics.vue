<script setup lang="ts">
/**
 * Pilot metrics (FalaDev.md, section 12). Numbers as observed, with no
 * targets: the document says not to set them before the first group.
 * Every bar prints its number, so the page doubles as its own table view.
 */
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { recorderErrorMessages } from '@/composables/useAudioRecorder';
import type { RecorderError } from '@/composables/useAudioRecorder';
import { MIN_PEAK_LEVEL } from '@/lib/microphone';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { metrics as metricsRoute } from '@/routes/pilot';

type BarRow = { label: string; count: number; title: string };

const props = defineProps<{
    days: number;
    periods: number[];
    metrics: {
        funnel: Array<{ key: string; label: string; count: number }>;
        durations: {
            interviewMinutesAvg: number | null;
            reportSecondsMedian: number | null;
            reportSecondsMax: number | null;
        };
        answers: {
            perCompletedAvg: number | null;
            repeatsPerQuestion: number | null;
        };
        returning: { users: number; returningUsers: number };
        microphone: {
            checks: number;
            checksPassed: number;
            peakBuckets: Array<{
                range: string;
                passed: number;
                failed: number;
            }>;
            errors: Record<string, number>;
        };
        failures: {
            transcriptionRequests: number;
            transcriptionFailures: number;
            evaluationFailures: number;
            reportsAwaitingRetry: number;
        };
        usefulness: {
            reports: number;
            rated: number;
            useful: number;
            comments: Array<{
                useful: boolean;
                comment: string;
                date: string | null;
            }>;
        };
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Início', href: dashboard() }],
    },
});

const number = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 1 });
const percent = (part: number, whole: number): string =>
    whole > 0 ? `${Math.round((part / whole) * 100)}%` : '—';
const orDash = (value: number | null, suffix = ''): string =>
    value === null ? '—' : `${number.format(value)}${suffix}`;

const periodLabel = (days: number): string =>
    days === 0 ? 'Tudo' : `${days} dias`;

const m = computed(() => props.metrics);
const started = computed(
    () => m.value.funnel.find((s) => s.key === 'started')?.count ?? 0,
);
const completed = computed(
    () => m.value.funnel.find((s) => s.key === 'completed')?.count ?? 0,
);

const tiles = computed(() => [
    {
        label: 'Concluídas / iniciadas',
        value: percent(completed.value, started.value),
        detail: `${completed.value} de ${started.value}`,
    },
    {
        label: 'Tempo até o relatório (mediana)',
        value: orDash(m.value.durations.reportSecondsMedian, ' s'),
        detail: `máximo ${orDash(m.value.durations.reportSecondsMax, ' s')}`,
    },
    {
        label: 'Duração média da entrevista',
        value: orDash(m.value.durations.interviewMinutesAvg, ' min'),
        detail: `${orDash(m.value.answers.perCompletedAvg)} respostas por entrevista concluída`,
    },
    {
        label: 'Voltaram (2+ entrevistas)',
        value: percent(
            m.value.returning.returningUsers,
            m.value.returning.users,
        ),
        detail: `${m.value.returning.returningUsers} de ${m.value.returning.users} pessoas`,
    },
    {
        label: 'Relatórios úteis',
        value: percent(m.value.usefulness.useful, m.value.usefulness.rated),
        detail: `${m.value.usefulness.rated} de ${m.value.usefulness.reports} relatórios avaliados`,
    },
]);

/** Funnel: each stage as a share of the sessions configured. */
const funnelRows = computed<BarRow[]>(() => {
    const total = m.value.funnel[0]?.count ?? 0;

    return m.value.funnel.map((stage, i) => {
        const previous = i > 0 ? m.value.funnel[i - 1].count : stage.count;

        return {
            label: stage.label,
            count: stage.count,
            title: `${stage.label}: ${stage.count} (${percent(stage.count, total)} das configuradas; ${percent(stage.count, previous)} da etapa anterior)`,
        };
    });
});

const peakRows = computed<BarRow[]>(() =>
    m.value.microphone.peakBuckets.map((bucket) => {
        const count = bucket.passed + bucket.failed;

        return {
            label: bucket.range.replaceAll('.', ','),
            count,
            title: `Pico entre ${bucket.range}: ${count} testes (${bucket.passed} aprovados, ${bucket.failed} reprovados)`,
        };
    }),
);

const errorRows = computed(() =>
    Object.entries(m.value.microphone.errors).map(([code, count]) => ({
        code,
        count,
        // Short version of the message the user saw.
        label:
            recorderErrorMessages[code as RecorderError]?.split('.')[0] ?? code,
    })),
);

const maxOf = (rows: BarRow[]): number =>
    Math.max(1, ...rows.map((row) => row.count));

const dateFormatter = new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: 'short',
});
</script>

<template>
    <Head title="Métricas do piloto" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Métricas do piloto"
            description="Números observados, sem metas. Contam as entrevistas configuradas no período."
        />

        <!-- Period filter: one row, above everything -->
        <nav
            class="flex flex-wrap gap-2"
            aria-label="Período"
            data-test="period-filter"
        >
            <Link
                v-for="period in periods"
                :key="period"
                :href="metricsRoute({ query: { days: period } })"
                preserve-scroll
                :aria-current="period === days ? 'page' : undefined"
                :class="
                    cn(
                        'rounded-md border px-3 py-1.5 text-sm transition-colors',
                        period === days
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border hover:bg-accent',
                    )
                "
            >
                {{ periodLabel(period) }}
            </Link>
        </nav>

        <!-- Headline numbers -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <Card v-for="tile in tiles" :key="tile.label" class="gap-2 py-4">
                <CardContent class="space-y-1 px-4">
                    <p class="text-xs text-muted-foreground">
                        {{ tile.label }}
                    </p>
                    <p class="text-2xl font-semibold">{{ tile.value }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ tile.detail }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <!-- Funnel -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base"
                        >Onde as pessoas param</CardTitle
                    >
                    <CardDescription>
                        Quantas sessões chegaram a cada etapa. No computador,
                        passe o mouse sobre uma etapa para ver a perda.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-3" data-test="funnel">
                        <li
                            v-for="row in funnelRows"
                            :key="row.label"
                            :title="row.title"
                            :aria-label="row.title"
                            class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1 sm:grid-cols-[11rem_minmax(0,1fr)_3rem]"
                        >
                            <span class="text-sm">{{ row.label }}</span>
                            <span
                                class="col-span-2 h-2.5 rounded-r bg-muted sm:col-span-1 sm:col-start-2 sm:row-start-1"
                                aria-hidden="true"
                            >
                                <span
                                    class="block h-full rounded-r bg-primary"
                                    :style="{
                                        width: `${(row.count / maxOf(funnelRows)) * 100}%`,
                                    }"
                                />
                            </span>
                            <span
                                class="col-start-2 row-start-1 text-right text-sm font-medium tabular-nums sm:col-start-3"
                                >{{ row.count }}</span
                            >
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <!-- Microphone -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Teste de microfone</CardTitle>
                    <CardDescription>
                        {{ m.microphone.checks }}
                        {{ m.microphone.checks === 1 ? 'teste' : 'testes' }},
                        {{
                            percent(
                                m.microphone.checksPassed,
                                m.microphone.checks,
                            )
                        }}
                        aprovados. Volume máximo medido (0 a 1); o limite atual
                        é {{ String(MIN_PEAK_LEVEL).replace('.', ',') }}: faixas
                        abaixo dele reprovam.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">
                    <ul class="space-y-3" data-test="peak-buckets">
                        <li
                            v-for="row in peakRows"
                            :key="row.label"
                            :title="row.title"
                            :aria-label="row.title"
                            class="grid grid-cols-[6.5rem_minmax(0,1fr)_2.5rem] items-center gap-3"
                        >
                            <span class="text-sm tabular-nums">{{
                                row.label
                            }}</span>
                            <span
                                class="h-2.5 rounded-r bg-muted"
                                aria-hidden="true"
                            >
                                <span
                                    class="block h-full rounded-r bg-primary"
                                    :style="{
                                        width: `${(row.count / maxOf(peakRows)) * 100}%`,
                                    }"
                                />
                            </span>
                            <span
                                class="text-right text-sm font-medium tabular-nums"
                                >{{ row.count }}</span
                            >
                        </li>
                    </ul>

                    <div class="space-y-2">
                        <p class="text-sm font-medium">Erros de microfone</p>
                        <p
                            v-if="errorRows.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            Nenhum erro no período.
                        </p>
                        <table v-else class="w-full text-sm">
                            <tbody>
                                <tr
                                    v-for="row in errorRows"
                                    :key="row.code"
                                    class="border-b border-border last:border-0"
                                >
                                    <td class="py-1.5 pr-3">{{ row.label }}</td>
                                    <td
                                        class="py-1.5 text-right font-medium tabular-nums"
                                    >
                                        {{ row.count }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <!-- Failures -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Falhas</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl
                        class="grid grid-cols-[minmax(0,1fr)_auto] gap-x-4 gap-y-2 text-sm"
                    >
                        <dt>Transcrições pedidas</dt>
                        <dd class="text-right font-medium tabular-nums">
                            {{ m.failures.transcriptionRequests }}
                        </dd>
                        <dt>Transcrições que falharam</dt>
                        <dd class="text-right font-medium tabular-nums">
                            {{ m.failures.transcriptionFailures }}
                        </dd>
                        <dt>Relatórios que falharam</dt>
                        <dd class="text-right font-medium tabular-nums">
                            {{ m.failures.evaluationFailures }}
                        </dd>
                        <dt>Relatórios aguardando nova tentativa</dt>
                        <dd class="text-right font-medium tabular-nums">
                            {{ m.failures.reportsAwaitingRetry }}
                        </dd>
                        <dt>Repetições por pergunta (média)</dt>
                        <dd class="text-right font-medium tabular-nums">
                            {{ orDash(m.answers.repeatsPerQuestion) }}
                        </dd>
                    </dl>
                </CardContent>
            </Card>

            <!-- Comments -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base"
                        >Comentários sobre os relatórios</CardTitle
                    >
                    <CardDescription>Os 10 mais recentes.</CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="m.usefulness.comments.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Nenhum comentário no período.
                    </p>
                    <ul v-else class="space-y-3 text-sm">
                        <li
                            v-for="(item, i) in m.usefulness.comments"
                            :key="i"
                            class="space-y-0.5"
                        >
                            <p class="text-xs text-muted-foreground">
                                {{ item.useful ? 'Útil' : 'Pouco útil' }} ·
                                {{
                                    item.date
                                        ? dateFormatter.format(
                                              new Date(item.date),
                                          )
                                        : ''
                                }}
                            </p>
                            <p>{{ item.comment }}</p>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
