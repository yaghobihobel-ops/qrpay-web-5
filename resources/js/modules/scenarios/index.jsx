import React from 'react';
import { createRoot } from 'react-dom/client';
import ScenarioExplorer from './scenario-explorer';

document.querySelectorAll('[data-scenario-explorer]').forEach((node) => {
    const scenarioKey = node.dataset.scenario;

    if (!scenarioKey) {
        return;
    }

    const defaultLocale = node.dataset.defaultLocale || undefined;

    const labels = {
        heading: node.dataset.heading || '',
        stepsLabel: node.dataset.stepsLabel || 'Steps',
        complianceLabel: node.dataset.complianceLabel || 'Compliance',
        handoffLabel: node.dataset.handoffLabel || 'Hand-off payload',
        fallback: node.dataset.fallback || 'Scenario details are unavailable.',
    };

    const root = createRoot(node);
    root.render(
        <ScenarioExplorer
            scenarioKey={scenarioKey}
            defaultLocale={defaultLocale}
            labels={labels}
        />
    );
});
