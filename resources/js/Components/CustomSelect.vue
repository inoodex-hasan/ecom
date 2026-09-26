<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { ChevronDown, Check } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean, null],
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Select an option',
    },
    icon: {
        type: [Object, Function],
        default: null,
    },
    valueKey: {
        type: String,
        default: 'value',
    },
    labelKey: {
        type: String,
        default: 'label',
    },
    sublabelKey: {
        type: String,
        default: 'sublabel',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    triggerClass: {
        type: String,
        default: '',
    },
    menuClass: {
        type: String,
        default: '',
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const selectRef = ref(null);

// Normalize options to { value, label, sublabel, icon, raw }
const normalizedOptions = computed(() => {
    return props.options.map(opt => {
        if (typeof opt === 'object' && opt !== null) {
            return {
                value: opt[props.valueKey] !== undefined ? opt[props.valueKey] : opt.id !== undefined ? opt.id : opt.name || '',
                label: opt[props.labelKey] !== undefined ? opt[props.labelKey] : opt.name !== undefined ? opt.name : String(opt.value ?? ''),
                sublabel: opt[props.sublabelKey] || opt.description || null,
                icon: opt.icon || null,
                raw: opt,
            };
        }
        return {
            value: opt,
            label: String(opt),
            sublabel: null,
            icon: null,
            raw: opt,
        };
    });
});

const selectedOption = computed(() => {
    return normalizedOptions.value.find(opt => String(opt.value) === String(props.modelValue)) || null;
});

const displayLabel = computed(() => {
    if (selectedOption.value) {
        return selectedOption.value.label;
    }
    return props.placeholder;
});

function toggleDropdown() {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
}

function selectOption(opt) {
    if (props.disabled) return;
    emit('update:modelValue', opt.value);
    emit('change', opt.value);
    isOpen.value = false;
}

function closeDropdown(e) {
    if (selectRef.value && !selectRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

onMounted(() => {
    window.addEventListener('click', closeDropdown);
});

onBeforeUnmount(() => {
    window.removeEventListener('click', closeDropdown);
});
</script>

<template>
    <div ref="selectRef" class="relative inline-block w-full text-left select-none">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="toggleDropdown"
            :disabled="disabled"
            :class="[
                'w-full flex items-center justify-between gap-2.5 rounded-2xl text-xs transition-all cursor-pointer shadow-xs focus:outline-hidden focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed',
                compact ? 'px-3 py-2' : 'px-3.5 py-2.5',
                isOpen
                    ? 'bg-white dark:bg-slate-800 border border-indigo-500 ring-2 ring-indigo-500/20'
                    : 'bg-slate-50 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700/60 hover:border-slate-300 dark:hover:border-slate-600',
                triggerClass
            ]"
        >
            <div class="flex items-center gap-2 min-w-0 flex-1">
                <!-- Leading Icon -->
                <component
                    :is="selectedOption?.icon || icon"
                    v-if="selectedOption?.icon || icon"
                    class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0"
                />

                <span
                    :class="[
                        'truncate leading-tight',
                        selectedOption
                            ? 'font-bold text-slate-900 dark:text-white'
                            : 'font-normal text-slate-400 dark:text-slate-500'
                    ]"
                >
                    {{ displayLabel }}
                </span>

                <span
                    v-if="selectedOption?.sublabel"
                    class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 hidden sm:inline-block truncate"
                >
                    • {{ selectedOption.sublabel }}
                </span>
            </div>

            <ChevronDown
                :class="[
                    'w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0',
                    isOpen ? 'rotate-180 text-indigo-600 dark:text-indigo-400' : ''
                ]"
            />
        </button>

        <!-- Dropdown Popup Menu -->
        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                :class="[
                    'absolute left-0 right-0 mt-2 min-w-[200px] w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-1.5 z-50 space-y-1 max-h-60 overflow-y-auto focus:outline-hidden',
                    menuClass
                ]"
            >
                <button
                    v-for="(opt, idx) in normalizedOptions"
                    :key="idx"
                    type="button"
                    @click="selectOption(opt)"
                    :class="[
                        'w-full flex items-center justify-between p-2 rounded-xl text-xs text-left transition-colors cursor-pointer',
                        String(opt.value) === String(modelValue)
                            ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold'
                            : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'
                    ]"
                >
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <component
                            :is="opt.icon"
                            v-if="opt.icon"
                            :class="[
                                'w-4 h-4 shrink-0',
                                String(opt.value) === String(modelValue) ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400'
                            ]"
                        />
                        <div class="min-w-0">
                            <p class="truncate leading-tight">{{ opt.label }}</p>
                            <p v-if="opt.sublabel" class="text-[10px] text-slate-500 dark:text-slate-400 font-normal truncate">
                                {{ opt.sublabel }}
                            </p>
                        </div>
                    </div>

                    <Check
                        v-if="String(opt.value) === String(modelValue)"
                        class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0 ml-2"
                    />
                </button>
            </div>
        </transition>
    </div>
</template>
