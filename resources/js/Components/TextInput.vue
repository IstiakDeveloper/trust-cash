<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number], // Accept both String and Number
        default: ''
    },
    type: {
        type: String,
        default: 'text'
    },
    placeholder: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    readonly: {
        type: Boolean,
        default: false
    },
    required: {
        type: Boolean,
        default: false
    },
    min: {
        type: [String, Number],
        default: null
    },
    max: {
        type: [String, Number],
        default: null
    },
    step: {
        type: [String, Number],
        default: null
    },
    inputmode: {
        type: String,
        default: null
    },
    class: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

// Convert modelValue to string for input display
const inputValue = computed({
    get() {
        return String(props.modelValue || '');
    },
    set(value) {
        emit('update:modelValue', value);
    }
});

// Base classes for the input: text-base on mobile prevents iOS safari auto-zoom, sm:text-xs on desktop
const baseClasses = 'block w-full min-h-[42px] px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-base sm:text-xs transition';

// Combine base classes with custom classes
const inputClasses = computed(() => {
    return `${baseClasses} ${props.class}`.trim();
});
</script>

<template>
    <input
        :type="type"
        :inputmode="inputmode || (type === 'number' ? 'decimal' : (type === 'tel' ? 'tel' : null))"
        :value="inputValue"
        @input="inputValue = $event.target.value"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :min="min"
        :max="max"
        :step="step"
        :class="inputClasses"
    />
</template>

<style scoped>
/* Add any additional styling if needed */
input:focus {
    outline: none;
}

input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

input:readonly {
    background-color: #f9fafb;
}
</style>
