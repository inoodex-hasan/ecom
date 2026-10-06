<script setup>
import { watch, onBeforeUnmount } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import {
    Bold,
    Italic,
    Strikethrough,
    Heading2,
    Heading3,
    List,
    ListOrdered,
    Quote,
    Minus,
    Code,
    Link as LinkIcon,
    Unlink,
    Undo2,
    Redo2,
    RemoveFormatting
} from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Write something compelling...',
    },
    minHeight: {
        type: String,
        default: '260px',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue || '',
    editable: !props.disabled,
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [2, 3],
            },
        }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-indigo-600 dark:text-indigo-400 underline underline-offset-2 hover:text-indigo-500 font-semibold',
                target: '_blank',
                rel: 'noopener noreferrer',
            },
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
            emptyEditorClass: 'is-editor-empty',
        }),
    ],
    editorProps: {
        attributes: {
            class: 'focus:outline-none min-h-[200px] text-xs sm:text-sm text-slate-800 dark:text-slate-100 p-4 leading-relaxed',
        },
    },
    onUpdate: ({ editor }) => {
        const html = editor.isEmpty ? '' : editor.getHTML();
        emit('update:modelValue', html);
    },
});

watch(
    () => props.modelValue,
    (newVal) => {
        if (!editor.value) return;
        const currentHtml = editor.value.isEmpty ? '' : editor.value.getHTML();
        if ((newVal || '') !== currentHtml) {
            editor.value.commands.setContent(newVal || '', false);
        }
    }
);

watch(
    () => props.disabled,
    (val) => {
        if (editor.value) {
            editor.value.setEditable(!val);
        }
    }
);

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
    }
});

function setLink() {
    if (!editor.value) return;
    const previousUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('Enter destination URL (https://...):', previousUrl || 'https://');

    if (url === null) {
        return; // cancelled
    }

    if (url.trim() === '' || url === 'https://') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
}
</script>

<template>
    <div class="rich-text-editor rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent transition-all">
        <!-- Toolbar -->
        <div
            v-if="editor && !disabled"
            class="flex flex-wrap items-center gap-1 p-2 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/80"
        >
            <!-- Headings -->
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="[
                    editor.isActive('heading', { level: 2 })
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'px-2 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-1 cursor-pointer'
                ]"
                title="Heading 2"
            >
                <Heading2 class="w-4 h-4" />
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                :class="[
                    editor.isActive('heading', { level: 3 })
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'px-2 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-1 cursor-pointer'
                ]"
                title="Heading 3"
            >
                <Heading3 class="w-4 h-4" />
            </button>

            <div class="w-px h-5 bg-slate-200 dark:bg-slate-700 mx-1" />

            <!-- Inline Styles -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :class="[
                    editor.isActive('bold')
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'p-1.5 rounded-lg transition-colors cursor-pointer'
                ]"
                title="Bold (Ctrl+B)"
            >
                <Bold class="w-4 h-4" />
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :class="[
                    editor.isActive('italic')
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'p-1.5 rounded-lg transition-colors cursor-pointer'
                ]"
                title="Italic (Ctrl+I)"
            >
                <Italic class="w-4 h-4" />
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleStrike().run()"
                :class="[
                    editor.isActive('strike')
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'p-1.5 rounded-lg transition-colors cursor-pointer'
                ]"
                title="Strikethrough"
            >
                <Strikethrough class="w-4 h-4" />
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleCode().run()"
                :class="[
                    editor.isActive('code')
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'p-1.5 rounded-lg transition-colors cursor-pointer'
                ]"
                title="Inline Code"
            >
                <Code class="w-4 h-4" />
            </button>

            <div class="w-px h-5 bg-slate-200 dark:bg-slate-700 mx-1" />

            <!-- Lists -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="[
                    editor.isActive('bulletList')
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'p-1.5 rounded-lg transition-colors cursor-pointer'
                ]"
                title="Bullet List"
            >
                <List class="w-4 h-4" />
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="[
                    editor.isActive('orderedList')
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'p-1.5 rounded-lg transition-colors cursor-pointer'
                ]"
                title="Numbered List"
            >
                <ListOrdered class="w-4 h-4" />
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleBlockquote().run()"
                :class="[
                    editor.isActive('blockquote')
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'p-1.5 rounded-lg transition-colors cursor-pointer'
                ]"
                title="Blockquote"
            >
                <Quote class="w-4 h-4" />
            </button>

            <button
                type="button"
                @click="editor.chain().focus().setHorizontalRule().run()"
                class="p-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                title="Horizontal Divider"
            >
                <Minus class="w-4 h-4" />
            </button>

            <div class="w-px h-5 bg-slate-200 dark:bg-slate-700 mx-1" />

            <!-- Links -->
            <button
                type="button"
                @click="setLink"
                :class="[
                    editor.isActive('link')
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'p-1.5 rounded-lg transition-colors cursor-pointer'
                ]"
                title="Insert / Edit Link"
            >
                <LinkIcon class="w-4 h-4" />
            </button>

            <button
                v-if="editor.isActive('link')"
                type="button"
                @click="editor.chain().focus().unsetLink().run()"
                class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                title="Remove Link"
            >
                <Unlink class="w-4 h-4" />
            </button>

            <button
                type="button"
                @click="editor.chain().focus().unsetAllMarks().clearNodes().run()"
                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                title="Clear Formatting"
            >
                <RemoveFormatting class="w-4 h-4" />
            </button>

            <div class="flex-1" />

            <!-- Undo / Redo -->
            <button
                type="button"
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().undo()"
                class="p-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:pointer-events-none transition-colors cursor-pointer"
                title="Undo (Ctrl+Z)"
            >
                <Undo2 class="w-4 h-4" />
            </button>

            <button
                type="button"
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().redo()"
                class="p-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:pointer-events-none transition-colors cursor-pointer"
                title="Redo (Ctrl+Y)"
            >
                <Redo2 class="w-4 h-4" />
            </button>
        </div>

        <!-- Editable Area -->
        <div :style="{ minHeight: minHeight }" class="overflow-y-auto max-h-[460px] bg-slate-50/50 dark:bg-slate-800/40">
            <EditorContent :editor="editor" class="tiptap-content-container" />
        </div>
    </div>
</template>

<style>
/* Tiptap Editor Content Typography */
.tiptap-content-container .tiptap {
    outline: none !important;
}

.tiptap-content-container .tiptap.is-editor-empty:first-child::before {
    color: #94a3b8;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

.tiptap-content-container .tiptap h2 {
    font-size: 1.25rem;
    font-weight: 800;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
    color: inherit;
    line-height: 1.3;
}

.tiptap-content-container .tiptap h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin-top: 1rem;
    margin-bottom: 0.35rem;
    color: inherit;
    line-height: 1.35;
}

.tiptap-content-container .tiptap p {
    margin-top: 0.4rem;
    margin-bottom: 0.4rem;
    line-height: 1.6;
}

.tiptap-content-container .tiptap ul {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

.tiptap-content-container .tiptap ol {
    list-style-type: decimal;
    padding-left: 1.5rem;
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

.tiptap-content-container .tiptap li {
    margin-top: 0.2rem;
    margin-bottom: 0.2rem;
}

.tiptap-content-container .tiptap blockquote {
    border-left: 3px solid #6366f1;
    padding-left: 1rem;
    margin-top: 0.75rem;
    margin-bottom: 0.75rem;
    font-style: italic;
    opacity: 0.9;
}

.tiptap-content-container .tiptap code {
    background-color: rgba(99, 102, 241, 0.1);
    color: #6366f1;
    padding: 0.15rem 0.35rem;
    border-radius: 0.375rem;
    font-family: monospace;
    font-size: 0.85em;
}

.tiptap-content-container .tiptap hr {
    border: none;
    border-top: 1px solid rgba(148, 163, 184, 0.3);
    margin: 1.25rem 0;
}
</style>
