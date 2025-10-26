<template>
    <section class="ds-surface p-6">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="ds-heading">{{ title }}</h3>
                <p v-if="subtitle" class="ds-subtitle">{{ subtitle }}</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-200">
                <i class="las la-sliders-h text-sm"></i>
                {{ badgeText }}
            </span>
        </header>
        <div class="ds-divider"></div>
        <div class="grid gap-6 md:grid-cols-3">
            <div class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ themeLabel }}</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ themeDescription }}</p>
                <div class="flex items-center gap-3">
                    <button
                        v-for="option in themeOptions"
                        :key="option.value"
                        class="ds-pill"
                        :class="option.value === internalTheme ? 'ds-pill-active' : 'ds-pill-muted'"
                        type="button"
                        @click="updateTheme(option.value)"
                    >
                        <i :class="option.icon" class="text-base"></i>
                        <span>{{ option.label }}</span>
                    </button>
                </div>
            </div>
            <div class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ languageLabel }}</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ languageDescription }}</p>
                <select
                    v-model="internalLanguage"
                    class="w-full rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                >
                    <option v-for="language in languages" :key="language.code" :value="language.code">
                        {{ language.label }}
                    </option>
                </select>
            </div>
            <div class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ notificationLabel }}</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ notificationDescription }}</p>
                <div class="flex flex-col gap-2">
                    <label v-for="channel in notificationChannels" :key="channel.key" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm dark:border-slate-700">
                        <div>
                            <p class="font-semibold text-slate-700 dark:text-slate-200">{{ channel.label }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ channel.caption }}</p>
                        </div>
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                            v-model="internalNotifications[channel.key]"
                        />
                    </label>
                </div>
            </div>
        </div>
        <div class="ds-divider"></div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ lastSavedCaption }}</p>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-full bg-primary-600 px-5 py-2 text-sm font-semibold text-white shadow-soft-xl transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                :disabled="isSaving"
                @click="savePreferences"
            >
                <span v-if="!isSaving">{{ saveButtonLabel }}</span>
                <span v-else class="flex items-center gap-2">
                    <i class="las la-spinner animate-spin"></i>
                    {{ savingLabel }}
                </span>
            </button>
        </div>
    </section>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { hydrateThemeFromPreference } from '../utils/theme';

const props = defineProps({
    title: { type: String, default: 'Experience Personalization' },
    subtitle: { type: String, default: null },
    badgeText: { type: String, default: 'Live preferences' },
    theme: { type: String, default: 'light' },
    language: { type: String, default: 'en' },
    languages: { type: Array, default: () => [] },
    notifications: { type: Object, default: () => ({ email: true, sms: false, push: true }) },
    saveEndpoint: { type: String, required: true },
    csrfToken: { type: String, required: true },
    labels: {
        type: Object,
        default: () => ({})
    },
});

const emit = defineEmits(['saved']);

const internalTheme = ref(props.theme);
const internalLanguage = ref(props.language);
const internalNotifications = ref({ ...props.notifications });
const lastSaved = ref(null);
const isSaving = ref(false);

hydrateThemeFromPreference(props.theme);

watch(
    () => props.theme,
    (theme) => {
        internalTheme.value = hydrateThemeFromPreference(theme);
    }
);

const themeOptions = [
    { value: 'light', label: props.labels.light ?? 'Light', icon: 'las la-sun' },
    { value: 'dark', label: props.labels.dark ?? 'Dark', icon: 'las la-moon' },
    { value: 'system', label: props.labels.system ?? 'System', icon: 'las la-desktop' },
];

const notificationChannels = computed(() => [
    {
        key: 'email',
        label: props.labels.email ?? 'Email alerts',
        caption: props.labels.emailCaption ?? 'Critical and marketing notifications.',
    },
    {
        key: 'sms',
        label: props.labels.sms ?? 'SMS alerts',
        caption: props.labels.smsCaption ?? 'Balance and security text messages.',
    },
    {
        key: 'push',
        label: props.labels.push ?? 'Push notifications',
        caption: props.labels.pushCaption ?? 'Instant updates inside the dashboard.',
    },
]);

const lastSavedCaption = computed(() => {
    if (!lastSaved.value) {
        return props.labels.neverSaved ?? 'Changes are applied instantly after saving.';
    }
    return `${props.labels.savedAt ?? 'Last synced'}: ${lastSaved.value.toLocaleTimeString()}`;
});

const themeLabel = computed(() => props.labels.themeLabel ?? 'Theme');
const themeDescription = computed(() => props.labels.themeDescription ?? 'Switch between light, dark or system-driven appearance.');
const languageLabel = computed(() => props.labels.languageLabel ?? 'Language');
const languageDescription = computed(() => props.labels.languageDescription ?? 'Choose your preferred experience language.');
const notificationLabel = computed(() => props.labels.notificationLabel ?? 'Notifications');
const notificationDescription = computed(() => props.labels.notificationDescription ?? 'Toggle where you receive important updates.');
const saveButtonLabel = computed(() => props.labels.save ?? 'Save changes');
const savingLabel = computed(() => props.labels.saving ?? 'Saving');

const updateTheme = (value) => {
    internalTheme.value = value;
    hydrateThemeFromPreference(value);
};

const savePreferences = async () => {
    isSaving.value = true;
    try {
        const response = await fetch(props.saveEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                theme: internalTheme.value,
                language: internalLanguage.value,
                notifications: internalNotifications.value,
            }),
        });
        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload.message || 'Unable to save preferences');
        }
        lastSaved.value = new Date();
        emit('saved', payload.preferences || {});
    } catch (error) {
        console.error(error);
    } finally {
        isSaving.value = false;
    }
};
</script>
