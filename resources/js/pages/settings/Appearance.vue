<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { BookOpen, LoaderCircle } from '@lucide/vue';
import { computed } from 'vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { tutorial } from '@/routes';
import { edit } from '@/routes/appearance';
import { restore } from '@/routes/tutorial';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Aparência',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const tutorialDismissed = computed(() =>
    Boolean(page.props.auth.user.tutorial_dismissed_at),
);
</script>

<template>
    <Head title="Aparência" />

    <h1 class="sr-only">Aparência</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Aparência"
            description="Escolha o tema da interface"
        />
        <AppearanceTabs />
    </div>

    <div class="space-y-4">
        <Heading
            variant="small"
            title="Tutorial"
            :description="
                tutorialDismissed
                    ? 'Você escolheu não exibir o tutorial. Ele pode voltar para o menu.'
                    : 'O tutorial aparece no menu, em Geral.'
            "
        />
        <Form
            v-if="tutorialDismissed"
            v-bind="restore.form()"
            v-slot="{ processing }"
        >
            <Button
                type="submit"
                variant="outline"
                :disabled="processing"
                data-test="settings-tutorial-restore"
            >
                <LoaderCircle v-if="processing" class="animate-spin" />
                <BookOpen v-else />
                Mostrar o tutorial no menu
            </Button>
        </Form>
        <Button v-else variant="outline" as-child>
            <Link :href="tutorial()">
                <BookOpen />
                Abrir o tutorial
            </Link>
        </Button>
    </div>
</template>
