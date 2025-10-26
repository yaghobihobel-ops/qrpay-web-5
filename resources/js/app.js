import './bootstrap';
import '../css/app.css';

import { createApp } from 'vue';
import DashboardApp from './pages/dashboard/DashboardApp.vue';
import { hydrateThemeFromPreference } from './design-system/utils/theme';

import React from 'react';
import { createRoot } from 'react-dom/client';
import FlowWizardApp from './design-system/react/FlowWizardApp.jsx';

const bootDashboard = () => {
    const dashboardEl = document.getElementById('user-dashboard-app');
    if (!dashboardEl) {
        return;
    }

    try {
        const props = JSON.parse(dashboardEl.dataset.dashboard ?? '{}');
        if (props?.preferences?.theme) {
            hydrateThemeFromPreference(props.preferences.theme);
        }
        const app = createApp(DashboardApp, props);
        app.mount(dashboardEl);
    } catch (error) {
        console.error('Unable to bootstrap dashboard', error);
    }
};

const bootFlowWizards = () => {
    const wizardNodes = document.querySelectorAll('[data-flow-wizard]');
    wizardNodes.forEach((node) => {
        try {
            const config = JSON.parse(node.getAttribute('data-flow-wizard') ?? '{}');
            if (!config.formId) {
                const form = node.querySelector('form');
                if (form?.id) {
                    config.formId = form.id;
                }
            }
            const root = createRoot(node);
            root.render(React.createElement(FlowWizardApp, config));
        } catch (error) {
            console.error('Unable to bootstrap flow wizard', error);
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    bootDashboard();
    bootFlowWizards();
});
