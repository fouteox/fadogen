import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { I18nextProvider } from 'react-i18next';
import Layout from '@/layouts/base-layout';
import { initI18n } from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Fadogen';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: () => Layout,
    strictMode: true,
    withApp(app, { page, ssr }) {
        const i18n = initI18n(page.props.locale, page.props.translations ?? {}, ssr);
        return <I18nextProvider i18n={i18n}>{app}</I18nextProvider>;
    },
    progress: { color: '#4B5563' },
});
