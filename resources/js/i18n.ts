import { createInstance, type Resource } from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import HttpBackend from 'i18next-http-backend';
import { initReactI18next } from 'react-i18next';

export const initI18n = (locale: string, resources: Resource, ssr = import.meta.env.SSR) => {
    const instance = createInstance().use(initReactI18next);

    if (!ssr) {
        instance.use(LanguageDetector).use(HttpBackend);
    }

    void instance.init({
        supportedLngs: ['en', 'fr', 'de', 'es'],
        fallbackLng: 'en',
        lng: locale,
        resources,
        interpolation: { escapeValue: false },
        partialBundledLanguages: !ssr,
        initAsync: false,
        detection: {
            order: ['cookie', 'navigator'],
            lookupCookie: 'locale',
            caches: ['cookie'],
        },
    });

    return instance;
};
