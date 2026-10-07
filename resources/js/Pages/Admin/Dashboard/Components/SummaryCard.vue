<template>
    <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-slate-200/90 shadow-xs dark:bg-slate-900 dark:border-slate-800 transition-all hover:shadow-md">
        <div class="flex items-center">
            <div class="p-2.5 sm:p-3 bg-indigo-50 rounded-xl dark:bg-indigo-950/60 shrink-0">
                <component
                    :is="iconComponent"
                    class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600 dark:text-indigo-400"
                />
            </div>
            <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                <h3 class="text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400 truncate">{{ title }}</h3>
                <p class="text-base sm:text-2xl font-extrabold text-slate-900 dark:text-slate-100 tabular-nums tracking-tight truncate mt-0.5">
                    {{ formattedValue }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    BanknotesIcon,
    ChartBarIcon,
    ArrowTrendingDownIcon,
    WalletIcon,
    ArchiveBoxIcon,
    CubeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    value: {
        type: Number,
        required: true
    },
    icon: {
        type: String,
        required: true
    },
    type: {
        type: String,
        default: 'currency'
    }
});

const iconComponent = computed(() => {
    const icons = {
        cash: BanknotesIcon,
        'trending-up': ChartBarIcon,
        'trending-down': ArrowTrendingDownIcon,
        wallet: WalletIcon,
        package: ArchiveBoxIcon,
        box: CubeIcon
    };
    return icons[props.icon];
});

const formattedValue = computed(() => {
    if (props.type === 'currency') {
        const formattedNumber = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(props.value);
        return `৳ ${formattedNumber}`;
    }
    return props.value;
});

</script>
