<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M9 5H31A6 6 0 0 1 37 11V23A6 6 0 0 1 31 29H19L10 36L11 29H9A6 6 0 0 1 3 23V11A6 6 0 0 1 9 5ZM16.5 11.5L9.5 17L16.5 22.5L18.3 20.4L13.4 17L18.3 13.6ZM23.5 11.5L21.7 13.6L26.6 17L21.7 20.4L23.5 22.5L30.5 17Z"
        />
    </svg>
</template>
