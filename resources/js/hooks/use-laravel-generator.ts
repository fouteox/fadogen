import { HttpCancelledError } from '@inertiajs/core';
import { useForm, useHttp } from '@inertiajs/react';
import { useEffect, useRef, useState, type ChangeEvent, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { detect } from '@/actions/App/Http/Controllers/DependenciesDetectionController';
import { store } from '@/routes/generator';
import type { DetectedDependencies, FormValues, QueueTypeValue, Stack } from '@/types';

export const useLaravelForm = () => {
    const { t } = useTranslation();
    const [detectedFields, setDetectedFields] = useState<{ packageName: string; fields: (keyof FormValues)[] } | null>(null);
    const form = useForm<FormValues>(store(), {
        project_name: '',
        php_version: '8.5',
        database: 'sqlite',
        starter_kit: 'none',
        custom_starter_kit: '',
        workos: undefined,
        testing_framework: 'pest',
        livewire_volt: undefined,
        queue_type: undefined,
        queue_driver: undefined,
        features: [],
        javascript_package_manager: 'npm',
        initialize_git: true,
    }).setValidationTimeout(500);
    const detection = useHttp<{ package: string }, { detected: DetectedDependencies }>({ package: '' });
    const { cancel } = detection;
    const pendingDetection = useRef<Promise<void> | null>(null);
    const detectionId = useRef(0);
    const lastDetectedPackage = useRef<string | null>(null);
    const currentData = useRef(form.data);

    useEffect(() => {
        currentData.current = form.data;
    }, [form.data]);

    useEffect(() => {
        lastDetectedPackage.current = null;

        return () => {
            detectionId.current += 1;
            cancel();
        };
    }, [form.data.starter_kit, form.data.custom_starter_kit, cancel]);

    const detectDependencies = async (customPackage: string): Promise<void> => {
        const packageName = customPackage.trim();
        if (
            !packageName ||
            currentData.current.starter_kit !== 'custom' ||
            currentData.current.custom_starter_kit.trim() !== packageName ||
            lastDetectedPackage.current === packageName
        ) {
            return;
        }

        const requestId = ++detectionId.current;
        cancel();
        if (pendingDetection.current) {
            await pendingDetection.current;
        }
        if (requestId !== detectionId.current) {
            return;
        }

        const initialData = currentData.current;
        const request = async () => {
            try {
                detection.transform(() => ({ package: packageName }));
                const response = await detection.get(detect().url);
                if (requestId !== detectionId.current || !response?.detected) {
                    return;
                }

                const { detected } = response;
                const detectChanges = (data: FormValues) => {
                    const changes: Partial<FormValues> = {};
                    const fields: (keyof FormValues)[] = [];
                    const applyDetectedValue = <K extends keyof FormValues>(key: K, value: FormValues[K] | undefined) => {
                        if (
                            value === undefined ||
                            JSON.stringify(data[key]) !== JSON.stringify(initialData[key]) ||
                            JSON.stringify(data[key]) === JSON.stringify(value)
                        ) {
                            return;
                        }
                        changes[key] = value;
                        fields.push(key);
                    };
                    applyDetectedValue('php_version', detected.php_version);
                    applyDetectedValue('database', detected.database);
                    applyDetectedValue('features', detected.features);
                    if (data.queue_type === initialData.queue_type && data.queue_driver === initialData.queue_driver) {
                        applyDetectedValue('queue_type', detected.queue_type);
                        applyDetectedValue('queue_driver', detected.queue_driver);
                    }
                    applyDetectedValue('testing_framework', detected.testing_framework);
                    applyDetectedValue('javascript_package_manager', detected.javascript_package_manager);
                    return { changes, fields };
                };
                const applied: { fields?: (keyof FormValues)[] } = {};
                form.setData((previous) => {
                    if (previous.starter_kit !== 'custom' || previous.custom_starter_kit.trim() !== packageName) {
                        return previous;
                    }
                    const { changes, fields } = detectChanges(previous);
                    applied.fields = fields;
                    return { ...previous, ...changes };
                });
                if (!applied.fields) {
                    return;
                }
                const fields = applied.fields;
                setDetectedFields({ packageName, fields });
                lastDetectedPackage.current = packageName;

                for (const notification of detected.notifications ?? []) {
                    toast[notification.type](notification.message);
                }
                toast.info(
                    t(
                        fields.length > 0
                            ? 'Some fields have been modified due to the selected custom package. Please carefully review the form.'
                            : 'No specific configuration has been detected for this package.',
                    ),
                );
            } catch (error: unknown) {
                if (requestId === detectionId.current && !(error instanceof HttpCancelledError)) {
                    toast.error(t('An error has occurred.'));
                }
            }
        };

        pendingDetection.current = request();
        await pendingDetection.current;
    };

    const handleSubmit = (event: FormEvent): void => {
        event.preventDefault();
        if (detection.processing || form.processing) {
            return;
        }
        form.transform((data) => ({ ...data, custom_starter_kit: data.starter_kit === 'custom' ? data.custom_starter_kit : '' }));
        form.submit();
    };

    const handleQueueChange = (event: ChangeEvent<HTMLSelectElement>): void => {
        const value = event.target.value as QueueTypeValue | 'none';
        form.setData((data) => ({
            ...data,
            queue_type: value === 'none' ? undefined : value,
            queue_driver: value === 'none' ? undefined : 'valkey',
        }));
    };

    const handleFeatureChange = (feature: string, checked: boolean): void => {
        form.setData((data) => ({ ...data, features: checked ? [...data.features, feature] : data.features.filter((value) => value !== feature) }));
    };

    const handleStackChange = (event: ChangeEvent<HTMLSelectElement>): void => {
        const starterKit = event.target.value as Stack;
        form.setData((data) => ({
            ...data,
            starter_kit: starterKit,
            custom_starter_kit: '',
            workos: starterKit === 'none' || starterKit === 'custom' ? undefined : (data.workos ?? false),
            livewire_volt: starterKit === 'livewire' ? false : undefined,
        }));
    };

    return {
        data: form.data,
        setData: form.setData,
        processing: form.processing,
        errors: form.errors,
        validating: form.validating,
        isLoading: detection.processing,
        detectionErrors: detection.errors,
        modifiedFields:
            form.data.starter_kit === 'custom' && form.data.custom_starter_kit.trim() === detectedFields?.packageName ? detectedFields.fields : [],
        handleSubmit,
        handleStackChange,
        handleQueueChange,
        handleFeatureChange,
        validate: form.validate,
        detectDependencies,
    };
};
