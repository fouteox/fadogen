import type { InertiaPrecognitiveFormProps } from '@inertiajs/react';
import type { Resource } from 'i18next';
import type { ChangeEvent } from 'react';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface NavItem {
    title: string;
    href: string;
}

export type PageProps = {
    auth: { user: User | null };
    locale: string;
    translations: Resource | null;
};

export type Stack = 'none' | 'react' | 'vue' | 'livewire' | 'custom';
export type QueueDriverValue = 'valkey' | 'redis' | 'database';
export type QueueTypeValue = 'horizon' | 'native';
export type PackageManager = 'npm' | 'bun';
export type TestingFramework = 'pest' | 'phpunit';
export type PhpVersion = '8.3' | '8.4' | '8.5';
export type DatabaseType = 'sqlite' | 'mysql' | 'mariadb' | 'pgsql';
export type SelectChangeEvent = ChangeEvent<HTMLSelectElement>;

export interface FormValues {
    project_name: string;
    php_version: PhpVersion;
    database: DatabaseType;
    starter_kit: Stack;
    custom_starter_kit: string;
    livewire_volt: boolean | undefined;
    workos: boolean | undefined;
    testing_framework: TestingFramework;
    queue_type: QueueTypeValue | undefined;
    queue_driver: QueueDriverValue | undefined;
    features: string[];
    javascript_package_manager: PackageManager;
    initialize_git: boolean;
}

export type GeneratorForm = InertiaPrecognitiveFormProps<FormValues>;
export type SetDataMethod = GeneratorForm['setData'];
export type BaseFormSectionProps = Pick<GeneratorForm, 'data' | 'setData' | 'errors' | 'validate'> & {
    modifiedFields?: (keyof FormValues)[];
};

export type DetectedDependencies = Partial<
    Pick<FormValues, 'features' | 'php_version' | 'database' | 'queue_type' | 'queue_driver' | 'testing_framework' | 'javascript_package_manager'>
> & {
    notifications?: { type: 'info' | 'success' | 'warning' | 'error'; message: string }[];
};
