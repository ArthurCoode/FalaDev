<script setup lang="ts">
/**
 * Plays the interviewer's test phrase (TTS, fetched from the server).
 * The text is always on screen, so a TTS failure never blocks the user.
 */
import {
    CircleAlert,
    CircleCheck,
    LoaderCircle,
    RotateCcw,
    Volume2,
} from '@lucide/vue';
import { onBeforeUnmount, ref } from 'vue';
import { Button } from '@/components/ui/button';

export type SampleAudioState =
    | 'idle'
    | 'loading'
    | 'playing'
    | 'played'
    | 'failed';

const props = defineProps<{
    audioUrl: string;
    phrase: string;
}>();

const state = defineModel<SampleAudioState>('state', { default: 'idle' });

let objectUrl: string | null = null;
let audio: HTMLAudioElement | null = null;

async function play(): Promise<void> {
    if (state.value === 'loading' || state.value === 'playing') {
        return;
    }

    state.value = 'loading';

    try {
        if (!objectUrl) {
            const response = await fetch(props.audioUrl, {
                headers: { Accept: 'audio/*' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            objectUrl = URL.createObjectURL(await response.blob());
        }

        audio = new Audio(objectUrl);
        audio.onended = () => {
            state.value = 'played';
        };
        audio.onerror = () => {
            state.value = 'failed';
        };
        await audio.play();
        state.value = 'playing';
    } catch {
        state.value = 'failed';
    }
}

onBeforeUnmount(() => {
    audio?.pause();

    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
    }
});
</script>

<template>
    <div class="space-y-4">
        <blockquote
            class="border-l-2 border-voice pl-3 text-sm italic"
            lang="en"
        >
            “{{ phrase }}”
        </blockquote>

        <Button
            :variant="state === 'played' ? 'outline' : 'default'"
            :disabled="state === 'loading' || state === 'playing'"
            data-test="play-sample-audio"
            @click="play"
        >
            <LoaderCircle v-if="state === 'loading'" class="animate-spin" />
            <RotateCcw v-else-if="state === 'played' || state === 'failed'" />
            <Volume2 v-else />
            <template v-if="state === 'loading'">Carregando…</template>
            <template v-else-if="state === 'playing'">Tocando…</template>
            <template v-else-if="state === 'played' || state === 'failed'"
                >Ouvir de novo</template
            >
            <template v-else>Ouvir frase de teste</template>
        </Button>

        <p
            v-if="state === 'played'"
            class="flex items-center gap-2 text-sm font-medium"
            role="status"
        >
            <CircleCheck class="size-5 text-success" aria-hidden="true" />
            Áudio reproduzido. Se não ouviu nada, confira o volume e a saída de
            som.
        </p>
        <p
            v-else-if="state === 'failed'"
            class="flex items-start gap-2 text-sm"
            role="alert"
        >
            <CircleAlert
                class="mt-0.5 size-5 shrink-0 text-warning"
                aria-hidden="true"
            />
            Não foi possível reproduzir o áudio agora. Você pode tentar de novo
            ou seguir assim: durante a entrevista, as perguntas também aparecem
            escritas na tela.
        </p>
    </div>
</template>
