<script setup lang="ts">
/**
 * Pre-interview microphone test. Everything happens in the browser:
 * the recording is only played back locally and never uploaded.
 */
import {
    CircleAlert,
    CircleCheck,
    Mic,
    RotateCcw,
    Square,
    X,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import { MIN_PEAK_LEVEL } from '@/lib/microphone';
import { reportPilotEvent } from '@/lib/pilot';
import { Button } from '@/components/ui/button';
import {
    recorderErrorMessages as errorMessages,
    useAudioRecorder,
} from '@/composables/useAudioRecorder';

const MAX_TEST_MS = 10_000;

const props = defineProps<{
    /** Links the pilot events to the session being prepared. */
    sessionId?: number;
}>();

const passed = defineModel<boolean>('passed', { default: false });

const recorder = useAudioRecorder({ maxDurationMs: MAX_TEST_MS });
const { state, error, level, peakLevel, elapsedMs, audioUrl } = recorder;

const heardSound = computed(() => peakLevel.value >= MIN_PEAK_LEVEL);
const seconds = computed(() => Math.floor(elapsedMs.value / 1000));

watch(
    [state, heardSound],
    () => {
        passed.value = state.value === 'recorded' && heardSound.value;
    },
    { immediate: true },
);

// Pilot metrics: real-world peak levels calibrate MIN_PEAK_LEVEL, and
// error codes show which microphone problems people hit.
watch(state, (value) => {
    if (value === 'recorded') {
        reportPilotEvent(
            {
                type: 'microphone_check',
                passed: heardSound.value,
                peak: Math.round(peakLevel.value * 1000) / 1000,
            },
            props.sessionId,
        );
    } else if (value === 'error' && error.value) {
        reportPilotEvent(
            { type: 'microphone_error', error: error.value },
            props.sessionId,
        );
    }
});

/** Ten bars that light up with the current input level. */
const bars = Array.from({ length: 10 }, (_, i) => (i + 1) / 10);
</script>

<template>
    <div class="space-y-4">
        <!-- Idle: explain before asking for permission -->
        <template v-if="state === 'idle'">
            <p class="text-sm text-muted-foreground">
                Vamos pedir acesso ao microfone só agora, para conferir se sua
                voz está sendo captada. Este teste fica apenas no seu navegador:
                nada é enviado.
            </p>
            <Button data-test="start-mic-test" @click="recorder.start()">
                <Mic />
                Testar microfone
            </Button>
        </template>

        <p
            v-else-if="state === 'requesting'"
            class="text-sm text-muted-foreground"
            role="status"
        >
            Aguardando você permitir o acesso ao microfone no navegador…
        </p>

        <!-- Recording: persistent indicator, live level and explicit controls -->
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
                Gravando · {{ seconds }}s de {{ MAX_TEST_MS / 1000 }}s
            </div>

            <p class="text-sm">
                Diga em inglês:
                <span class="font-medium"
                    >“Hello, I'm ready for my interview.”</span
                >
            </p>

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
                <Button data-test="stop-mic-test" @click="recorder.stop()">
                    <Square />
                    Parar
                </Button>
                <Button variant="ghost" @click="recorder.reset()">
                    <X />
                    Cancelar
                </Button>
            </div>
        </template>

        <!-- Recorded: result and local playback -->
        <template v-else-if="state === 'recorded'">
            <p
                v-if="heardSound"
                class="flex items-center gap-2 text-sm font-medium"
                role="status"
            >
                <CircleCheck class="size-5 text-success" aria-hidden="true" />
                Microfone funcionando. Ouça a gravação para conferir a
                qualidade.
            </p>
            <p
                v-else
                class="flex items-start gap-2 text-sm font-medium"
                role="alert"
            >
                <CircleAlert
                    class="mt-0.5 size-5 shrink-0 text-warning"
                    aria-hidden="true"
                />
                Quase não captamos som. Confira se o microfone certo está
                selecionado no sistema e se não está mudo, e grave de novo.
            </p>

            <audio
                v-if="audioUrl"
                :src="audioUrl"
                controls
                class="w-full max-w-md"
                aria-label="Sua gravação de teste"
            />

            <Button variant="outline" @click="recorder.start()">
                <RotateCcw />
                Gravar novamente
            </Button>
        </template>

        <!-- Error -->
        <template v-else-if="state === 'error' && error">
            <p class="flex items-start gap-2 text-sm" role="alert">
                <CircleAlert
                    class="mt-0.5 size-5 shrink-0 text-destructive"
                    aria-hidden="true"
                />
                {{ errorMessages[error] }}
            </p>
            <Button variant="outline" @click="recorder.start()">
                <RotateCcw />
                Tentar novamente
            </Button>
        </template>
    </div>
</template>
