<script setup lang="ts">
/**
 * A lesson or a review, one exercise at a time. A wrong exercise comes back
 * at the end of the queue until it is right; only the first try is sent to
 * the server, which grades it again and records the result. The page's own
 * grading only gives instant feedback.
 */
import { Head, router } from '@inertiajs/vue3';
import {
    CircleAlert,
    CircleCheck,
    CircleX,
    LoaderCircle,
    X,
} from '@lucide/vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import ChoiceExercise from '@/components/study/ChoiceExercise.vue';
import OrderExercise from '@/components/study/OrderExercise.vue';
import PracticeResult from '@/components/study/PracticeResult.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { postJson } from '@/lib/http';
import { isCorrect, newAttemptId, shuffled } from '@/lib/study';
import { index as studyIndex } from '@/routes/study';
import { complete as completeLesson } from '@/routes/study/lessons';
import { complete as completeReview } from '@/routes/study/review';
import type {
    StudyAnswer,
    StudyExercise,
    StudyLessonLink,
    StudyOutcome,
} from '@/types';

const props = defineProps<{
    mode: 'lesson' | 'review';
    lesson: StudyLessonLink | null;
    /** Unit title, or "Revisão". */
    context: string;
    exercises: StudyExercise[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Estudar', href: studyIndex() }],
    },
});

type Phase = 'answering' | 'feedback' | 'submitting' | 'failed' | 'done';

const byKey = new Map(
    props.exercises.map((exercise) => [exercise.key, exercise]),
);
const total = props.exercises.length;
const attempt = newAttemptId();

/** Keys still to answer; the first one is on screen. */
const queue = ref<string[]>(props.exercises.map((exercise) => exercise.key));
/** First answer per exercise: the one that counts. */
const firstTry = new Map<string, StudyAnswer>();
/** firstTry.size, kept reactive. */
const answered = ref(0);
const cleared = ref(0);
/** Bumped on every new exercise on screen; keys the slide transition. */
const turn = ref(0);
const phase = ref<Phase>('answering');
const lastCorrect = ref(false);
const errorMessage = ref('');
const outcome = ref<StudyOutcome | null>(null);
const exitOpen = ref(false);

// Current answer: the option text, or indexes into the shuffled words.
const choice = ref<string | null>(null);
const picked = ref<number[]>([]);
const displayOptions = ref<string[]>([]);
const displayTokens = ref<string[]>([]);

const current = computed<StudyExercise | null>(() =>
    queue.value.length > 0 ? (byKey.get(queue.value[0]) ?? null) : null,
);
const isRetry = computed(
    // answered.value makes this recompute when firstTry changes.
    () =>
        answered.value > 0 &&
        current.value !== null &&
        firstTry.has(current.value.key) &&
        phase.value === 'answering',
);
const progress = computed(() => Math.round((cleared.value / total) * 100));

const answer = computed<StudyAnswer | null>(() => {
    if (!current.value) {
        return null;
    }

    if (current.value.type === 'order') {
        return picked.value.length > 0
            ? picked.value.map((index) => displayTokens.value[index])
            : null;
    }

    return choice.value;
});

const correctText = computed(() => {
    const exercise = current.value;

    if (!exercise) {
        return '';
    }

    return exercise.type === 'order' ? exercise.sentence : exercise.answer;
});

const heading = ref<HTMLElement | null>(null);
const continueButton = ref<InstanceType<typeof Button> | null>(null);

function prepare(exercise: StudyExercise | null): void {
    choice.value = null;
    picked.value = [];
    // Reshuffled on every try, so a retry is not answered by position.
    displayOptions.value =
        exercise && exercise.type !== 'order' ? shuffled(exercise.options) : [];
    displayTokens.value =
        exercise && exercise.type === 'order' ? shuffled(exercise.tokens) : [];
}

function check(): void {
    const exercise = current.value;

    if (!exercise || answer.value === null || phase.value !== 'answering') {
        return;
    }

    const right = isCorrect(exercise, answer.value);

    if (!firstTry.has(exercise.key)) {
        firstTry.set(exercise.key, answer.value);
        answered.value = firstTry.size;
    }

    if (right) {
        cleared.value += 1;
    } else {
        queue.value = [...queue.value, exercise.key];
    }

    lastCorrect.value = right;
    phase.value = 'feedback';
    void nextTick(() => focusElement(continueButton.value?.$el));
}

function next(): void {
    if (phase.value !== 'feedback') {
        return;
    }

    queue.value = queue.value.slice(1);

    if (queue.value.length === 0) {
        void submit();

        return;
    }

    prepare(current.value);
    turn.value += 1;
    phase.value = 'answering';
    void nextTick(() => heading.value?.focus());
}

async function submit(): Promise<void> {
    phase.value = 'submitting';
    errorMessage.value = '';

    const url =
        props.mode === 'lesson' && props.lesson
            ? completeLesson(props.lesson.slug).url
            : completeReview().url;

    try {
        const response = await postJson(url, {
            attempt,
            answers: [...firstTry].map(([key, value]) => ({ key, value })),
        });

        if (response.ok) {
            outcome.value = (await response.json()) as StudyOutcome;
            phase.value = 'done';

            return;
        }

        errorMessage.value = await failureMessage(response);
    } catch {
        errorMessage.value =
            'Sem conexão. Confira a internet e tente de novo; suas respostas continuam aqui.';
    }

    phase.value = 'failed';
}

async function failureMessage(response: Response): Promise<string> {
    if (response.status === 419) {
        return 'Sua sessão expirou. Recarregue a página e faça a lição de novo.';
    }

    if (response.status === 429) {
        return 'Muitas tentativas seguidas. Espere um minuto e tente de novo.';
    }

    if (response.status === 422) {
        const body = (await response.json().catch(() => null)) as {
            message?: string;
        } | null;

        return body?.message ?? 'Não foi possível salvar o resultado.';
    }

    return 'Não foi possível salvar o resultado. Tente de novo; nada foi contado em dobro.';
}

function focusElement(element: unknown): void {
    if (element instanceof HTMLElement) {
        element.focus();
    }
}

// --- Keyboard: 1-4 pick an option, Enter checks and continues, Backspace
// removes the last word. Buttons keep their own Enter.

function onKeydown(event: KeyboardEvent): void {
    if (event.ctrlKey || event.metaKey || event.altKey || exitOpen.value) {
        return;
    }

    const target = event.target as HTMLElement | null;
    const onButton = target?.closest('button, a') !== null;
    const exercise = current.value;

    if (event.key === 'Enter' && !onButton) {
        if (phase.value === 'answering') {
            event.preventDefault();
            check();
        } else if (phase.value === 'feedback') {
            event.preventDefault();
            next();
        }

        return;
    }

    if (phase.value !== 'answering' || !exercise) {
        return;
    }

    if (exercise.type !== 'order' && /^[1-9]$/.test(event.key)) {
        const option = displayOptions.value[Number(event.key) - 1];

        if (option !== undefined) {
            choice.value = option;
        }
    }

    if (exercise.type === 'order' && event.key === 'Backspace' && !onButton) {
        event.preventDefault();
        picked.value = picked.value.slice(0, -1);
    }
}

// --- Leaving in the middle loses the lesson: ask first.

const inProgress = computed(() => answered.value > 0 && phase.value !== 'done');
let leaving = false;

function onBeforeUnload(event: BeforeUnloadEvent): void {
    if (inProgress.value) {
        event.preventDefault();
    }
}

const removeRouterGuard = router.on('before', (event) => {
    const visit = event.detail.visit;

    if (leaving || !inProgress.value || visit.method !== 'get') {
        return;
    }

    return window.confirm(
        'Sair da lição? O progresso desta lição não é salvo.',
    );
});

function leave(): void {
    leaving = true;
    router.visit(studyIndex());
}

onMounted(() => {
    prepare(current.value);
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('beforeunload', onBeforeUnload);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('beforeunload', onBeforeUnload);
    removeRouterGuard();
});

watch(phase, (value) => {
    if (value === 'done') {
        window.removeEventListener('beforeunload', onBeforeUnload);
    }
});

const title = computed(() =>
    props.mode === 'lesson' && props.lesson ? props.lesson.title : 'Revisão',
);
</script>

<template>
    <Head :title="title" />

    <div class="flex h-full flex-1 flex-col p-4 md:p-6">
        <PracticeResult
            v-if="phase === 'done' && outcome"
            :outcome="outcome"
            :mode="mode"
        />

        <div v-else class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6">
            <!-- Progress -->
            <div class="flex items-center gap-3">
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Sair da lição"
                    data-test="exit-practice"
                    @click="inProgress ? (exitOpen = true) : leave()"
                >
                    <X />
                </Button>
                <div
                    class="h-3 flex-1 overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    :aria-valuenow="cleared"
                    aria-valuemin="0"
                    :aria-valuemax="total"
                    :aria-label="`${cleared} de ${total} exercícios concluídos`"
                >
                    <div
                        class="h-full rounded-full bg-primary transition-[width] duration-500 ease-out motion-reduce:transition-none"
                        :style="{ width: `${progress}%` }"
                    />
                </div>
                <span
                    class="w-12 text-right text-sm text-muted-foreground tabular-nums"
                >
                    {{ cleared }}/{{ total }}
                </span>
            </div>

            <!-- Exercise -->
            <Transition
                mode="out-in"
                enter-active-class="transition duration-200 ease-out motion-reduce:transition-none"
                enter-from-class="translate-x-4 opacity-0"
                leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
                leave-to-class="-translate-x-4 opacity-0"
            >
                <section
                    v-if="current"
                    :key="turn"
                    class="flex flex-1 flex-col gap-6"
                    aria-labelledby="exercise-prompt"
                    :data-exercise="current.key"
                >
                    <header class="space-y-2">
                        <p
                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            {{ context }}
                            <span v-if="isRetry" class="text-voice">
                                · De novo, sem pressa</span
                            >
                        </p>
                        <h1
                            id="exercise-prompt"
                            ref="heading"
                            tabindex="-1"
                            class="text-xl font-semibold text-balance outline-none md:text-2xl"
                        >
                            {{ current.prompt }}
                        </h1>
                    </header>

                    <ChoiceExercise
                        v-if="current.type !== 'order'"
                        v-model="choice"
                        :exercise="current"
                        :options="displayOptions"
                        :revealed="phase !== 'answering'"
                    />
                    <OrderExercise
                        v-else
                        v-model="picked"
                        :exercise="current"
                        :tokens="displayTokens"
                        :revealed="phase !== 'answering'"
                        :correct="lastCorrect"
                    />
                </section>
            </Transition>

            <!-- Check / feedback / saving -->
            <div class="sticky bottom-4 mt-auto">
                <div
                    v-if="phase === 'answering'"
                    class="flex justify-end bg-background/95 py-2 backdrop-blur"
                >
                    <Button
                        size="lg"
                        class="w-full sm:w-auto"
                        :disabled="answer === null"
                        data-test="check-answer"
                        @click="check"
                    >
                        Verificar
                    </Button>
                </div>

                <div
                    v-else-if="phase === 'feedback' && current"
                    class="flex flex-col gap-4 rounded-xl border-2 p-4 shadow-lg motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2 sm:flex-row sm:items-end"
                    :class="
                        lastCorrect
                            ? 'border-success bg-success-soft'
                            : 'border-destructive-foreground bg-destructive-soft'
                    "
                    role="status"
                    aria-live="assertive"
                    data-test="answer-feedback"
                >
                    <div class="flex flex-1 gap-3">
                        <CircleCheck
                            v-if="lastCorrect"
                            class="mt-0.5 size-6 shrink-0 text-success"
                            aria-hidden="true"
                        />
                        <CircleX
                            v-else
                            class="mt-0.5 size-6 shrink-0 text-destructive-foreground"
                            aria-hidden="true"
                        />
                        <div class="space-y-1">
                            <p
                                class="font-semibold"
                                :class="
                                    lastCorrect
                                        ? 'text-success'
                                        : 'text-destructive-foreground'
                                "
                            >
                                {{
                                    lastCorrect
                                        ? 'Isso mesmo!'
                                        : 'Resposta certa:'
                                }}
                            </p>
                            <p
                                v-if="!lastCorrect"
                                class="font-medium"
                                lang="en"
                            >
                                {{ correctText }}
                            </p>
                            <p class="text-sm text-foreground/80">
                                {{ current.explain }}
                            </p>
                            <p
                                v-if="!lastCorrect"
                                class="text-xs text-muted-foreground"
                            >
                                Ela volta no fim da lição e na revisão de
                                amanhã.
                            </p>
                        </div>
                    </div>
                    <Button
                        ref="continueButton"
                        size="lg"
                        class="w-full sm:w-auto"
                        data-test="continue-practice"
                        @click="next"
                    >
                        Continuar
                    </Button>
                </div>

                <div
                    v-else-if="phase === 'submitting'"
                    class="flex items-center justify-center gap-2 rounded-xl border border-border bg-card px-4 py-5 text-muted-foreground"
                    role="status"
                >
                    <LoaderCircle
                        class="size-5 animate-spin"
                        aria-hidden="true"
                    />
                    Salvando seu resultado…
                </div>

                <div
                    v-else-if="phase === 'failed'"
                    class="flex flex-col gap-3 rounded-xl border-2 border-destructive-foreground bg-destructive-soft p-4 sm:flex-row sm:items-center"
                    role="alert"
                >
                    <CircleAlert
                        class="size-5 shrink-0 text-destructive-foreground"
                        aria-hidden="true"
                    />
                    <p class="flex-1 text-sm">{{ errorMessage }}</p>
                    <Button size="lg" data-test="retry-submit" @click="submit">
                        Tentar de novo
                    </Button>
                </div>
            </div>
        </div>

        <Dialog v-model:open="exitOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Sair da lição?</DialogTitle>
                    <DialogDescription>
                        O progresso desta lição não é salvo. Na próxima vez, ela
                        começa do início.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="outline">Continuar a lição</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        data-test="confirm-exit"
                        @click="leave"
                    >
                        Sair
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
