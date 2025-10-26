<template>
    <div class="flex flex-wrap items-center gap-3">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            class="ds-pill"
            :class="option.value === modelValue ? 'ds-pill-active' : 'ds-pill-muted'"
            @click="$emit('update:modelValue', option.value)"
        >
            <span>{{ option.label }}</span>
            <span v-if="option.count" class="rounded-full bg-white/80 px-2 py-0.5 text-xs font-semibold text-primary-600 dark:bg-slate-900/40 dark:text-primary-100">
                {{ option.count }}
            </span>
        </button>
    </div>
</template>

<script setup>
defineProps({
    options: { type: Array, default: () => [] },
    modelValue: { type: [String, Number], default: null },
});

defineEmits(['update:modelValue']);
</script>
