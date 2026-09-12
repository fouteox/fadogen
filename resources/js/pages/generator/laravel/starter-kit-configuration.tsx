import { AnimatePresence, motion } from 'motion/react';
import React from 'react';
import { useTranslation } from 'react-i18next';
import { Description, ErrorMessage, Field, FieldGroup, Fieldset, FieldsetInfoMessage, InfoMessage, Label, Legend } from '@/components/ui/fieldset';
import { Input } from '@/components/ui/input';
import { Radio, RadioField, RadioGroup } from '@/components/ui/radio';
import { Select } from '@/components/ui/select';
import { Switch, SwitchField } from '@/components/ui/switch';
import { fadeInAnimation } from '@/constants/animations';
import type { useLaravelForm } from '@/hooks/use-laravel-generator';
import type { BaseFormSectionProps, PackageManager, TestingFramework } from '@/types';

type StarterKitConfigurationProps = BaseFormSectionProps &
    Pick<ReturnType<typeof useLaravelForm>, 'handleStackChange' | 'detectDependencies' | 'isLoading'> & { packageError?: string };

export const StarterKitConfiguration = ({
    data,
    setData,
    errors,
    validate,
    modifiedFields = [],
    handleStackChange,
    detectDependencies,
    isLoading,
    packageError,
}: StarterKitConfigurationProps) => {
    const { t } = useTranslation();

    const handleTestingFrameworkChange = (value: string) => {
        setData('testing_framework', value as TestingFramework);
    };

    const handleAuthChange = (value: string) => {
        setData('workos', value === 'workos');

        if (data.starter_kit === 'livewire') {
            if (value === 'workos') {
                setData('livewire_volt', undefined);
            } else {
                setData('livewire_volt', false);
            }
        }
    };

    const handleCustomPackageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const packageName = e.target.value;
        setData('custom_starter_kit', packageName);
    };

    const handlePackageBlur = async () => {
        validate('custom_starter_kit');

        await detectDependencies(data.custom_starter_kit);
    };

    const isFieldAutoDetected = (field: keyof typeof data): boolean => {
        return modifiedFields.includes(field);
    };

    return (
        <FieldGroup>
            <Field>
                <Label>{t('laravel.starter_kit')}</Label>
                <Select
                    name="starter_kit"
                    value={data.starter_kit}
                    onChange={handleStackChange}
                    onBlur={() => validate('starter_kit')}
                    required
                    invalid={!!errors.starter_kit}
                >
                    <option value="none">{t('laravel.starter_kit_none')}</option>
                    <option value="react">React</option>
                    <option value="vue">Vue</option>
                    <option value="livewire">Livewire</option>
                    <option value="custom">Custom</option>
                </Select>
                {errors.starter_kit && <ErrorMessage>{errors.starter_kit}</ErrorMessage>}
            </Field>

            <AnimatePresence mode="wait">
                {data.starter_kit === 'custom' && (
                    <motion.div {...fadeInAnimation}>
                        <Field>
                            <Label>{t('Name of the starter kit')}</Label>
                            <Description>
                                {t(
                                    'An automatic detection of the starter kit will be carried out and the various questions will be filled in automatically.',
                                )}
                            </Description>
                            <Input
                                name="custom_starter_kit"
                                isLoading={isLoading}
                                value={data.custom_starter_kit || ''}
                                onChange={handleCustomPackageChange}
                                onBlur={handlePackageBlur}
                                placeholder="vendor/package-name"
                                required
                                invalid={!!(errors.custom_starter_kit || packageError)}
                            />
                            {(errors.custom_starter_kit || packageError) && <ErrorMessage>{errors.custom_starter_kit || packageError}</ErrorMessage>}
                        </Field>
                    </motion.div>
                )}
            </AnimatePresence>

            <AnimatePresence mode="wait">
                {data.starter_kit !== 'none' && data.starter_kit !== 'custom' && (
                    <motion.div {...fadeInAnimation}>
                        <Fieldset>
                            <Legend>{t('laravel.authentication_provider')}</Legend>
                            <RadioGroup value={data.workos === true ? 'workos' : 'laravel'} onChange={handleAuthChange}>
                                <RadioField>
                                    <Radio value="laravel" />
                                    <Label>{t('laravel.laravel_auth')}</Label>
                                </RadioField>
                                <RadioField>
                                    <Radio value="workos" />
                                    <Label>{t('laravel.workos')}</Label>
                                </RadioField>
                            </RadioGroup>
                        </Fieldset>
                    </motion.div>
                )}
            </AnimatePresence>

            <AnimatePresence mode="wait">
                {data.starter_kit === 'livewire' && data.workos === false && (
                    <motion.div {...fadeInAnimation}>
                        <SwitchField>
                            <Switch
                                name="livewire_volt"
                                checked={data.livewire_volt}
                                onChange={(checked: boolean) => setData('livewire_volt', checked)}
                            />
                            <Label>{t('Would you like to use Laravel Volt?')}</Label>
                        </SwitchField>
                    </motion.div>
                )}
            </AnimatePresence>

            <Fieldset>
                <Legend>{t('laravel.testing_framework')}</Legend>
                <RadioGroup value={data.testing_framework} onChange={handleTestingFrameworkChange}>
                    <RadioField>
                        <Radio value="pest" isAutoDetected={isFieldAutoDetected('testing_framework')} />
                        <Label>Pest</Label>
                    </RadioField>
                    <RadioField>
                        <Radio value="phpunit" isAutoDetected={isFieldAutoDetected('testing_framework')} />
                        <Label>PHPUnit</Label>
                    </RadioField>
                </RadioGroup>
                {isFieldAutoDetected('testing_framework') && <FieldsetInfoMessage>{t('Auto-detected value')}</FieldsetInfoMessage>}
            </Fieldset>

            <Field>
                <Label>{t('laravel.javascript_package_manager')}</Label>
                <Select
                    name="javascript_package_manager"
                    value={data.javascript_package_manager}
                    onChange={(e) => setData('javascript_package_manager', e.target.value as PackageManager)}
                    isAutoDetected={isFieldAutoDetected('javascript_package_manager')}
                >
                    <option value="npm">npm</option>
                    <option value="bun">bun</option>
                </Select>
                {isFieldAutoDetected('javascript_package_manager') && <InfoMessage>{t('Auto-detected value')}</InfoMessage>}
                {errors.javascript_package_manager && <ErrorMessage>{errors.javascript_package_manager}</ErrorMessage>}
            </Field>
        </FieldGroup>
    );
};
