<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    height: { type: Number, default: 300 },
    readonly: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue']);
const content = ref(props.modelValue);
const editorRef = ref(null);
const isDark = ref(false);

// Toolbar state
const boldActive = ref(false);
const italicActive = ref(false);
const ulActive = ref(false);
const olActive = ref(false);

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    nextTick(() => {
        if (editorRef.value && !editorRef.value.innerHTML.trim()) editorRef.value.innerHTML = '<p><br></p>';
    });
});

// Sync v-model
watch(content, val => emit('update:modelValue', val));
watch(() => props.modelValue, val => {
    if (val !== content.value && editorRef.value?.innerHTML !== val) {
        content.value = val;
        editorRef.value.innerHTML = val || '<p><br></p>';
    }
});

// Execute command helper
const execCommand = (command) => {
    if (props.readonly || !editorRef.value) return;

    // Ensure at least one paragraph exists
    if (!editorRef.value.innerHTML.trim()) editorRef.value.innerHTML = '<p><br></p>';

    // Special handling for multiple lines for lists
    if (command === 'insertUnorderedList' || command === 'insertOrderedList') {
        const sel = window.getSelection();
        if (!sel.rangeCount) return;
        const range = sel.getRangeAt(0);
        const selectedText = range.cloneContents().textContent;
        const lines = selectedText.split('\n').filter(l => l.trim() !== '');
        if (lines.length) {
            let list = document.createElement(command === 'insertUnorderedList' ? 'ul' : 'ol');
            lines.forEach(l => {
                const li = document.createElement('li');
                li.textContent = l;
                list.appendChild(li);
            });
            range.deleteContents();
            range.insertNode(list);
        } else {
            document.execCommand(command, false);
        }
    } else {
        document.execCommand(command, false);
    }

    content.value = editorRef.value.innerHTML;
    updateToolbarState();
};

// Update toolbar button states
const updateToolbarState = () => {
    boldActive.value = document.queryCommandState('bold');
    italicActive.value = document.queryCommandState('italic');
    ulActive.value = document.queryCommandState('insertUnorderedList');
    olActive.value = document.queryCommandState('insertOrderedList');
};

// Listen selection change to update toolbar
document.addEventListener('selectionchange', () => {
    const sel = window.getSelection();
    if (!sel || !editorRef.value?.contains(sel.anchorNode)) return;
    updateToolbarState();
});

// Keyboard shortcuts
const handleKeydown = (e) => {
    if (e.ctrlKey || e.metaKey) {
        switch (e.key.toLowerCase()) {
            case 'b': e.preventDefault(); execCommand('bold'); break;
            case 'i': e.preventDefault(); execCommand('italic'); break;
            case 'u': e.preventDefault(); execCommand('insertUnorderedList'); break;
            case 'o': e.preventDefault(); execCommand('insertOrderedList'); break;
        }
    }
};
</script>

<template>
    <div class="w-full">
        <!-- Status & shortcuts -->
        <div class="flex justify-between mb-1 text-xs text-gray-500 dark:text-gray-400">
            <div>Mode: <span class="font-medium">{{ isDark ? 'Dark' : 'Light' }}</span></div>
            <div>Shortcut: Ctrl+B Bold | Ctrl+I Italic | Ctrl+U Bullet | Ctrl+O Numbered</div>
        </div>

        <!-- Toolbar -->
        <div class="flex gap-2 mb-2">
            <button @click="execCommand('bold')" :class="[
                'px-2 py-1 rounded hover:bg-gray-300 dark:hover:bg-gray-600',
                boldActive ? 'bg-indigo-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
            ]">B</button>
            <button @click="execCommand('italic')" :class="[
                'px-2 py-1 rounded hover:bg-gray-300 dark:hover:bg-gray-600',
                italicActive ? 'bg-indigo-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
            ]">I</button>
            <button @click="execCommand('insertUnorderedList')" :class="[
                'px-2 py-1 rounded hover:bg-gray-300 dark:hover:bg-gray-600',
                ulActive ? 'bg-indigo-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
            ]">• List</button>
            <button @click="execCommand('insertOrderedList')" :class="[
                'px-2 py-1 rounded hover:bg-gray-300 dark:hover:bg-gray-600',
                olActive ? 'bg-indigo-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
            ]">1. List</button>
        </div>

        <!-- Contenteditable -->
        <div ref="editorRef" contenteditable="true" :placeholder="placeholder" :class="[
            'w-full rounded-xl border p-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 overflow-y-auto overflow-x-hidden scrollbar-thin scrollbar-thumb-gray-400/50 scrollbar-track-transparent',
            isDark ? 'bg-black/20 text-gray-100 border-gray-600' : 'bg-white/90 text-gray-900 border-gray-300'
        ]" :style="{ minHeight: height + 'px' }" @input="content = editorRef.value.innerHTML" @keydown="handleKeydown"
            :readonly="props.readonly" v-html="content">
        </div>

        <!-- Preview -->
        <div class="mt-4 p-4 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/70 dark:bg-black/20 text-gray-900 dark:text-gray-100 prose max-w-full"
            v-html="content"></div>
    </div>
</template>

<style>
div[contenteditable]::-webkit-scrollbar {
    width: 6px;
}

div[contenteditable]::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 3px;
}

div[contenteditable]::-webkit-scrollbar-track {
    background: transparent;
}
</style>
