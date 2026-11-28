<script setup lang="ts">
/**
 * The study track: streak, review queue and the lessons of each unit. A
 * unit's lessons open in order; units are independent.
 */
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    Flame,
    Lock,
    RotateCcw,
    ShieldCheck,
    Sparkles,
    Trophy,
} from '@lucide/vue';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { index as studyIndex, review } from '@/routes/study';
import { show as showLesson } from '@/routes/study/lessons';
import type { StudyLessonLink, StudyStats, StudyUnit } from '@/types';

const props = defineProps<{
    stats: StudyStats;
    units: StudyUnit[];
    nextLesson: StudyLessonLink | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Estudar', href: studyIndex() }],
    },
});

const days = (count: number) => (count === 1 ? '1 dia' : `${count} dias`);
const phrases = (count: number) =>
    count === 1 ? '1 frase' : `${count} frases`;

const streakMessage = computed(() => {
    if (props.stats.studiedToday) {
        return 'Meta de hoje cumprida. Volte amanhã para manter a sequência.';
    }

    if (props.stats.streak > 0) {
        return 'Faça uma lição ou uma revisão hoje para manter a sequência.';
    }

    return 'Uma lição ou revisão por dia mantém a sequência.';
});

const totalLessons = computed(() =>
    props.units.reduce((sum, unit) => sum + unit.lessons.length, 0),
);
const doneLessons = computed(() =>
    props.units.reduce((sum, unit) => sum + unit.completedCount, 0),
);
</script>

<template>
    <Head title="Estudar" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Estudar"
            description="Lições de 3 minutos com o inglês das entrevistas técnicas. As frases que você erra voltam para revisão nos dias certos."
        />

        <div class="grid gap-4 md:grid-cols-3">
            <!-- Streak -->
            <Card data-test="study-streak">
                <CardHeader>
                    <CardDescription>Sequência</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-3xl">
                        <Flame
                            class="size-7 transition-colors"
                            :class="
                                stats.studiedToday
                                    ? 'text-voice motion-safe:animate-pulse'
                                    : 'text-muted-foreground'
                            "
                            aria-hidden="true"
                        />
                        {{ days(stats.streak) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <p class="text-muted-foreground">{{ streakMessage }}</p>
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <p
                                    class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs"
                                    tabindex="0"
                                >
                                    <ShieldCheck
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    Proteções: {{ stats.freezes }} de
                                    {{ stats.maxFreezes }}
                                </p>
                            </TooltipTrigger>
                            <TooltipContent class="max-w-64">
                                Uma proteção cobre um dia sem estudo sem perder
                                a sequência. Você ganha uma a cada
                                {{ stats.freezeEveryDays }} dias seguidos.
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </CardContent>
            </Card>

            <!-- Review -->
            <Card
                :class="stats.reviewsDue > 0 ? 'border-primary' : ''"
                data-test="study-review"
            >
                <CardHeader>
                    <CardDescription>Revisão de hoje</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-3xl">
                        <RotateCcw
                            class="size-6 text-muted-foreground"
                            aria-hidden="true"
                        />
                        {{ phrases(stats.reviewsDue) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <template v-if="stats.reviewsDue > 0">
                        <p class="text-muted-foreground">
                            Frases que você errou, de volta no dia certo para
                            fixar.
                        </p>
                        <Button as-child data-test="start-review">
                            <Link :href="review()">
                                Revisar agora
                                <ArrowRight />
                            </Link>
                        </Button>
                    </template>
                    <p v-else class="text-muted-foreground">
                        <template v-if="stats.learning > 0">
                            Nada para hoje. {{ phrases(stats.learning) }} em
                            revisão
                            {{ stats.learning === 1 ? 'volta' : 'voltam' }}
                            nos próximos dias.
                        </template>
                        <template v-else>
                            Quando você errar uma frase numa lição, ela aparece
                            aqui no dia seguinte.
                        </template>
                    </p>
                </CardContent>
            </Card>

            <!-- XP and mastery -->
            <Card>
                <CardHeader>
                    <CardDescription>Seu progresso</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-3xl">
                        <Sparkles
                            class="size-6 text-muted-foreground"
                            aria-hidden="true"
                        />
                        {{ stats.totalXp }} XP
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div
                        class="h-2 w-full overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="doneLessons"
                        aria-valuemin="0"
                        :aria-valuemax="totalLessons"
                        aria-label="Lições concluídas"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-[width] duration-700 motion-reduce:transition-none"
                            :style="{
                                width: `${(doneLessons / totalLessons) * 100}%`,
                            }"
                        />
                    </div>
                    <p class="text-muted-foreground">
                        {{ doneLessons }} de {{ totalLessons }} lições ·
                        {{ phrases(stats.mastered) }}
                        {{ stats.mastered === 1 ? 'dominada' : 'dominadas' }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Next lesson -->
        <Card
            v-if="nextLesson"
            class="flex flex-col gap-4 bg-primary text-primary-foreground sm:flex-row sm:items-center sm:justify-between"
            data-test="study-next"
        >
            <CardHeader class="flex-1">
                <CardDescription class="text-primary-foreground/70">
                    {{
                        doneLessons === 0
                            ? 'Comece por aqui'
                            : 'Continue de onde parou'
                    }}
                </CardDescription>
                <CardTitle class="text-xl">{{ nextLesson.title }}</CardTitle>
            </CardHeader>
            <CardContent class="sm:py-0">
                <Button
                    size="lg"
                    variant="secondary"
                    class="w-full sm:w-auto"
                    as-child
                    data-test="start-next-lesson"
                >
                    <Link :href="showLesson(nextLesson.slug)">
                        Começar lição
                        <ArrowRight />
                    </Link>
                </Button>
            </CardContent>
        </Card>
        <Card v-else class="border-dashed">
            <CardContent class="flex items-center gap-3 py-6">
                <Trophy class="size-6 text-voice" aria-hidden="true" />
                <p>
                    Você concluiu todas as lições. Refaça as que quiser e
                    mantenha a revisão em dia.
                </p>
            </CardContent>
        </Card>

        <!-- Units -->
        <section
            v-for="unit in units"
            :key="unit.slug"
            class="space-y-3"
            :aria-labelledby="`unit-${unit.slug}`"
        >
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 :id="`unit-${unit.slug}`" class="text-lg font-semibold">
                        {{ unit.title }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ unit.description }}
                    </p>
                </div>
                <span
                    class="shrink-0 text-sm text-muted-foreground tabular-nums"
                >
                    {{ unit.completedCount }}/{{ unit.lessons.length }}
                </span>
            </div>

            <ol class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <li
                    v-for="(lesson, position) in unit.lessons"
                    :key="lesson.slug"
                >
                    <component
                        :is="lesson.status === 'locked' ? 'div' : Link"
                        v-bind="
                            lesson.status === 'locked'
                                ? { 'aria-disabled': 'true' }
                                : { href: showLesson(lesson.slug) }
                        "
                        class="group flex h-full flex-col gap-3 rounded-xl border-2 bg-card p-4 transition-[border-color,transform,box-shadow] duration-150"
                        :class="{
                            'border-primary shadow-sm hover:-translate-y-0.5':
                                lesson.status === 'next',
                            'border-border hover:-translate-y-0.5 hover:border-primary/60':
                                lesson.status === 'available' ||
                                lesson.status === 'completed',
                            'cursor-not-allowed border-border opacity-60':
                                lesson.status === 'locked',
                        }"
                        :data-test="`lesson-${lesson.slug}`"
                        :data-status="lesson.status"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="flex size-8 items-center justify-center rounded-full text-sm font-semibold"
                                :class="{
                                    'bg-primary text-primary-foreground':
                                        lesson.status === 'completed' ||
                                        lesson.status === 'next',
                                    'bg-muted text-muted-foreground':
                                        lesson.status === 'available' ||
                                        lesson.status === 'locked',
                                }"
                            >
                                <Check
                                    v-if="lesson.status === 'completed'"
                                    class="size-4"
                                    aria-label="Concluída"
                                />
                                <Lock
                                    v-else-if="lesson.status === 'locked'"
                                    class="size-4"
                                    aria-label="Bloqueada"
                                />
                                <template v-else>{{ position + 1 }}</template>
                            </span>
                            <span
                                v-if="lesson.bestCorrect !== null"
                                class="text-xs text-muted-foreground tabular-nums"
                                :title="'Melhor resultado de primeira'"
                            >
                                {{ lesson.bestCorrect }}/{{ lesson.exercises }}
                            </span>
                            <span
                                v-else-if="lesson.status === 'next'"
                                class="text-xs font-medium text-primary"
                            >
                                Próxima
                            </span>
                        </div>
                        <div class="space-y-1">
                            <h3 class="font-medium">{{ lesson.title }}</h3>
                            <p class="text-sm text-muted-foreground">
                                {{ lesson.description }}
                            </p>
                        </div>
                        <p
                            v-if="lesson.status === 'locked'"
                            class="mt-auto text-xs text-muted-foreground"
                        >
                            Conclua a lição anterior para abrir.
                        </p>
                        <p
                            v-else
                            class="mt-auto flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors group-hover:text-foreground"
                        >
                            {{
                                lesson.status === 'completed'
                                    ? 'Refazer'
                                    : `${lesson.exercises} exercícios · 3 min`
                            }}
                            <ArrowRight class="size-3.5" aria-hidden="true" />
                        </p>
                    </component>
                </li>
            </ol>
        </section>
    </div>
</template>
