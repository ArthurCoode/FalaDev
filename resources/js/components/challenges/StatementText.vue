<script setup lang="ts">
/**
 * One line of a challenge statement, in English. `code` becomes inline code
 * and {{term}} a glossary term: hovering or focusing it shows the Portuguese
 * meaning. On touch screens the glossary list below the statement has the
 * same meanings.
 */
import { computed } from 'vue';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

const props = defineProps<{
    text: string;
    glossary?: Record<string, string>;
}>();

type Segment =
    | { kind: 'text'; value: string }
    | { kind: 'code'; value: string }
    | { kind: 'term'; value: string; meaning: string };

const segments = computed<Segment[]>(() =>
    props.text
        .split(/(\{\{.+?\}\}|`[^`]+`)/g)
        .filter((part) => part !== '')
        .map((part): Segment => {
            if (part.startsWith('{{') && part.endsWith('}}')) {
                const term = part.slice(2, -2);

                return {
                    kind: 'term',
                    value: term,
                    meaning: props.glossary?.[term] ?? '',
                };
            }

            if (part.startsWith('`') && part.endsWith('`')) {
                return { kind: 'code', value: part.slice(1, -1) };
            }

            return { kind: 'text', value: part };
        }),
);
</script>

<template>
    <span lang="en">
        <template v-for="(segment, index) in segments" :key="index">
            <code
                v-if="segment.kind === 'code'"
                class="rounded bg-muted px-1.5 py-0.5 font-mono text-[0.9em]"
                >{{ segment.value }}</code
            >
            <Tooltip v-else-if="segment.kind === 'term'">
                <TooltipTrigger as-child>
                    <button
                        type="button"
                        class="cursor-help rounded-sm font-medium underline decoration-primary decoration-dotted decoration-2 underline-offset-4 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        :aria-label="`${segment.value}: ${segment.meaning}`"
                        data-test="glossary-term"
                    >
                        {{ segment.value }}
                    </button>
                </TooltipTrigger>
                <TooltipContent lang="pt-BR" class="max-w-64">
                    {{ segment.meaning }}
                </TooltipContent>
            </Tooltip>
            <template v-else>{{ segment.value }}</template>
        </template>
    </span>
</template>
