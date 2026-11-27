<script setup lang="ts">
import { Form, Head, Link, usePoll } from '@inertiajs/vue3';
import {
    CircleAlert,
    CircleCheck,
    Flag,
    Home,
    Mic,
    Play,
    RotateCcw,
    Volume2,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import DeleteInterviewDialog from '@/components/interview/DeleteInterviewDialog.vue';
import EvaluationReport from '@/components/interview/EvaluationReport.vue';
import MicrophoneCheck from '@/components/interview/MicrophoneCheck.vue';
import SampleAudioCheck from '@/components/interview/SampleAudioCheck.vue';
import type { SampleAudioState } from '@/components/interview/SampleAudioCheck.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { create, ready, start } from '@/routes/interviews';
import { retry as retryEvaluation } from '@/routes/interviews/evaluation';
import type {
    InterviewEvaluation,
    InterviewerLine,
    InterviewSessionDetails,
    InterviewTurnDetails,
} from '@/types';

const props = defineProps<{
    session: InterviewSessionDetails;
    samplePhrase: string;
    sampleAudioUrl: string | null;
    /** Only filled once the interview is over. */
    turns: InterviewTurnDetails[];
    closing: InterviewerLine | null;
    /** Only when the report is ready. */
    evaluation: InterviewEvaluation | null;
    scoreAnchors: Record<string, string>;
    /** Setup of this interview, to prefill "Refazer entrevista". */
    redoQuery: Record<string, string | number>;
}>();

// While the report is prepared in the background, ask the server again
// every few seconds; stop as soon as the status changes.
const poll = usePoll(
    3000,
    { only: ['session', 'evaluation'] },
    { autoStart: false },
);
const isEvaluating = computed(() => props.session.status === 'evaluating');
const hasAnswers = computed(() => props.turns.some((turn) => turn.answer));

watch(isEvaluating, (evaluating) => (evaluating ? poll.start() : poll.stop()));
onMounted(() => {
    if (isEvaluating.value) {
        poll.start();
    }
});

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Início', href: dashboard() }],
    },
});

const micPassed = ref(false);
const sampleState = ref<SampleAudioState>('idle');

// Audio is "checked" once played, or once it failed: the text is on screen,
// so a TTS problem must not block the interview (FalaDev.md, section 8).
const sampleChecked = computed(
    () => sampleState.value === 'played' || sampleState.value === 'failed',
);
const canConfirm = computed(() => micPassed.value && sampleChecked.value);

const createdAt = computed(() =>
    props.session.createdAt
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'long',
              timeStyle: 'short',
          }).format(new Date(props.session.createdAt))
        : '',
);

const details = computed(() => [
    { term: 'Categoria', value: props.session.category },
    { term: 'Nível', value: props.session.level },
    { term: 'Duração', value: props.session.duration },
    { term: 'Status', value: props.session.statusLabel },
    { term: 'Criada em', value: createdAt.value },
]);
</script>

<template>
    <Head :title="`Entrevista de ${session.category}`" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="`Entrevista de ${session.category}`"
            :description="
                session.status === 'draft'
                    ? 'Faça os testes de áudio antes de começar.'
                    : closing
                      ? 'Entrevista concluída. Veja o relatório e a transcrição.'
                      : 'Resumo da sua sessão.'
            "
        >
            <template #actions>
                <Button v-if="session.canEditSetup" variant="outline" as-child>
                    <Link :href="create()">Alterar configuração</Link>
                </Button>
                <DeleteInterviewDialog :session-id="session.id" />
            </template>
        </PageHeader>

        <Card>
            <CardContent>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div v-for="item in details" :key="item.term">
                        <dt class="text-xs text-muted-foreground">
                            {{ item.term }}
                        </dt>
                        <dd class="mt-0.5 font-medium">{{ item.value }}</dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <!-- Pre-interview checks -->
        <template v-if="session.status === 'draft'">
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Mic class="size-5 text-voice" aria-hidden="true" />
                            1. Microfone
                        </CardTitle>
                        <CardDescription>
                            Grave uma frase curta e ouça como ficou.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <MicrophoneCheck v-model:passed="micPassed" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Volume2
                                class="size-5 text-voice"
                                aria-hidden="true"
                            />
                            2. Áudio do entrevistador
                        </CardTitle>
                        <CardDescription>
                            Confira se você ouve o entrevistador com clareza.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <SampleAudioCheck
                            v-model:state="sampleState"
                            :audio-url="sampleAudioUrl"
                            :phrase="samplePhrase"
                        />
                    </CardContent>
                </Card>
            </div>

            <Form
                v-bind="ready.form(session.id)"
                class="flex flex-col gap-2"
                v-slot="{ errors, processing }"
            >
                <InputError :message="errors.status" />
                <div class="flex flex-wrap items-center gap-3">
                    <Button
                        type="submit"
                        size="lg"
                        :disabled="!canConfirm || processing"
                        data-test="confirm-ready-button"
                    >
                        <Spinner v-if="processing" />
                        Tudo certo, estou pronto
                    </Button>
                    <p v-if="!canConfirm" class="text-sm text-muted-foreground">
                        <template v-if="!micPassed && !sampleChecked">
                            Faça os dois testes para continuar.
                        </template>
                        <template v-else-if="!micPassed">
                            Falta testar o microfone.
                        </template>
                        <template v-else>
                            Falta ouvir a frase de teste.
                        </template>
                    </p>
                </div>
            </Form>
        </template>

        <!-- Ready to start -->
        <Card v-else-if="session.status === 'ready'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <CircleCheck
                        class="size-5 text-success"
                        aria-hidden="true"
                    />
                    Tudo pronto para começar
                </CardTitle>
                <CardDescription>
                    Microfone e áudio conferidos. Quando começar, o
                    entrevistador se apresenta em inglês e faz a primeira
                    pergunta.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="start.form(session.id)"
                    class="flex flex-col items-start gap-2"
                    v-slot="{ errors, processing }"
                >
                    <InputError :message="errors.status ?? errors.usage" />
                    <Button
                        type="submit"
                        size="lg"
                        :disabled="processing"
                        data-test="start-interview-button"
                    >
                        <Spinner v-if="processing" />
                        <Play v-else />
                        Iniciar entrevista
                    </Button>
                    <p class="text-xs text-muted-foreground">
                        O microfone só é ativado quando você clicar em gravar
                        cada resposta.
                    </p>
                </Form>
            </CardContent>
        </Card>

        <!-- Finished: closing line and the full transcript -->
        <template v-else-if="closing">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Flag class="size-5 text-primary" aria-hidden="true" />
                        Entrevista concluída
                    </CardTitle>
                    <CardDescription lang="en" class="italic">
                        “{{ closing.text }}”
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex flex-wrap gap-2">
                    <Button as-child data-test="redo-interview">
                        <Link :href="create({ query: redoQuery })">
                            <RotateCcw />
                            Refazer entrevista
                        </Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="dashboard()">
                            <Home />
                            Voltar ao início
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <!-- Report -->
            <Card v-if="session.status === 'evaluating'">
                <CardContent
                    class="flex items-center gap-3 text-sm"
                    role="status"
                    aria-live="polite"
                >
                    <Spinner />
                    Preparando seu feedback… Isso costuma levar menos de um
                    minuto. Você pode sair e voltar depois.
                </CardContent>
            </Card>

            <Card v-else-if="session.status === 'failed_retryable'">
                <CardContent class="space-y-3">
                    <p class="flex items-start gap-2 text-sm" role="alert">
                        <CircleAlert
                            class="mt-0.5 size-5 shrink-0 text-destructive"
                            aria-hidden="true"
                        />
                        Não foi possível preparar o relatório agora. Sua
                        entrevista e a transcrição continuam salvas.
                    </p>
                    <Form
                        v-bind="retryEvaluation.form(session.id)"
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            :disabled="processing"
                            data-test="retry-evaluation"
                        >
                            <Spinner v-if="processing" />
                            <RotateCcw v-else />
                            Tentar novamente
                        </Button>
                    </Form>
                </CardContent>
            </Card>

            <EvaluationReport
                v-else-if="evaluation"
                :evaluation="evaluation"
                :score-anchors="scoreAnchors"
            />

            <Card v-else-if="session.status === 'completed'">
                <CardContent class="space-y-3 text-sm">
                    <template v-if="hasAnswers">
                        <p class="text-muted-foreground">
                            Esta entrevista ainda não tem relatório.
                        </p>
                        <Form
                            v-bind="retryEvaluation.form(session.id)"
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                :disabled="processing"
                                data-test="generate-evaluation"
                            >
                                <Spinner v-if="processing" />
                                Gerar relatório
                            </Button>
                        </Form>
                    </template>
                    <p v-else class="text-muted-foreground">
                        Nenhuma resposta foi enviada nesta entrevista, então não
                        há relatório.
                    </p>
                </CardContent>
            </Card>

            <section aria-labelledby="transcript-heading" class="space-y-3">
                <h2 id="transcript-heading" class="text-lg font-semibold">
                    Transcrição
                </h2>
                <p
                    v-if="turns.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma pergunta foi feita nesta sessão.
                </p>
                <Card v-for="turn in turns" :key="turn.id">
                    <CardContent class="space-y-3">
                        <p class="text-xs text-muted-foreground">
                            Pergunta {{ turn.position
                            }}{{ turn.isFollowUp ? ' · aprofundamento' : '' }}
                        </p>
                        <p class="font-medium" lang="en">{{ turn.question }}</p>
                        <p
                            v-if="turn.answer"
                            class="border-l-2 border-primary pl-3 text-sm whitespace-pre-line"
                            lang="en"
                        >
                            {{ turn.answer }}
                        </p>
                        <p v-else class="text-sm text-muted-foreground">
                            Sem resposta: a entrevista foi encerrada antes.
                        </p>
                    </CardContent>
                </Card>
            </section>
        </template>
    </div>
</template>
