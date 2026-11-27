<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardCheck, Mic, SlidersHorizontal } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

const steps = [
    {
        icon: SlidersHorizontal,
        title: 'Escolha o formato',
        description:
            'Algoritmos, system design ou comportamental, no nível júnior, pleno ou sênior, em sessões de 15 ou 30 minutos.',
    },
    {
        icon: Mic,
        title: 'Responda em inglês, por voz',
        description:
            'A IA faz uma pergunta por vez. Você grava a resposta, revisa a transcrição e só então envia.',
    },
    {
        icon: ClipboardCheck,
        title: 'Receba feedback',
        description:
            'Notas explicadas sobre conteúdo técnico, comunicação e inglês, com trechos das suas respostas como evidência.',
    },
];
</script>

<template>
    <Head title="Entrevistas técnicas em inglês" />

    <div class="flex min-h-svh flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-5"
        >
            <Link
                href="/"
                class="flex items-center gap-2 text-lg font-semibold"
            >
                <AppLogoIcon class="size-7 fill-current text-primary" />
                {{ $page.props.name }}
            </Link>

            <nav class="flex items-center gap-2">
                <Button v-if="$page.props.auth.user" as-child>
                    <Link :href="dashboard()">Ir para o início</Link>
                </Button>
                <template v-else>
                    <Button variant="ghost" as-child>
                        <Link :href="login()">Entrar</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="register()">Criar conta</Link>
                    </Button>
                </template>
            </nav>
        </header>

        <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col px-4">
            <section class="py-16 md:py-24">
                <p
                    class="mb-4 inline-flex items-center gap-2 rounded-full bg-secondary px-3 py-1 text-sm font-medium text-secondary-foreground"
                >
                    <span
                        class="size-2 rounded-full bg-voice"
                        aria-hidden="true"
                    />
                    Prática por voz com IA
                </p>
                <h1
                    class="max-w-3xl text-4xl font-bold tracking-tight md:text-5xl"
                >
                    Entrevistas técnicas em inglês, por voz e com IA.
                </h1>
                <p class="mt-4 max-w-2xl text-lg text-muted-foreground">
                    Treine para explicar seu raciocínio em inglês antes da
                    entrevista de verdade, em sessões curtas e no seu ritmo.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Button v-if="$page.props.auth.user" size="lg" as-child>
                        <Link :href="dashboard()">Ir para o início</Link>
                    </Button>
                    <template v-else>
                        <Button size="lg" as-child>
                            <Link :href="register()">Começar a praticar</Link>
                        </Button>
                        <Button size="lg" variant="outline" as-child>
                            <Link :href="login()">Já tenho conta</Link>
                        </Button>
                    </template>
                </div>
            </section>

            <section aria-labelledby="how-it-works" class="pb-16">
                <h2 id="how-it-works" class="mb-6 text-xl font-semibold">
                    Como funciona
                </h2>
                <ol class="grid gap-4 md:grid-cols-3">
                    <li
                        v-for="step in steps"
                        :key="step.title"
                        class="rounded-xl border border-border bg-card p-5"
                    >
                        <component
                            :is="step.icon"
                            class="mb-3 size-6 text-primary"
                            aria-hidden="true"
                        />
                        <h3 class="font-medium">{{ step.title }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ step.description }}
                        </p>
                    </li>
                </ol>
            </section>
        </main>

        <footer
            class="mx-auto w-full max-w-5xl px-4 py-6 text-xs text-muted-foreground"
        >
            O FalaDev oferece prática e feedback gerados por IA. Não é uma
            avaliação de contratação nem substitui a opinião de um recrutador.
        </footer>
    </div>
</template>
