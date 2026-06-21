<template>
    <div class="chart-card">
        <div class="chart-card__header">
            <div>
                <h3 class="chart-card__title">{{ title }}</h3>
                <p v-if="subtitle" class="chart-card__subtitle">{{ subtitle }}</p>
            </div>
            <slot name="actions" />
        </div>

        <apexchart
            type="bar"
            height="300"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    colors: { type: Array, default: () => ['#2563eb', '#38bdf8'] },
    stacked: { type: Boolean, default: false },
});

const chartOptions = computed(() => ({
    chart: {
        id: `bar-${props.title}`,
        toolbar: { show: false },
        stacked: props.stacked,
        fontFamily: 'inherit',
    },
    colors: props.colors,
    plotOptions: {
        bar: {
            borderRadius: 5,
            columnWidth: '55%',
            borderRadiusApplication: 'end',
        },
    },
    dataLabels: { enabled: false },
    grid: {
        borderColor: 'rgba(15, 23, 42, 0.08)',
        strokeDashArray: 4,
    },
    xaxis: {
        categories: props.categories,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: 'rgba(15, 23, 42, 0.6)' } },
    },
    yaxis: {
        labels: {
            style: { colors: 'rgba(15, 23, 42, 0.6)' },
            formatter: (value) => Math.round(value),
        },
    },
    tooltip: { theme: 'light' },
    legend: {
        show: true,
        position: 'top',
        horizontalAlign: 'left',
    },
}));
</script>

<style scoped>
.chart-card {
    display: grid;
    gap: 1rem;
    padding: 1.15rem 1.2rem;
    background: var(--rw-surface);
    border-radius: 1.1rem;
    border: 1px solid var(--rw-border);
}

.chart-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.chart-card__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
}

.chart-card__subtitle {
    margin: 0.35rem 0 0;
    color: var(--rw-muted);
    font-size: 0.85rem;
}
</style>
