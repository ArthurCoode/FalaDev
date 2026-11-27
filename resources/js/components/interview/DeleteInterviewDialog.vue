<script setup lang="ts">
/**
 * Deletes an interview with everything tied to it (FalaDev.md, section 9),
 * after an explicit confirmation.
 */
import { Form } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { destroy } from '@/routes/interviews';

defineProps<{
    sessionId: number;
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button variant="outline" data-test="delete-interview">
                <Trash2 />
                Apagar entrevista
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Apagar esta entrevista?</DialogTitle>
                <DialogDescription>
                    As perguntas, suas respostas transcritas e o relatório serão
                    apagados permanentemente. Isso não pode ser desfeito e não
                    devolve a entrevista ao seu limite do mês.
                </DialogDescription>
            </DialogHeader>
            <Form v-bind="destroy.form(sessionId)" v-slot="{ processing }">
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="outline" type="button">
                            Cancelar
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                        data-test="confirm-delete-interview"
                    >
                        <Spinner v-if="processing" />
                        Apagar
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
