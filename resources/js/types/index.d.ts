import { ChangeEvent } from 'react';
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

export type Stack = 'none' | 'react' | 'vue' | 'livewire';

export type QueueDriverValue = 'valkey' | 'redis' | 'database';
export type QueueTypeValue = 'horizon' | 'native';

export type PackageManager = 'npm' | 'bun';
export type TestingFramework = 'pest' | 'phpunit';
export type AuthProvider = 'laravel' | 'workos';
export type PhpVersion = '8.2' | '8.3' | '8.4';
export type DatabaseType = 'sqlite' | 'mysql' | 'mariadb' | 'postgres';

export interface BaseFormSectionProps {
    data: FormValues;
    setData: <K extends keyof FormValues>(key: K, value: FormValues[K]) => void;
    errors: FormErrors<FormValues>;
}

export type SelectChangeEvent = ChangeEvent<HTMLSelectElement>;

export interface QueueHandlers {
    handleQueueChange: (e: SelectChangeEvent) => void;
}

export interface FormValues {
    project_name: string;
    php_version: PhpVersion;
    database: DatabaseType;
    starter_kit: 'none' | 'react' | 'vue' | 'livewire';
    livewire_volt?: boolean;
    workos: AuthProvider | undefined;
    testing_framework: TestingFramework;
    queue_type: QueueTypeValue | 'none' | undefined;
    queue_driver: QueueDriverValue | undefined;
    features: string[];
    javascript_package_manager: PackageManager;
    initialize_git: boolean;
    [key: string]: string | boolean | undefined | string[];
}
