<script setup lang="ts">
/**
 * One code challenge: the statement in English on the left, the editor and
 * the tests on the right. "Rodar testes" sends the code to the server, which
 * runs it in an isolated container and grades every test; when all pass,
 * the server keeps the code as the solution. Drafts stay in this browser
 * between visits.
 */
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CircleAlert,
    CircleCheck,
    CircleX,
    CloudOff,
    Languages,
    Lightbulb,
    LoaderCircle,
    Play,
    RotateCcw,
    Sparkles,
    Timer,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import CodeEditor from '@/components/challenges/CodeEditor.vue';
import DifficultyBadge from '@/components/challenges/DifficultyBadge.vue';
import StatementText from '@/components/challenges/StatementText.vue';
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
import { TooltipProvider } from '@/components/ui/tooltip';
import { postJson } from '@/lib/http';
import {
    index as challengesIndex,
    run as runRoute,
    show,
} from '@/routes/challenges';
import type { ChallengeDetails, ChallengeRunResult } from '@/types';

const props = defineProps<{
    challenge: ChallengeDetails;
    savedCode: string | null;
    solved: boolean;
    next: { slug: string; title: string } | null;
    xpReward: number;
    /** Seconds a run may take on the server. */
    timeLimit: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Desafios', href: challengesIndex() }],
    },
});

// --- Code and its draft --------------------------------------------------

const draftKey = `faladev.challenge.${props.challenge.slug}`;

function readDraft(): string | null {
    try {
        return window.localStorage.getItem(draftKey);
    } catch {
        return null;
    }
}

const code = ref(
    (typeof window !== 'undefined' ? readDraft() : null) ??
        props.savedCode ??
        props.challenge.starter,
);
let draftTimer: number | undefined;

watch(code, (value) => {
    window.clearTimeout(draftTimer);
    draftTimer = window.setTimeout(() => {
        try {
            window.localStorage.setItem(draftKey, value);
        } catch {
            // Private mode or full storage: the draft just is not kept.
        }
    }, 400);
});

onBeforeUnmount(() => window.clearTimeout(draftTimer));

const resetOpen = ref(false);

function resetCode(): void {
    code.value = props.challenge.starter;
    resetOpen.value = false;
}

// --- Running the tests ---------------------------------------------------

const running = ref(false);
const run = ref<ChallengeRunResult | null>(null);
/** The request itself failed (session, rate limit, network). */
const requestError = ref('');
const solvedNow = ref(props.solved);

const passedCount = computed(() =>
    run.value?.status === 'ok'
        ? run.value.results.filter((result) => result.passed).length
        : 0,
);
const allPassed = computed(
    () =>
        run.value?.status === 'ok' &&
        passedCount.value === props.challenge.tests.length,
);
/** Compiled languages take a little longer to start. */
const compiles = computed(() =>
    ['java', 'csharp', 'go'].includes(props.challenge.language),
);

async function runTests(): Promise<void> {
    if (running.value) {
        return;
    }

    running.value = true;
    requestError.value = '';

    try {
        const response = await postJson(runRoute(props.challenge.slug).url, {
            code: code.value,
        });

        if (response.ok) {
            run.value = (await response.json()) as ChallengeRunResult;

            if (run.value.outcome) {
                solvedNow.value = true;
            }
        } else {
            run.value = null;
            requestError.value = failureMessage(response.status);
        }
    } catch {
        run.value = null;
        requestError.value =
            'Sem conexão com o servidor. Seu código continua aqui; rode de novo quando a internet voltar.';
    } finally {
        running.value = false;
    }
}

function failureMessage(status: number): string {
    if (status === 419) {
        return 'Sua sessão expirou. Recarregue a página e rode de novo (o rascunho fica salvo).';
    }

    if (status === 429) {
        return 'Muitas execuções seguidas. Espere um minuto e rode de novo.';
    }

    if (status === 422) {
        return 'O código está vazio ou passou do tamanho máximo (20 mil caracteres).';
    }

    return 'Não foi possível rodar os testes agora. Tente de novo em instantes.';
}

// --- Statement helpers -----------------------------------------------------

const glossary = computed(() =>
    Object.fromEntries(
        props.challenge.glossary.map((entry) => [entry.term, entry.meaning]),
    ),
);
const hintsShown = ref(0);
const showTranslation = ref(false);
</script>

<template>
    <Head :title="challenge.title" />

    <TooltipProvider :delay-duration="150">
        <div
            class="grid flex-1 gap-4 p-4 md:p-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]"
        >
            <!-- Statement -->
            <article
                class="flex flex-col gap-6 rounded-xl border border-border bg-card p-5 lg:max-h-[calc(100svh-8rem)] lg:overflow-y-auto"
                aria-labelledby="challenge-title"
            >
                <header class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full bg-foreground px-2.5 py-0.5 text-xs font-medium text-background"
                            data-test="challenge-language"
                        >
                            {{ challenge.languageLabel }}
                        </span>
                        <DifficultyBadge :difficulty="challenge.difficulty" />
                        <span
                            v-if="solvedNow"
                            class="inline-flex items-center gap-1 text-xs font-medium text-success"
                        >
                            <CircleCheck class="size-3.5" aria-hidden="true" />
                            Resolvido
                        </span>
                    </div>
                    <h1
                        id="challenge-title"
                        class="text-2xl font-semibold tracking-tight"
                        lang="en"
                    >
                        {{ challenge.title }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Enunciado em inglês, como na entrevista. Palavras
                        sublinhadas mostram a tradução.
                    </p>
                </header>

                <div class="space-y-3 leading-relaxed">
                    <p
                        v-for="(paragraph, index) in challenge.statement"
                        :key="index"
                    >
                        <StatementText :text="paragraph" :glossary="glossary" />
                    </p>
                </div>

                <section aria-labelledby="examples-title" class="space-y-3">
                    <h2
                        id="examples-title"
                        class="text-sm font-semibold"
                        lang="en"
                    >
                        Examples
                    </h2>
                    <div
                        v-for="(example, index) in challenge.examples"
                        :key="index"
                        class="space-y-1 rounded-lg bg-muted p-3 font-mono text-sm"
                        lang="en"
                    >
                        <p>
                            <span class="text-muted-foreground">Input:</span>
                            {{ example.input }}
                        </p>
                        <p>
                            <span class="text-muted-foreground">Output:</span>
                            {{ example.output }}
                        </p>
                        <p v-if="example.explanation" class="font-sans">
                            <span class="text-muted-foreground"
                                >Explanation:</span
                            >
                            {{ example.explanation }}
                        </p>
                    </div>
                </section>

                <section
                    v-if="challenge.constraints.length"
                    aria-labelledby="constraints-title"
                    class="space-y-2"
                >
                    <h2
                        id="constraints-title"
                        class="text-sm font-semibold"
                        lang="en"
                    >
                        Constraints
                    </h2>
                    <ul class="list-disc space-y-1 pl-5 text-sm">
                        <li
                            v-for="(constraint, index) in challenge.constraints"
                            :key="index"
                        >
                            <StatementText :text="constraint" />
                        </li>
                    </ul>
                </section>

                <section aria-labelledby="hints-title" class="space-y-2">
                    <h2 id="hints-title" class="text-sm font-semibold">
                        Dicas
                    </h2>
                    <ol class="space-y-2 text-sm" aria-live="polite">
                        <li
                            v-for="(hint, index) in challenge.hints.slice(
                                0,
                                hintsShown,
                            )"
                            :key="index"
                            class="flex gap-2 rounded-lg border border-border p-3 motion-safe:animate-in motion-safe:fade-in"
                        >
                            <Lightbulb
                                class="mt-0.5 size-4 shrink-0 text-voice"
                                aria-hidden="true"
                            />
                            <StatementText :text="hint" />
                        </li>
                    </ol>
                    <Button
                        v-if="hintsShown < challenge.hints.length"
                        variant="outline"
                        size="sm"
                        data-test="show-hint"
                        @click="hintsShown++"
                    >
                        <Lightbulb />
                        Mostrar dica {{ hintsShown + 1 }} de
                        {{ challenge.hints.length }}
                    </Button>
                </section>

                <section aria-labelledby="glossary-title" class="space-y-2">
                    <h2 id="glossary-title" class="text-sm font-semibold">
                        Glossário
                    </h2>
                    <dl class="grid gap-2 text-sm">
                        <div
                            v-for="entry in challenge.glossary"
                            :key="entry.term"
                            class="rounded-lg bg-muted px-3 py-2"
                        >
                            <dt class="font-medium" lang="en">
                                {{ entry.term }}
                            </dt>
                            <dd class="text-muted-foreground">
                                {{ entry.meaning }}
                            </dd>
                        </div>
                    </dl>
                </section>
            </article>

            <!-- Editor and tests -->
            <section
                class="flex min-w-0 flex-col gap-4"
                aria-label="Sua solução"
            >
                <div
                    class="flex flex-col overflow-hidden rounded-xl border border-border bg-card"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-4 py-2"
                    >
                        <code
                            class="truncate font-mono text-xs text-muted-foreground"
                        >
                            {{ challenge.signature }}
                        </code>
                        <div class="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                data-test="reset-code"
                                @click="resetOpen = true"
                            >
                                <RotateCcw />
                                Recomeçar
                            </Button>
                            <Button
                                size="sm"
                                :disabled="running"
                                data-test="run-tests"
                                @click="runTests"
                            >
                                <LoaderCircle
                                    v-if="running"
                                    class="animate-spin"
                                />
                                <Play v-else />
                                Rodar testes
                            </Button>
                        </div>
                    </div>
                    <div class="h-[22rem] lg:h-[26rem]">
                        <CodeEditor
                            v-model="code"
                            :language="challenge.language"
                            :label="`Editor de código ${challenge.languageLabel}: ${challenge.signature}`"
                            @run="runTests"
                        />
                    </div>
                    <p
                        class="border-t border-border px-4 py-2 text-xs text-muted-foreground"
                    >
                        {{ challenge.languageLabel }}. Os testes rodam no
                        servidor, num ambiente isolado. Ctrl+Enter roda os
                        testes; Esc e depois Tab sai do editor.
                    </p>
                </div>

                <!-- Results -->
                <div aria-live="polite" class="space-y-3">
                    <div
                        v-if="running"
                        class="flex items-center gap-3 rounded-xl border border-border p-4 text-sm"
                        data-test="tests-running"
                    >
                        <LoaderCircle
                            class="size-5 shrink-0 animate-spin text-muted-foreground"
                            aria-hidden="true"
                        />
                        <p>
                            Rodando os testes no servidor…
                            <span v-if="compiles" class="text-muted-foreground">
                                {{ challenge.languageLabel }} compila antes de
                                rodar, leva uns segundos.
                            </span>
                        </p>
                    </div>

                    <div
                        v-else-if="requestError"
                        class="flex gap-3 rounded-xl border-2 border-destructive-foreground bg-destructive-soft p-4 text-sm"
                        role="alert"
                    >
                        <CircleAlert
                            class="size-5 shrink-0 text-destructive-foreground"
                            aria-hidden="true"
                        />
                        <p>{{ requestError }}</p>
                    </div>

                    <div
                        v-else-if="!run"
                        class="rounded-xl border border-dashed border-border p-4 text-sm text-muted-foreground"
                    >
                        Escreva a função e rode os testes. São
                        {{ challenge.tests.length }} casos, incluindo os
                        casos-limite.
                        <template v-if="!solvedNow">
                            Resolver vale {{ xpReward }} XP e conta para a sua
                            sequência.
                        </template>
                    </div>

                    <div
                        v-else-if="
                            run.status === 'busy' ||
                            run.status === 'unavailable'
                        "
                        class="flex gap-3 rounded-xl border-2 border-border bg-muted p-4 text-sm"
                        role="alert"
                    >
                        <CloudOff class="size-5 shrink-0" aria-hidden="true" />
                        <p>
                            {{
                                run.status === 'busy'
                                    ? 'Muita gente rodando código agora. Tente de novo em alguns segundos.'
                                    : 'O executor de código está fora do ar agora. Seu rascunho fica salvo aqui; tente de novo mais tarde.'
                            }}
                        </p>
                    </div>

                    <div
                        v-else-if="run.status === 'timeout'"
                        class="flex gap-3 rounded-xl border-2 border-destructive-foreground bg-destructive-soft p-4 text-sm"
                        role="alert"
                    >
                        <Timer
                            class="size-5 shrink-0 text-destructive-foreground"
                            aria-hidden="true"
                        />
                        <p>
                            O código passou de {{ timeLimit }} segundos e foi
                            interrompido. Confira se não há um laço infinito ou
                            uma recursão sem fim.
                        </p>
                    </div>

                    <div
                        v-else-if="run.status === 'error'"
                        class="flex gap-3 rounded-xl border-2 border-destructive-foreground bg-destructive-soft p-4 text-sm"
                        role="alert"
                    >
                        <CircleAlert
                            class="size-5 shrink-0 text-destructive-foreground"
                            aria-hidden="true"
                        />
                        <div class="min-w-0 flex-1 space-y-1">
                            <p class="font-medium">O código não rodou.</p>
                            <pre
                                class="max-h-64 overflow-auto font-mono text-xs whitespace-pre-wrap"
                                lang="en"
                                data-test="run-error"
                                >{{ run.error }}</pre>
                        </div>
                    </div>

                    <template v-else-if="run.status === 'ok'">
                        <p
                            class="text-sm font-medium"
                            :class="allPassed ? 'text-success' : ''"
                            data-test="tests-summary"
                        >
                            {{ passedCount }} de
                            {{ challenge.tests.length }} testes passaram
                            <span class="font-normal text-muted-foreground">
                                · {{ run.seconds.toLocaleString('pt-BR') }} s
                            </span>
                        </p>
                        <pre
                            v-if="run.error"
                            class="max-h-40 overflow-auto rounded-lg border border-destructive-foreground bg-destructive-soft p-3 font-mono text-xs whitespace-pre-wrap"
                            lang="en"
                            >{{ run.error }}</pre>
                        <ul class="space-y-2">
                            <li
                                v-for="result in run.results"
                                :key="result.index"
                                class="rounded-lg border p-3 text-sm"
                                :class="
                                    result.passed
                                        ? 'border-border'
                                        : 'border-destructive-foreground bg-destructive-soft'
                                "
                                data-test="test-result"
                                :data-passed="result.passed"
                            >
                                <div class="flex items-start gap-2">
                                    <CircleCheck
                                        v-if="result.passed"
                                        class="mt-0.5 size-4 shrink-0 text-success"
                                        aria-label="Passou"
                                    />
                                    <CircleX
                                        v-else
                                        class="mt-0.5 size-4 shrink-0 text-destructive-foreground"
                                        aria-label="Falhou"
                                    />
                                    <code
                                        class="min-w-0 font-mono text-xs break-all"
                                    >
                                        {{ challenge.tests[result.index].call }}
                                    </code>
                                </div>
                                <dl
                                    v-if="!result.passed"
                                    class="mt-2 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 pl-6 font-mono text-xs"
                                >
                                    <dt class="text-muted-foreground">
                                        Esperado
                                    </dt>
                                    <dd class="break-all">
                                        {{
                                            challenge.tests[result.index]
                                                .expected
                                        }}
                                    </dd>
                                    <template v-if="result.error">
                                        <dt class="text-muted-foreground">
                                            Erro
                                        </dt>
                                        <dd class="break-all" lang="en">
                                            {{ result.error }}
                                        </dd>
                                    </template>
                                    <template v-else>
                                        <dt class="text-muted-foreground">
                                            Recebido
                                        </dt>
                                        <dd class="break-all">
                                            {{ result.actual }}
                                        </dd>
                                    </template>
                                </dl>
                            </li>
                        </ul>
                    </template>

                    <details
                        v-if="run && run.logs.length"
                        class="rounded-lg border border-border p-3 text-sm"
                    >
                        <summary class="cursor-pointer font-medium">
                            Saída do programa ({{ run.logs.length }}
                            {{ run.logs.length === 1 ? 'linha' : 'linhas' }})
                        </summary>
                        <pre
                            class="mt-2 max-h-48 overflow-auto font-mono text-xs whitespace-pre-wrap"
                            >{{ run.logs.join('\n') }}</pre>
                    </details>

                    <!-- Solved -->
                    <div
                        v-if="allPassed"
                        class="space-y-4 rounded-xl border-2 border-success bg-success-soft p-5 motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2"
                        data-test="challenge-solved"
                    >
                        <div class="flex items-center gap-3">
                            <Sparkles
                                class="size-6 text-success"
                                aria-hidden="true"
                            />
                            <div>
                                <p class="font-semibold">
                                    Todos os testes passaram!
                                </p>
                                <p class="text-sm text-foreground/80">
                                    <template v-if="run?.outcome?.firstSolve">
                                        +{{ run.outcome.xp }} XP · sequência de
                                        {{
                                            run.outcome.streak === 1
                                                ? '1 dia'
                                                : `${run.outcome.streak} dias`
                                        }}
                                    </template>
                                    <template v-else>Solução salva.</template>
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2 rounded-lg bg-background/60 p-4">
                            <p class="text-sm font-semibold">
                                Agora explique em voz alta, como na entrevista:
                            </p>
                            <p lang="en" class="leading-relaxed">
                                “{{ challenge.explanation.en }}”
                            </p>
                            <p
                                v-if="showTranslation"
                                class="text-sm text-muted-foreground"
                            >
                                {{ challenge.explanation.pt }}
                            </p>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="showTranslation = !showTranslation"
                            >
                                <Languages />
                                {{
                                    showTranslation
                                        ? 'Esconder tradução'
                                        : 'Ver tradução'
                                }}
                            </Button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-if="next"
                                as-child
                                data-test="next-challenge"
                            >
                                <Link :href="show(next.slug)">
                                    Próximo: {{ next.title }}
                                    <ArrowRight />
                                </Link>
                            </Button>
                            <Button variant="outline" as-child>
                                <Link :href="challengesIndex()"
                                    >Todos os desafios</Link
                                >
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </TooltipProvider>

    <Dialog v-model:open="resetOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Recomeçar o desafio?</DialogTitle>
                <DialogDescription>
                    O editor volta para o código inicial e o que você escreveu
                    aqui é descartado.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="outline">Cancelar</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    data-test="confirm-reset"
                    @click="resetCode"
                >
                    Recomeçar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
