<template>
    <section class="ds-surface p-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="ds-heading">{{ title }}</h3>
                <p v-if="subtitle" class="ds-subtitle">{{ subtitle }}</p>
            </div>
            <slot name="badge"></slot>
        </header>
        <div class="mt-6 grid gap-6 md:grid-cols-4">
            <article
                v-for="item in insights"
                :key="item.key"
                class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 transition hover:border-primary-200 hover:bg-white dark:border-slate-700 dark:bg-slate-900/60 dark:hover:border-primary-500/40"
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ item.label }}</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ item.value }}</p>
                <p v-if="item.caption" class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ item.caption }}</p>
            </article>
        </div>
    </section>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    insights: { type: Array, default: () => [] },
});
</script>
