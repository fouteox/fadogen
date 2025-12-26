import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Heading, Subheading } from '@/components/ui/heading';
import { Text } from '@/components/ui/text';
import SimpleVideoPlayer from '@/components/video-player';
import { Head, WhenVisible } from '@inertiajs/react';
import { Apple, Heart, Rocket } from 'lucide-react';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { index } from '@/routes/generator';

const Welcome: React.FC = () => {
    const { t, i18n } = useTranslation();

    const getDocsUrl = () => {
        const lang = i18n.language;
        if (lang === 'fr') return 'https://docs.fadogen.app/fr';
        if (lang === 'de') return 'https://docs.fadogen.app/de';
        return 'https://docs.fadogen.app';
    };

    return (
        <>
            <Head title={t('Welcome')} />

            <div className="mx-auto flex max-w-5xl flex-col">
                {/* Native App Announcement */}
                <a
                    href={getDocsUrl()}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="mb-8 flex flex-col items-center gap-2 rounded-lg bg-gradient-to-r from-zinc-800 to-zinc-900 px-4 py-3 text-white ring-1 ring-zinc-700 transition-all hover:from-zinc-700 hover:to-zinc-800 md:gap-3 md:py-4 lg:flex-row lg:justify-center dark:from-zinc-800 dark:to-zinc-900 dark:ring-zinc-600 dark:hover:from-zinc-700 dark:hover:to-zinc-800"
                >
                    <span className="flex items-center gap-2">
                        <Apple className="size-5 md:size-6" />
                        <span className="rounded bg-white/20 px-2 py-0.5 text-xs font-semibold lg:hidden">
                            {t('New')}
                        </span>
                    </span>
                    <span className="text-center text-sm font-medium md:text-base lg:text-left">
                        {t('Native macOS app available with even more features!')}
                    </span>
                    <span className="hidden rounded bg-white/20 px-2 py-0.5 text-xs font-semibold lg:inline">
                        {t('New')}
                    </span>
                </a>

                {/* Hero Section */}
                <section className="text-center">
                    <Heading className="mb-6 text-4xl font-bold tracking-tight sm:text-5xl">{t('Fadogen')}</Heading>
                    <Text className="mx-auto mb-8 max-w-2xl text-lg text-zinc-600 dark:text-zinc-300">
                        {t('Initialize a development environment in seconds')}
                    </Text>
                    <div className="mb-8 flex flex-col justify-center gap-4 sm:flex-row">
                        <Button color="blue" href={index().url}>
                            <Rocket className="size-5" data-slot="icon" />
                            {t('Web Generator')}
                        </Button>
                    </div>
                </section>

                {/* Video Demo Section */}
                <section className="mb-16">
                    <div className="mx-auto mb-8 max-w-3xl text-center">
                        <Subheading className="mb-4 text-xl font-medium sm:text-2xl">{t('See how it works')}</Subheading>
                        <Text className="mb-6">{t('Watch this quick demo to see how Fadogen simplifies your development workflow.')}</Text>
                    </div>
                    <div className="overflow-hidden rounded-lg">
                        <WhenVisible
                            data="videoPlayer"
                            buffer={200}
                            fallback={
                                <div className="flex aspect-video w-full items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    <p className="text-zinc-500 dark:text-zinc-400">{t('Loading video...')}</p>
                                </div>
                            }
                        >
                            <SimpleVideoPlayer />
                        </WhenVisible>
                    </div>
                </section>

                {/* Pricing Section */}
                <section className="mb-16 rounded-lg bg-white p-8 ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
                    <div className="mx-auto max-w-3xl text-center">
                        <Badge color="blue" className="mb-4">
                            {t('Pricing')}
                        </Badge>
                        <Subheading className="mb-4 text-xl font-medium sm:text-2xl">{t('How much does it cost?')}</Subheading>
                        <Heading className="mb-4 text-3xl font-bold text-blue-500 sm:text-5xl">{t('cost_free')}</Heading>
                        <Text className="mb-6">
                            {t(
                                'Fadogen is completely free to use. No hidden fees, no premium tiers, no credit card required. Just pure development happiness.',
                            )}
                        </Text>
                        <div className="flex justify-center">
                            <Badge color="green">{t('Free Forever')}</Badge>
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="mb-8 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white sm:p-8">
                    <div className="mx-auto max-w-3xl text-center">
                        <p className="mb-4 text-2xl font-medium text-white sm:text-3xl">{t('Ready to transform your development process?')}</p>
                        <p className="mb-6 text-white/90">{t('Start now and discover how Fadogen can speed up your workflow.')}</p>
                        <div className="flex justify-center">
                            <Button color="white" href={index().url}>
                                {t('Try Fadogen now')}
                            </Button>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="mb-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    <p className="mb-2">{t('Fadogen — A tool to quickly create development environments without hassle')}</p>
                    <p className="mb-2">
                        <Trans i18nKey="created_with_by">
                            Created with
                            <Heart className="mx-1 inline-block h-3 w-3 text-red-500" />
                            by
                            <a
                                href="https://github.com/fouteox"
                                className="text-zinc-900 underline decoration-zinc-950/30 hover:decoration-zinc-950 dark:text-zinc-200 dark:decoration-white/30 dark:hover:decoration-white"
                            >
                                Fouteox
                            </a>
                        </Trans>
                    </p>
                    <a
                        href="mailto:contact@fadogen.app"
                        className="text-zinc-900 underline decoration-zinc-950/30 hover:decoration-zinc-950 dark:text-zinc-200 dark:decoration-white/30 dark:hover:decoration-white"
                    >
                        contact@fadogen.app
                    </a>
                </footer>
            </div>
        </>
    );
};

export default Welcome;
