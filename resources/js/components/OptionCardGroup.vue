<script setup lang="ts">
/**
 * Single-choice group rendered as selectable cards.
 * Uses native radio inputs, so it works inside Inertia <Form> and keeps
 * keyboard navigation (Tab to the group, arrow keys between options).
 */
import { CircleCheck } from '@lucide/vue';
import type { Component } from 'vue';
import InputError from '@/components/InputError.vue';
import type { InterviewOption } from '@/types';

const emit = defineEmits<{
    change: [value: string];
}>();

withDefaults(
    defineProps<{
        name: string;
        legend: string;
        options: InterviewOption[];
        defaultValue?: string | number | null;
        error?: string;
        columns?: 2 | 3;
        icons?: Record<string, Component>;
    }>(),
    {
        defaultValue: null,
        error: undefined,
        columns: 3,
        icons: () => ({}),
    },
);
</script>

<template>
    <fieldset class="space-y-3">
        <legend class="text-sm font-medium">{{ legend }}</legend>

        <div
            class="grid gap-3"
            :class="columns === 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-2'"
        >
            <label
                v-for="option in options"
                :key="option.value"
                class="relative flex cursor-pointer flex-col gap-1 rounded-xl border border-border bg-card p-4 transition-colors hover:border-primary/60 has-checked:border-primary has-checked:bg-secondary has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50"
            >
                <input
                    type="radio"
                    class="peer sr-only"
                    :name="name"
                    :value="option.value"
                    :checked="option.value === defaultValue"
                    :aria-invalid="error ? true : undefined"
                    @change="
                        emit(
                            'change',
                            ($event.target as HTMLInputElement).value,
                        )
                    "
                />
                <CircleCheck
                    class="absolute top-3 right-3 hidden size-5 text-primary peer-checked:block"
                    aria-hidden="true"
                />
                <span class="flex items-center gap-2 pr-6 font-medium">
                    <component
                        :is="icons[String(option.value)]"
                        v-if="icons[String(option.value)]"
                        class="size-4 text-primary"
                        aria-hidden="true"
                    />
                    {{ option.label }}
                </span>
                <span
                    v-if="option.description"
                    class="text-sm text-muted-foreground"
                >
                    {{ option.description }}
                </span>
            </label>
        </div>

        <InputError :message="error" />
    </fieldset>
</template>
