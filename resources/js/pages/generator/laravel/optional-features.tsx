import {
    Checkbox,
    CheckboxField,
    CheckboxGroup,
} from '@/components/ui/checkbox';
import { Fieldset, Label, Legend } from '@/components/ui/fieldset';
import { FormValues } from '@/types';
import { useTranslation } from 'react-i18next';

interface OptionalFeaturesProps {
    data: FormValues;
    handleFeatureChange: (feature: string, checked: boolean) => void;
}

export const OptionalFeatures = ({
    data,
    handleFeatureChange,
}: OptionalFeaturesProps) => {
    const { t } = useTranslation();

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
                <CheckboxField>
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
                <CheckboxField>
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
