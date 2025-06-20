import React, { ChangeEvent } from 'react';
import { Config } from 'ziggy-js';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    ziggy: Config & { location: string };
    locale: string;
    translations: TranslationStore | null;
};

export type FormErrors<T> = Partial<Record<keyof T, string>>;

export type Stack = 'none' | 'react' | 'vue' | 'livewire' | 'custom';

export type QueueDriverValue = 'valkey' | 'redis' | 'database';
export type QueueTypeValue = 'horizon' | 'native';

export type PackageManager = 'npm' | 'bun';
export type TestingFramework = 'pest' | 'phpunit';
export type AuthProvider = 'laravel' | 'workos';
export type PhpVersion = '8.2' | '8.3' | '8.4';
export type DatabaseType = 'sqlite' | 'mysql' | 'mariadb' | 'pgsql';
export type NotificationType = 'info' | 'success' | 'warning' | 'error';

export interface ValidationMethod {
    (field: string): void;
    (options: PrecognitionValidationOptions): void;
}

export interface SetDataMethod {
    <K extends keyof FormValues>(key: K, value: FormValues[K]): void;
    validate?: ValidationMethod;
}

export interface BaseFormSectionProps {
    data: FormValues;
    setData: SetDataMethod;
    errors: FormErrors<FormValues>;
    validating?: boolean | Record<string, boolean>;
    modifiedFields?: string[];
}

export type SelectChangeEvent = ChangeEvent<HTMLSelectElement>;

export interface QueueHandlers {
    handleQueueChange: (e: SelectChangeEvent) => void;
}

export type FormDataConvertible =
    | string
    | number
    | boolean
    | File
    | string[]
    | undefined;

export interface FormValues extends Record<string, FormDataConvertible> {
    project_name: string;
    php_version: PhpVersion;
    database: DatabaseType;
    starter_kit: Stack;
    custom_starter_kit: string;
    livewire_volt?: boolean;
    workos: boolean | undefined;
    testing_framework: TestingFramework;
    queue_type: QueueTypeValue | undefined;
    queue_driver: QueueDriverValue | undefined;
    features: string[];
    javascript_package_manager: PackageManager;
    initialize_git: boolean;
}

export interface PrecognitionValidationOptions {
    only?: string[];
    onSuccess?: (response: unknown) => void;
    onValidationError?: (response: unknown) => void;
}

export interface PrecognitionFormData extends FormValues {}

export interface Notification {
    type: NotificationType;
    message: string;
}

export interface DetectedDependencies {
    features?: string[];
    php_version?: PhpVersion;
    database?: DatabaseType;
    queue_type?: QueueTypeValue;
    queue_driver?: QueueDriverValue;
    starter_kit?: Stack;
    workos?: AuthProvider;
    testing_framework?: TestingFramework;
    livewire_volt?: boolean;
    javascript_package_manager?: PackageManager;
    notifications?: Notification[];
}

export interface LaravelFormHook {
    data: FormValues;
    setData: <K extends keyof FormValues>(key: K, value: FormValues[K]) => void;
    processing: boolean;
    validating: boolean | Record<string, boolean>;
    errors: FormErrors<FormValues>;
    isLoading: boolean;
    dependenciesDetected: boolean;
    modifiedFields: string[];
    handleSubmit: (e: React.FormEvent) => void;
    handleStackChange: (e: React.ChangeEvent<HTMLSelectElement>) => void;
    handleQueueChange: (e: React.ChangeEvent<HTMLSelectElement>) => void;
    handleFeatureChange: (feature: string, checked: boolean) => void;
    validate: ValidationMethod;
    detectDependencies?: (customPackage: string) => Promise<void>;
}
