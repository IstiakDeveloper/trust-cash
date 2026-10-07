<template>
    <div class="p-3.5 sm:p-5 bg-white rounded-2xl border border-slate-200/90 shadow-xs dark:bg-slate-900 dark:border-slate-800">
        <h3 class="mb-3 text-sm sm:text-base font-bold text-slate-800 dark:text-slate-200">Sales Overview</h3>
        <div class="h-56 sm:h-72 w-full">
            <LineChart
                :data="chartData"
                :options="chartOptions"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Line as LineChart } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

const props = defineProps({
    data: {
        type: Array,
        required: true
    }
});

const chartData = computed(() => ({
    labels: props.data.map(item => item.created_at),
    datasets: [
        {
            label: 'Sales',
            data: props.data.map(item => item.total),
            borderColor: '#2563EB',
            tension: 0.1
        }
    ]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false
};
</script>
