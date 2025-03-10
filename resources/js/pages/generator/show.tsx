import AnimatedBorder from '@/components/animated-border';
import { Text } from '@/components/ui/text';
import { Head, usePoll } from '@inertiajs/react';
import { Check, Copy, Sparkles } from 'lucide-react';
import { AnimatePresence, motion } from 'motion/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface Template {
    id: string;
    status: 'pending' | 'completed' | 'failed' | 'downloaded';
    download_command: string | null;
}

interface Props {
    template: Template;
}

const fadeIn = {
    initial: { opacity: 0, y: 20 },
    animate: { opacity: 1, y: 0 },
    exit: { opacity: 0, y: -20 },
};

export default function Show({ template }: Props) {
    const { t } = useTranslation();
    const [copied, setCopied] = useState(false);
    const { stop } = usePoll(2000);

    const getPageTitle = () => {
        switch (template.status) {
            case 'pending':
                return t('Generating the template...');
            case 'completed':
                return t('Template ready');
            case 'failed':
                return t('Template unavailable');
            default:
                return t('Génération du template');
        }
    };

    useEffect(() => {
        if (template.status === 'completed' || template.status === 'failed') {
            stop();
        }
    }, [template.status, stop]);

    const copyToClipboard = async () => {
        if (!template.download_command) return;

        try {
            await navigator.clipboard.writeText(template.download_command);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (error) {
            console.error(
                'Erreur lors de la copie dans le presse-papiers :',
                error,
            );
        }
    };

    return (
        <>
            <Head title={getPageTitle()} />

            <div className="flex min-h-[60vh] items-center justify-center">
                <AnimatePresence mode="wait">
                    {template.status === 'pending' && (
                        <motion.div
                            key="pending"
                            className="space-y-8 text-center"
                            initial="initial"
                            animate="animate"
                            exit="exit"
                            variants={fadeIn}
                        >
                            <div className="space-y-4">
                                <h3 className="text-2xl font-medium">
                                    {t('Preparing your environment')}
                                </h3>
                                <Text>
                                    {t(
                                        "We're setting up your template. It will only take a few seconds.",
                                    )}
                                </Text>
                            </div>
                        </motion.div>
                    )}

                    {template.status === 'failed' && (
                        <motion.div
                            key="error"
                            variants={fadeIn}
                            initial="initial"
                            animate="animate"
                            exit="exit"
                        >
                            <Text>{t('Template generation failed')}</Text>
                        </motion.div>
                    )}

                    {template.status === 'completed' &&
                        template.download_command && (
                            <motion.div
                                key="success"
                                className="w-full max-w-3xl space-y-8"
                                variants={fadeIn}
                                initial="initial"
                                animate="animate"
                                exit="exit"
                            >
                                <div className="space-y-4 text-center">
                                    <motion.div
                                        initial={{ scale: 0 }}
                                        animate={{ scale: 1 }}
                                        transition={{
                                            type: 'spring',
                                            bounce: 0.5,
                                        }}
                                    >
                                        <div className="inline-block">
                                            <Sparkles className="size-12" />
                                        </div>
                                    </motion.div>

                                    <h3 className="text-2xl font-medium">
                                        {t('Your template is ready!')}
                                    </h3>
                                    <Text>
                                        {t(
                                            'Copy and run the command below in a terminal to start your project.',
                                        )}
                                    </Text>
                                </div>

                                <motion.div
                                    initial={{ opacity: 0, scale: 0.95 }}
                                    animate={{ opacity: 1, scale: 1 }}
                                    transition={{ delay: 0.2 }}
                                >
                                    <AnimatedBorder>
                                        <div className="relative flex items-center overflow-hidden rounded-lg bg-neutral-100 dark:bg-zinc-900">
                                            <div className="grow px-6 py-4 text-center">
                                                <motion.span
                                                    className="font-mono text-lg text-neutral-900 dark:text-neutral-100"
                                                    initial={{ opacity: 0 }}
                                                    animate={{ opacity: 1 }}
                                                    transition={{ delay: 0.4 }}
                                                >
                                                    {template.download_command}
                                                </motion.span>
                                            </div>
                                            <motion.button
                                                onClick={copyToClipboard}
                                                className="p-4 text-neutral-600 transition-colors duration-200 hover:text-neutral-800 focus:outline-none dark:text-neutral-300 dark:hover:text-neutral-100"
                                                type="button"
                                                whileHover={{ scale: 1.1 }}
                                                whileTap={{ scale: 0.95 }}
                                            >
                                                {copied ? (
                                                    <Check className="h-6 w-6" />
                                                ) : (
                                                    <Copy className="h-6 w-6" />
                                                )}
                                            </motion.button>
                                        </div>
                                    </AnimatedBorder>
                                </motion.div>

                                <motion.div
                                    className="text-center text-sm text-zinc-600 dark:text-zinc-400"
                                    initial={{ opacity: 0 }}
                                    animate={{ opacity: 1 }}
                                    transition={{ delay: 0.6 }}
                                >
                                    <Text>
                                        {t(
                                            'This command will automatically configure your project with the settings you selected.',
                                        )}
                                    </Text>
                                </motion.div>
                            </motion.div>
                        )}
                </AnimatePresence>
            </div>
        </>
    );
}
