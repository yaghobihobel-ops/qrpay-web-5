<template>
    <div class="ds-stat-card">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="ds-card-title">{{ title }}</p>
                <p class="ds-card-value">{{ formattedValue }}</p>
            </div>
            <slot name="icon">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-primary-600 dark:bg-primary-500/20 dark:text-primary-100">
                    <i :class="icon" class="text-xl"></i>
                </div>
            </slot>
        </div>
        <div class="ds-card-trend" :class="trendClass" v-if="trend">
            <i :class="trendIcon" class="text-sm"></i>
            <span>{{ trend }}</span>
        </div>
        <p v-if="subtitle" class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatCurrency } from '../../design-system/tokens';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [Number, String], default: null },
    icon: { type: String, default: 'las la-wallet' },
    currency: { type: String, default: 'USD' },
    locale: { type: String, default: 'en-US' },
    trend: { type: String, default: null },
    trendDirection: { type: String, default: 'up' },
    subtitle: { type: String, default: null },
});

const formattedValue = computed(() => formatCurrency(props.value, props.currency, props.locale));

const trendClass = computed(() => (props.trendDirection === 'down' ? 'text-danger' : 'text-success'));

const trendIcon = computed(() => (props.trendDirection === 'down' ? 'las la-arrow-down' : 'las la-arrow-up'));
</script>
