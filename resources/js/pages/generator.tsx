import AppHead from '@/components/app-head';
import { Button } from '@/components/ui/button';
import { Divider } from '@/components/ui/divider';
import { FieldGroup } from '@/components/ui/fieldset';
import { Subheading } from '@/components/ui/heading';
import { Text } from '@/components/ui/text';
import { useLaravelForm } from '@/hooks/use-laravel-generator';
import { BasicInformation } from '@/pages/generator/laravel/basic-information';
import { OptionalFeatures } from '@/pages/generator/laravel/optional-features';
import { QueueConfiguration } from '@/pages/generator/laravel/queue-configuration';
import { StarterKitConfiguration } from '@/pages/generator/laravel/starter-kit-configuration';
import React from 'react';
import { useTranslation } from 'react-i18next';
import { Toaster } from 'sonner';

export default function Generator() {
    const { t } = useTranslation();

    const {
        data,
        setData,
        processing,
        validating,
        errors,
        isLoading,
        modifiedFields,
        handleSubmit,
        handleStackChange,
        handleQueueChange,
        handleFeatureChange,
        validate,
        detectDependencies,
    } = useLaravelForm();

    const validateAllFields = (e: React.FormEvent) => {
        e.preventDefault();

        validate({
            only: [
                'project_name',
                'php_version',
                'database',
                'starter_kit',
                'custom_starter_kit',
                'testing_framework',
            ],
            onSuccess: () => {
                handleSubmit(e);
            },
        });
    };

    return (
        <>
            <AppHead title={t('Generator')} />
            <Toaster
                position="top-right"
                richColors
                closeButton
                duration={5000}
            />

            <Divider className="my-10 mt-6" />

            <form onSubmit={validateAllFields}>
                <section className="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div className="space-y-1">
                        <Subheading>
                            {t('General information about the project')}
                        </Subheading>
                        <Text>
                            {t(
                                'Configure the basic settings needed to initialize your project.',
                            )}
                        </Text>
                    </div>

                    <BasicInformation
                        data={data}
                        setData={setData}
                        errors={errors}
                        validating={validating}
                        modifiedFields={modifiedFields}
                    />
                </section>

                <Divider className="my-10" soft />

                <section className="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div className="space-y-1">
                        <Subheading>{t('Choice of starter kit')}</Subheading>
                        <Text>
                            {t(
                                "Select and customize your application's initial setup and development tools.",
                            )}
                        </Text>
                    </div>

                    <StarterKitConfiguration
                        data={data}
                        setData={setData}
                        errors={errors}
                        validating={validating}
                        modifiedFields={modifiedFields}
                        handleStackChange={handleStackChange}
                        detectDependencies={detectDependencies}
                    />
                </section>

                <Divider className="my-10" soft />

                <section className="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div className="space-y-1">
                        <Subheading>{t('Optional features')}</Subheading>
                        <Text>
                            {t(
                                'Add additional features like task scheduling, websockets, and job queue workers.',
                            )}
                        </Text>
                    </div>

                    <FieldGroup>
                        <QueueConfiguration
                            data={data}
                            setData={setData}
                            errors={errors}
                            validating={validating}
                            modifiedFields={modifiedFields}
                            handleQueueChange={handleQueueChange}
                        />

                        <OptionalFeatures
                            data={data}
                            handleFeatureChange={handleFeatureChange}
                            modifiedFields={modifiedFields}
                        />
                    </FieldGroup>
                </section>

                <div className="mt-8 flex items-center justify-end gap-4">
                    {isLoading && (
                        <Text className="text-sm text-gray-500">
                            {t('Analyse du package en cours...')}
                        </Text>
                    )}
                    {validating && (
                        <Text className="text-sm text-gray-500">
                            {t('Validation en cours...')}
                        </Text>
                    )}
                    <Button
                        type="submit"
                        disabled={processing || validating || isLoading}
                    >
                        {processing
                            ? t('Génération...')
                            : t('Generate project')}
                    </Button>
                </div>
            </form>
        </>
    );
}
