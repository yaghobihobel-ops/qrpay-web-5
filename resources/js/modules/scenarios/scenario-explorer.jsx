import React, { useMemo, useState } from 'react';
import { getScenarioConfig } from './scenario-data';

function ScenarioExplorer({ scenarioKey, defaultLocale, labels }) {
    const config = getScenarioConfig(scenarioKey);

    const locales = useMemo(() => {
        if (!config) {
            return [];
        }

        return Object.entries(config.locales || {}).map(([code, details]) => ({
            code,
            ...details,
        }));
    }, [config]);

    const initialLocale = useMemo(() => {
        if (!locales.length) {
            return defaultLocale || 'en';
        }

        if (defaultLocale && locales.find((entry) => entry.code === defaultLocale)) {
            return defaultLocale;
        }

        if (config?.defaultLocale && locales.find((entry) => entry.code === config.defaultLocale)) {
            return config.defaultLocale;
        }

        return locales[0].code;
    }, [defaultLocale, locales, config]);

    const [locale, setLocale] = useState(initialLocale);

    const activeLocale = useMemo(() => {
        if (!locales.length) {
            return null;
        }

        return locales.find((entry) => entry.code === locale) || locales[0];
    }, [locale, locales]);

    if (!config || !activeLocale) {
        return (
            <div className="scenario-explorer-card">
                {labels?.fallback || 'Scenario details are unavailable.'}
            </div>
        );
    }

    return (
        <div
            className="scenario-explorer-card"
            dir={activeLocale.direction || 'ltr'}
            data-active-locale={locale}
        >
            <div className="scenario-explorer-header">
                <h3 className="scenario-explorer-title">{labels?.heading || config.title || ''}</h3>
                <div className="scenario-explorer-locales">
                    {locales.map((entry) => (
                        <button
                            key={entry.code}
                            type="button"
                            className={entry.code === locale ? 'is-active' : ''}
                            onClick={() => setLocale(entry.code)}
                        >
                            {entry.label}
                        </button>
                    ))}
                </div>
            </div>
            <p className="scenario-explorer-summary">{activeLocale.summary}</p>
            <div className="scenario-explorer-body">
                <div>
                    <span className="scenario-meta-label">{labels?.stepsLabel}</span>
                    <ol className="scenario-steps">
                        {activeLocale.steps?.map((step, index) => (
                            <li key={index}>{step}</li>
                        ))}
                    </ol>
                </div>
                <div className="scenario-meta">
                    <span className="scenario-meta-label">{labels?.complianceLabel}</span>
                    <div className="scenario-compliance">
                        {activeLocale.compliance?.title && (
                            <strong>{activeLocale.compliance.title}</strong>
                        )}
                        <div>{activeLocale.compliance?.copy}</div>
                    </div>
                </div>
                <div className="scenario-meta">
                    <span className="scenario-meta-label">{labels?.handoffLabel}</span>
                    <div className="scenario-handoff">
                        <code>{activeLocale.handoff}</code>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default ScenarioExplorer;
