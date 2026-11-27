<script setup lang="ts">
/**
 * The interview report: scores always shown with their scale, and every
 * comment tied to evidence from the transcript (FalaDev.md, sections 5C and 7).
 */
import { useForm } from '@inertiajs/vue3';
import {
    ArrowRight,
    ThumbsDown,
    ThumbsUp,
    FlaskConical,
    Languages,
    ListChecks,
    Sparkles,
    TrendingUp,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { feedback } from '@/routes/interviews/evaluation';
import type { InterviewEvaluation } from '@/types';

const props = defineProps<{
    sessionId: number;
    evaluation: InterviewEvaluation;
    /** Score → meaning, e.g. { 1: 'insuficiente', 3: …, 5: … }. */
    scoreAnchors: Record<string, string>;
}>();

const MAX_SCORE = 5;

// "Este feedback foi útil?" (pilot metric, FalaDev.md section 12).
const feedbackForm = useForm({
    useful: props.evaluation.userFeedback?.useful ?? (null as boolean | null),
    comment: props.evaluation.userFeedback?.comment ?? '',
});
const editingFeedback = ref(props.evaluation.userFeedback === null);

function sendFeedback(): void {
    feedbackForm.post(feedback.url(props.sessionId), {
        preserveScroll: true,
        onSuccess: () => (editingFeedback.value = false),
    });
}
const segments = Array.from({ length: MAX_SCORE }, (_, i) => i + 1);

const scaleText = computed(() =>
    Object.entries(props.scoreAnchors)
        .map(([score, meaning]) => `${score} = ${meaning}`)
        .join(' · '),
);
</script>

<template>
    <div class="space-y-4" data-test="evaluation-report">
        <p
            v-if="evaluation.isSimulated"
            class="flex items-start gap-2 rounded-md border border-warning bg-warning-soft px-3 py-2 text-sm text-foreground"
            role="note"
        >
            <FlaskConical
                class="mt-0.5 size-5 shrink-0 text-warning"
                aria-hidden="true"
            />
            Relatório simulado: o avaliador com IA ainda não está ligado. As
            notas são fixas e não avaliam suas respostas; os trechos citados são
            seus.
        </p>

        <!-- Overall -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Resultado geral</CardTitle>
                <CardDescription
                    >Escala de 1 a 5: {{ scaleText }}.</CardDescription
                >
            </CardHeader>
            <CardContent
                class="flex flex-col gap-4 sm:flex-row sm:items-center"
            >
                <p class="shrink-0" data-test="overall-score">
                    <span class="text-4xl font-semibold">{{
                        evaluation.overallScore
                    }}</span>
                    <span class="text-muted-foreground">
                        de {{ MAX_SCORE }}</span
                    >
                </p>
                <p class="text-sm">{{ evaluation.summary }}</p>
            </CardContent>
        </Card>

        <!-- Dimensions -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Notas por dimensão</CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="space-y-4">
                    <li
                        v-for="dimension in evaluation.dimensions"
                        :key="dimension.key"
                        class="space-y-1.5"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-medium">{{ dimension.label }}</p>
                            <p class="shrink-0 text-sm font-medium">
                                {{ dimension.score }} de {{ MAX_SCORE }}
                            </p>
                        </div>
                        <div
                            class="flex gap-1"
                            role="img"
                            :aria-label="`${dimension.score} de ${MAX_SCORE}`"
                        >
                            <span
                                v-for="segment in segments"
                                :key="segment"
                                class="h-1.5 flex-1 rounded-full"
                                :class="
                                    segment <= dimension.score
                                        ? 'bg-primary'
                                        : 'bg-muted'
                                "
                            />
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ dimension.explanation }}
                        </p>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <div class="grid gap-4 md:grid-cols-2">
            <!-- Strengths -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Sparkles
                            class="size-5 text-success"
                            aria-hidden="true"
                        />
                        Pontos fortes
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-4">
                        <li
                            v-for="(item, i) in evaluation.strengths"
                            :key="i"
                            class="space-y-1.5"
                        >
                            <p class="text-sm font-medium">{{ item.point }}</p>
                            <blockquote
                                class="border-l-2 border-success pl-3 text-sm text-muted-foreground italic"
                                lang="en"
                            >
                                “{{ item.evidence }}”
                            </blockquote>
                            <p class="text-xs text-muted-foreground">
                                Resposta à pergunta {{ item.position }}
                            </p>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <!-- Improvements -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <TrendingUp
                            class="size-5 text-highlight"
                            aria-hidden="true"
                        />
                        Oportunidades de melhoria
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-4">
                        <li
                            v-for="(item, i) in evaluation.improvements"
                            :key="i"
                            class="space-y-1.5"
                        >
                            <p class="text-sm font-medium">{{ item.point }}</p>
                            <blockquote
                                class="border-l-2 border-highlight pl-3 text-sm text-muted-foreground italic"
                                lang="en"
                            >
                                “{{ item.evidence }}”
                            </blockquote>
                            <p class="text-xs text-muted-foreground">
                                Resposta à pergunta {{ item.position }}
                            </p>
                            <p class="flex items-start gap-1.5 text-sm">
                                <ArrowRight
                                    class="mt-0.5 size-4 shrink-0 text-primary"
                                    aria-hidden="true"
                                />
                                {{ item.action }}
                            </p>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <!-- English -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Languages class="size-5 text-voice" aria-hidden="true" />
                    Feedback de inglês
                </CardTitle>
                <CardDescription>
                    Baseado no texto transcrito: não avalia pronúncia nem
                    sotaque.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ul class="space-y-4">
                    <li
                        v-for="(item, i) in evaluation.englishFeedback"
                        :key="i"
                        class="space-y-1.5 text-sm"
                    >
                        <p lang="en">
                            <span class="text-muted-foreground"
                                >Você disse:</span
                            >
                            “{{ item.original }}”
                        </p>
                        <p lang="en">
                            <span class="text-muted-foreground">Sugestão:</span>
                            “{{ item.suggestion }}”
                        </p>
                        <p class="text-muted-foreground">
                            {{ item.explanation }} (pergunta
                            {{ item.position }})
                        </p>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <!-- Next steps -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <ListChecks
                        class="size-5 text-primary"
                        aria-hidden="true"
                    />
                    Próximos exercícios
                </CardTitle>
            </CardHeader>
            <CardContent>
                <ol class="list-decimal space-y-1.5 pl-5 text-sm">
                    <li v-for="(step, i) in evaluation.nextSteps" :key="i">
                        {{ step }}
                    </li>
                </ol>
            </CardContent>
        </Card>

        <!-- Was it useful? -->
        <Card data-test="report-feedback">
            <CardHeader>
                <CardTitle class="text-base">Este feedback foi útil?</CardTitle>
                <CardDescription>
                    Sua resposta ajuda a melhorar os relatórios.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="!editingFeedback && evaluation.userFeedback"
                    class="flex flex-wrap items-center gap-3 text-sm"
                    role="status"
                >
                    <span>
                        Você marcou como
                        <strong>{{
                            evaluation.userFeedback.useful
                                ? 'útil'
                                : 'pouco útil'
                        }}</strong
                        >. Obrigado!
                    </span>
                    <Button
                        variant="link"
                        size="sm"
                        class="h-auto p-0"
                        @click="editingFeedback = true"
                    >
                        Mudar resposta
                    </Button>
                </div>
                <form v-else class="space-y-3" @submit.prevent="sendFeedback">
                    <div
                        class="flex flex-wrap gap-2"
                        role="radiogroup"
                        aria-label="Este feedback foi útil?"
                    >
                        <Button
                            type="button"
                            role="radio"
                            :aria-checked="feedbackForm.useful === true"
                            :variant="
                                feedbackForm.useful === true
                                    ? 'default'
                                    : 'outline'
                            "
                            data-test="feedback-useful"
                            @click="feedbackForm.useful = true"
                        >
                            <ThumbsUp />
                            Sim, foi útil
                        </Button>
                        <Button
                            type="button"
                            role="radio"
                            :aria-checked="feedbackForm.useful === false"
                            :variant="
                                feedbackForm.useful === false
                                    ? 'default'
                                    : 'outline'
                            "
                            data-test="feedback-not-useful"
                            @click="feedbackForm.useful = false"
                        >
                            <ThumbsDown />
                            Não muito
                        </Button>
                    </div>
                    <div
                        v-if="feedbackForm.useful !== null"
                        class="space-y-1.5"
                    >
                        <label
                            for="feedback-comment"
                            class="text-sm font-medium"
                        >
                            Quer contar o porquê? (opcional)
                        </label>
                        <textarea
                            id="feedback-comment"
                            v-model="feedbackForm.comment"
                            rows="3"
                            maxlength="500"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                            data-test="feedback-comment"
                        />
                        <InputError
                            :message="
                                feedbackForm.errors.comment ??
                                feedbackForm.errors.useful
                            "
                        />
                    </div>
                    <Button
                        type="submit"
                        :disabled="
                            feedbackForm.useful === null ||
                            feedbackForm.processing
                        "
                        data-test="send-feedback"
                    >
                        <Spinner v-if="feedbackForm.processing" />
                        Enviar avaliação
                    </Button>
                </form>
            </CardContent>
        </Card>

        <p class="text-xs text-muted-foreground">
            O FalaDev oferece prática e feedback com IA. Não é uma decisão de
            contratação nem uma avaliação infalível.
        </p>
    </div>
</template>
