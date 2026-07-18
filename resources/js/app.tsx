import '../css/app.css';
// import './echo';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { I18nextProvider } from 'react-i18next';
import Layout from '@/layouts/base-layout';
import { initI18n, setLocale } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Fadogen';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: () => Layout,
    strictMode: true,
    setup({ el, App, props }) {
        const currentLocale = props.initialPage.props.locale;
        const i18nInstance = initI18n(currentLocale, props.initialPage.props.translations || {});
        setLocale(currentLocale);

        const appElement = (
            <I18nextProvider i18n={i18nInstance}>
                <App {...props} />
            </I18nextProvider>
        );

        if (import.meta.env.SSR || el === null) {
            return appElement;
        }

        if (el.hasAttribute('data-server-rendered')) {
            hydrateRoot(el, appElement);
        } else {
            createRoot(el).render(appElement);
        }
    },
    progress: {
        color: '#4B5563',
    },
});
