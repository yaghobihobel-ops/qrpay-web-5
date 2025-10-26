<template>
    <div class="ds-chart-card">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="ds-heading">{{ title }}</h3>
                <p v-if="subtitle" class="ds-subtitle">{{ subtitle }}</p>
            </div>
            <slot name="actions"></slot>
        </div>
        <div ref="chartRef" class="mt-6 h-72 w-full"></div>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import ApexCharts from 'apexcharts';

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    theme: { type: String, default: 'light' },
});

const chartRef = ref(null);
let chartInstance = null;

const renderChart = () => {
    if (!chartRef.value) return;
    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new ApexCharts(chartRef.value, {
        chart: {
            type: 'area',
            toolbar: { show: false },
            background: 'transparent',
        },
        theme: {
            mode: props.theme,
        },
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: 3,
        },
        grid: {
            borderColor: props.theme === 'dark' ? 'rgba(148, 163, 184, 0.2)' : 'rgba(148, 163, 184, 0.4)',
            strokeDashArray: 6,
        },
        xaxis: {
            categories: props.categories,
            labels: { style: { colors: props.theme === 'dark' ? '#cbd5f5' : '#475569' } },
        },
        yaxis: {
            labels: { style: { colors: props.theme === 'dark' ? '#cbd5f5' : '#475569' } },
        },
        tooltip: {
            theme: props.theme,
        },
        series: props.series,
        colors: ['#4f46e5', '#06b6d4', '#f59e0b', '#ef4444'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 0.4,
                opacityFrom: 0.65,
                opacityTo: 0.15,
                stops: [0, 90, 100],
            },
        },
    });

    chartInstance.render();
};

onMounted(renderChart);

watch(
    () => [props.categories, props.series, props.theme],
    () => renderChart(),
    { deep: true }
);

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
});
</script>
