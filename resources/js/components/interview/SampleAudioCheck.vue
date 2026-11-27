<script setup lang="ts">
/**
 * Plays the interviewer's pre-recorded test phrase. The text is always on
 * screen, so a missing or broken recording never blocks the user.
 */
import {
    CircleAlert,
    CircleCheck,
    LoaderCircle,
    RotateCcw,
    Volume2,
} from '@lucide/vue';
import { onMounted, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { useAudioPlayer } from '@/composables/useAudioPlayer';
import type { PlaybackState } from '@/composables/useAudioPlayer';

export type SampleAudioState = PlaybackState;

const props = defineProps<{
    audioUrl: string | null;
    phrase: string;
}>();

const state = defineModel<SampleAudioState>('state', { default: 'idle' });

const player = useAudioPlayer();

watch(player.state, (value) => {
    state.value = value;
});

onMounted(() => {
    // Not recorded yet: same fallback as a playback failure.
    if (!props.audioUrl) {
        state.value = 'failed';
    }
});

function play(): void {
    if (state.value !== 'loading' && state.value !== 'playing') {
        void player.play(props.audioUrl);
    }
}
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
            v-if="audioUrl"
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
            Não foi possível reproduzir o áudio agora. Você pode
            {{ audioUrl ? 'tentar de novo ou ' : '' }}seguir assim: durante a
            entrevista, as perguntas também aparecem escritas na tela.
        </p>
    </div>
</template>
