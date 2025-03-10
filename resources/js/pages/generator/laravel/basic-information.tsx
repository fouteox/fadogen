import {
    ErrorMessage,
    Field,
    FieldGroup,
    Label,
} from '@/components/ui/fieldset';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Switch, SwitchField } from '@/components/ui/switch';
import {
    BaseFormSectionProps,
    DatabaseType,
    PhpVersion,
    SelectChangeEvent,
} from '@/types';
import React from 'react';
import { useTranslation } from 'react-i18next';

type BasicInformationHandlers = {
    handleProjectNameChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
    handlePhpVersionChange: (e: SelectChangeEvent) => void;
    handleDatabaseChange: (e: SelectChangeEvent) => void;
    handleGitInitChange: (checked: boolean) => void;
};

const useBasicInformationHandlers = (
    setData: BaseFormSectionProps['setData'],
): BasicInformationHandlers => {
    const handleProjectNameChange = (
        e: React.ChangeEvent<HTMLInputElement>,
    ) => {
        setData('project_name', e.target.value);
    };

    const handlePhpVersionChange = (e: SelectChangeEvent) => {
        setData('php_version', e.target.value as PhpVersion);
    };

    const handleDatabaseChange = (e: SelectChangeEvent) => {
        setData('database', e.target.value as DatabaseType);
    };

    const handleGitInitChange = (checked: boolean) => {
        setData('initialize_git', checked);
    };

    return {
        handleProjectNameChange,
        handlePhpVersionChange,
        handleDatabaseChange,
        handleGitInitChange,
    };
};

export const BasicInformation = ({
    data,
    setData,
    errors,
}: BaseFormSectionProps) => {
    const { t } = useTranslation();
    const handlers = useBasicInformationHandlers(setData);

    return (
        <FieldGroup>
            <Field>
                <Label>{t('laravel.name_project')}</Label>
                <Input
                    name="project_name"
                    value={data.project_name}
                    placeholder={'my-awesome-project'}
                    onChange={handlers.handleProjectNameChange}
                    required
                    invalid={!!errors.project_name}
                />
                {errors.project_name && (
                    <ErrorMessage>{errors.project_name}</ErrorMessage>
                )}
            </Field>

            <Field>
                <Label>{t('laravel.php_version')}</Label>
                <Select
                    name="php_version"
                    value={data.php_version}
                    onChange={handlers.handlePhpVersionChange}
                    required
                    invalid={!!errors.php_version}
                >
                    <option value="8.4">8.4 ({t('Recommended')})</option>
                    <option value="8.3">8.3</option>
                    <option value="8.2">8.2</option>
                </Select>
                {errors.php_version && (
                    <ErrorMessage>{errors.php_version}</ErrorMessage>
                )}
            </Field>

            <Field>
                <Label>{t('laravel.database')}</Label>
                <Select
                    name="database"
                    value={data.database}
                    onChange={handlers.handleDatabaseChange}
                    required
                    invalid={!!errors.database}
                >
                    <option value="sqlite">SQLite</option>
                    <option value="mysql">MySQL</option>
                    <option value="mariadb">MariaDB</option>
                    <option value="pgsql">PostgreSQL</option>
                </Select>
                {errors.database && (
                    <ErrorMessage>{errors.database}</ErrorMessage>
                )}
            </Field>

            <SwitchField>
                <Switch
                    name="initialize_git"
                    checked={data.initialize_git}
                    onChange={handlers.handleGitInitChange}
                />
                <Label>{t('laravel.initialize_git')}</Label>
            </SwitchField>
        </FieldGroup>
    );
};
