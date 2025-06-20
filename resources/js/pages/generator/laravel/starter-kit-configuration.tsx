import {
    Description,
    ErrorMessage,
    Field,
    FieldGroup,
    Fieldset,
    FieldsetInfoMessage,
    InfoMessage,
    Label,
    Legend,
} from '@/components/ui/fieldset';
import { Input } from '@/components/ui/input';
import { Radio, RadioField, RadioGroup } from '@/components/ui/radio';
import { Select } from '@/components/ui/select';
import { Switch, SwitchField } from '@/components/ui/switch';
import { fadeInAnimation } from '@/constants/animations';
import {
    LaravelFormHook,
    PackageManager,
    SetDataMethod,
    TestingFramework,
} from '@/types';
import { AnimatePresence, motion } from 'motion/react';
import React, { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface StarterKitConfigurationProps {
    data: LaravelFormHook['data'];
    setData: SetDataMethod;
    errors: LaravelFormHook['errors'];
    validating?: LaravelFormHook['validating'];
    modifiedFields?: string[];
    handleStackChange: LaravelFormHook['handleStackChange'];
    detectDependencies?: LaravelFormHook['detectDependencies'];
}

export const StarterKitConfiguration = ({
    data,
    setData,
    errors,
    modifiedFields = [],
    handleStackChange,
    detectDependencies,
}: StarterKitConfigurationProps) => {
    const { t } = useTranslation();
    const [isPackageLoading, setIsPackageLoading] = useState(false);
    const lastCheckedPackageRef = useRef<string | undefined>(
        data.custom_starter_kit,
    );

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

    const handleCustomPackageChange = (
        e: React.ChangeEvent<HTMLInputElement>,
    ) => {
        const packageName = e.target.value;
        setData('custom_starter_kit', packageName);
    };

    const handlePackageBlur = async () => {
        // Validation à la perte de focus
        if (typeof setData.validate === 'function') {
            setData.validate('custom_starter_kit');
        }

        // Vérifier si la valeur a changé depuis la dernière vérification
        const currentPackage = data.custom_starter_kit;
        if (
            currentPackage === lastCheckedPackageRef.current ||
            !currentPackage ||
            currentPackage.trim() === ''
        ) {
            return; // Ne rien faire si la valeur n'a pas changé ou est vide
        }

        // Déclencher la détection des dépendances seulement si:
        // 1. Le starter_kit est 'custom'
        // 2. Un nom de package a été entré
        // 3. La fonction detectDependencies existe
        if (data.starter_kit === 'custom' && detectDependencies) {
            setIsPackageLoading(true);
            try {
                await detectDependencies(currentPackage);
                // Mettre à jour la référence après une détection réussie
                lastCheckedPackageRef.current = currentPackage;
            } finally {
                setIsPackageLoading(false);
            }
        }
    };

    // Vérifier si un champ a été modifié automatiquement
    const isFieldAutoDetected = (field: string): boolean => {
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
                    onBlur={() => {
                        if (typeof setData.validate === 'function') {
                            setData.validate('starter_kit');
                        }
                    }}
                    required
                    invalid={!!errors.starter_kit}
                >
                    <option value="none">
                        {t('laravel.starter_kit_none')}
                    </option>
                    <option value="react">React</option>
                    <option value="vue">Vue</option>
                    <option value="livewire">Livewire</option>
                    <option value="custom">Custom</option>
                </Select>
                {errors.starter_kit && (
                    <ErrorMessage>{errors.starter_kit}</ErrorMessage>
                )}
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
                                isLoading={isPackageLoading}
                                value={data.custom_starter_kit || ''}
                                onChange={handleCustomPackageChange}
                                onBlur={handlePackageBlur}
                                placeholder="vendor/package-name"
                                required
                                invalid={!!errors.custom_starter_kit}
                            />
                            {errors.custom_starter_kit && (
                                <ErrorMessage>
                                    {errors.custom_starter_kit}
                                </ErrorMessage>
                            )}
                        </Field>
                    </motion.div>
                )}
            </AnimatePresence>

            <AnimatePresence mode="wait">
                {data.starter_kit !== 'none' &&
                    data.starter_kit !== 'custom' && (
                        <motion.div {...fadeInAnimation}>
                            <Fieldset>
                                <Legend>
                                    {t('laravel.authentication_provider')}
                                </Legend>
                                <RadioGroup
                                    value={
                                        data.workos === true
                                            ? 'workos'
                                            : 'laravel'
                                    }
                                    onChange={handleAuthChange}
                                >
                                    <RadioField>
                                        <Radio value="laravel" />
                                        <Label>
                                            {t('laravel.laravel_auth')}
                                        </Label>
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
                                onChange={(checked: boolean) =>
                                    setData('livewire_volt', checked)
                                }
                            />
                            <Label>
                                {t('Would you like to use Laravel Volt?')}
                            </Label>
                        </SwitchField>
                    </motion.div>
                )}
            </AnimatePresence>

            <Fieldset>
                <Legend>{t('laravel.testing_framework')}</Legend>
                <RadioGroup
                    value={data.testing_framework}
                    onChange={handleTestingFrameworkChange}
                >
                    <RadioField>
                        <Radio
                            value="pest"
                            isAutoDetected={isFieldAutoDetected(
                                'testing_framework',
                            )}
                        />
                        <Label>Pest</Label>
                    </RadioField>
                    <RadioField>
                        <Radio
                            value="phpunit"
                            isAutoDetected={isFieldAutoDetected(
                                'testing_framework',
                            )}
                        />
                        <Label>PHPUnit</Label>
                    </RadioField>
                </RadioGroup>
                {isFieldAutoDetected('testing_framework') && (
                    <FieldsetInfoMessage>
                        {t('Auto-detected value')}
                    </FieldsetInfoMessage>
                )}
            </Fieldset>

            <Field>
                <Label>{t('laravel.javascript_package_manager')}</Label>
                <Select
                    name="javascript_package_manager"
                    value={data.javascript_package_manager}
                    onChange={(e) =>
                        setData(
                            'javascript_package_manager',
                            e.target.value as PackageManager,
                        )
                    }
                    isAutoDetected={isFieldAutoDetected(
                        'javascript_package_manager',
                    )}
                >
                    <option value="npm">npm</option>
                    <option value="bun">bun</option>
                </Select>
                {isFieldAutoDetected('javascript_package_manager') && (
                    <InfoMessage>{t('Auto-detected value')}</InfoMessage>
                )}
                {errors.javascript_package_manager && (
                    <ErrorMessage>
                        {errors.javascript_package_manager}
                    </ErrorMessage>
                )}
            </Field>
        </FieldGroup>
    );
};
