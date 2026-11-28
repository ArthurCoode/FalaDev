<script setup lang="ts">
/**
 * Tutorial: what FalaDev is, how an interview works and the minimum English
 * to follow it. "Modo treino" hides the translations, so the phrases can be
 * practiced right here.
 */
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    Code,
    Eye,
    EyeOff,
    FileText,
    GraduationCap,
    LoaderCircle,
    Mic,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { tutorial } from '@/routes';
import { index as challengesIndex } from '@/routes/challenges';
import {
    create as createInterview,
    index as interviewHistory,
} from '@/routes/interviews';
import { index as studyIndex } from '@/routes/study';
import { dismiss, restore } from '@/routes/tutorial';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tutorial', href: tutorial() }],
    },
});

type Phrase = { en: string; pt: string; note?: string };

const page = usePage();
// Dismissed tutorials leave the menu, but the page still opens by its URL.
const dismissed = computed(() =>
    Boolean(page.props.auth.user.tutorial_dismissed_at),
);

const features = [
    {
        icon: Mic,
        title: 'Entrevista por voz',
        text: 'Algoritmos, system design ou comportamental, de 15 ou 30 minutos. O entrevistador fala em inglês e você responde em voz alta.',
        href: createInterview(),
        cta: 'Nova entrevista',
    },
    {
        icon: FileText,
        title: 'Relatório',
        text: 'Notas de 1 a 5 em conteúdo técnico, raciocínio, comunicação e inglês, com as frases que você pode melhorar.',
        href: interviewHistory(),
        cta: 'Ver histórico',
    },
    {
        icon: GraduationCap,
        title: 'Trilha de estudos',
        text: 'Lições de 3 minutos com o inglês das entrevistas. O que você erra volta para revisão nos dias certos.',
        href: studyIndex(),
        cta: 'Abrir a trilha',
    },
    {
        icon: Code,
        title: 'Desafios de código',
        text: 'Problemas de entrevista com o enunciado em inglês, em 7 linguagens, resolvidos no editor e explicados em voz alta.',
        href: challengesIndex(),
        cta: 'Ver desafios',
    },
];

const steps = [
    { text: 'Escolha a categoria, o nível e a duração.' },
    { text: 'Teste o fone e o microfone: leva menos de um minuto.' },
    {
        text: 'Ouça a pergunta (ela também aparece escrita), grave a resposta, revise a transcrição e envie. Cada resposta tem até 3 minutos.',
    },
    { text: 'No fim, o relatório fica pronto em alguns instantes.' },
];

const groups: {
    id: string;
    title: string;
    description: string;
    phrases: Phrase[];
}[] = [
    {
        id: 'interviewer',
        title: 'O que o entrevistador vai dizer',
        description: 'Reconhecer estas frases já tira metade do nervoso.',
        phrases: [
            {
                en: 'Tell me about yourself.',
                pt: 'Fale um pouco sobre você.',
                note: 'A abertura mais comum.',
            },
            {
                en: 'Could you walk me through your solution?',
                pt: 'Pode me explicar sua solução passo a passo?',
            },
            {
                en: 'Can you elaborate on that?',
                pt: 'Pode detalhar isso?',
                note: 'É a pergunta de aprofundamento.',
            },
            {
                en: "What's the time complexity?",
                pt: 'Qual é a complexidade de tempo?',
            },
            {
                en: 'How would you handle a spike in traffic?',
                pt: 'Como você lidaria com um pico de tráfego?',
            },
            {
                en: 'Tell me about a time when you disagreed with someone.',
                pt: 'Conte sobre uma vez em que você discordou de alguém.',
                note: 'Comportamental: responda com situação, ação e resultado.',
            },
            {
                en: 'Do you have any questions for me?',
                pt: 'Você tem alguma pergunta para mim?',
            },
        ],
    },
    {
        id: 'you',
        title: 'O que você pode dizer',
        description: 'Frases curtas para pedir tempo, confirmar e encerrar.',
        phrases: [
            {
                en: 'Could you repeat the question, please?',
                pt: 'Você pode repetir a pergunta, por favor?',
            },
            {
                en: 'Let me think about that for a second.',
                pt: 'Deixe-me pensar nisso por um instante.',
            },
            {
                en: 'Just to make sure I understood: …',
                pt: 'Só para confirmar que entendi: …',
            },
            {
                en: 'Can I assume the input is sorted?',
                pt: 'Posso supor que a entrada está ordenada?',
            },
            {
                en: "I'm not sure, but my first guess would be…",
                pt: 'Não tenho certeza, mas meu primeiro palpite seria…',
            },
            {
                en: 'I think that covers it.',
                pt: 'Acho que é isso.',
                note: 'Para avisar que terminou a resposta.',
            },
        ],
    },
    {
        id: 'complexity',
        title: 'Como falar de complexidade',
        description: 'O "O" se lê "big O". Estas são as que mais aparecem.',
        phrases: [
            {
                en: 'O(1): "big O of one", constant time',
                pt: 'Tempo constante.',
            },
            {
                en: 'O(log n): "big O of log n", logarithmic time',
                pt: 'Tempo logarítmico, como na busca binária.',
            },
            {
                en: 'O(n): "big O of n", linear time',
                pt: 'Tempo linear: passa pelos dados uma vez.',
            },
            {
                en: 'O(n log n): "big O of n log n"',
                pt: 'O custo típico de ordenar.',
            },
            {
                en: 'O(n²): "big O of n squared", quadratic time',
                pt: 'Tempo quadrático, como checar todos os pares.',
            },
        ],
    },
    {
        id: 'words',
        title: 'Palavras dos enunciados',
        description: 'Aparecem nas perguntas de algoritmos e nos desafios.',
        phrases: [
            { en: 'given', pt: 'dado, considerando' },
            { en: 'return', pt: 'devolver (o retorno da função)' },
            { en: 'input / output', pt: 'entrada / saída' },
            { en: 'edge case', pt: 'caso-limite, como lista vazia' },
            { en: 'constraint', pt: 'restrição, limite do problema' },
            {
                en: 'trade-off',
                pt: 'troca: ganhar algo abrindo mão de outra coisa',
            },
        ],
    },
];

const tips = [
    'A pronúncia e o sotaque não entram na nota. O inglês é avaliado pela gramática, pelo vocabulário e pela clareza.',
    'Pedir para repetir a pergunta é normal, e no app ela também fica escrita na tela.',
    'Você revisa a transcrição antes de enviar: dá para corrigir o que saiu errado.',
    'Pense em voz alta. O entrevistador quer ver o caminho, não só a resposta.',
];

/** Practice mode: translations hidden until revealed one by one. */
const practice = ref(false);
const revealed = reactive(new Set<string>());

function togglePractice(): void {
    practice.value = !practice.value;
    revealed.clear();
}
</script>

<template>
    <Head title="Tutorial" />

    <div class="flex h-full flex-1 flex-col gap-10 p-4 md:p-6">
        <PageHeader
            title="Tutorial"
            description="O que é o FalaDev, como funciona uma entrevista e o inglês mínimo para não ficar perdido. Leva uns 5 minutos."
        />

        <!-- About -->
        <section aria-labelledby="about-title" class="space-y-4">
            <div
                class="rounded-2xl bg-primary p-6 text-primary-foreground md:p-8"
            >
                <h2
                    id="about-title"
                    class="text-2xl font-semibold tracking-tight md:text-3xl"
                >
                    Bem-vindo ao FalaDev
                </h2>
                <p
                    class="mt-3 max-w-3xl text-base leading-relaxed text-primary-foreground/85 md:text-lg"
                >
                    O FalaDev é um simulador de entrevista técnica em inglês
                    para devs brasileiros. Você responde por voz, como numa
                    entrevista real, e recebe um relatório do que foi bem e do
                    que melhorar. Entre uma entrevista e outra, lições curtas e
                    desafios de código mantêm o seu inglês em dia.
                </p>
            </div>

            <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <li
                    v-for="feature in features"
                    :key="feature.title"
                    class="flex flex-col gap-3 rounded-xl border border-border bg-card p-4"
                >
                    <component
                        :is="feature.icon"
                        class="size-6 text-voice"
                        aria-hidden="true"
                    />
                    <div class="space-y-1">
                        <h3 class="font-semibold">{{ feature.title }}</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ feature.text }}
                        </p>
                    </div>
                    <Link
                        :href="feature.href"
                        class="mt-auto inline-flex items-center gap-1 text-sm font-medium hover:underline"
                    >
                        {{ feature.cta }}
                        <ArrowRight class="size-3.5" aria-hidden="true" />
                    </Link>
                </li>
            </ul>
        </section>

        <!-- How an interview works -->
        <section aria-labelledby="how-title" class="space-y-4">
            <h2 id="how-title" class="text-xl font-semibold">
                Como é uma entrevista
            </h2>
            <ol class="grid gap-3 md:grid-cols-4">
                <li
                    v-for="(step, index) in steps"
                    :key="index"
                    class="flex gap-3 rounded-xl bg-muted p-4 md:flex-col"
                >
                    <span
                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground"
                        aria-hidden="true"
                    >
                        {{ index + 1 }}
                    </span>
                    <p class="text-sm">{{ step.text }}</p>
                </li>
            </ol>
        </section>

        <!-- Minimum English -->
        <section aria-labelledby="english-title" class="space-y-6">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
            >
                <div class="space-y-1">
                    <h2 id="english-title" class="text-xl font-semibold">
                        O inglês mínimo
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        As frases que mais aparecem. No modo treino, tente
                        entender antes de ver a tradução.
                    </p>
                </div>
                <Button
                    :variant="practice ? 'default' : 'outline'"
                    :aria-pressed="practice"
                    data-test="practice-mode"
                    @click="togglePractice"
                >
                    <EyeOff v-if="practice" />
                    <Eye v-else />
                    Modo treino {{ practice ? 'ligado' : 'desligado' }}
                </Button>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <article
                    v-for="group in groups"
                    :key="group.id"
                    class="rounded-xl border border-border bg-card"
                    :aria-labelledby="`group-${group.id}`"
                >
                    <header class="border-b border-border p-4">
                        <h3 :id="`group-${group.id}`" class="font-semibold">
                            {{ group.title }}
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            {{ group.description }}
                        </p>
                    </header>
                    <ul class="divide-y divide-border">
                        <li
                            v-for="(phrase, index) in group.phrases"
                            :key="index"
                            class="space-y-1 px-4 py-3"
                            data-test="tutorial-phrase"
                        >
                            <p class="font-medium" lang="en">{{ phrase.en }}</p>
                            <template
                                v-if="
                                    !practice ||
                                    revealed.has(`${group.id}-${index}`)
                                "
                            >
                                <p class="text-sm text-muted-foreground">
                                    {{ phrase.pt }}
                                </p>
                                <p
                                    v-if="phrase.note"
                                    class="text-xs text-muted-foreground/80"
                                >
                                    {{ phrase.note }}
                                </p>
                            </template>
                            <button
                                v-else
                                type="button"
                                class="text-sm font-medium text-muted-foreground underline decoration-dotted underline-offset-4 hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                @click="revealed.add(`${group.id}-${index}`)"
                            >
                                Ver tradução
                            </button>
                        </li>
                    </ul>
                </article>
            </div>
        </section>

        <!-- Tips -->
        <section aria-labelledby="tips-title" class="space-y-4">
            <h2 id="tips-title" class="text-xl font-semibold">
                Para não ficar perdido
            </h2>
            <ul class="grid gap-3 md:grid-cols-2">
                <li
                    v-for="(tip, index) in tips"
                    :key="index"
                    class="rounded-xl border-l-4 border-primary bg-muted p-4 text-sm"
                >
                    {{ tip }}
                </li>
            </ul>
        </section>

        <!-- Where to start -->
        <section
            aria-labelledby="start-title"
            class="flex flex-col gap-4 rounded-2xl border border-border bg-card p-6 md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h2 id="start-title" class="text-xl font-semibold">
                    Por onde começar?
                </h2>
                <p class="text-sm text-muted-foreground">
                    Faça a primeira lição da trilha e depois uma entrevista de
                    15 minutos.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button as-child data-test="tutorial-start-study">
                    <Link :href="studyIndex()">
                        <GraduationCap />
                        Primeira lição
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="createInterview()">
                        <Mic />
                        Nova entrevista
                    </Link>
                </Button>
            </div>
        </section>

        <!-- Dismiss -->
        <section
            aria-labelledby="dismiss-title"
            class="flex flex-col gap-3 border-t border-border pt-6 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 id="dismiss-title" class="font-semibold">
                    {{
                        dismissed
                            ? 'O tutorial está fora do menu'
                            : 'Já sabe como funciona?'
                    }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{
                        dismissed
                            ? 'Você escolheu não exibir mais. Coloque de volta quando quiser.'
                            : 'O tutorial sai do menu e do Início. Dá para trazer de volta em Configurações > Aparência.'
                    }}
                </p>
            </div>
            <Form
                v-if="dismissed"
                v-bind="restore.form()"
                v-slot="{ processing }"
            >
                <Button
                    type="submit"
                    variant="outline"
                    :disabled="processing"
                    data-test="tutorial-restore"
                >
                    <LoaderCircle v-if="processing" class="animate-spin" />
                    <BookOpen v-else />
                    Mostrar no menu de novo
                </Button>
            </Form>
            <Form v-else v-bind="dismiss.form()" v-slot="{ processing }">
                <Button
                    type="submit"
                    variant="outline"
                    :disabled="processing"
                    data-test="tutorial-dismiss"
                >
                    <LoaderCircle v-if="processing" class="animate-spin" />
                    <EyeOff v-else />
                    Não exibir novamente
                </Button>
            </Form>
        </section>
    </div>
</template>
