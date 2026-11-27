<script setup lang="ts">
/**
 * One answer: record → transcribe → review/edit → send.
 *
 * The recording stays in the browser until it is transcribed; the server
 * does not keep the audio. Nothing counts as answered until "Enviar
 * resposta" (cursor/skills/FalaDev.md, section 8). If the transcription
 * fails, the recording is kept in memory so the user can retry.
 */
import { router, useForm } from '@inertiajs/vue3';
import { CircleAlert, Mic, RotateCcw, Send, Square, X } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    recorderErrorMessages,
    useAudioRecorder,
} from '@/composables/useAudioRecorder';
import { post } from '@/lib/http';
import { reportPilotEvent } from '@/lib/pilot';
import { answer, transcribe } from '@/routes/interviews/turns';

const props = defineProps<{
    sessionId: number;
    turnId: number;
    maxSeconds: number;
    sttIsFake: boolean;
}>();

type Phase = 'record' | 'transcribing' | 'review' | 'transcription_failed';

const recorder = useAudioRecorder({
    maxDurationMs: props.maxSeconds * 1000,
    audioBitsPerSecond: 32_000,
});
const { state, error, level, elapsedMs, audioBlob, audioUrl } = recorder;

const phase = ref<Phase>('record');
const transcriptionError = ref('');
const form = useForm({ transcript: '' });
// The server may also reject the send with a "status" error (interview over).
const sendError = computed(
    () =>
        form.errors.transcript ??
        (form.errors as Record<string, string | undefined>).status,
);

const route = computed(() => ({
    interviewSession: props.sessionId,
    turn: props.turnId,
}));
const seconds = computed(() => Math.floor(elapsedMs.value / 1000));
const formatSeconds = (total: number) =>
    `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
const bars = Array.from({ length: 10 }, (_, i) => (i + 1) / 10);

// Recording finished (stop button or time limit): transcribe right away.
watch(state, (value) => {
    if (value === 'recorded') {
        void sendToTranscription();
    } else if (value === 'error' && error.value) {
        reportPilotEvent(
            { type: 'microphone_error', error: error.value },
            props.sessionId,
        );
    }
});

function extensionFor(type: string): string {
    if (type.includes('ogg')) {
        return 'ogg';
    }

    if (type.includes('mp4')) {
        return 'mp4';
    }

    return 'webm';
}

async function sendToTranscription(): Promise<void> {
    const blob = audioBlob.value;

    if (!blob) {
        return;
    }

    phase.value = 'transcribing';
    transcriptionError.value = '';

    const body = new FormData();
    body.append('audio', blob, `answer.${extensionFor(blob.type)}`);

    try {
        const response = await post(transcribe.url(route.value), body);
        const data = (await response.json().catch(() => ({}))) as {
            transcript?: string;
            message?: string;
        };

        if (!response.ok || typeof data.transcript !== 'string') {
            throw new Error(data.message ?? '');
        }

        form.transcript = data.transcript;
        phase.value = 'review';
    } catch (e) {
        transcriptionError.value =
            e instanceof Error && e.message
                ? e.message
                : 'Não foi possível transcrever agora. Verifique sua conexão.';
        phase.value = 'transcription_failed';
    }
}

function recordAgain(): void {
    form.reset();
    form.clearErrors();
    phase.value = 'record';
    void recorder.start();
}

function send(): void {
    form.post(answer.url(route.value), { preserveScroll: true });
}

// Warn before leaving with an answer that was not sent yet (section 8).
const hasUnsentAnswer = computed(
    () => state.value === 'recording' || phase.value !== 'record',
);

function onBeforeUnload(event: BeforeUnloadEvent): void {
    if (hasUnsentAnswer.value) {
        event.preventDefault();
    }
}

let removeNavigationGuard: (() => void) | null = null;

onMounted(() => {
    window.addEventListener('beforeunload', onBeforeUnload);
    // Only page changes ask: sending the answer or ending the interview are
    // POSTs, and ending already has its own confirmation.
    removeNavigationGuard = router.on('before', (event) => {
        if (
            hasUnsentAnswer.value &&
            event.detail.visit.method === 'get' &&
            !window.confirm(
                'Sua resposta ainda não foi enviada e será perdida. Sair mesmo assim?',
            )
        ) {
            event.preventDefault();
        }
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', onBeforeUnload);
    removeNavigationGuard?.();
});
</script>

<template>
    <div class="space-y-4">
        <!-- Record -->
        <template v-if="phase === 'record'">
            <template v-if="state === 'idle'">
                <p class="text-sm text-muted-foreground">
                    Responda em inglês e explique seu raciocínio. Você pode
                    gravar até {{ formatSeconds(maxSeconds) }} e revisar o texto
                    antes de enviar.
                </p>
                <Button
                    size="lg"
                    data-test="start-answer"
                    @click="recorder.start()"
                >
                    <Mic />
                    Gravar resposta
                </Button>
            </template>

            <p
                v-else-if="state === 'requesting'"
                class="text-sm text-muted-foreground"
                role="status"
            >
                Aguardando você permitir o acesso ao microfone no navegador…
            </p>

            <template v-else-if="state === 'recording'">
                <div
                    class="flex items-center gap-2 text-sm font-medium"
                    role="status"
                    aria-live="polite"
                >
                    <span class="relative flex size-3">
                        <span
                            class="absolute inline-flex size-full animate-ping rounded-full bg-voice opacity-75 motion-reduce:animate-none"
                        />
                        <span
                            class="relative inline-flex size-3 rounded-full bg-voice"
                        />
                    </span>
                    Gravando · {{ formatSeconds(seconds) }} de
                    {{ formatSeconds(maxSeconds) }}
                </div>

                <div
                    class="flex h-8 items-end gap-1"
                    role="meter"
                    aria-label="Volume do microfone"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    :aria-valuenow="Math.round(level * 100)"
                >
                    <span
                        v-for="bar in bars"
                        :key="bar"
                        class="w-2 rounded-sm transition-colors"
                        :class="level >= bar - 0.05 ? 'bg-voice' : 'bg-muted'"
                        :style="{ height: `${bar * 100}%` }"
                    />
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button data-test="stop-answer" @click="recorder.stop()">
                        <Square />
                        Parar e transcrever
                    </Button>
                    <Button variant="ghost" @click="recorder.reset()">
                        <X />
                        Cancelar
                    </Button>
                </div>
            </template>

            <template v-else-if="state === 'error' && error">
                <p class="flex items-start gap-2 text-sm" role="alert">
                    <CircleAlert
                        class="mt-0.5 size-5 shrink-0 text-destructive"
                        aria-hidden="true"
                    />
                    {{ recorderErrorMessages[error] }}
                </p>
                <Button variant="outline" @click="recorder.start()">
                    <RotateCcw />
                    Tentar novamente
                </Button>
            </template>
        </template>

        <!-- Transcribing -->
        <p
            v-else-if="phase === 'transcribing'"
            class="flex items-center gap-2 text-sm"
            role="status"
        >
            <Spinner />
            Transcrevendo sua resposta…
        </p>

        <!-- Transcription failed: the recording is still in memory -->
        <template v-else-if="phase === 'transcription_failed'">
            <p class="flex items-start gap-2 text-sm" role="alert">
                <CircleAlert
                    class="mt-0.5 size-5 shrink-0 text-warning"
                    aria-hidden="true"
                />
                {{ transcriptionError }} Sua resposta ainda não foi enviada; a
                gravação continua aqui.
            </p>
            <div class="flex flex-wrap gap-2">
                <Button @click="sendToTranscription()">
                    <RotateCcw />
                    Tentar de novo
                </Button>
                <Button variant="outline" @click="recordAgain()">
                    <Mic />
                    Gravar de novo
                </Button>
            </div>
        </template>

        <!-- Review and send -->
        <form
            v-else-if="phase === 'review'"
            class="space-y-3"
            @submit.prevent="send"
        >
            <audio
                v-if="audioUrl"
                :src="audioUrl"
                controls
                class="w-full max-w-md"
                aria-label="Sua resposta gravada"
            />

            <div class="space-y-1.5">
                <label for="transcript" class="text-sm font-medium">
                    Transcrição — revise e corrija antes de enviar
                </label>
                <p
                    v-if="sttIsFake"
                    class="rounded-md bg-muted px-3 py-2 text-xs text-muted-foreground"
                >
                    Ambiente de desenvolvimento: a transcrição automática ainda
                    não está ligada. Substitua o texto pelo que você falou.
                </p>
                <textarea
                    id="transcript"
                    v-model="form.transcript"
                    lang="en"
                    rows="6"
                    maxlength="5000"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                    :aria-invalid="Boolean(form.errors.transcript)"
                    data-test="answer-transcript"
                />
                <InputError :message="sendError" />
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    type="submit"
                    :disabled="form.processing || !form.transcript.trim()"
                    data-test="send-answer"
                >
                    <Spinner v-if="form.processing" />
                    <Send v-else />
                    Enviar resposta
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.processing"
                    @click="recordAgain()"
                >
                    <Mic />
                    Gravar de novo
                </Button>
            </div>
        </form>
    </div>
</template>
