<script setup lang="ts">
/**
 * Build a sentence: tap the words in order. Tapping a placed word sends it
 * back. Words are buttons, so Tab and Enter work; Backspace (handled by the
 * page) removes the last one.
 */
import { computed } from 'vue';
import type { OrderExercise } from '@/types';

const props = defineProps<{
    exercise: OrderExercise;
    /** Words in display order (shuffled by the page). */
    tokens: string[];
    revealed: boolean;
    correct: boolean;
}>();

/** Indexes into `tokens`, in the order the user placed them. */
const picked = defineModel<number[]>({ required: true });

const pickedSet = computed(() => new Set(picked.value));

function place(index: number): void {
    if (!props.revealed && !pickedSet.value.has(index)) {
        picked.value = [...picked.value, index];
    }
}

function remove(position: number): void {
    if (!props.revealed) {
        picked.value = picked.value.filter((_, i) => i !== position);
    }
}
</script>

<template>
    <div class="space-y-6">
        <div
            class="flex min-h-28 flex-wrap content-start gap-2 rounded-xl border-2 border-dashed p-3 transition-colors"
            :class="
                revealed
                    ? correct
                        ? 'border-success bg-success-soft'
                        : 'border-destructive-foreground bg-destructive-soft motion-safe:animate-shake'
                    : 'border-border'
            "
            lang="en"
            aria-live="polite"
            data-test="order-answer"
        >
            <span class="sr-only">Sua frase:</span>
            <p
                v-if="picked.length === 0"
                class="self-center px-1 text-sm text-muted-foreground"
                lang="pt-BR"
            >
                Toque nas palavras abaixo, na ordem certa.
            </p>
            <TransitionGroup
                enter-active-class="transition duration-150 ease-out motion-reduce:transition-none"
                enter-from-class="scale-90 opacity-0"
            >
                <button
                    v-for="(tokenIndex, position) in picked"
                    :key="tokenIndex"
                    type="button"
                    class="rounded-lg border border-border bg-card px-3 py-2 text-base font-medium shadow-xs transition-colors hover:border-primary/60 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:cursor-default"
                    :disabled="revealed"
                    :aria-label="`${tokens[tokenIndex]} (remover)`"
                    @click="remove(position)"
                >
                    {{ tokens[tokenIndex] }}
                </button>
            </TransitionGroup>
        </div>

        <div
            class="flex flex-wrap justify-center gap-2"
            lang="en"
            role="group"
            aria-label="Palavras disponíveis"
        >
            <button
                v-for="(token, index) in tokens"
                :key="index"
                type="button"
                class="rounded-lg border px-3 py-2 text-base font-medium transition-[background-color,border-color,opacity,transform] duration-150 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                :class="
                    pickedSet.has(index)
                        ? 'border-dashed border-border bg-muted text-transparent'
                        : 'border-border bg-card shadow-xs hover:-translate-y-0.5 hover:border-primary/60'
                "
                :disabled="revealed || pickedSet.has(index)"
                :aria-hidden="pickedSet.has(index) ? 'true' : undefined"
                data-test="order-token"
                @click="place(index)"
            >
                {{ token }}
            </button>
        </div>
    </div>
</template>
