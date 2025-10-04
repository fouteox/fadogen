import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Heading, Subheading } from '@/components/ui/heading';
import { Text } from '@/components/ui/text';
import { Head } from '@inertiajs/react';
import { CheckCircle2, CloudCog, Code, Globe, Monitor, Network, Server, Shield, Terminal } from 'lucide-react';
import React from 'react';
import { useTranslation } from 'react-i18next';
import { index } from '@/routes/generator';

interface DeploymentStepProps {
    step: number;
    title: string;
    description: string;
    icon: React.ReactNode;
}

const DeploymentStep: React.FC<DeploymentStepProps> = ({ step, title, description, icon }) => {
    return (
        <div className="flex gap-4 md:gap-6">
            <div className="flex-shrink-0">
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                    {icon}
                </div>
            </div>
            <div>
                <h3 className="mb-2 text-lg font-medium text-zinc-900 dark:text-white">
                    {step}. {title}
                </h3>
                <p className="text-zinc-500 dark:text-zinc-400">{description}</p>
            </div>
        </div>
    );
};

interface FeatureCardProps {
    icon: React.ReactNode;
    title: string;
    description: string;
}

const FeatureCard: React.FC<FeatureCardProps> = ({ icon, title, description }) => {
    return (
        <div className="rounded-lg bg-white p-6 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
            <div className="mb-4 flex items-center">
                <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-md bg-blue-50 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                    {icon}
                </div>
                <h3 className="ml-3 text-lg font-medium text-zinc-900 dark:text-white">{title}</h3>
            </div>
            <p className="text-zinc-500 dark:text-zinc-400">{description}</p>
        </div>
    );
};

const Deploy: React.FC = () => {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Deploy')} />

            <div className="mx-auto flex max-w-5xl flex-col">
                {/* Hero Section */}
                <section className="mb-16 text-center">
                    <Badge color="blue" className="mb-4">
                        {t('New')}
                    </Badge>
                    <Heading className="mb-6 text-4xl font-bold tracking-tight sm:text-5xl">{t('Simplified Deployment')}</Heading>
                    <Text className="mx-auto mb-8 max-w-2xl text-lg text-zinc-600 dark:text-zinc-300">
                        {t("Deploy your Laravel application in minutes on your own infrastructure, using Fadogen's automated deployment feature.")}
                    </Text>
                </section>

                {/* Overview Section */}
                <section className="mb-16">
                    <Subheading className="mb-8 text-center text-xl font-medium sm:text-2xl">{t('Why choose Fadogen for deployment?')}</Subheading>

                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <FeatureCard
                            icon={<Shield className="h-5 w-5" />}
                            title={t('Built-in Security')}
                            description={t('Fadogen sets up Cloudflare Tunnels for secure connections without opening ports on your network.')}
                        />
                        <FeatureCard
                            icon={
                                <svg width="24" height="24" viewBox="0 0 98 96" xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 fill-current">
                                    <path
                                        fillRule="evenodd"
                                        clipRule="evenodd"
                                        d="M48.854 0C21.839 0 0 22 0 49.217c0 21.756 13.993 40.172 33.405 46.69 2.427.49 3.316-1.059 3.316-2.362 0-1.141-.08-5.052-.08-9.127-13.59 2.934-16.42-5.867-16.42-5.867-2.184-5.704-5.42-7.17-5.42-7.17-4.448-3.015.324-3.015.324-3.015 4.934.326 7.523 5.052 7.523 5.052 4.367 7.496 11.404 5.378 14.235 4.074.404-3.178 1.699-5.378 3.074-6.6-10.839-1.141-22.243-5.378-22.243-24.283 0-5.378 1.94-9.778 5.014-13.2-.485-1.222-2.184-6.275.486-13.038 0 0 4.125-1.304 13.426 5.052a46.97 46.97 0 0 1 12.214-1.63c4.125 0 8.33.571 12.213 1.63 9.302-6.356 13.427-5.052 13.427-5.052 2.67 6.763.97 11.816.485 13.038 3.155 3.422 5.015 7.822 5.015 13.2 0 18.905-11.404 23.06-22.324 24.283 1.78 1.548 3.316 4.481 3.316 9.126 0 6.6-.08 11.897-.08 13.526 0 1.304.89 2.853 3.316 2.364 19.412-6.52 33.405-24.935 33.405-46.691C97.707 22 75.788 0 48.854 0z"
                                    />
                                </svg>
                            }
                            title={t('Automated CI/CD')}
                            description={t('Integration with GitHub Actions for continuous deployment with every push to your repository.')}
                        />
                        <FeatureCard
                            icon={<CloudCog className="h-5 w-5" />}
                            title={t('Automatic Configuration')}
                            description={t('Fadogen automatically sets up the infrastructure on your Raspberry Pi via Ansible playbooks.')}
                        />
                        <FeatureCard
                            icon={<Globe className="h-5 w-5" />}
                            title={t('Simplified DNS Management')}
                            description={t('Automatic DNS configuration via Cloudflare API to point to your application, no manual setup needed.')}
                        />
                        <FeatureCard
                            icon={<Server className="h-5 w-5" />}
                            title={t('Personal Infrastructure')}
                            description={t('Host on your own Raspberry Pi without depending on third-party hosting services.')}
                        />
                        <FeatureCard
                            icon={<CheckCircle2 className="h-5 w-5" />}
                            title={t('Zero Configuration')}
                            description={t('Automatic installation of Docker, necessary services, and network configuration on your Raspberry Pi.')}
                        />
                    </div>
                </section>

                {/* How It Works Section */}
                <section className="mb-16">
                    <div className="mx-auto mb-10 max-w-3xl text-center">
                        <Subheading className="mb-4 text-xl font-medium sm:text-2xl">{t('How it works')}</Subheading>
                        <Text className="mb-8">{t('Fadogen automates the entire deployment process in a few simple steps.')}</Text>

                        <div className="mx-auto mb-10 max-w-2xl rounded-xl bg-white p-6 shadow-md ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
                            <h3 className="mb-2 text-center text-lg font-semibold text-zinc-900 dark:text-white">{t('About Fadogen')}</h3>
                            <p className="mb-4 text-center text-zinc-600 dark:text-zinc-400">
                                {t(
                                    'Similar to how shadcn/ui generates UI components, Fadogen generates development environments and deployment configurations based on your choices, without requiring you to install any dependencies.',
                                )}
                            </p>

                            <h3 className="mb-4 text-center text-lg font-semibold text-zinc-900 dark:text-white">{t('Prerequisites')}</h3>

                            <div className="space-y-3">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                        <CheckCircle2 className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <span className="text-zinc-700 dark:text-zinc-300">{t('A Cloudflare account (free)')}</span>
                                </div>

                                <div className="flex items-center gap-3">
                                    <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                        <CheckCircle2 className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <span className="text-zinc-700 dark:text-zinc-300">{t('A Cloudflare API key')}</span>
                                </div>

                                <div className="flex items-center gap-3">
                                    <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                        <CheckCircle2 className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <span className="text-zinc-700 dark:text-zinc-300">
                                        {t('A Raspberry Pi 5 with at least 4GB RAM (recommended)')}
                                    </span>
                                </div>

                                <div className="flex items-center gap-3">
                                    <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                        <CheckCircle2 className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <span className="text-zinc-700 dark:text-zinc-300">{t('A project created with Fadogen')}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mx-auto max-w-3xl space-y-8">
                        <DeploymentStep
                            step={1}
                            icon={<Terminal className="h-5 w-5" />}
                            title={t('Run the "ddev prepare" command')}
                            description={t('In your terminal, inside your Fadogen project directory, run the "ddev prepare" command.')}
                        />
                        <DeploymentStep
                            step={2}
                            icon={<CloudCog className="h-5 w-5" />}
                            title={t('Provide your Cloudflare credentials')}
                            description={t('The script will ask for your Cloudflare email and API key to configure the secure tunnel.')}
                        />
                        <DeploymentStep
                            step={3}
                            icon={<Globe className="h-5 w-5" />}
                            title={t('Select a domain')}
                            description={t('Choose from your Cloudflare domains the one you want to use for your application.')}
                        />
                        <DeploymentStep
                            step={4}
                            icon={<Server className="h-5 w-5" />}
                            title={t('Specify your server information')}
                            description={t('Provide the username and address of your Raspberry Pi or personal server.')}
                        />
                        <DeploymentStep
                            step={5}
                            icon={<Network className="h-5 w-5" />}
                            title={t('Automatic configuration')}
                            description={t(
                                'The script automatically configures your server by installing Docker, necessary services, and setting up the Cloudflare tunnel.',
                            )}
                        />
                        <DeploymentStep
                            step={6}
                            icon={
                                <svg width="24" height="24" viewBox="0 0 98 96" xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 fill-current">
                                    <path
                                        fillRule="evenodd"
                                        clipRule="evenodd"
                                        d="M48.854 0C21.839 0 0 22 0 49.217c0 21.756 13.993 40.172 33.405 46.69 2.427.49 3.316-1.059 3.316-2.362 0-1.141-.08-5.052-.08-9.127-13.59 2.934-16.42-5.867-16.42-5.867-2.184-5.704-5.42-7.17-5.42-7.17-4.448-3.015.324-3.015.324-3.015 4.934.326 7.523 5.052 7.523 5.052 4.367 7.496 11.404 5.378 14.235 4.074.404-3.178 1.699-5.378 3.074-6.6-10.839-1.141-22.243-5.378-22.243-24.283 0-5.378 1.94-9.778 5.014-13.2-.485-1.222-2.184-6.275.486-13.038 0 0 4.125-1.304 13.426 5.052a46.97 46.97 0 0 1 12.214-1.63c4.125 0 8.33.571 12.213 1.63 9.302-6.356 13.427-5.052 13.427-5.052 2.67 6.763.97 11.816.485 13.038 3.155 3.422 5.015 7.822 5.015 13.2 0 18.905-11.404 23.06-22.324 24.283 1.78 1.548 3.316 4.481 3.316 9.126 0 6.6-.08 11.897-.08 13.526 0 1.304.89 2.853 3.316 2.364 19.412-6.52 33.405-24.935 33.405-46.691C97.707 22 75.788 0 48.854 0z"
                                    />
                                </svg>
                            }
                            title={t('Trigger the GitHub workflow')}
                            description={t('Start the GitHub workflow that will automatically build and deploy your application to your server.')}
                        />
                        <DeploymentStep
                            step={7}
                            icon={<Monitor className="h-5 w-5" />}
                            title={t('Your application is online!')}
                            description={t('Access your application at the configured address, without any additional configuration needed.')}
                        />
                    </div>
                </section>

                {/* Technical Details */}
                <section className="mb-16 rounded-lg bg-zinc-50 p-8 dark:bg-zinc-800/30">
                    <div className="mx-auto max-w-3xl">
                        <Subheading className="mb-4 text-xl font-medium sm:text-2xl">{t('Technical Details')}</Subheading>
                        <Text className="mb-6">{t('Fadogen uses several technologies to ensure a simple and secure deployment process:')}</Text>
                        <ul className="ml-6 list-disc space-y-2 text-zinc-500 dark:text-zinc-400">
                            <li>
                                <strong>{t('Ansible')} :</strong> {t('For automating Raspberry Pi configuration')}
                            </li>
                            <li>
                                <strong>{t('Cloudflare Tunnels')} :</strong>{' '}
                                {t('For secure connections without direct exposure of your Raspberry Pi')}
                            </li>
                            <li>
                                <strong>{t('GitHub Actions')} :</strong> {t('For continuous integration and deployment')}
                            </li>
                            <li>
                                <strong>{t('Docker')} :</strong> {t('For containerization and environment consistency')}
                            </li>
                            <li>
                                <strong>{t('Cloudflare API')} :</strong> {t('For automatic DNS configuration')}
                            </li>
                        </ul>

                        <div className="mt-6 rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            fillRule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clipRule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-300">{t('Important Note')}</h3>
                                    <div className="mt-2 text-sm text-yellow-700 dark:text-yellow-200">
                                        <p>
                                            {t(
                                                'Currently, this deployment feature has been tested only on Raspberry Pi devices. While it may work on other Linux servers, your experience may vary. A Raspberry Pi 5 with at least 4GB of RAM is recommended for optimal performance.',
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="mb-16 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 p-8 text-white">
                    <div className="mx-auto max-w-3xl text-center">
                        <p className="mb-4 text-2xl font-medium text-white sm:text-3xl">{t('Ready to deploy your application?')}</p>
                        <p className="mb-6 text-white/90">
                            {t('Start now by creating your project with Fadogen and follow the simple deployment steps.')}
                        </p>
                        <div className="flex flex-col justify-center gap-4 sm:flex-row">
                            <Button color="white" href={index().url}>
                                <Code className="h-5 w-5" data-slot="icon" />
                                {t('Create a new project')}
                            </Button>
                        </div>
                    </div>
                </section>

                {/* FAQ Section */}
                <section className="mb-16">
                    <div className="mx-auto mb-8 max-w-3xl text-center">
                        <Subheading className="mb-4 text-xl font-medium sm:text-2xl">{t('Frequently Asked Questions')}</Subheading>
                    </div>

                    <div className="mx-auto max-w-3xl">
                        <div className="rounded-xl bg-white p-2 shadow-md ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
                            <div className="divide-y divide-zinc-200 dark:divide-zinc-800">
                                <div className="p-4">
                                    <h3 className="text-lg font-semibold text-zinc-900 dark:text-white">
                                        {t('Do I have to pay to use this feature?')}
                                    </h3>
                                    <p className="mt-2 text-zinc-500 dark:text-zinc-400">
                                        {t(
                                            'No, the deployment feature is completely free. You only need a free Cloudflare account and your own Raspberry Pi.',
                                        )}
                                    </p>
                                </div>

                                <div className="p-4">
                                    <h3 className="text-lg font-semibold text-zinc-900 dark:text-white">
                                        {t('How is the performance on a Raspberry Pi?')}
                                    </h3>
                                    <p className="mt-2 text-zinc-500 dark:text-zinc-400">
                                        {t(
                                            'Performance depends on the Raspberry Pi model and the complexity of your application. A Raspberry Pi 5 with at least 4GB of RAM is recommended for optimal performance.',
                                        )}
                                    </p>
                                </div>

                                <div className="p-4">
                                    <h3 className="text-lg font-semibold text-zinc-900 dark:text-white">
                                        {t('Can I use a server other than a Raspberry Pi?')}
                                    </h3>
                                    <p className="mt-2 text-zinc-500 dark:text-zinc-400">
                                        {t(
                                            'Currently, the deployment feature has been tested only on Raspberry Pi. While it might work on other Linux servers, your experience may vary. We recommend sticking to Raspberry Pi for now.',
                                        )}
                                    </p>
                                </div>

                                <div className="p-4">
                                    <h3 className="text-lg font-semibold text-zinc-900 dark:text-white">{t('How is my data protected?')}</h3>
                                    <p className="mt-2 text-zinc-500 dark:text-zinc-400">
                                        {t(
                                            'Your data remains on your own Raspberry Pi. The connection is secured via an encrypted Cloudflare tunnel, and your credentials are stored as GitHub secrets, protected by encryption.',
                                        )}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
};

export default Deploy;
