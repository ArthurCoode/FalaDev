<script setup lang="ts">
/**
 * Code editor (CodeMirror 6) for the challenge languages. Each language's
 * grammar loads on demand. Tab indents; Esc then Tab leaves the editor, so
 * keyboard users are never trapped. Ctrl/Cmd+Enter emits "run". Colors come
 * from the .tok-* classes in resources/css/app.css, so the editor follows
 * the light and dark themes.
 */
import { closeBrackets, closeBracketsKeymap } from '@codemirror/autocomplete';
import {
    defaultKeymap,
    history,
    historyKeymap,
    indentWithTab,
} from '@codemirror/commands';
import {
    bracketMatching,
    indentOnInput,
    indentUnit,
    StreamLanguage,
    syntaxHighlighting,
} from '@codemirror/language';
import type { LanguageSupport } from '@codemirror/language';
import { Compartment, EditorState } from '@codemirror/state';
import type { Extension } from '@codemirror/state';
import {
    drawSelection,
    EditorView,
    highlightActiveLine,
    highlightActiveLineGutter,
    keymap,
    lineNumbers,
} from '@codemirror/view';
import { classHighlighter } from '@lezer/highlight';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { ChallengeLanguage } from '@/types';

const props = defineProps<{
    label: string;
    language: ChallengeLanguage;
}>();

const grammars: Record<
    ChallengeLanguage,
    () => Promise<LanguageSupport | Extension>
> = {
    javascript: () =>
        import('@codemirror/lang-javascript').then((m) => m.javascript()),
    typescript: () =>
        import('@codemirror/lang-javascript').then((m) =>
            m.javascript({ typescript: true }),
        ),
    python: () => import('@codemirror/lang-python').then((m) => m.python()),
    java: () => import('@codemirror/lang-java').then((m) => m.java()),
    csharp: () =>
        import('@codemirror/legacy-modes/mode/clike').then((m) =>
            StreamLanguage.define(m.csharp),
        ),
    go: () => import('@codemirror/lang-go').then((m) => m.go()),
    php: () => import('@codemirror/lang-php').then((m) => m.php()),
};

/** Each language's usual indentation: tabs in Go, 2 spaces in JS and TS. */
const indents: Record<ChallengeLanguage, string> = {
    javascript: '  ',
    typescript: '  ',
    python: '    ',
    java: '    ',
    csharp: '    ',
    go: '\t',
    php: '    ',
};

const grammar = new Compartment();
const indentation = new Compartment();

function indentFor(language: ChallengeLanguage): Extension {
    const unit = indents[language];

    return [
        indentUnit.of(unit),
        EditorState.tabSize.of(unit === '\t' ? 4 : unit.length),
    ];
}

async function loadGrammar(language: ChallengeLanguage): Promise<void> {
    const support = await grammars[language]();

    // The language may have changed while the grammar was loading.
    if (view && props.language === language) {
        view.dispatch({ effects: grammar.reconfigure(support) });
    }
}

const emit = defineEmits<{
    run: [];
}>();

const code = defineModel<string>({ required: true });

const host = ref<HTMLElement | null>(null);
let view: EditorView | null = null;

const theme = EditorView.theme({
    '&': {
        height: '100%',
        fontSize: '14px',
        backgroundColor: 'transparent',
        color: 'var(--foreground)',
    },
    '&.cm-focused': { outline: 'none' },
    '.cm-scroller': {
        fontFamily:
            "ui-monospace, SFMono-Regular, Menlo, Consolas, 'Liberation Mono', monospace",
        lineHeight: '1.6',
    },
    '.cm-content': { padding: '12px 0', caretColor: 'var(--primary)' },
    '.cm-gutters': {
        backgroundColor: 'transparent',
        color: 'var(--muted-foreground)',
        border: 'none',
    },
    '.cm-activeLine': {
        backgroundColor: 'color-mix(in srgb, var(--muted) 55%, transparent)',
    },
    '.cm-activeLineGutter': {
        backgroundColor: 'transparent',
        color: 'var(--foreground)',
    },
    '.cm-cursor': { borderLeftColor: 'var(--primary)', borderLeftWidth: '2px' },
    '&.cm-focused .cm-selectionBackground, .cm-selectionBackground, ::selection':
        {
            backgroundColor:
                'color-mix(in srgb, var(--primary) 30%, transparent) !important',
        },
    '.cm-matchingBracket': {
        backgroundColor: 'color-mix(in srgb, var(--primary) 25%, transparent)',
        outline: 'none',
    },
});

onMounted(() => {
    if (!host.value) {
        return;
    }

    view = new EditorView({
        parent: host.value,
        state: EditorState.create({
            doc: code.value,
            extensions: [
                lineNumbers(),
                highlightActiveLineGutter(),
                highlightActiveLine(),
                history(),
                drawSelection(),
                indentOnInput(),
                bracketMatching(),
                closeBrackets(),
                grammar.of([]),
                syntaxHighlighting(classHighlighter),
                indentation.of(indentFor(props.language)),
                keymap.of([
                    {
                        key: 'Mod-Enter',
                        run: () => {
                            emit('run');

                            return true;
                        },
                    },
                    ...closeBracketsKeymap,
                    ...defaultKeymap,
                    ...historyKeymap,
                    indentWithTab,
                ]),
                EditorView.contentAttributes.of({
                    'aria-label': props.label,
                    spellcheck: 'false',
                    autocapitalize: 'off',
                    autocorrect: 'off',
                }),
                EditorView.updateListener.of((update) => {
                    if (update.docChanged) {
                        code.value = update.state.doc.toString();
                    }
                }),
                theme,
            ],
        }),
    });

    void loadGrammar(props.language);
});

watch(
    () => props.language,
    (language) => {
        view?.dispatch({
            effects: indentation.reconfigure(indentFor(language)),
        });
        void loadGrammar(language);
    },
);

// Outside changes (restoring the starter code) replace the document.
watch(code, (value) => {
    if (view && value !== view.state.doc.toString()) {
        view.dispatch({
            changes: { from: 0, to: view.state.doc.length, insert: value },
        });
    }
});

onBeforeUnmount(() => {
    view?.destroy();
    view = null;
});

defineExpose({ focus: () => view?.focus() });
</script>

<template>
    <div ref="host" class="h-full min-h-0" data-test="code-editor" />
</template>
