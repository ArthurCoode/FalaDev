<script setup lang="ts">
/**
 * The code challenges, six per language, with what the user already solved.
 * The language picked is kept in the URL and in a cookie, so the list opens
 * on it next time.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    CircleCheck,
    Code,
    Languages,
} from '@lucide/vue';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import DifficultyBadge from '@/components/challenges/DifficultyBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import { index as challengesIndex, show } from '@/routes/challenges';
import type {
    ChallengeLanguage,
    ChallengeLanguageSummary,
    ChallengeSummary,
} from '@/types';

const props = defineProps<{
    language: ChallengeLanguage;
    languages: ChallengeLanguageSummary[];
    challenges: ChallengeSummary[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Desafios', href: challengesIndex() }],
    },
});

const current = computed(
    () =>
        props.languages.find((language) => language.value === props.language) ??
        props.languages[0],
);
const shown = computed(() =>
    props.challenges.filter(
        (challenge) => challenge.language === current.value.value,
    ),
);

function pick(language: ChallengeLanguage): void {
    if (language === props.language) {
        return;
    }

    document.cookie = `challenge_language=${language}; path=/; max-age=31536000; samesite=lax`;
    // Every challenge is already on the page: only the URL and the prop change.
    router.replace({
        url: challengesIndex({ query: { language } }).url,
        props: (current) => ({ ...current, language }),
        preserveScroll: true,
        preserveState: true,
    });
}

/** Arrow keys move between the language tabs (WAI-ARIA tabs pattern). */
function onTabKeydown(event: KeyboardEvent, index: number): void {
    const keys: Record<string, number> = {
        ArrowRight: index + 1,
        ArrowLeft: index - 1,
        Home: 0,
        End: props.languages.length - 1,
    };

    if (!(event.key in keys)) {
        return;
    }

    event.preventDefault();
    const count = props.languages.length;
    const target = (keys[event.key] + count) % count;
    pick(props.languages[target].value);
    document
        .getElementById(`language-tab-${props.languages[target].value}`)
        ?.focus();
}

// On a phone the tabs scroll sideways: keep the selected one in view.
const tablist = ref<HTMLElement | null>(null);

function revealSelectedTab(): void {
    const list = tablist.value;
    const tab = list?.querySelector<HTMLElement>('[aria-selected=true]');

    if (list && tab) {
        list.scrollLeft =
            tab.offsetLeft - (list.clientWidth - tab.clientWidth) / 2;
    }
}

onMounted(revealSelectedTab);
watch(
    () => props.language,
    () => nextTick(revealSelectedTab),
);

const steps = [
    {
        icon: BookOpen,
        title: 'Leia em inglês',
        text: 'O enunciado vem como na entrevista. As palavras sublinhadas mostram a tradução.',
    },
    {
        icon: Code,
        title: 'Resolva na sua linguagem',
        text: 'Seis problemas por linguagem. Os testes rodam no servidor, num ambiente isolado.',
    },
    {
        icon: Languages,
        title: 'Explique em voz alta',
        text: 'No fim, uma resposta-modelo em inglês para você treinar a explicação.',
    },
];
</script>

<template>
    <Head title="Desafios" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Desafios de código"
            description="Problemas de entrevista com o enunciado em inglês. Treine ler a especificação, resolver e explicar."
        >
            <template #actions>
                <p
                    class="text-sm text-muted-foreground tabular-nums"
                    data-test="challenges-progress"
                >
                    {{ current.solved }} de {{ current.total }} resolvidos em
                    {{ current.label }}
                </p>
            </template>
        </PageHeader>

        <ol class="grid gap-3 md:grid-cols-3">
            <li
                v-for="(step, index) in steps"
                :key="step.title"
                class="flex gap-3 rounded-xl bg-muted p-4"
            >
                <component
                    :is="step.icon"
                    class="mt-0.5 size-5 shrink-0"
                    aria-hidden="true"
                />
                <div>
                    <p class="font-medium">{{ index + 1 }}. {{ step.title }}</p>
                    <p class="text-sm text-muted-foreground">{{ step.text }}</p>
                </div>
            </li>
        </ol>

        <section class="space-y-4" aria-labelledby="language-label">
            <h2 id="language-label" class="text-sm font-semibold">
                Escolha a linguagem
            </h2>
            <div
                ref="tablist"
                role="tablist"
                aria-labelledby="language-label"
                class="relative -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 md:mx-0 md:flex-wrap md:overflow-visible md:px-0"
            >
                <button
                    v-for="(language, index) in languages"
                    :id="`language-tab-${language.value}`"
                    :key="language.value"
                    type="button"
                    role="tab"
                    :aria-selected="language.value === current.value"
                    aria-controls="language-panel"
                    :tabindex="language.value === current.value ? 0 : -1"
                    class="inline-flex shrink-0 items-center gap-2 rounded-full border-2 px-4 py-1.5 text-sm font-medium transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                    :class="
                        language.value === current.value
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-card text-foreground hover:border-primary/60'
                    "
                    :data-test="`language-${language.value}`"
                    @click="pick(language.value)"
                    @keydown="onTabKeydown($event, index)"
                >
                    {{ language.label }}
                    <span
                        class="text-xs tabular-nums"
                        :class="
                            language.value === current.value
                                ? 'text-primary-foreground/75'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ language.solved }}/{{ language.total }}
                    </span>
                </button>
            </div>

            <ul
                id="language-panel"
                role="tabpanel"
                :aria-labelledby="`language-tab-${current.value}`"
                class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
            >
                <li v-for="challenge in shown" :key="challenge.slug">
                    <Link
                        :href="show(challenge.slug)"
                        class="group flex h-full flex-col gap-3 rounded-xl border-2 border-border bg-card p-4 transition-[border-color,transform] duration-150 hover:-translate-y-0.5 hover:border-primary/60 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        :data-test="`challenge-${challenge.slug}`"
                    >
                        <div class="flex items-center justify-between">
                            <DifficultyBadge
                                :difficulty="challenge.difficulty"
                            />
                            <span
                                v-if="challenge.solved"
                                class="inline-flex items-center gap-1 text-xs font-medium text-success"
                            >
                                <CircleCheck
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                Resolvido
                            </span>
                        </div>
                        <div class="space-y-1">
                            <h3 class="font-semibold" lang="en">
                                {{ challenge.title }}
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                {{ challenge.summary }}
                            </p>
                        </div>
                        <p
                            class="mt-auto flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors group-hover:text-foreground"
                        >
                            {{ challenge.terms }}
                            {{ challenge.terms === 1 ? 'termo' : 'termos' }}
                            no glossário ·
                            {{
                                challenge.solved ? 'Abrir solução' : 'Resolver'
                            }}
                            <ArrowRight class="size-3.5" aria-hidden="true" />
                        </p>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
