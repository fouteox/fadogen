import {
    AuthProvider,
    DatabaseType,
    DetectedDependencies,
    FormValues,
    PackageManager,
    PhpVersion,
    PrecognitionFormData,
    QueueDriverValue,
    QueueTypeValue,
    Stack,
    TestingFramework,
} from '@/types';
import axios, { AxiosResponse } from 'axios';
import { useForm } from 'laravel-precognition-react-inertia';
import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';

export const useLaravelForm = () => {
    const { t } = useTranslation();
    const [isLoading, setIsLoading] = useState(false);
    const [modifiedFields, setModifiedFields] = useState<string[]>([]);

    const form = useForm('post', route('generator.store'), {
        project_name: '',
        php_version: '8.4' as PhpVersion,
        database: 'sqlite' as DatabaseType,
        starter_kit: 'none' as Stack,
        custom_starter_kit: '',
        workos: undefined as AuthProvider | undefined,
        testing_framework: 'pest' as TestingFramework,
        livewire_volt: undefined as boolean | undefined,
        queue_type: undefined as QueueTypeValue | undefined,
        queue_driver: undefined as QueueDriverValue | undefined,
        features: [] as string[],
        javascript_package_manager: 'npm' as PackageManager,
        initialize_git: true,
    });

    form.setValidationTimeout(500);

    /**
     * Dependency detection function
     * Only triggered when starter_kit is 'custom' and custom_starter_kit is filled
     */
    const detectDependencies = async (customPackage: string): Promise<void> => {
        if (!customPackage || customPackage.trim() === '') {
            return;
        }

        try {
            setIsLoading(true);
            const response: AxiosResponse<{ detected: DetectedDependencies }> =
                await axios.get(route('dependencies.detect'), {
                    params: { package: customPackage },
                });

            const { detected } = response.data;

            if (detected) {
                const newModifiedFields: string[] = [];

                Object.entries(detected).forEach(([key, value]) => {
                    if (key !== 'notifications' && value !== undefined) {
                        if (key in form.data) {
                            const typedKey = key as keyof FormValues;
                            const originalValue = (
                                form.data as Record<string, unknown>
                            )[typedKey];

                            if (
                                JSON.stringify(originalValue) !==
                                JSON.stringify(value)
                            ) {
                                (
                                    form.setData as (
                                        key: string,
                                        value: unknown,
                                    ) => void
                                )(String(typedKey), value);

                                newModifiedFields.push(typedKey as string);
                            }
                        }
                    }
                });

                setModifiedFields(newModifiedFields);

                if (newModifiedFields.length > 0) {
                    toast.info(
                        t(
                            'Some fields have been modified due to the selected custom package. Please carefully review the form.',
                        ),
                    );
                } else {
                    toast.info(
                        t(
                            'No specific configuration has been detected for this package.',
                        ),
                    );
                }
            }
        } catch (error: unknown) {
            console.error(error);

            toast.error(t('An error has occurred.'));
        } finally {
            setIsLoading(false);
        }
    };

    const handleSubmit = (e: React.FormEvent): void => {
        e.preventDefault();

        form.transform((data: PrecognitionFormData): PrecognitionFormData => {
            const transformedData: PrecognitionFormData = { ...data };

            if (transformedData.starter_kit !== 'custom') {
                transformedData.custom_starter_kit = '';
            }

            return transformedData;
        });

        form.submit();
    };

    const handleQueueChange = (
        e: React.ChangeEvent<HTMLSelectElement>,
    ): void => {
        const value = e.target.value as QueueTypeValue | 'none';
        if (value === 'none') {
            form.setData('queue_type', undefined);
            form.setData('queue_driver', undefined);
        } else {
            form.setData('queue_type', value);
            form.setData('queue_driver', 'valkey');
        }
    };

    const handleFeatureChange = (feature: string, checked: boolean): void => {
        const updatedFeatures = checked
            ? [...form.data.features, feature]
            : form.data.features.filter((f) => f !== feature);
        form.setData('features', updatedFeatures);
    };

    const handleStackChange = (
        e: React.ChangeEvent<HTMLSelectElement>,
    ): void => {
        const value = e.target.value as Stack;
        const previousValue = form.data.starter_kit;
        form.setData('starter_kit', value);

        if (previousValue === 'custom' && value !== 'custom') {
            form.setData('custom_starter_kit', '');
            setModifiedFields([]);
        }

        if (value === 'none') {
            form.setData('workos', undefined);
            form.setData('livewire_volt', undefined);
            form.setData('custom_starter_kit', '');
        } else if (value === 'custom') {
            form.setData('workos', undefined);
            form.setData('livewire_volt', undefined);
        } else {
            if (form.data.workos === undefined) {
                form.setData('workos', 'laravel');
            }

            if (value === 'livewire') {
                form.setData('livewire_volt', false);
            } else {
                form.setData('livewire_volt', undefined);
            }

            form.setData('custom_starter_kit', '');
        }
    };

    return {
        data: form.data,
        setData: form.setData,
        processing: form.processing,
        errors: form.errors,
        validating: form.validating,
        isLoading,
        modifiedFields,
        handleSubmit,
        handleStackChange,
        handleQueueChange,
        handleFeatureChange,
        validate: form.validate,
        detectDependencies,
    };
};
