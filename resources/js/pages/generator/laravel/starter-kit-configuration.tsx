import {
    ErrorMessage,
    Field,
    FieldGroup,
    Fieldset,
    Label,
    Legend,
} from '@/components/ui/fieldset';
import { Radio, RadioField, RadioGroup } from '@/components/ui/radio';
import { Select } from '@/components/ui/select';
import { Switch, SwitchField } from '@/components/ui/switch';
import { fadeInAnimation } from '@/constants/animations';
import {
    AuthProvider,
    BaseFormSectionProps,
    PackageManager,
    TestingFramework,
} from '@/types';
import { AnimatePresence, motion } from 'motion/react';
import { ChangeEvent } from 'react';
import { useTranslation } from 'react-i18next';

interface StarterKitConfigurationProps extends BaseFormSectionProps {
    handleStackChange: (e: ChangeEvent<HTMLSelectElement>) => void;
}

export const StarterKitConfiguration = ({
    data,
    setData,
    errors,
    handleStackChange,
}: StarterKitConfigurationProps) => {
    const { t } = useTranslation();

    const handleTestingFrameworkChange = (value: string) => {
        setData('testing_framework', value as TestingFramework);
    };

    const handleAuthChange = (value: string) => {
        setData('workos', value as AuthProvider);

        if (data.starter_kit === 'livewire') {
            if (value === 'workos') {
                setData('livewire_volt', undefined);
            } else {
                setData('livewire_volt', false);
            }
        }
    };

    return (
        <FieldGroup>
            <Field>
                <Label>{t('laravel.starter_kit')}</Label>
                <Select
                    name="starter_kit"
                    value={data.starter_kit}
                    onChange={handleStackChange}
                    required
                    invalid={!!errors.starter_kit}
                >
                    <option value="none">
                        {t('laravel.starter_kit_none')}
                    </option>
                    <option value="react">React</option>
                    <option value="vue">Vue</option>
                    <option value="livewire">Livewire</option>
                </Select>
                {errors.starter_kit && (
                    <ErrorMessage>{errors.starter_kit}</ErrorMessage>
                )}
            </Field>

            <AnimatePresence mode="wait">
                {data.starter_kit !== 'none' && (
                    <motion.div {...fadeInAnimation}>
                        <Fieldset>
                            <Legend>
                                {t('laravel.authentication_provider')}
                            </Legend>
                            <RadioGroup
                                value={data.workos}
                                onChange={handleAuthChange}
                            >
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
                {data.starter_kit === 'livewire' &&
                    data.workos === 'laravel' && (
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
                        <Radio value="pest" />
                        <Label>Pest</Label>
                    </RadioField>
                    <RadioField>
                        <Radio value="phpunit" />
                        <Label>PHPUnit</Label>
                    </RadioField>
                </RadioGroup>
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
                >
                    <option value="npm">npm</option>
                    <option value="bun">bun</option>
                </Select>
                {errors.javascript_package_manager && (
                    <ErrorMessage>
                        {errors.javascript_package_manager}
                    </ErrorMessage>
                )}
            </Field>
        </FieldGroup>
    );
};
