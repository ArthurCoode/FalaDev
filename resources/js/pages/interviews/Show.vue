<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Mic } from '@lucide/vue';
import { computed } from 'vue';
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
import { create } from '@/routes/interviews';
import type { InterviewSessionDetails } from '@/types';

const props = defineProps<{
    session: InterviewSessionDetails;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Início', href: dashboard() }],
    },
});

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
            description="Confira a configuração antes de começar."
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

        <Card class="border-dashed">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Mic class="size-5 text-voice" aria-hidden="true" />
                    Próximo passo: testar o microfone
                </CardTitle>
                <CardDescription>
                    Antes da primeira pergunta, você vai testar o microfone e
                    ouvir uma frase de exemplo. Essa parte ainda está em
                    desenvolvimento.
                </CardDescription>
            </CardHeader>
        </Card>
    </div>
</template>
