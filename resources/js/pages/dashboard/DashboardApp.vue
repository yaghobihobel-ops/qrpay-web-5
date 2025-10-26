<template>
    <div class="space-y-10">
        <section class="grid-responsive">
            <StatCard
                v-for="card in summary"
                :key="card.title"
                :title="card.title"
                :value="card.value"
                :currency="card.currency || currency"
                :locale="locale"
                :icon="card.icon"
                :trend="card.trend"
                :trend-direction="card.trendDirection"
                :subtitle="card.subtitle"
            />
        </section>

        <RealtimeInsight
            :title="analytics.title"
            :subtitle="analytics.subtitle"
            :insights="analytics.items"
        >
            <template #badge>
                <span class="inline-flex items-center gap-2 rounded-full bg-accent-100 px-3 py-1 text-xs font-semibold text-accent-600 dark:bg-accent-500/20 dark:text-accent-100">
                    <i class="las la-satellite-dish"></i>
                    {{ analytics.badge }}
                </span>
            </template>
        </RealtimeInsight>

        <section class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="ds-heading">{{ chart.title }}</h3>
                    <p class="ds-subtitle">{{ chart.subtitle }}</p>
                </div>
                <FilterGroup :options="chartOptions" v-model="activeChart" />
            </div>
            <ChartCard
                :title="selectedChart.title"
                :subtitle="selectedChart.subtitle"
                :categories="chart.categories"
                :series="selectedChart.series"
                :theme="activeTheme"
            >
                <template #actions>
                    <FilterGroup :options="currencyFilters" v-model="activeCurrency" />
                </template>
            </ChartCard>
        </section>

        <section class="ds-surface p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="ds-heading">{{ latestTransactions.title }}</h3>
                    <p class="ds-subtitle">{{ latestTransactions.subtitle }}</p>
                </div>
                <a :href="latestTransactions.cta.href" class="ds-pill ds-pill-active">
                    <span>{{ latestTransactions.cta.label }}</span>
                    <i class="las la-external-link-alt"></i>
                </a>
            </div>
            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-100 shadow-sm dark:border-slate-700">
                <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-slate-500 dark:text-slate-300">{{ latestTransactions.headers.reference }}</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-500 dark:text-slate-300">{{ latestTransactions.headers.type }}</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-500 dark:text-slate-300">{{ latestTransactions.headers.amount }}</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-500 dark:text-slate-300">{{ latestTransactions.headers.status }}</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-500 dark:text-slate-300">{{ latestTransactions.headers.date }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-900/40">
                        <tr v-for="transaction in filteredTransactions" :key="transaction.trx" class="hover:bg-primary-50/40 dark:hover:bg-primary-500/10">
                            <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ transaction.trx }}</td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-300">{{ transaction.title }}</td>
                            <td class="px-6 py-4 text-slate-700 dark:text-slate-100">{{ transaction.amount }}</td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold', transaction.badge.class]">
                                    <i :class="transaction.badge.icon"></i>
                                    {{ transaction.badge.label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-300">{{ transaction.date }}</td>
                        </tr>
                        <tr v-if="!filteredTransactions.length">
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500 dark:text-slate-300">{{ latestTransactions.empty }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <PersonalizationPanel
            :title="personalization.title"
            :subtitle="personalization.subtitle"
            :badge-text="personalization.badge"
            :theme="preferencesState.theme"
            :language="preferencesState.language"
            :languages="languages"
            :notifications="preferencesState.notifications"
            :save-endpoint="endpoints.preferences"
            :csrf-token="preferencesState.csrf"
            :labels="personalization.labels"
            @saved="handlePreferencesSaved"
        />
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { StatCard, ChartCard, FilterGroup, RealtimeInsight, PersonalizationPanel } from '../../design-system/vue/index.js';
import { formatCurrency } from '../../design-system/tokens.js';

const props = defineProps({
    summary: { type: Array, default: () => [] },
    analytics: { type: Object, default: () => ({}) },
    chart: { type: Object, default: () => ({ categories: [], seriesGroups: {} }) },
    transactions: { type: Array, default: () => [] },
    latestTransactions: { type: Object, default: () => ({}) },
    preferences: { type: Object, default: () => ({ theme: 'light', notifications: {} }) },
    personalization: { type: Object, default: () => ({}) },
    languages: { type: Array, default: () => [] },
    endpoints: { type: Object, default: () => ({}) },
    currency: { type: String, default: 'USD' },
    locale: { type: String, default: 'en-US' },
});

const activeCurrency = ref('all');
const chartKeys = Object.keys(props.chart.seriesGroups || {});
const activeChart = ref(chartKeys[0] || null);
const preferencesState = ref({ ...props.preferences });

const currencyFilters = computed(() => {
    const options = (props.chart.filters || []).map((item) => ({
        value: item.code,
        label: `${item.code} · ${formatCurrency(item.balance, item.code, props.locale)}`,
        count: item.transactions,
    }));
    return [{ value: 'all', label: props.chart.labels?.all || 'All currencies' }, ...options];
});

const chartOptions = computed(() =>
    chartKeys.map((key) => ({
        value: key,
        label: props.chart.seriesGroups[key]?.title || key,
    }))
);

const selectedChart = computed(() => {
    const base = props.chart.seriesGroups[activeChart.value] || { title: '', series: [] };
    if (activeCurrency.value === 'all') {
        return base;
    }
    const currencySeries = base.byCurrency?.[activeCurrency.value];
    if (!currencySeries) {
        return base;
    }
    return {
        ...base,
        series: currencySeries,
    };
});

const activeTheme = computed(() =>
    preferencesState.value.theme === 'system'
        ? window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light'
        : preferencesState.value.theme
);

const filteredTransactions = computed(() => {
    if (activeCurrency.value === 'all') {
        return props.transactions;
    }
    return props.transactions.filter((item) => item.currency === activeCurrency.value);
});

watch(
    () => props.preferences,
    () => {
        preferencesState.value = { ...props.preferences };
        if (activeChart.value === null && chartKeys.length) {
            activeChart.value = chartKeys[0];
        }
    },
    { deep: true }
);

const handlePreferencesSaved = (payload) => {
    preferencesState.value = {
        ...preferencesState.value,
        ...payload,
    };
};
</script>
