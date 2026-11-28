<script setup lang="ts">
/**
 * End of a lesson or review: first-try score, XP, the streak and what comes
 * next. Every number comes from the server's grading.
 */
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Flame,
    RotateCcw,
    ShieldCheck,
    Sparkles,
    Trophy,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { index as studyIndex, review } from '@/routes/study';
import { show as showLesson } from '@/routes/study/lessons';
import type { StudyOutcome } from '@/types';

const props = defineProps<{
    outcome: StudyOutcome;
    mode: 'lesson' | 'review';
}>();

const heading = ref<HTMLElement | null>(null);

onMounted(() => heading.value?.focus());

const perfect = computed(
    () =>
        props.outcome.total > 0 &&
        props.outcome.correct === props.outcome.total,
);

const title = computed(() => {
    if (perfect.value) {
        return props.mode === 'lesson'
            ? 'Lição perfeita!'
            : 'Revisão perfeita!';
    }

    return props.mode === 'lesson' ? 'Lição concluída' : 'Revisão concluída';
});

const days = (count: number) => (count === 1 ? '1 dia' : `${count} dias`);
const phrases = (count: number) =>
    count === 1 ? '1 frase' : `${count} frases`;
</script>

<template>
    <section
        class="mx-auto flex w-full max-w-xl flex-col items-center gap-8 py-6 text-center motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-4"
        aria-labelledby="result-title"
        data-test="practice-result"
    >
        <div
            class="flex size-20 items-center justify-center rounded-full bg-primary text-primary-foreground motion-safe:animate-in motion-safe:duration-500 motion-safe:zoom-in-50"
            aria-hidden="true"
        >
            <Trophy v-if="perfect" class="size-10" />
            <Sparkles v-else class="size-10" />
        </div>

        <div class="space-y-2">
            <h1
                id="result-title"
                ref="heading"
                tabindex="-1"
                class="text-3xl font-semibold tracking-tight outline-none"
            >
                {{ title }}
            </h1>
            <p class="text-muted-foreground">
                <template v-if="outcome.streakExtended">
                    Meta de hoje cumprida.
                </template>
                <template v-else
                    >Você já tinha estudado hoje. Bom reforço!</template
                >
            </p>
        </div>

        <dl class="grid w-full grid-cols-3 gap-3">
            <div class="rounded-xl border border-border bg-card p-4">
                <dt class="text-xs text-muted-foreground">De primeira</dt>
                <dd
                    class="mt-1 text-2xl font-semibold"
                    data-test="result-score"
                >
                    {{ outcome.correct }}/{{ outcome.total }}
                </dd>
            </div>
            <div class="rounded-xl border border-border bg-card p-4">
                <dt class="text-xs text-muted-foreground">XP ganho</dt>
                <dd class="mt-1 text-2xl font-semibold" data-test="result-xp">
                    +{{ outcome.xp }}
                </dd>
            </div>
            <div class="rounded-xl border border-border bg-card p-4">
                <dt class="text-xs text-muted-foreground">Sequência</dt>
                <dd
                    class="mt-1 flex items-center justify-center gap-1 text-2xl font-semibold"
                    data-test="result-streak"
                >
                    <Flame class="size-5 text-voice" aria-hidden="true" />
                    {{ days(outcome.streak) }}
                </dd>
            </div>
        </dl>

        <ul
            v-if="
                outcome.sentToReview ||
                outcome.mastered ||
                outcome.freezesUsed ||
                outcome.freezeEarned
            "
            class="w-full space-y-2 text-left text-sm"
        >
            <li
                v-if="outcome.sentToReview"
                class="flex gap-3 rounded-lg bg-muted p-3"
            >
                <RotateCcw class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                <span>
                    {{ phrases(outcome.sentToReview) }} que você errou de
                    primeira
                    {{ outcome.sentToReview === 1 ? 'volta' : 'voltam' }}
                    na revisão de amanhã.
                </span>
            </li>
            <li
                v-if="outcome.mastered"
                class="flex gap-3 rounded-lg bg-muted p-3"
            >
                <Trophy class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                <span>
                    {{ phrases(outcome.mastered) }}
                    {{ outcome.mastered === 1 ? 'dominada' : 'dominadas' }}:
                    {{ outcome.mastered === 1 ? 'saiu' : 'saíram' }} da revisão.
                </span>
            </li>
            <li
                v-if="outcome.freezesUsed"
                class="flex gap-3 rounded-lg bg-muted p-3"
            >
                <ShieldCheck
                    class="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                <span>
                    {{
                        outcome.freezesUsed === 1
                            ? 'Uma proteção cobriu o dia sem estudo'
                            : `${outcome.freezesUsed} proteções cobriram os dias sem estudo`
                    }}
                    e sua sequência continua.
                </span>
            </li>
            <li
                v-if="outcome.freezeEarned"
                class="flex gap-3 rounded-lg bg-muted p-3"
            >
                <ShieldCheck
                    class="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                <span>
                    Você ganhou uma proteção: ela cobre um dia sem estudo sem
                    perder a sequência.
                </span>
            </li>
        </ul>

        <div class="flex w-full flex-col gap-2 sm:flex-row sm:justify-center">
            <Button
                v-if="mode === 'review' && outcome.reviewsDue > 0"
                size="lg"
                as-child
            >
                <Link :href="review()">
                    Revisar mais {{ phrases(outcome.reviewsDue) }}
                    <ArrowRight />
                </Link>
            </Button>
            <Button v-else-if="outcome.nextLesson" size="lg" as-child>
                <Link
                    :href="showLesson(outcome.nextLesson.slug)"
                    data-test="next-lesson-link"
                >
                    Próxima lição: {{ outcome.nextLesson.title }}
                    <ArrowRight />
                </Link>
            </Button>
            <Button
                size="lg"
                :variant="
                    outcome.nextLesson || outcome.reviewsDue
                        ? 'outline'
                        : 'default'
                "
                as-child
            >
                <Link :href="studyIndex()">Voltar para a trilha</Link>
            </Button>
        </div>

        <p class="text-xs text-muted-foreground">
            Total: {{ outcome.totalXp }} XP · Maior sequência:
            {{ days(outcome.longestStreak) }}
        </p>
    </section>
</template>
