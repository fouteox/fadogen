import { FormValues, QueueTypeValue, Stack } from '@/types';
import { useForm } from '@inertiajs/react';
import React from 'react';

export const useLaravelForm = () => {
    const { data, setData, post, processing, errors, transform } =
        useForm<FormValues>('CreateTemplate', {
            project_name: '',
            php_version: '8.4',
            database: 'sqlite',
            starter_kit: 'none',
            workos: undefined,
            testing_framework: 'pest',
            livewire_volt: undefined,
            queue_type: undefined,
            queue_driver: undefined,
            features: [],
            javascript_package_manager: 'npm',
            initialize_git: true,
        });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (data.workos) {
            transform((data) => ({
                ...data,
                workos: data.workos === 'workos',
            }));
        }
        post(route('generator.store'));
    };

    const handleQueueChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const value = e.target.value as QueueTypeValue | 'none';
        if (value === 'none') {
            setData('queue_type', undefined);
            setData('queue_driver', undefined);
        } else {
            setData('queue_type', value);
            setData('queue_driver', 'valkey');
        }
    };

    const handleFeatureChange = (feature: string, checked: boolean) => {
        const updatedFeatures = checked
            ? [...data.features, feature]
            : data.features.filter((f) => f !== feature);
        setData('features', updatedFeatures);
    };

    const handleStackChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const value = e.target.value as Stack;
        setData('starter_kit', value);

        if (value === 'none') {
            setData('workos', undefined);
            setData('livewire_volt', undefined);
        } else {
            if (data.workos === undefined) {
                setData('workos', 'laravel');
            }

            if (value === 'livewire') {
                setData('livewire_volt', false);
            } else {
                setData('livewire_volt', undefined);
            }
        }
    };

    return {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        handleStackChange,
        handleQueueChange,
        handleFeatureChange,
    };
};
