<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { CircleCheck, Mic, Play, Volume2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import MicrophoneCheck from '@/components/interview/MicrophoneCheck.vue';
import SampleAudioCheck from '@/components/interview/SampleAudioCheck.vue';
import type { SampleAudioState } from '@/components/interview/SampleAudioCheck.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { create, ready, sampleAudio } from '@/routes/interviews';
import type { InterviewSessionDetails } from '@/types';

const props = defineProps<{
    session: InterviewSessionDetails;
    samplePhrase: string;
    ttsIsFake: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Início', href: dashboard() }],
    },
});

const micPassed = ref(false);
const sampleState = ref<SampleAudioState>('idle');

// Audio is "checked" once played, or once it failed: the text is on screen,
// so a TTS problem must not block the interview (FalaDev.md, section 8).
const sampleChecked = computed(
    () => sampleState.value === 'played' || sampleState.value === 'failed',
);
const canConfirm = computed(() => micPassed.value && sampleChecked.value);

const createdAt = computed(() =>
    props.session.createdAt
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'long',
              timeStyle: 'short',
          }).format(new Date(props.session.createdAt))
        : '',
);

const details = computed(() => [
    { term: 'Categoria', value: props.session.category },
    { term: 'Nível', value: props.session.level },
    { term: 'Duração', value: props.session.duration },
    { term: 'Status', value: props.session.statusLabel },
    { term: 'Criada em', value: createdAt.value },
]);
</script>

<template>
    <Head :title="`Entrevista de ${session.category}`" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="`Entrevista de ${session.category}`"
            :description="
                session.status === 'draft'
                    ? 'Faça os testes de áudio antes de começar.'
                    : 'Resumo da sua sessão.'
            "
        >
            <template v-if="session.canEditSetup" #actions>
                <Button variant="outline" as-child>
                    <Link :href="create()">Alterar configuração</Link>
                </Button>
            </template>
        </PageHeader>

        <Card>
            <CardContent>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div v-for="item in details" :key="item.term">
                        <dt class="text-xs text-muted-foreground">
                            {{ item.term }}
                        </dt>
                        <dd class="mt-0.5 font-medium">{{ item.value }}</dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <!-- Pre-interview checks -->
        <template v-if="session.status === 'draft'">
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Mic class="size-5 text-voice" aria-hidden="true" />
                            1. Microfone
                        </CardTitle>
                        <CardDescription>
                            Grave uma frase curta e ouça como ficou.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <MicrophoneCheck v-model:passed="micPassed" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Volume2
                                class="size-5 text-voice"
                                aria-hidden="true"
                            />
                            2. Áudio do entrevistador
                        </CardTitle>
                        <CardDescription>
                            Confira se você ouve o entrevistador com clareza.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <SampleAudioCheck
                            v-model:state="sampleState"
                            :audio-url="sampleAudio.url(session.id)"
                            :phrase="samplePhrase"
                        />
                        <p
                            v-if="ttsIsFake"
                            class="rounded-md bg-muted px-3 py-2 text-xs text-muted-foreground"
                        >
                            Ambiente de desenvolvimento: o áudio é um sinal
                            sonoro, não a frase falada. A voz real entra quando
                            o serviço de voz for configurado.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Form
                v-bind="ready.form(session.id)"
                class="flex flex-col gap-2"
                v-slot="{ errors, processing }"
            >
                <InputError :message="errors.status" />
                <div class="flex flex-wrap items-center gap-3">
                    <Button
                        type="submit"
                        size="lg"
                        :disabled="!canConfirm || processing"
                        data-test="confirm-ready-button"
                    >
                        <Spinner v-if="processing" />
                        Tudo certo, estou pronto
                    </Button>
                    <p v-if="!canConfirm" class="text-sm text-muted-foreground">
                        <template v-if="!micPassed && !sampleChecked">
                            Faça os dois testes para continuar.
                        </template>
                        <template v-else-if="!micPassed">
                            Falta testar o microfone.
                        </template>
                        <template v-else>
                            Falta ouvir a frase de teste.
                        </template>
                    </p>
                </div>
            </Form>
        </template>

        <!-- Ready to start -->
        <Card v-else-if="session.status === 'ready'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <CircleCheck
                        class="size-5 text-success"
                        aria-hidden="true"
                    />
                    Tudo pronto para começar
                </CardTitle>
                <CardDescription>
                    Microfone e áudio conferidos. Quando começar, o
                    entrevistador se apresenta em inglês e faz a primeira
                    pergunta.
                </CardDescription>
            </CardHeader>
            <CardContent class="flex flex-col items-start gap-2">
                <Button size="lg" disabled>
                    <Play />
                    Iniciar entrevista
                </Button>
                <p class="text-xs text-muted-foreground">
                    A entrevista por turnos ainda está em desenvolvimento.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
