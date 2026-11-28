<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Flame,
    GraduationCap,
    History,
    Mic,
    Play,
    RotateCcw,
} from '@lucide/vue';
import { computed } from 'vue';
import InterviewSessionList from '@/components/interview/InterviewSessionList.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create, index, show } from '@/routes/interviews';
import { index as studyIndex, review as studyReview } from '@/routes/study';
import { show as showLesson } from '@/routes/study/lessons';
import type {
    InterviewSessionSummary,
    StudyLessonLink,
    StudyStats,
    UsageSummary,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Início',
                href: dashboard(),
            },
        ],
    },
});

const props = defineProps<{
    /** The running interview; while it exists no new one can start. */
    inProgress: {
        id: number;
        category: string;
        level: string;
        startedAt: string | null;
    } | null;
    recentSessions: InterviewSessionSummary[];
    usage: UsageSummary;
    study: StudyStats & { nextLesson: StudyLessonLink | null };
}>();

const streakDays = computed(() =>
    props.study.streak === 1 ? '1 dia' : `${props.study.streak} dias`,
);

const page = usePage();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);

const usagePercent = computed(() =>
    props.usage.limit > 0
        ? Math.min((props.usage.used / props.usage.limit) * 100, 100)
        : 100,
);
</script>

<template>
    <Head title="Início" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="`Olá, ${firstName}`"
            description="Pratique entrevistas técnicas em inglês, por voz e com IA."
        />

        <div class="grid gap-4 md:grid-cols-3">
            <Card
                v-if="inProgress"
                class="border-voice md:col-span-2"
                data-test="interview-in-progress"
            >
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <span class="relative flex size-3" aria-hidden="true">
                            <span
                                class="absolute inline-flex size-full animate-ping rounded-full bg-voice opacity-75 motion-reduce:animate-none"
                            />
                            <span
                                class="relative inline-flex size-3 rounded-full bg-voice"
                            />
                        </span>
                        Entrevista em andamento
                    </CardTitle>
                    <CardDescription>
                        {{ inProgress.category }} · {{ inProgress.level }}. Suas
                        respostas enviadas estão salvas; continue de onde parou.
                        Para começar outra, conclua ou encerre esta.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Button
                        size="lg"
                        as-child
                        data-test="continue-interview-button"
                    >
                        <Link :href="show(inProgress.id)">
                            <Play />
                            Continuar entrevista
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <Card v-else class="md:col-span-2">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Mic class="size-5 text-voice" aria-hidden="true" />
                        Nova entrevista
                    </CardTitle>
                    <CardDescription>
                        Escolha a categoria (algoritmos, system design ou
                        comportamental), o nível e a duração. A entrevista é
                        conduzida em inglês e termina com feedback técnico, de
                        comunicação e de inglês.
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col items-start gap-2">
                    <Button
                        v-if="usage.remaining > 0"
                        size="lg"
                        as-child
                        data-test="new-interview-button"
                    >
                        <Link :href="create()">Nova entrevista</Link>
                    </Button>
                    <template v-else>
                        <Button size="lg" disabled>Nova entrevista</Button>
                        <p class="text-xs text-muted-foreground">
                            Você atingiu o limite deste mês.
                        </p>
                    </template>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Uso neste mês</CardTitle>
                    <CardDescription>
                        Contam as entrevistas iniciadas, não as apenas
                        configuradas.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <p class="text-sm">
                        <span class="text-2xl font-semibold">{{
                            usage.used
                        }}</span>
                        <span class="text-muted-foreground">
                            de {{ usage.limit }} entrevistas</span
                        >
                    </p>
                    <div
                        class="h-2 w-full overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="usage.used"
                        aria-valuemin="0"
                        :aria-valuemax="usage.limit"
                        aria-label="Entrevistas usadas neste mês"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-[width]"
                            :style="{ width: `${usagePercent}%` }"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        <template v-if="usage.remaining > 0">
                            Restam {{ usage.remaining }}
                            {{
                                usage.remaining === 1
                                    ? 'entrevista'
                                    : 'entrevistas'
                            }}
                            neste mês.
                        </template>
                        <template v-else>
                            Você atingiu o limite deste mês.
                        </template>
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card data-test="dashboard-study">
            <div
                class="flex flex-col gap-4 px-6 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-full"
                        :class="
                            study.studiedToday
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        <Flame class="size-6" aria-hidden="true" />
                    </div>
                    <div>
                        <p class="font-semibold">
                            <template v-if="study.streak > 0">
                                Sequência de {{ streakDays }}
                            </template>
                            <template v-else>Estudo diário</template>
                        </p>
                        <p class="text-sm text-muted-foreground">
                            <template v-if="study.studiedToday">
                                Meta de hoje cumprida.
                            </template>
                            <template v-else-if="study.reviewsDue > 0">
                                {{
                                    study.reviewsDue === 1
                                        ? '1 frase para revisar hoje.'
                                        : `${study.reviewsDue} frases para revisar hoje.`
                                }}
                            </template>
                            <template v-else>
                                Uma lição de 3 minutos com o inglês das
                                entrevistas.
                            </template>
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-if="study.reviewsDue > 0"
                        as-child
                        data-test="dashboard-review"
                    >
                        <Link :href="studyReview()">
                            <RotateCcw />
                            Revisar
                        </Link>
                    </Button>
                    <Button
                        v-if="study.nextLesson"
                        :variant="study.reviewsDue > 0 ? 'outline' : 'default'"
                        as-child
                        data-test="dashboard-next-lesson"
                    >
                        <Link :href="showLesson(study.nextLesson.slug)">
                            <GraduationCap />
                            {{ study.nextLesson.title }}
                        </Link>
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="studyIndex()">
                            Ver trilha
                            <ArrowRight />
                        </Link>
                    </Button>
                </div>
            </div>
        </Card>

        <section aria-labelledby="recent-sessions-title" class="space-y-3">
            <h2
                id="recent-sessions-title"
                class="flex items-center gap-2 text-lg font-semibold"
            >
                <History
                    class="size-5 text-muted-foreground"
                    aria-hidden="true"
                />
                Entrevistas recentes
            </h2>

            <Card v-if="recentSessions.length === 0" class="border-dashed">
                <CardContent class="py-8 text-center">
                    <p class="font-medium">
                        Você ainda não fez nenhuma entrevista.
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Suas entrevistas e relatórios aparecerão aqui.
                    </p>
                </CardContent>
            </Card>

            <template v-else>
                <InterviewSessionList :sessions="recentSessions" />
                <Link
                    :href="index()"
                    class="inline-block text-sm font-medium text-primary hover:underline"
                >
                    Ver histórico completo
                </Link>
            </template>
        </section>
    </div>
</template>
