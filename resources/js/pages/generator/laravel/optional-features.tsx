import {
    Checkbox,
    CheckboxField,
    CheckboxGroup,
} from '@/components/ui/checkbox';
import { Fieldset, Label, Legend } from '@/components/ui/fieldset';
import { FormValues } from '@/types';
import clsx from 'clsx';
import { useTranslation } from 'react-i18next';

interface OptionalFeaturesProps {
    data: FormValues;
    handleFeatureChange: (feature: string, checked: boolean) => void;
    modifiedFields?: string[];
}

export const OptionalFeatures = ({
    data,
    handleFeatureChange,
    modifiedFields = [],
}: OptionalFeaturesProps) => {
    const { t } = useTranslation();

    // Vérifier si un champ de features spécifique a été modifié automatiquement
    const isFeatureAutoDetected = (feature: string): boolean => {
        return (
            modifiedFields.includes('features') &&
            data.features.includes(feature)
        );
    };

    return (
        <Fieldset>
            <Legend>{t('laravel.optional_features')}</Legend>
            <CheckboxGroup>
                <CheckboxField>
                    <Checkbox
                        name="features"
                        value="schedule"
                        checked={data.features.includes('schedule')}
                        onChange={(checked) =>
                            handleFeatureChange('schedule', checked)
                        }
                    />
                    <Label>Task Scheduling</Label>
                </CheckboxField>
                <CheckboxField
                    className={clsx(
                        isFeatureAutoDetected('reverb') &&
                            'rounded-md border border-blue-500 p-1 dark:border-blue-500',
                    )}
                >
                    <Checkbox
                        name="features"
                        value="reverb"
                        checked={data.features.includes('reverb')}
                        onChange={(checked) =>
                            handleFeatureChange('reverb', checked)
                        }
                    />
                    <Label>Reverb</Label>
                </CheckboxField>
                <CheckboxField
                    className={clsx(
                        isFeatureAutoDetected('octane') &&
                            'rounded-md border border-blue-500 p-1 dark:border-blue-500',
                    )}
                >
                    <Checkbox
                        name="features"
                        value="octane"
                        checked={data.features.includes('octane')}
                        onChange={(checked) =>
                            handleFeatureChange('octane', checked)
                        }
                    />
                    <Label>{t('Octane with FrankenPHP')}</Label>
                </CheckboxField>
            </CheckboxGroup>
        </Fieldset>
    );
};
