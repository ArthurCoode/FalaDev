<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Brain, Code, Network, ShieldCheck, Users } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import OptionCardGroup from '@/components/OptionCardGroup.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { create, store } from '@/routes/interviews';
import type {
    InterviewSetupDraft,
    InterviewSetupOptions,
    UsageSummary,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Início', href: dashboard() },
            { title: 'Nova entrevista', href: create() },
        ],
    },
});

defineProps<{
    options: InterviewSetupOptions;
    draft: InterviewSetupDraft | null;
    usage: UsageSummary;
}>();

const categoryIcons = {
    algorithms: Code,
    system_design: Network,
    behavioral: Users,
};
</script>

<template>
    <Head title="Nova entrevista" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Nova entrevista"
            description="Configure sua sessão. A entrevista é conduzida em inglês; esta tela e o relatório ficam em português."
        />

        <div
            v-if="usage.remaining === 0"
            role="alert"
            class="rounded-xl border border-warning/40 bg-warning-soft p-4 text-sm"
        >
            <p class="font-medium">Você atingiu o limite deste mês.</p>
            <p class="mt-1 text-muted-foreground">
                Você já usou {{ usage.used }} de {{ usage.limit }} entrevistas.
                Seu histórico continua disponível no início.
            </p>
        </div>

        <Form
            v-bind="store.form()"
            class="flex flex-col gap-8"
            v-slot="{ errors, processing, clearErrors }"
        >
            <OptionCardGroup
                name="category"
                legend="Categoria"
                :options="options.categories"
                :default-value="draft?.category"
                :error="errors.category"
                :icons="categoryIcons"
                @change="clearErrors('category')"
            />

            <OptionCardGroup
                name="level"
                legend="Nível"
                :options="options.levels"
                :default-value="draft?.level"
                :error="errors.level"
                @change="clearErrors('level')"
            />

            <OptionCardGroup
                name="duration_minutes"
                legend="Duração"
                :options="options.durations"
                :default-value="draft?.duration_minutes"
                :error="errors.duration_minutes"
                :columns="2"
                @change="clearErrors('duration_minutes')"
            />

            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Brain
                                class="size-5 text-primary"
                                aria-hidden="true"
                            />
                            Antes de começar
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul
                            class="list-disc space-y-1.5 pl-5 text-sm text-muted-foreground"
                        >
                            <li>Responda em inglês, falando em voz alta.</li>
                            <li>
                                Explique seu raciocínio, não apenas a resposta
                                final.
                            </li>
                            <li>
                                Pode pedir para repetir a pergunta (“Could you
                                repeat the question?”) sem perder pontos.
                            </li>
                            <li>
                                A gravação só começa quando você clicar para
                                gravar, e você revisa o texto antes de enviar.
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <ShieldCheck
                                class="size-5 text-primary"
                                aria-hidden="true"
                            />
                            Seus dados
                        </CardTitle>
                        <CardDescription>
                            O que acontece com a sua voz e as suas respostas.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <ul
                            class="list-disc space-y-1.5 pl-5 text-sm text-muted-foreground"
                        >
                            <li>
                                O microfone só é usado enquanto você grava uma
                                resposta.
                            </li>
                            <li>
                                O áudio é enviado a um serviço de transcrição
                                para virar texto.
                            </li>
                            <li>
                                O texto das respostas é enviado ao modelo de IA
                                que conduz e avalia a entrevista.
                            </li>
                            <li>
                                Guardamos a transcrição e o relatório no seu
                                histórico. O áudio não é guardado.
                            </li>
                        </ul>

                        <div class="space-y-2">
                            <Label
                                for="data_notice_accepted"
                                class="flex items-start gap-3 font-normal"
                            >
                                <Checkbox
                                    id="data_notice_accepted"
                                    name="data_notice_accepted"
                                    class="mt-0.5"
                                    @update:model-value="
                                        clearErrors('data_notice_accepted')
                                    "
                                />
                                <span
                                    >Li e entendi como meus dados são
                                    usados.</span
                                >
                            </Label>
                            <InputError
                                :message="errors.data_notice_accepted"
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <InputError :message="errors.usage" />

            <div class="flex flex-wrap items-center gap-3">
                <Button
                    type="submit"
                    size="lg"
                    :disabled="processing || usage.remaining === 0"
                    data-test="save-interview-setup-button"
                >
                    <Spinner v-if="processing" />
                    Salvar e continuar
                </Button>
                <Button variant="ghost" size="lg" as-child>
                    <Link :href="dashboard()">Cancelar</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
