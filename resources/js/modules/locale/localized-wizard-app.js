import { createApp, reactive, computed } from 'vue';
import { getLocalesForContext, getDefaultLocaleForContext } from './locales';

const nodes = document.querySelectorAll('[data-localized-wizard]');

nodes.forEach((node) => {
    const context = node.dataset.context || 'default';
    const providedDefault = node.dataset.defaultLocale || '';
    const heading = node.dataset.heading || '';
    const description = node.dataset.description || '';

    const locales = getLocalesForContext(context);

    if (!locales.length) {
        return;
    }

    const fallbackLocale = getDefaultLocaleForContext(context);
    const initialLocale = locales.some((locale) => locale.code === providedDefault)
        ? providedDefault
        : (fallbackLocale && locales.some((locale) => locale.code === fallbackLocale)
            ? fallbackLocale
            : locales[0].code);

    const app = createApp({
        setup() {
            const state = reactive({
                locale: initialLocale,
            });

            const activeLocale = computed(() => {
                return locales.find((locale) => locale.code === state.locale) || locales[0];
            });

            function changeLocale(code) {
                const target = locales.find((locale) => locale.code === code);
                if (target) {
                    state.locale = target.code;
                }
            }

            return {
                heading,
                description,
                locales,
                state,
                activeLocale,
                changeLocale,
            };
        },
        template: `
            <section
                class="localized-wizard"
                :dir="activeLocale.direction || 'ltr'"
                :data-active-locale="state.locale"
            >
                <header class="localized-wizard__header">
                    <h3 class="localized-wizard__title">{{ heading }}</h3>
                    <div class="localized-wizard__tabs">
                        <button
                            v-for="locale in locales"
                            :key="locale.code"
                            type="button"
                            class="localized-wizard__tab"
                            :class="{ 'is-active': locale.code === state.locale }"
                            @click="changeLocale(locale.code)"
                        >
                            {{ locale.label }}
                        </button>
                    </div>
                </header>
                <p v-if="description" class="localized-wizard__summary">{{ description }}</p>
                <p v-else-if="activeLocale.summary" class="localized-wizard__summary">{{ activeLocale.summary }}</p>
                <div class="localized-wizard__content">
                    <div class="localized-wizard__formats">
                        <div class="localized-wizard__format">
                            <span class="localized-wizard__format-label">{{ activeLocale.labels.date }}</span>
                            <span class="localized-wizard__format-value">{{ activeLocale.formats.date }}</span>
                        </div>
                        <div class="localized-wizard__format">
                            <span class="localized-wizard__format-label">{{ activeLocale.labels.amount }}</span>
                            <span class="localized-wizard__format-value">{{ activeLocale.formats.amount }}</span>
                        </div>
                        <div
                            class="localized-wizard__format"
                            v-if="activeLocale.formats.reference"
                        >
                            <span class="localized-wizard__format-label">{{ activeLocale.labels.reference }}</span>
                            <span class="localized-wizard__format-value">{{ activeLocale.formats.reference }}</span>
                        </div>
                    </div>
                    <ul class="localized-wizard__instructions">
                        <li v-for="(instruction, index) in activeLocale.instructions" :key="index">
                            {{ instruction }}
                        </li>
                    </ul>
                    <p v-if="activeLocale.regulation" class="localized-wizard__regulation">{{ activeLocale.regulation }}</p>
                </div>
            </section>
        `,
    });

    app.mount(node);
});
