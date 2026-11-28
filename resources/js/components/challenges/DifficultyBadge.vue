<script setup lang="ts">
import type { ChallengeDifficulty } from '@/types';

defineProps<{
    difficulty: ChallengeDifficulty;
}>();

const labels: Record<ChallengeDifficulty, string> = {
    easy: 'Fácil',
    medium: 'Médio',
    hard: 'Difícil',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
        :class="{
            'border-success/40 text-success': difficulty === 'easy',
            'border-primary/50 text-foreground': difficulty === 'medium',
            'border-destructive-foreground/50 text-destructive-foreground':
                difficulty === 'hard',
        }"
    >
        {{ labels[difficulty] }}
    </span>
</template>
