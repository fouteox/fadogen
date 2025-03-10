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
import { useTranslation } from 'react-i18next';

export default function Generator() {
    const { t } = useTranslation();

    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        handleStackChange,
        handleQueueChange,
        handleFeatureChange,
    } = useLaravelForm();

    return (
        <>
            <AppHead title={t('Generator')} />

            <Divider className="my-10 mt-6" />

            <form onSubmit={handleSubmit}>
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
                        handleStackChange={handleStackChange}
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
                            handleQueueChange={handleQueueChange}
                        />

                        <OptionalFeatures
                            data={data}
                            handleFeatureChange={handleFeatureChange}
                        />
                    </FieldGroup>
                </section>

                <div className="mt-8 flex items-center justify-end gap-4">
                    <Button type="submit" disabled={processing}>
                        {t('Generate project')}
                    </Button>
                </div>
            </form>
        </>
    );
}
