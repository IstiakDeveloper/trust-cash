<template>
    <div v-if="links.length > 3" class="flex items-center justify-between sm:justify-start flex-wrap gap-1">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="px-3 py-1.5 text-xs text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-900 cursor-not-allowed select-none"
                :class="{ 'hidden sm:block': !link.label.includes('Previous') && !link.label.includes('Next') && !link.label.includes('&laquo;') && !link.label.includes('&raquo;') }"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="px-3 py-1.5 text-xs font-semibold rounded-xl border transition-all select-none active:scale-95"
                :class="[
                    link.active
                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                        : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 bg-white dark:bg-slate-900',
                    { 'hidden sm:inline-flex': !link.active && !link.label.includes('Previous') && !link.label.includes('Next') && !link.label.includes('&laquo;') && !link.label.includes('&raquo;') }
                ]"
                preserve-scroll
                v-html="link.label"
            />
        </template>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true
    }
});
</script>
