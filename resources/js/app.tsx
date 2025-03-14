import '../css/app.css';
// import './echo';

import Layout from '@/layouts/base-layout';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import React from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { I18nextProvider } from 'react-i18next';
import { initI18n, setLocale } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Fadogen';

interface PageModule {
    default: React.ComponentType & {
        layout?: (page: React.ReactNode) => React.ReactNode;
    };
}

void createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        );

        page.then((module) => {
            (module as PageModule).default.layout = (
                pageContent: React.ReactNode,
            ) => <Layout>{pageContent}</Layout>;
        });

        return page;
    },
    setup({ el, App, props }) {
        const currentLocale = props.initialPage.props.locale;
        const i18nInstance = initI18n(
            currentLocale,
            props.initialPage.props.translations || {},
        );
        setLocale(currentLocale);

        const AppWithProviders = (
            <I18nextProvider i18n={i18nInstance}>
                <App {...props} />
            </I18nextProvider>
        );

        if (import.meta.env.SSR) {
            hydrateRoot(el, AppWithProviders);
            return;
        }

        createRoot(el).render(AppWithProviders);
    },
    progress: {
        color: '#4B5563',
    },
});
