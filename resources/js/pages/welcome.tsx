import AnimatedBorder from '@/components/animated-border';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Heading, Subheading } from '@/components/ui/heading';
import { Text, TextLink } from '@/components/ui/text';
import SimpleVideoPlayer from '@/components/video-player';
import { Head, WhenVisible } from '@inertiajs/react';
import {
    CheckCircle2,
    Code,
    Copy,
    Heart,
    Rocket,
    Terminal,
} from 'lucide-react';
import React, { useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';

interface FeatureCardProps {
    icon: React.ReactNode;
    title: string;
    description: string;
}

const FeatureCard: React.FC<FeatureCardProps> = ({
    icon,
    title,
    description,
}) => {
    return (
        <div className="rounded-lg bg-white p-6 ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
            <div className="mb-4 flex items-center">
                <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-md bg-zinc-50 text-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-200">
                    {icon}
                </div>
                <h3 className="ml-3 text-lg font-medium text-zinc-900 dark:text-white">
                    {title}
                </h3>
            </div>
            <p className="text-zinc-500 dark:text-zinc-400">{description}</p>
        </div>
    );
};

interface CodeInstallProps {
    cliCommand: string;
    copyToClipboard: () => Promise<void>;
    copied: boolean;
}

const CodeInstall: React.FC<CodeInstallProps> = ({
    cliCommand,
    copyToClipboard,
    copied,
}) => {
    const { t } = useTranslation();

    return (
        <AnimatedBorder>
            <div className="relative flex items-center overflow-hidden rounded-lg bg-zinc-50 dark:bg-zinc-900">
                <div className="flex w-full justify-center px-4 py-4 pr-[60px] sm:px-6 sm:pr-[60px]">
                    <div className="flex flex-wrap items-center justify-center gap-2 text-center">
                        <Terminal className="hidden h-5 w-5 flex-shrink-0 text-zinc-400 sm:block dark:text-zinc-500" />
                        <code className="text-center font-mono text-xs break-all text-zinc-900 sm:text-sm md:text-base dark:text-zinc-100">
                            {cliCommand}
                        </code>
                    </div>
                </div>

                <div className="absolute top-0 right-0 h-full border-l border-zinc-200 dark:border-zinc-700">
                    <button
                        onClick={copyToClipboard}
                        className="flex h-full w-[60px] items-center justify-center text-zinc-500 transition-colors duration-200 hover:bg-zinc-100 hover:text-zinc-700 focus:ring-2 focus:ring-blue-500 focus:outline-none focus:ring-inset dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                        type="button"
                        aria-label={copied ? t('Copied') : t('Copy command')}
                    >
                        {copied ? (
                            <CheckCircle2 className="h-5 w-5" />
                        ) : (
                            <Copy className="h-5 w-5" />
                        )}
                    </button>
                </div>
            </div>
        </AnimatedBorder>
    );
};

const Welcome: React.FC = () => {
    const { t } = useTranslation();
    const [copied, setCopied] = useState<boolean>(false);
    const cliCommand = `sh -c "$(curl -fsSL ${route('init')})"`;

    const copyToClipboard = async (): Promise<void> => {
        try {
            await navigator.clipboard.writeText(cliCommand);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (error) {
            console.error('Error copying to clipboard:', error);
        }
    };

    return (
        <>
            <Head title={t('Welcome')} />

            <div className="mx-auto flex max-w-5xl flex-col">
                {/* Hero Section */}
                <section className="mb-16 text-center">
                    <Badge color="red" className="mb-4">
                        {t('Alpha')}
                    </Badge>
                    <Heading className="mb-6 text-4xl font-bold tracking-tight sm:text-5xl">
                        {t('Fadogen')}
                    </Heading>
                    <Text className="mx-auto mb-8 max-w-2xl text-lg text-zinc-600 dark:text-zinc-300">
                        {t('Initialize a development environment in seconds')}
                    </Text>
                    <div className="mb-10 flex flex-col justify-center gap-4 sm:flex-row">
                        <Button color="blue" href={route('generator.index')}>
                            <Rocket className="h-5 w-5" data-slot="icon" />
                            {t('Web Generator')}
                        </Button>
                    </div>

                    <div className="mb-6">
                        <Text className="text-sm text-zinc-500 italic dark:text-zinc-400">
                            <Trans i18nKey="require_ddev">
                                Requires
                                <TextLink
                                    href="https://ddev.com/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    DDEV
                                </TextLink>
                                to be installed on your system
                            </Trans>
                        </Text>
                    </div>

                    <CodeInstall
                        cliCommand={cliCommand}
                        copyToClipboard={copyToClipboard}
                        copied={copied}
                    />
                </section>

                {/* Features Section */}
                <section className="mb-16">
                    <Subheading className="mb-4 text-center text-xl font-medium sm:text-2xl">
                        {t('Simplify your workflow')}
                    </Subheading>
                    <Text className="mx-auto mb-10 max-w-2xl text-center">
                        {t(
                            'Fadogen automates the setup of your development environment so you can focus on what really matters: your code.',
                        )}
                    </Text>

                    <div className="grid gap-6 md:grid-cols-2">
                        <FeatureCard
                            icon={<Terminal className="h-5 w-5" />}
                            title={t('Fast Setup')}
                            description={t(
                                'Initialize a complete Laravel application in seconds, without tedious manual configuration.',
                            )}
                        />
                        <FeatureCard
                            icon={<Code className="h-5 w-5" />}
                            title={t('Customizable')}
                            description={t(
                                'Choose your database, JavaScript package manager, and enable the features you need.',
                            )}
                        />
                    </div>
                </section>

                {/* Deployment Section */}
                <section className="mb-16 rounded-lg bg-blue-50 p-8 dark:bg-blue-900/20">
                    <div className="mx-auto max-w-3xl text-center">
                        <Badge color="blue" className="mb-4">
                            {t('New')}
                        </Badge>
                        <Subheading className="mb-4 text-xl font-medium sm:text-2xl">
                            {t('Simplified Deployment')}
                        </Subheading>
                        <Text className="mb-6">
                            {t(
                                "Deploying your application is now as simple as initializing it. Use Fadogen's integrated deployment feature to get your application online in minutes, without complex configuration.",
                            )}
                        </Text>
                        <Button color="blue" href={route('deploy')}>
                            <Rocket className="h-5 w-5" data-slot="icon" />
                            {t('Learn more')}
                        </Button>
                    </div>
                </section>

                {/* Pricing Section */}
                <section className="mb-16 rounded-lg bg-white p-8 ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
                    <div className="mx-auto max-w-3xl text-center">
                        <Badge color="blue" className="mb-4">
                            {t('Pricing')}
                        </Badge>
                        <Subheading className="mb-4 text-xl font-medium sm:text-2xl">
                            {t('How much does it cost?')}
                        </Subheading>
                        <Heading className="mb-4 text-3xl font-bold text-blue-500 sm:text-5xl">
                            {t('cost_free')}
                        </Heading>
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

                {/* Video Demo Section */}
                <section className="mb-16">
                    <div className="mx-auto mb-8 max-w-3xl text-center">
                        <Subheading className="mb-4 text-xl font-medium sm:text-2xl">
                            {t('See how it works')}
                        </Subheading>
                        <Text className="mb-6">
                            {t(
                                'Watch this quick demo to see how Fadogen simplifies your development workflow.',
                            )}
                        </Text>
                    </div>
                    <div className="overflow-hidden rounded-lg">
                        <WhenVisible
                            data="videoPlayer"
                            buffer={200}
                            fallback={
                                <div className="flex aspect-video w-full items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    <p className="text-zinc-500 dark:text-zinc-400">
                                        {t('Loading video...')}
                                    </p>
                                </div>
                            }
                        >
                            <SimpleVideoPlayer />
                        </WhenVisible>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="mb-8 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white sm:p-8">
                    <div className="mx-auto max-w-3xl text-center">
                        <p className="mb-4 text-2xl font-medium text-white sm:text-3xl">
                            {t('Ready to transform your development process?')}
                        </p>
                        <p className="mb-6 text-white/90">
                            {t(
                                'Start now and discover how Fadogen can speed up your workflow.',
                            )}
                        </p>
                        <div className="flex justify-center">
                            <Button
                                color="white"
                                href={route('generator.index')}
                            >
                                {t('Try Fadogen now')}
                            </Button>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="mb-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    <p className="mb-2">
                        {t(
                            'Fadogen — A tool to quickly create development environments without hassle',
                        )}
                    </p>
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
