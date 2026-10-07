<template>
    <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-slate-200/90 shadow-xs dark:bg-slate-900 dark:border-slate-800">
        <h3 class="mb-3 text-sm sm:text-base font-bold text-slate-800 dark:text-slate-200">Stock Status</h3>
        <div class="h-56 sm:h-72 w-full">
            <BarChart
                :data="chartData"
                :options="chartOptions"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Bar as BarChart } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
    data: {
        type: Array,
        required: true
    }
});

const chartData = computed(() => ({
    labels: props.data.map(item => item.product.name),
    datasets: [
        {
            label: 'Stock Quantity',
            data: props.data.map(item => item.total_quantity),
            backgroundColor: '#3B82F6'
        }
    ]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false
};
</script>
