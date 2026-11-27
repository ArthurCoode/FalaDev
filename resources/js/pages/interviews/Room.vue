<script setup lang="ts">
/**
 * The interview in progress: one question at a time, read aloud from the
 * pre-recorded files and always shown as text. Reloading or coming back
 * later resumes from the server's state, so an answer is never sent twice.
 */
import { Form, Head } from '@inertiajs/vue3';
import {
    CircleAlert,
    Clock,
    LoaderCircle,
    RotateCcw,
    Volume2,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AnswerRecorder from '@/components/interview/AnswerRecorder.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useAudioPlayer } from '@/composables/useAudioPlayer';
import { post } from '@/lib/http';
import { dashboard } from '@/routes';
import { finish } from '@/routes/interviews';
import { repeat } from '@/routes/interviews/turns';
import type {
    InterviewerLine,
    InterviewSessionDetails,
    InterviewTurnDetails,
} from '@/types';

const props = defineProps<{
    session: InterviewSessionDetails;
    /** Only before the first answer. */
    opening: InterviewerLine | null;
    currentTurn: InterviewTurnDetails | null;
    answeredTurns: InterviewTurnDetails[];
    endsAt: string | null;
    maxAnswerSeconds: number;
    sttIsFake: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Início', href: dashboard() }],
    },
});

// --- Interviewer audio -------------------------------------------------------

const player = useAudioPlayer();
const heardQuestion = ref(false);

watch(player.state, (value) => {
    if (value === 'played' || value === 'failed') {
        heardQuestion.value = true;
    }
});

function playCurrent(): void {
    const turn = props.currentTurn;

    if (!turn) {
        return;
    }

    heardQuestion.value = false;
    void player.playSequence(
        props.opening
            ? [props.opening.audioUrl, turn.audioUrl]
            : [turn.audioUrl],
    );
}

function repeatQuestion(): void {
    const turn = props.currentTurn;

    if (!turn) {
        return;
    }

    void player.play(turn.audioUrl);
    // Counted for the report's context only; failures here do not matter.
    void post(
        repeat.url({ interviewSession: props.session.id, turn: turn.id }),
    ).catch(() => undefined);
}

// Each new question is read aloud. Browsers may block this autoplay; the
// state then stays "idle" and the user clicks "Ouvir pergunta".
watch(() => props.currentTurn?.id, playCurrent);
onMounted(playCurrent);

// --- Time --------------------------------------------------------------------

const now = ref(Date.now());
const timer = window.setInterval(() => (now.value = Date.now()), 1000);
onBeforeUnmount(() => window.clearInterval(timer));

const remainingSeconds = computed(() =>
    props.endsAt
        ? Math.max(Math.floor((Date.parse(props.endsAt) - now.value) / 1000), 0)
        : null,
);
const timeIsUp = computed(() => remainingSeconds.value === 0);
const remainingLabel = computed(() => {
    const total = remainingSeconds.value ?? 0;

    return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
});
</script>

<template>
    <Head :title="`Entrevista de ${session.category}`" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="`Entrevista de ${session.category}`"
            :description="`${session.level} · ${session.duration} · em inglês`"
        >
            <template #actions>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button variant="outline" data-test="finish-interview">
                            Encerrar entrevista
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle
                                >Encerrar a entrevista agora?</DialogTitle
                            >
                            <DialogDescription>
                                As respostas que você já enviou ficam salvas.
                                Uma resposta gravada e ainda não enviada será
                                descartada.
                            </DialogDescription>
                        </DialogHeader>
                        <Form
                            v-bind="finish.form(session.id)"
                            v-slot="{ processing }"
                        >
                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <Button variant="outline" type="button">
                                        Voltar à entrevista
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    :disabled="processing"
                                    data-test="confirm-finish-interview"
                                >
                                    <Spinner v-if="processing" />
                                    Concluir agora
                                </Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </template>
        </PageHeader>

        <div
            class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
        >
            <span v-if="currentTurn">Pergunta {{ currentTurn.position }}</span>
            <span
                v-if="remainingSeconds !== null"
                class="flex items-center gap-1.5"
            >
                <Clock class="size-4" aria-hidden="true" />
                <template v-if="!timeIsUp"
                    >Tempo restante: {{ remainingLabel }}</template
                >
                <template v-else>Tempo esgotado</template>
            </span>
        </div>

        <p
            v-if="timeIsUp"
            class="flex items-start gap-2 rounded-md bg-warning-soft px-3 py-2 text-sm"
            role="status"
        >
            <CircleAlert
                class="mt-0.5 size-5 shrink-0 text-warning"
                aria-hidden="true"
            />
            O tempo da entrevista terminou. Você pode concluir e enviar esta
            resposta; depois a entrevista será encerrada.
        </p>

        <template v-if="currentTurn">
            <Card>
                <CardHeader class="space-y-3">
                    <p
                        v-if="opening"
                        class="text-sm text-muted-foreground italic"
                        lang="en"
                    >
                        {{ opening.text }}
                    </p>
                    <p class="text-xs font-medium text-voice">
                        {{
                            currentTurn.isFollowUp
                                ? 'Pergunta de aprofundamento'
                                : 'Pergunta'
                        }}
                    </p>
                    <CardTitle
                        class="text-lg leading-snug"
                        lang="en"
                        data-test="current-question"
                    >
                        {{ currentTurn.question }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            v-if="currentTurn.audioUrl"
                            variant="outline"
                            size="sm"
                            :disabled="
                                player.state.value === 'loading' ||
                                player.state.value === 'playing'
                            "
                            @click="
                                heardQuestion ? repeatQuestion() : playCurrent()
                            "
                        >
                            <LoaderCircle
                                v-if="player.state.value === 'loading'"
                                class="animate-spin"
                            />
                            <Volume2
                                v-else-if="player.state.value === 'playing'"
                            />
                            <RotateCcw v-else-if="heardQuestion" />
                            <Volume2 v-else />
                            <template v-if="player.state.value === 'playing'"
                                >Falando…</template
                            >
                            <template v-else-if="heardQuestion"
                                >Repetir pergunta</template
                            >
                            <template v-else>Ouvir pergunta</template>
                        </Button>
                    </div>
                    <p
                        v-if="
                            !currentTurn.audioUrl ||
                            player.state.value === 'failed'
                        "
                        class="text-xs text-muted-foreground"
                        role="status"
                    >
                        Áudio indisponível para esta pergunta. Leia o texto
                        acima.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Sua resposta</CardTitle>
                </CardHeader>
                <CardContent>
                    <AnswerRecorder
                        :key="currentTurn.id"
                        :session-id="session.id"
                        :turn-id="currentTurn.id"
                        :max-seconds="maxAnswerSeconds"
                        :stt-is-fake="sttIsFake"
                    />
                </CardContent>
            </Card>
        </template>

        <details
            v-if="answeredTurns.length > 0"
            class="rounded-lg border bg-card p-4 text-sm"
        >
            <summary class="cursor-pointer font-medium">
                Respostas enviadas ({{ answeredTurns.length }})
            </summary>
            <ol class="mt-4 space-y-4">
                <li
                    v-for="turn in answeredTurns"
                    :key="turn.id"
                    class="space-y-1"
                >
                    <p class="font-medium" lang="en">{{ turn.question }}</p>
                    <p
                        class="border-l-2 border-primary pl-3 whitespace-pre-line text-muted-foreground"
                        lang="en"
                    >
                        {{ turn.answer }}
                    </p>
                </li>
            </ol>
        </details>
    </div>
</template>
