<!-- resources/js/Components/DateRangePicker.vue -->
<template>
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600 dark:text-slate-400 shrink-0">Start:</label>
            <input
                type="date"
                v-model="localStartDate"
                class="w-full sm:w-auto px-2.5 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-xl shadow-xs focus:ring-1 focus:ring-indigo-500"
                @change="emitUpdate"
            >
        </div>
        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600 dark:text-slate-400 shrink-0">End:</label>
            <input
                type="date"
                v-model="localEndDate"
                class="w-full sm:w-auto px-2.5 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-xl shadow-xs focus:ring-1 focus:ring-indigo-500"
                @change="emitUpdate"
            >
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    startDate: {
        type: Date,
        required: true
    },
    endDate: {
        type: Date,
        required: true
    }
});

const emit = defineEmits(['update:startDate', 'update:endDate', 'update:range']);

const localStartDate = ref(formatDate(props.startDate));
const localEndDate = ref(formatDate(props.endDate));

function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function emitUpdate() {
    emit('update:startDate', new Date(localStartDate.value));
    emit('update:endDate', new Date(localEndDate.value));
    emit('update:range');
}

watch(() => props.startDate, (newVal) => {
    localStartDate.value = formatDate(newVal);
});

watch(() => props.endDate, (newVal) => {
    localEndDate.value = formatDate(newVal);
});
</script>
