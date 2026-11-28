<script setup lang="ts">
/**
 * Pick one option. For "gap" exercises the chosen option fills the blank of
 * the sentence. Native radios keep keyboard navigation (arrows between
 * options); the page also maps the 1-4 keys to the options.
 */
import { CircleCheck, CircleX } from '@lucide/vue';
import { computed } from 'vue';
import type { ChoiceExercise } from '@/types';

const props = defineProps<{
    exercise: ChoiceExercise;
    /** Options in display order (shuffled by the page). */
    options: string[];
    /** After "Verificar": shows which option was right. */
    revealed: boolean;
}>();

const selected = defineModel<string | null>({ required: true });

const sentenceParts = computed(() =>
    props.exercise.type === 'gap' && props.exercise.sentence
        ? props.exercise.sentence.split('___')
        : null,
);

function optionState(option: string): 'right' | 'wrong' | 'selected' | 'idle' {
    if (props.revealed && option === props.exercise.answer) {
        return 'right';
    }

    if (props.revealed && option === selected.value) {
        return 'wrong';
    }

    return option === selected.value ? 'selected' : 'idle';
}
</script>

<template>
    <div class="space-y-6">
        <p
            v-if="sentenceParts"
            lang="en"
            class="font-display text-2xl leading-snug text-balance md:text-3xl"
            data-test="gap-sentence"
        >
            {{ sentenceParts[0]
            }}<span
                class="mx-1 inline-block min-w-[4ch] border-b-2 px-1 text-center transition-colors"
                :class="
                    selected
                        ? revealed && selected !== exercise.answer
                            ? 'border-destructive-foreground text-destructive-foreground'
                            : 'border-primary text-foreground'
                        : 'border-muted-foreground/50 text-transparent'
                "
                >{{ selected ?? '____' }}</span
            >{{ sentenceParts[1] }}
        </p>

        <fieldset :disabled="revealed" class="grid gap-3">
            <legend class="sr-only">Escolha uma opção</legend>
            <label
                v-for="(option, index) in options"
                :key="option"
                class="group relative flex cursor-pointer items-center gap-3 rounded-xl border-2 bg-card p-4 text-left transition-[border-color,background-color,transform] duration-150 has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50 has-disabled:cursor-default"
                :class="{
                    'border-border hover:-translate-y-0.5 hover:border-primary/60':
                        optionState(option) === 'idle' && !revealed,
                    'border-border opacity-60':
                        optionState(option) === 'idle' && revealed,
                    'border-primary bg-secondary':
                        optionState(option) === 'selected',
                    'border-success bg-success-soft motion-safe:animate-pop':
                        optionState(option) === 'right',
                    'border-destructive-foreground bg-destructive-soft motion-safe:animate-shake':
                        optionState(option) === 'wrong',
                }"
                data-test="choice-option"
            >
                <input
                    v-model="selected"
                    type="radio"
                    class="sr-only"
                    :name="exercise.key"
                    :value="option"
                />
                <kbd
                    class="hidden size-6 shrink-0 items-center justify-center rounded-md border border-border text-xs text-muted-foreground sm:flex"
                    aria-hidden="true"
                    >{{ index + 1 }}</kbd
                >
                <span class="flex-1 text-base">{{ option }}</span>
                <CircleCheck
                    v-if="optionState(option) === 'right'"
                    class="size-5 shrink-0 text-success"
                    aria-label="Resposta certa"
                />
                <CircleX
                    v-else-if="optionState(option) === 'wrong'"
                    class="size-5 shrink-0 text-destructive-foreground"
                    aria-label="Sua resposta"
                />
            </label>
        </fieldset>
    </div>
</template>
